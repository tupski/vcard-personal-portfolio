<?php

namespace App\Mail;

use App\Models\ContactMessage;
use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Route;
use Throwable;

/**
 * "New contact message" notification for the site owner.
 *
 * The visitor's address is never used as the envelope sender: doing so breaks
 * SPF/DMARC for most domains and invites header-injection problems. The
 * application's configured sender is used as `From`, and the visitor is set
 * as `Reply-To` so replying from a mail client still reaches them.
 *
 * Blade escapes every interpolated value in the templates, so a message
 * containing markup stays data and never becomes executable markup.
 *
 * Only the model is injected: a Mailable is serialised when queued, so it must
 * not hold service dependencies. The few values the templates need are
 * resolved inside the mailable instead.
 */
class ContactMessageReceived extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly ContactMessage $message,
    ) {}

    public function envelope(): Envelope
    {
        $name = trim((string) $this->message->name);
        $email = trim((string) $this->message->email);

        // Reply-To is only set for a syntactically valid address, so a
        // malformed value can never reach the header.
        $replyTo = filter_var($email, FILTER_VALIDATE_EMAIL) !== false
            ? [new Address($email, $name === '' ? null : $name)]
            : [];

        return new Envelope(
            subject: __('New contact message: :subject', [
                'subject' => $this->message->displaySubject(),
            ]),
            replyTo: $replyTo,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.contact-message-received',
            text: 'mail.contact-message-received-text',
            with: [
                // Named `contact` on purpose: Laravel shares its own
                // Illuminate\Mail\Message instance as `$message` inside mail
                // views, so a model passed under that name would be shadowed.
                'contact' => $this->message,
                'siteName' => $this->siteName(),
                'inboxUrl' => $this->inboxUrl(),
            ],
        );
    }

    /**
     * Site name for the notification header, with a config fallback.
     */
    private function siteName(): string
    {
        try {
            return (string) (Setting::get('site.name') ?: config('app.name'));
        } catch (Throwable) {
            return (string) config('app.name');
        }
    }

    /**
     * Absolute link to the admin inbox entry, when the route exists.
     *
     * Built from the configured application URL only, so a hostile request
     * host can never end up in the email body.
     */
    private function inboxUrl(): ?string
    {
        if (! Route::has('admin.contact-messages.show')) {
            return null;
        }

        $base = rtrim((string) config('app.url', ''), '/');

        if ($base === '') {
            return null;
        }

        return $base.'/'.ltrim(
            route('admin.contact-messages.show', $this->message, absolute: false),
            '/',
        );
    }
}
