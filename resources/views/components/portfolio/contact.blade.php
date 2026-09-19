@php
    /*
     * Port of the original #CONTACT section.
     *
     * The original form was inert (`action="#"`) and only toggled the submit
     * button's `disabled` attribute via `data-form-input`. Phase 7 wires the
     * real submission + inbox; here we preserve the exact markup and behaviour.
     */
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

    <form action="#" class="form" data-controller="contact-form">

        <div class="input-wrapper">
            <input type="text"
                   name="fullname"
                   class="form-input"
                   placeholder="{{ __('Full name') }}"
                   aria-label="{{ __('Full name') }}"
                   required
                   data-action="input->contact-form#validate">

            <input type="email"
                   name="email"
                   class="form-input"
                   placeholder="{{ __('Email address') }}"
                   aria-label="{{ __('Email address') }}"
                   required
                   data-action="input->contact-form#validate">
        </div>

        <textarea name="message"
                  class="form-input"
                  placeholder="{{ __('Your Message') }}"
                  aria-label="{{ __('Your Message') }}"
                  required
                  data-action="input->contact-form#validate"></textarea>

        <button class="form-btn" type="submit" disabled data-contact-form-target="submit">
            <x-portfolio-icon name="paper-plane" />
            <span>{{ __('Send Message') }}</span>
        </button>

    </form>

</section>
