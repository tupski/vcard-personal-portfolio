@php
    /*
     * Port of the original #CONTACT section, now wired to a real submission.
     *
     * The Phase 2 markup and class names are preserved exactly so the visual
     * contract is untouched: the map, the "Contact Form" heading, the paired
     * name/email inputs inside `.input-wrapper`, the message textarea and the
     * gradient submit button with its paper-plane icon.
     *
     * Added for Phase 7:
     *   - a real POST target, so the form works with JavaScript disabled
     *   - a subject field (full width, between the paired inputs and message)
     *   - server-rendered validation errors and old input
     *   - an inline result notice (success / rate limit)
     *   - an off-screen honeypot
     *
     * One deliberate change to the original behaviour: the submit button is
     * rendered ENABLED. The original template marked it `disabled` in the
     * markup and re-enabled it from JavaScript once the form was valid, which
     * was harmless there because the form never submitted anything. Here it
     * would leave the form permanently unusable for a visitor without
     * JavaScript, so the button starts enabled and the Stimulus controller
     * reapplies the original disabled-until-valid behaviour when JS is
     * available. The form therefore works with and without JavaScript, and
     * server-side validation stays authoritative in both cases.
     */
    $contactStatus = session('contact_status');
    $rateLimitError = $errors->first('contact');
@endphp

<section class="mapbox" data-mapbox>
    <figure>
        <iframe
            src="{{ App\Support\PortfolioContent::mapEmbedUrl() }}"
            width="400"
            height="300"
            title="{{ __('Location map') }}"
            loading="lazy"></iframe>
    </figure>
</section>

<section class="contact-form">

    <h3 class="h3 form-title">{{ __('Contact Form') }}</h3>

    @if ($contactStatus)
        <p class="form-notice form-notice-success" role="status" data-contact-status>
            {{ $contactStatus }}
        </p>
    @endif

    @if ($rateLimitError)
        <p class="form-notice form-notice-error" role="alert">
            {{ $rateLimitError }}
        </p>
    @endif

    <form action="{{ route('contact.store') }}"
          method="POST"
          class="form"
          data-controller="contact-form"
          data-action="submit->contact-form#submit turbo:submit-end->contact-form#restore">

        @csrf

        {{-- Honeypot: off-screen, hidden from assistive tech and skipped by
             keyboard navigation, so only an automated filler ever populates
             it. A filled honeypot is discarded server-side. --}}
        <div class="form-honeypot" aria-hidden="true">
            <label for="contact-website">{{ __('Website') }}</label>
            <input type="text"
                   id="contact-website"
                   name="website"
                   tabindex="-1"
                   autocomplete="off">
        </div>

        <div class="input-wrapper">
            <input type="text"
                   name="name"
                   class="form-input @error('name') is-invalid @enderror"
                   value="{{ old('name') }}"
                   placeholder="{{ __('Full name') }}"
                   aria-label="{{ __('Full name') }}"
                   @error('name') aria-invalid="true" aria-describedby="contact-name-error" @enderror
                   required
                   autocomplete="name"
                   data-action="input->contact-form#validate">

            <input type="email"
                   name="email"
                   class="form-input @error('email') is-invalid @enderror"
                   value="{{ old('email') }}"
                   placeholder="{{ __('Email address') }}"
                   aria-label="{{ __('Email address') }}"
                   @error('email') aria-invalid="true" aria-describedby="contact-email-error" @enderror
                   required
                   autocomplete="email"
                   data-action="input->contact-form#validate">
        </div>

        @error('name')
            <p class="form-error" id="contact-name-error">{{ $message }}</p>
        @enderror

        @error('email')
            <p class="form-error" id="contact-email-error">{{ $message }}</p>
        @enderror

        <input type="text"
               name="subject"
               class="form-input form-subject @error('subject') is-invalid @enderror"
               value="{{ old('subject') }}"
               placeholder="{{ __('Subject') }}"
               aria-label="{{ __('Subject') }}"
               @error('subject') aria-invalid="true" aria-describedby="contact-subject-error" @enderror
               required
               data-action="input->contact-form#validate">

        @error('subject')
            <p class="form-error" id="contact-subject-error">{{ $message }}</p>
        @enderror

        <textarea name="message"
                  class="form-input @error('message') is-invalid @enderror"
                  placeholder="{{ __('Your Message') }}"
                  aria-label="{{ __('Your Message') }}"
                  @error('message') aria-invalid="true" aria-describedby="contact-message-error" @enderror
                  required
                  data-action="input->contact-form#validate">{{ old('message') }}</textarea>

        @error('message')
            <p class="form-error" id="contact-message-error">{{ $message }}</p>
        @enderror

        <button class="form-btn" type="submit" data-contact-form-target="submit">
            <x-portfolio-icon name="paper-plane" />
            <span data-contact-form-target="label">{{ __('Send Message') }}</span>
        </button>

    </form>

</section>
