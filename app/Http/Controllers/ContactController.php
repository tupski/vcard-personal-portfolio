<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContactRequest;
use App\Models\ContactMessage;
use App\Support\ContactNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

/**
 * Public contact form submission.
 *
 * Order of operations is deliberate and documented:
 *
 *   validate (ContactRequest)
 *     → rate limit (ContactRequest)
 *     → anti-spam (honeypot)
 *     → persist ContactMessage
 *     → dispatch notification (best effort)
 *
 * The database write happens before the notification and is never rolled
 * back, so a mail failure can never make a submitted message disappear.
 */
class ContactController extends Controller
{
    public function __construct(
        private readonly ContactNotifier $notifier,
    ) {}

    /**
     * Store a contact message and redirect back with a result.
     *
     * Post/Redirect/Get: the visitor lands on a fresh GET of /contact, so a
     * refresh cannot resubmit the form and browser back/forward stays sane.
     */
    public function store(ContactRequest $request): RedirectResponse
    {
        // Anti-spam runs before persistence. A triggered honeypot is answered
        // with the ordinary success state on purpose: telling a bot it was
        // detected only helps it adapt. The event is logged for the operator.
        if ($request->honeypotTriggered()) {
            Log::notice('Contact form honeypot triggered; submission discarded.', [
                'ip' => $request->ip(),
            ]);

            return $this->successRedirect();
        }

        $request->ensureIsNotRateLimited();

        $message = ContactMessage::query()->create([
            'name' => (string) $request->string('name'),
            'email' => (string) $request->string('email'),
            'subject' => (string) $request->string('subject'),
            'message' => (string) $request->string('message'),
        ]);

        // Best effort. ContactNotifier logs and swallows transport failures so
        // a broken mail configuration cannot fail the visitor's request.
        $this->notifier->notify($message);

        return $this->successRedirect();
    }

    /**
     * Shared success redirect.
     */
    private function successRedirect(): RedirectResponse
    {
        return redirect()
            ->route('contact')
            ->with('contact_status', __('Thanks — your message has been sent. I will get back to you soon.'));
    }
}
