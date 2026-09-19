<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

/**
 * Validation and abuse protection for the public contact form.
 *
 * This is an untrusted public input boundary, so the rules are explicit and
 * the checks run in the documented order: validate, then rate-limit, then
 * anti-spam, then persist. Rate limiting is reported the same way the login
 * form reports it (a validation-style message) so the whole application has
 * one convention.
 */
class ContactRequest extends FormRequest
{
    /**
     * A guest may always attempt to contact the site.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules.
     *
     * Lengths are generous: they exist to stop payload abuse, not to police
     * how a person writes. No character-class regexes are used, because they
     * reject legitimate names and messages (non-Latin scripts, punctuation,
     * emoji) for no security benefit — escaping happens at output time.
     *
     * @return array<string, ValidationRule|array<int, string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'email' => ['required', 'string', 'email:rfc', 'max:180'],
            'subject' => ['required', 'string', 'min:2', 'max:150'],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
        ];
    }

    /**
     * Strip surrounding whitespace only.
     *
     * The message body is never rewritten beyond its outer whitespace: the
     * text the visitor typed is what gets stored and mailed.
     */
    protected function prepareForValidation(): void
    {
        foreach (['name', 'email', 'subject', 'message'] as $field) {
            $value = $this->input($field);

            if (is_string($value)) {
                $this->merge([$field => trim($value)]);
            }
        }
    }

    /**
     * Friendly, non-technical validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => __('Please tell us your name.'),
            'email.required' => __('Please add an email address so we can reply.'),
            'email.email' => __('That email address does not look valid.'),
            'subject.required' => __('Please add a subject.'),
            'message.required' => __('Please write a message.'),
            'message.min' => __('Please write a little more (at least :min characters).'),
        ];
    }

    /**
     * Attribute names used inside the messages above.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => __('name'),
            'email' => __('email address'),
            'subject' => __('subject'),
            'message' => __('message'),
        ];
    }

    /**
     * The honeypot was filled — only a bot does that.
     *
     * Deliberately not a validation rule: the decoy field must not appear in
     * error output, or the trap would teach bots which field to skip.
     */
    public function honeypotTriggered(): bool
    {
        $field = (string) config('contact.honeypot', 'website');

        return $field !== '' && $this->filled($field);
    }

    /**
     * Reject the request once an IP exceeds the configured attempt budget.
     *
     * Keyed on the IP address alone. A visitor-supplied email is not a usable
     * limiter axis — it is trivially changed between attempts, and keying on
     * it would let one attacker burn a real person's budget by guessing their
     * address.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        $key = $this->limiterKey();
        $max = max(1, (int) config('contact.rate_limit.max', 10));

        RateLimiter::hit($key, (int) config('contact.rate_limit.decay', 600));

        if (RateLimiter::attempts($key) <= $max) {
            return;
        }

        // Reported on a dedicated key so it renders as a form-level notice
        // rather than looking like a problem with one specific field. No
        // limiter internals (key, counts, storage) are exposed.
        throw ValidationException::withMessages([
            'contact' => __('You have sent several messages already. Please wait a few minutes before trying again.'),
        ]);
    }

    /**
     * Rate limiter bucket for this request.
     */
    public function limiterKey(): string
    {
        return 'contact-form:'.$this->ip();
    }
}
