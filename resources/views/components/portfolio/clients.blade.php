@php
    $clients = App\Support\PortfolioContent::clients();
@endphp

<section class="clients">

    <h3 class="h3 clients-title">{{ __('Clients') }}</h3>

    <ul class="clients-list has-scrollbar">

        @foreach ($clients as $logo)
            <li class="clients-item">
                <a href="#">
                    {{-- The logos are rendered at `width: 100%` by CSS. Declaring the
                         intrinsic dimensions does not change the rendered size: CSS
                         still sets the width, and the height follows the same aspect
                         ratio the browser would have derived from the file anyway. It
                         just lets the browser reserve the space up front. --}}
                    @php([$logoWidth, $logoHeight] = App\Support\PortfolioContent::mediaDimensions($logo))
                    <img src="{{ App\Support\PortfolioContent::mediaUrl($logo) }}"
                         alt="{{ __('Client logo') }}"
                         @if ($logoWidth && $logoHeight) width="{{ $logoWidth }}" height="{{ $logoHeight }}" @endif
                         loading="lazy"
                         decoding="async">
                </a>
            </li>
        @endforeach

    </ul>

</section>
