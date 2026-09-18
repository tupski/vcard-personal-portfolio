@php
    /*
     * Port of the original <nav class="navbar">.
     *
     * The original rendered <button data-nav-link> elements and switched the
     * visible <article> with JavaScript. Each entry is now a real named route
     * so Turbo Drive handles navigation and browser history, and the active
     * state comes from the current route.
     */
    $navigation = App\Support\PortfolioContent::navigation();
@endphp

<nav class="navbar" aria-label="{{ __('Primary') }}">

    <ul class="navbar-list">

        @foreach ($navigation as $item)
            @php($isActive = request()->routeIs($item['route']))

            <li class="navbar-item">
                <a href="{{ route($item['route']) }}"
                   class="navbar-link @if ($isActive) active @endif"
                   @if ($isActive) aria-current="page" @endif>
                    {{ $item['label'] }}
                </a>
            </li>
        @endforeach

    </ul>

</nav>
