@php
    $clients = App\Support\PortfolioContent::clients();
@endphp

<section class="clients">

    <h3 class="h3 clients-title">{{ __('Clients') }}</h3>

    <ul class="clients-list has-scrollbar">

        @foreach ($clients as $logo)
            <li class="clients-item">
                <a href="#">
                    <img src="{{ App\Support\PortfolioContent::mediaUrl($logo) }}" alt="client logo" loading="lazy">
                </a>
            </li>
        @endforeach

    </ul>

</section>
