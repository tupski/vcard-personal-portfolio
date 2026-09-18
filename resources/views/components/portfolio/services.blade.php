@php
    $services = App\Support\PortfolioContent::services();
@endphp

<section class="service">

    <h3 class="h3 service-title">{{ __("What i'm doing") }}</h3>

    <ul class="service-list">

        @foreach ($services as $service)
            <li class="service-item">

                <div class="service-icon-box">
                    <img src="{{ asset($service['icon']) }}"
                         alt="{{ $service['icon_alt'] }}"
                         width="40">
                </div>

                <div class="service-content-box">
                    <h4 class="h4 service-item-title">{{ $service['title'] }}</h4>

                    <p class="service-item-text">{{ $service['text'] }}</p>
                </div>

            </li>
        @endforeach

    </ul>

</section>
