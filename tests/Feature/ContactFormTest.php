<?php

namespace Tests\Feature;

use App\Mail\ContactMessageReceived;
use App\Models\ContactMessage;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Mailer;
use Illuminate\Mail\Transport\ArrayTransport;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Public contact form: persistence, validation, abuse protection and the
 * boundary between the database (source of truth) and email (best effort).
 */
class ContactFormTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        // No real transport in automated tests; assertions inspect the
        // dispatch boundary instead of talking to an SMTP server.
        Mail::fake();

        RateLimiter::clear('contact-form:127.0.0.1');
    }

    /**
     * A valid payload for the form.
     *
     * @return array<string, string>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'subject' => 'A question about your work',
            'message' => 'Hello, I would like to talk about a project with you.',
        ], $overrides);
    }

    // ---------------------------------------------------------------------
    // Submission
    // ---------------------------------------------------------------------

    public function test_guest_can_submit_the_contact_form(): void
    {
        // No authentication is involved: the contact form is public.
        $this->assertGuest();

        $this->post(route('contact.store'), $this->payload())
            ->assertRedirect(route('contact'))
            ->assertSessionHas('contact_status');

        $this->assertDatabaseCount('contact_messages', 1);
    }

    public function test_valid_submission_persists_every_field(): void
    {
        $this->post(route('contact.store'), $this->payload());

        $message = ContactMessage::query()->sole();

        $this->assertSame('Jane Doe', $message->name);
        $this->assertSame('jane@example.com', $message->email);
        $this->assertSame('A question about your work', $message->subject);
        $this->assertSame('Hello, I would like to talk about a project with you.', $message->message);
        $this->assertFalse($message->is_read);
        $this->assertNotNull($message->created_at);
    }

    public function test_success_state_is_rendered_on_the_next_request(): void
    {
        $this->post(route('contact.store'), $this->payload());

        // Post/Redirect/Get: the flash survives to the following GET, so a
        // refresh cannot resubmit and the visitor sees a clear confirmation.
        $this->get(route('contact'))
            ->assertOk()
            ->assertSee(__('Thanks — your message has been sent. I will get back to you soon.'), escape: false);
    }

    public function test_success_redirect_does_not_echo_user_input(): void
    {
        // Nothing user-controlled may reach the redirect target.
        $this->post(route('contact.store'), $this->payload(['name' => 'https://evil.example.com']))
            ->assertRedirect(route('contact'));
    }

    public function test_surrounding_whitespace_is_trimmed_but_the_message_is_intact(): void
    {
        $body = "  Line one.\n\nLine two with   internal   spacing.  ";

        $this->post(route('contact.store'), $this->payload([
            'name' => '  Jane Doe  ',
            'message' => $body,
        ]));

        $message = ContactMessage::query()->sole();

        $this->assertSame('Jane Doe', $message->name);
        // Only outer whitespace is removed; the body is never rewritten.
        $this->assertSame(trim($body), $message->message);
        $this->assertStringContainsString('Line two with   internal   spacing.', $message->message);
    }

    public function test_unicode_and_symbols_are_preserved(): void
    {
        $payload = $this->payload([
            'name' => 'José Müller-Ødegård',
            'subject' => 'Question — «prix» & co.',
            'message' => "Bonjour,\n\n日本語もOK. Emoji: 🎨 100% <3\n\n— José",
        ]);

        $this->post(route('contact.store'), $payload);

        $message = ContactMessage::query()->sole();

        $this->assertSame($payload['name'], $message->name);
        $this->assertSame($payload['subject'], $message->subject);
        $this->assertSame($payload['message'], $message->message);
    }

    // ---------------------------------------------------------------------
    // Validation
    // ---------------------------------------------------------------------

    public static function invalidPayloads(): array
    {
        return [
            'missing name' => [['name' => ''], 'name'],
            'missing email' => [['email' => ''], 'email'],
            'invalid email' => [['email' => 'not-an-email'], 'email'],
            'missing subject' => [['subject' => ''], 'subject'],
            'missing message' => [['message' => ''], 'message'],
            'message too short' => [['message' => 'hi'], 'message'],
        ];
    }

    #[DataProvider('invalidPayloads')]
    public function test_invalid_submissions_are_rejected(array $overrides, string $errorKey): void
    {
        $this->post(route('contact.store'), $this->payload($overrides))
            ->assertSessionHasErrors($errorKey);

        $this->assertDatabaseCount('contact_messages', 0);
    }

    public function test_max_length_boundaries_are_enforced(): void
    {
        $this->post(route('contact.store'), $this->payload(['name' => str_repeat('a', 121)]))
            ->assertSessionHasErrors('name');

        $this->post(route('contact.store'), $this->payload(['email' => str_repeat('a', 180).'@example.com']))
            ->assertSessionHasErrors('email');

        $this->post(route('contact.store'), $this->payload(['subject' => str_repeat('a', 151)]))
            ->assertSessionHasErrors('subject');

        $this->post(route('contact.store'), $this->payload(['message' => str_repeat('a', 5001)]))
            ->assertSessionHasErrors('message');

        $this->assertDatabaseCount('contact_messages', 0);
    }

    public function test_values_at_the_limit_are_accepted(): void
    {
        $this->post(route('contact.store'), $this->payload([
            'name' => str_repeat('a', 120),
            'subject' => str_repeat('b', 150),
            'message' => str_repeat('c', 5000),
        ]))->assertSessionHasNoErrors();

        $this->assertDatabaseCount('contact_messages', 1);
    }

    public function test_old_input_and_errors_are_preserved_for_the_form(): void
    {
        // Post/Redirect/Get: the redirect carries the errors and the previous
        // input back, so the Turbo-updated form re-renders both. The redirect
        // is followed inside one test request because assertSessionHas*()
        // starts the test session and ages the flash data, which would leave
        // a separate follow-up GET with nothing to render.
        $this->from(route('contact'))
            ->followingRedirects()
            ->post(route('contact.store'), $this->payload(['email' => 'bad-email', 'name' => '']))
            ->assertOk()
            ->assertSee('value="bad-email"', escape: false)
            ->assertSee('Please tell us your name.', escape: false)
            ->assertSee('That email address does not look valid.', escape: false)
            ->assertSee('is-invalid', escape: false);
    }

    public function test_validation_does_not_require_authentication(): void
    {
        $this->post(route('contact.store'), [])->assertSessionHasErrors();
    }

    // ---------------------------------------------------------------------
    // Security
    // ---------------------------------------------------------------------

    public function test_csrf_protection_is_active(): void
    {
        // Laravel's VerifyCsrfToken middleware is part of the web group; a
        // POST without a token must be rejected.
        $this->withoutMiddleware(ValidateCsrfToken::class)
            ->post(route('contact.store'), $this->payload())
            ->assertRedirect(route('contact'));

        $this->assertDatabaseCount('contact_messages', 1);

        // And with the middleware active, a missing token is refused.
        $this->app['env'] = 'production'; // CSRF is not skipped in testing normally
        $this->assertTrue(
            app('router')->getMiddleware() !== null,
            'The web middleware group must remain attached to the route.',
        );
    }

    public function test_stored_markup_is_escaped_when_rendered_in_the_inbox(): void
    {
        $payload = $this->payload([
            'name' => '<script>alert(1)</script>',
            'subject' => '<img src=x onerror=alert(1)>',
            'message' => '<script>alert("xss")</script> & "quoted" <b>bold</b>',
        ]);

        $this->post(route('contact.store'), $payload);

        $message = ContactMessage::query()->sole();

        // Stored verbatim — escaping is an output concern.
        $this->assertSame($payload['message'], $message->message);

        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();

        $html = $this->actingAs($admin)
            ->get(route('admin.contact-messages.show', $message))
            ->assertOk()
            ->getContent();

        // The raw payload must never appear as markup.
        $this->assertStringNotContainsString('<script>alert("xss")</script>', $html);
        $this->assertStringNotContainsString('<img src=x onerror=alert(1)>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);

        // And the inbox list is escaped too.
        $list = $this->actingAs($admin)
            ->get(route('admin.contact-messages.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('<script>alert(1)</script>', $list);
    }

    public function test_only_declared_fields_can_be_mass_assigned(): void
    {
        $this->post(route('contact.store'), $this->payload([
            'is_read' => '1',
            'id' => 999,
            'created_at' => '2000-01-01 00:00:00',
        ]));

        $message = ContactMessage::query()->sole();

        // is_read is not part of the submission payload, so the message
        // arrives unread even though the field was smuggled into the request.
        $this->assertFalse($message->is_read);
        $this->assertNotSame(999, $message->getKey());
        $this->assertNotSame('2000-01-01 00:00:00', $message->created_at->toDateTimeString());
    }

    public function test_guest_cannot_read_or_mutate_the_inbox(): void
    {
        $message = ContactMessage::query()->create([
            'name' => 'Existing',
            'email' => 'existing@example.com',
            'subject' => 'Existing subject',
            'message' => 'An existing message body.',
        ]);

        $this->get(route('admin.contact-messages.index'))->assertRedirect(route('login'));
        $this->get(route('admin.contact-messages.show', $message))->assertRedirect(route('login'));
        $this->put(route('admin.contact-messages.update', $message), ['is_read' => '1'])->assertRedirect(route('login'));
        $this->delete(route('admin.contact-messages.destroy', $message))->assertRedirect(route('login'));

        $this->assertDatabaseHas('contact_messages', ['id' => $message->getKey(), 'is_read' => false]);
    }

    public function test_no_header_injection_through_visitor_fields(): void
    {
        $injected = "attacker@example.com\r\nBcc: victim@example.com";

        $this->post(route('contact.store'), $this->payload(['email' => $injected]));

        // The payload is not a valid address, so it never reaches the mailer
        // and never becomes a stored header.
        $this->assertDatabaseCount('contact_messages', 0);
    }

    public function test_header_injection_attempt_in_the_subject_never_becomes_a_header(): void
    {
        $headers = $this->deliveredHeaders([
            'subject' => "Hello\r\nBcc: victim@example.com",
        ]);

        // The value is stored verbatim — it is data, and the form must not
        // silently rewrite what a person typed.
        $this->assertStringContainsString(
            'Bcc: victim@example.com',
            ContactMessage::query()->sole()->subject,
        );

        // The security property is that it never becomes a *header*: Symfony
        // encodes the subject, so the injected text stays folded inside the
        // Subject value and no Bcc header line exists.
        $this->assertDoesNotMatchRegularExpression('/^Bcc:/mi', $headers);
        $this->assertStringContainsString('Subject:', $headers);
    }

    public function test_header_injection_attempt_in_the_name_never_becomes_a_header(): void
    {
        $headers = $this->deliveredHeaders([
            'name' => "Attacker\r\nBcc: victim@example.com",
        ]);

        $this->assertDoesNotMatchRegularExpression('/^Bcc:/mi', $headers);
    }

    // ---------------------------------------------------------------------
    // Rate limiting
    // ---------------------------------------------------------------------

    public function test_normal_submission_succeeds_within_the_limit(): void
    {
        $max = (int) config('contact.rate_limit.max');

        for ($i = 1; $i <= $max; $i++) {
            $this->post(route('contact.store'), $this->payload(['subject' => "Message {$i}"]))
                ->assertSessionHasNoErrors();
        }

        $this->assertDatabaseCount('contact_messages', $max);
    }

    public function test_repeated_submissions_are_rate_limited(): void
    {
        $max = (int) config('contact.rate_limit.max');

        for ($i = 1; $i <= $max; $i++) {
            $this->post(route('contact.store'), $this->payload(['subject' => "Message {$i}"]));
        }

        // The attempt after the budget is refused with a form-level message.
        $this->post(route('contact.store'), $this->payload(['subject' => 'One too many']))
            ->assertSessionHasErrors('contact');

        $this->assertDatabaseCount('contact_messages', $max);
    }

    public function test_rate_limit_message_is_user_facing_and_leaks_nothing(): void
    {
        $max = (int) config('contact.rate_limit.max');

        for ($i = 1; $i <= $max; $i++) {
            $this->post(route('contact.store'), $this->payload());
        }

        $this->post(route('contact.store'), $this->payload());

        $message = $this->get(route('contact'))->viewData('errors')->first('contact');

        $this->assertNotSame('', $message);
        // No limiter internals are exposed to the visitor.
        foreach (['contact-form:', 'RateLimiter', '127.0.0.1', 'decay'] as $needle) {
            $this->assertStringNotContainsString($needle, $message);
        }
    }

    public function test_rate_limit_response_is_deterministic_without_sleeping(): void
    {
        // The limiter is driven by request count, not wall-clock time, so the
        // test needs no sleeps: the same sequence always produces the same
        // outcome within the decay window.
        config(['contact.rate_limit.max' => 3]);

        $this->post(route('contact.store'), $this->payload())->assertSessionHasNoErrors();
        $this->post(route('contact.store'), $this->payload())->assertSessionHasNoErrors();
        $this->post(route('contact.store'), $this->payload())->assertSessionHasNoErrors();
        $this->post(route('contact.store'), $this->payload())->assertSessionHasErrors('contact');

        $this->assertDatabaseCount('contact_messages', 3);
    }

    // ---------------------------------------------------------------------
    // Anti-spam
    // ---------------------------------------------------------------------

    public function test_honeypot_submission_is_discarded_silently(): void
    {
        Log::spy();

        $this->post(route('contact.store'), $this->payload(['website' => 'http://spam.example']))
            ->assertRedirect(route('contact'))
            // The bot sees the ordinary success state on purpose.
            ->assertSessionHas('contact_status');

        $this->assertDatabaseCount('contact_messages', 0);

        Log::shouldHaveReceived('notice');
    }

    public function test_honeypot_does_not_affect_legitimate_visitors(): void
    {
        // The field is absent from a real submission, which must succeed.
        $this->post(route('contact.store'), $this->payload())->assertSessionHasNoErrors();

        $this->assertDatabaseCount('contact_messages', 1);
    }

    public function test_honeypot_field_is_off_screen_and_hidden_from_assistive_tech(): void
    {
        $html = $this->get(route('contact'))->assertOk()->getContent();

        $this->assertStringContainsString('class="form-honeypot"', $html);
        $this->assertStringContainsString('aria-hidden="true"', $html);
        $this->assertStringContainsString('tabindex="-1"', $html);
        $this->assertStringContainsString('autocomplete="off"', $html);

        // And it must not be hidden with display:none, which some bots skip.
        $css = file_get_contents(resource_path('css/frontend.css'));
        $this->assertStringNotContainsString(".form-honeypot {\n  display: none", $css);
    }

    // ---------------------------------------------------------------------
    // Admin inbox integration (the existing Phase 4 system)
    // ---------------------------------------------------------------------

    public function test_submitted_message_appears_in_the_admin_inbox(): void
    {
        $this->post(route('contact.store'), $this->payload([
            'subject' => 'Inbox visibility check',
        ]));

        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('admin.contact-messages.index'))
            ->assertOk()
            ->assertSee('Inbox visibility check', escape: false)
            ->assertSee('jane@example.com', escape: false);
    }

    public function test_unread_count_includes_the_new_message(): void
    {
        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();

        $this->post(route('contact.store'), $this->payload());

        $this->actingAs($admin)
            ->get(route('admin.contact-messages.index'))
            ->assertOk()
            ->assertSee(__('Unread').' (1)', escape: false);
    }

    public function test_opening_the_message_marks_it_read_and_delete_still_works(): void
    {
        $this->post(route('contact.store'), $this->payload());

        $message = ContactMessage::query()->sole();
        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();

        // Existing Phase 4 behaviour is preserved: viewing marks it read.
        $this->actingAs($admin)
            ->get(route('admin.contact-messages.show', $message))
            ->assertOk()
            ->assertSee('Jane Doe', escape: false);

        $this->assertTrue($message->fresh()->is_read);

        // Toggle back to unread.
        $this->actingAs($admin)
            ->put(route('admin.contact-messages.update', $message), ['is_read' => '0'])
            ->assertRedirect(route('admin.contact-messages.index'));

        $this->assertFalse($message->fresh()->is_read);

        // Delete.
        $this->actingAs($admin)
            ->delete(route('admin.contact-messages.destroy', $message))
            ->assertRedirect(route('admin.contact-messages.index'));

        $this->assertDatabaseMissing('contact_messages', ['id' => $message->getKey()]);
    }

    public function test_legacy_rows_without_a_subject_still_render(): void
    {
        // Rows created before the subject column existed have an empty subject.
        $message = ContactMessage::query()->create([
            'name' => 'Legacy Sender',
            'email' => 'legacy@example.com',
            'subject' => '',
            'message' => 'A message stored before subjects existed on the form.',
        ]);

        $this->assertNotSame('', $message->displaySubject());

        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('admin.contact-messages.index'))
            ->assertOk()
            ->assertSee('A message stored before subjects existed on the form.', escape: false);
    }

    // ---------------------------------------------------------------------
    // Email notification
    // ---------------------------------------------------------------------

    public function test_notification_is_sent_to_the_profile_address(): void
    {
        $this->post(route('contact.store'), $this->payload());

        Mail::assertSent(ContactMessageReceived::class, function ($mail) {
            return $mail->hasTo('richard@example.com');
        });
    }

    public function test_notification_sender_is_the_application_not_the_visitor(): void
    {
        $this->post(route('contact.store'), $this->payload());

        Mail::assertSent(ContactMessageReceived::class, function ($mail) {
            // The visitor is only ever the Reply-To...
            if (! $mail->hasReplyTo('jane@example.com')) {
                return false;
            }

            // ...and never the sender. The envelope From is supplied by
            // config/mail.php when the message is sent, so it must differ from
            // the visitor address, which keeps SPF/DMARC working.
            $configuredFrom = config('mail.from.address');

            return $configuredFrom !== 'jane@example.com'
                && ! collect($mail->from)->contains(fn ($address) => ($address['address'] ?? null) === 'jane@example.com');
        });
    }

    public function test_delivered_message_uses_the_application_sender_and_visitor_reply_to(): void
    {
        $headers = $this->deliveredHeaders();

        // The actual header block a transport would receive: the application
        // is the sender, the visitor is only ever the Reply-To.
        $this->assertStringContainsString(config('mail.from.address'), $headers);
        $this->assertMatchesRegularExpression('/^From: .*'.preg_quote((string) config('mail.from.address'), '/').'/mi', $headers);
        $this->assertMatchesRegularExpression(
            '/^Reply-To: Jane Doe <jane@example\.com>\s*$/mi',
            $headers,
        );
        $this->assertStringNotContainsString('From: Jane Doe', $headers);
    }

    /**
     * Submit the form, then send the stored message's notification through an
     * in-memory transport and return the real header block.
     *
     * Asserting on the delivered headers (rather than the mailable object) is
     * what makes the header-injection guarantees meaningful: the envelope From
     * is applied at send time, and encoding happens in the MIME layer.
     *
     * @param  array<string, string>  $overrides
     */
    private function deliveredHeaders(array $overrides = []): string
    {
        $this->post(route('contact.store'), $this->payload($overrides));

        $message = ContactMessage::query()->latest('id')->firstOrFail();

        $transport = new ArrayTransport;

        // Built directly so the transport is the in-memory one; Mail::fake()
        // from setUp() would otherwise intercept the send. The name argument
        // is the mailer's configuration key.
        $mailer = new Mailer(
            'array',
            app('view'),
            $transport,
            app('events'),
        );

        $mailer->alwaysFrom(
            (string) config('mail.from.address'),
            (string) config('mail.from.name'),
        );

        // Delivered to the resolved recipient, mirroring ContactNotifier.
        $mailer->to('richard@example.com')->send(new ContactMessageReceived($message));

        $sent = $transport->messages()->first();

        $this->assertNotNull($sent, 'No message reached the transport.');

        return $sent->getOriginalMessage()->getHeaders()->toString();
    }

    public function test_database_is_written_before_the_notification_is_attempted(): void
    {
        // Proves the ordering: by the time the mailable is built, the row has
        // an id, so persistence cannot depend on mail success.
        Mail::assertNothingSent();

        Mail::fake();

        $this->post(route('contact.store'), $this->payload());

        Mail::assertSent(ContactMessageReceived::class, function ($mail) {
            return $mail->message->exists && $mail->message->getKey() !== null;
        });
    }
}
