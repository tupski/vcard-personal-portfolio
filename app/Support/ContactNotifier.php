<?php

namespace App\Support;

use App\Mail\ContactMessageReceived;
use App\Models\ContactMessage;
use App\Models\Profile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Sends the "new contact message" notification.
 *
 * Delivery is best-effort and synchronous. A queue would need a worker on the
 * deployment target (aaPanel/VPS) that the project does not currently
 * guarantee, and a contact form that silently drops messages because no
 * worker is running is worse than a request that waits a moment for SMTP.
 * The tradeoff is documented in the Phase 7 report; switching to `->queue()`
 * later is a one-line change once a worker is guaranteed.
 *
 * Crucially, a failure here is swallowed after being logged: the message is
 * already committed to the database and must never disappear because the
 * mail transport is misconfigured.
 */
class ContactNotifier
{
    /**
     * Notify the site owner about a stored message.
     *
     * @return bool whether the notification was handed to the mailer
     */
    public function notify(ContactMessage $message): bool
    {
        $recipient = $this->recipient();

        if ($recipient === null) {
            Log::warning('Contact message stored but no notification recipient is configured.', [
                'contact_message_id' => $message->getKey(),
            ]);

            return false;
        }

        try {
            Mail::to($recipient)->send(new ContactMessageReceived($message));

            return true;
        } catch (Throwable $e) {
            // The database row is the source of truth and is already saved.
            // Never rethrow: the visitor's submission succeeded.
            Log::error('Contact message notification failed to send.', [
                'contact_message_id' => $message->getKey(),
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Where notifications are sent.
     *
     * Explicit configuration wins; otherwise the published profile address is
     * used, so the feature works without inventing another CMS setting. Both
     * sources are validated before use so a malformed value can never reach
     * the mailer.
     */
    public function recipient(): ?string
    {
        $configured = trim((string) config('contact.notify_to'));

        if ($configured !== '') {
            return filter_var($configured, FILTER_VALIDATE_EMAIL) !== false ? $configured : null;
        }

        try {
            $profile = Profile::current();
        } catch (Throwable) {
            return null;
        }

        $email = trim((string) ($profile?->email ?? ''));

        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false ? $email : null;
    }
}
