@props([
    'title' => null,
    'heading' => null,
    'description' => null,
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ? $title.' — '.$siteName : $siteName }}</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen items-center justify-center bg-smoky-black px-4 py-10 font-sans text-white-2 antialiased"
      data-controller="turbo">

    <main class="w-full max-w-md">
        <x-ui.flash />

        <div class="rounded-2xl border border-jet/50 bg-gradient-onyx p-6 shadow-card-1 sm:p-8">
            <header class="mb-6">
                <p class="text-fs-7 uppercase tracking-[0.2em] text-brand">
                    {{ $siteName }}
                </p>

                <h1 class="mt-2 text-fs-1 font-semibold text-white-1">
                    {{ $heading ?? $title ?? __('Sign in') }}
                </h1>

                @if ($description)
                    <p class="mt-2 text-fs-6 text-light-gray/70">{{ $description }}</p>
                @endif
            </header>

            {{ $slot }}
        </div>
    </main>

</body>
</html>
