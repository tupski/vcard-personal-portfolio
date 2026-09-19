@props([
    'seo' => null,
])

@php
    /*
     * Head metadata comes from the SeoManager via the page controller. The
     * fallback keeps the layout usable from any template (and from tests)
     * without a controller: it derives metadata from the current route name.
     */
    $seo ??= app(App\Support\Seo\SeoManager::class)->forPage(request()->route()?->getName() ?? 'home');
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <x-seo.meta :seo="$seo" />

    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">

    @vite(['resources/css/frontend.css', 'resources/js/app.js'])

    {{ $head ?? '' }}
</head>

<body>

    <a href="#main-content" class="skip-link">{{ __('Skip to content') }}</a>

    <main id="main-content">
        <x-portfolio.sidebar />

        <div class="main-content">
            <x-portfolio.navbar />

            {{ $slot }}
        </div>
    </main>

    <x-seo.json-ld :seo="$seo" />

</body>

</html>
