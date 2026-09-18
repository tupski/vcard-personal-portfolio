@php
    /*
     * Port of the original <aside class="sidebar">.
     *
     * The original toggled a single `active` class on this element via
     * `data-sidebar`; the CSS still drives the height/opacity transition, and
     * the Stimulus `sidebar` controller replaces the inline script.
     */
    $profile = App\Support\PortfolioContent::profile();
    $socialLinks = App\Support\PortfolioContent::socialLinks();
@endphp

<aside class="sidebar" data-controller="sidebar">

    <div class="sidebar-info">

        <figure class="avatar-box">
            <img src="{{ asset($profile['avatar']) }}"
                 alt="{{ $profile['name'] }}"
                 width="80">
        </figure>

        <div class="info-content">
            <h1 class="name" title="{{ $profile['name'] }}">{{ $profile['name'] }}</h1>

            <p class="title">{{ $profile['title'] }}</p>
        </div>

        <button class="info_more-btn"
                type="button"
                data-action="sidebar#toggle"
                data-sidebar-target="toggle"
                aria-expanded="false"
                aria-controls="sidebar-contacts">
            <span>{{ __('Show Contacts') }}</span>

            <x-icon name="chevron-down" />
        </button>

    </div>

    <div class="sidebar-info_more" id="sidebar-contacts">

        <div class="separator"></div>

        <ul class="contacts-list">

            <li class="contact-item">

                <div class="icon-box">
                    <x-icon name="mail-outline" />
                </div>

                <div class="contact-info">
                    <p class="contact-title">{{ __('Email') }}</p>

                    <a href="mailto:{{ $profile['email'] }}" class="contact-link">{{ $profile['email'] }}</a>
                </div>

            </li>

            <li class="contact-item">

                <div class="icon-box">
                    <x-icon name="phone-portrait-outline" />
                </div>

                <div class="contact-info">
                    <p class="contact-title">{{ __('Phone') }}</p>

                    <a href="{{ $profile['phone_href'] }}" class="contact-link">{{ $profile['phone'] }}</a>
                </div>

            </li>

            <li class="contact-item">

                <div class="icon-box">
                    <x-icon name="calendar-outline" />
                </div>

                <div class="contact-info">
                    <p class="contact-title">{{ __('Birthday') }}</p>

                    <time datetime="{{ $profile['birthday_iso'] }}">{{ $profile['birthday'] }}</time>
                </div>

            </li>

            <li class="contact-item">

                <div class="icon-box">
                    <x-icon name="location-outline" />
                </div>

                <div class="contact-info">
                    <p class="contact-title">{{ __('Location') }}</p>

                    <address>{{ $profile['location'] }}</address>
                </div>

            </li>

        </ul>

        <div class="separator"></div>

        <ul class="social-list">

            @foreach ($socialLinks as $link)
                <li class="social-item">
                    <a href="{{ $link['url'] }}"
                       class="social-link"
                       aria-label="{{ $link['label'] }}">
                        <x-icon :name="$link['icon']" />
                    </a>
                </li>
            @endforeach

        </ul>

    </div>

</aside>
