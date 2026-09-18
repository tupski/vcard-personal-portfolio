@props([
    'title' => null,
    'description' => null,
    'canonical' => null,
    'robots' => 'index, follow',
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>{{ $title ? $title.' - '.$siteName : $siteName }}</title>

    @if ($description)
        <meta name="description" content="{{ $description }}">
    @endif

    <meta name="robots" content="{{ $robots }}">

    @if ($canonical)
        <link rel="canonical" href="{{ $canonical }}">
    @endif

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

</body>

</html>
