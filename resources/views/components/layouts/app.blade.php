@props([
    'title' => null,
    'description' => null,
    'canonical' => null,
    'robots' => 'index, follow',
    'bodyClass' => '',
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ $title ? $title.' — '.config('app.name') : config('app.name') }}</title>

    @if ($description)
        <meta name="description" content="{{ $description }}">
    @endif

    <meta name="robots" content="{{ $robots }}">

    @if ($canonical)
        <link rel="canonical" href="{{ $canonical }}">
    @endif

    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">

    {{-- Turbo Drive replaces the body on navigation; the head is merged. --}}
    <meta name="turbo-cache-control" content="no-preview">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{ $head ?? '' }}
</head>
<body class="min-h-screen bg-smoky-black font-sans text-white-2 antialiased {{ $bodyClass }}"
      data-controller="turbo">

    <a href="#main"
       class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 focus:rounded focus:bg-brand focus:px-4 focus:py-2 focus:text-smoky-black">
        {{ __('Skip to content') }}
    </a>

    <x-ui.flash />

    {{ $slot }}

</body>
</html>
