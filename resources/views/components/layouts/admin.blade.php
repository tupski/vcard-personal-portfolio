@props([
    'title' => null,
    'description' => null,
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ? $title.' — '.__('Admin').' — '.config('app.name') : __('Admin').' — '.config('app.name') }}</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-eerie-black-1 font-sans text-white-2 antialiased"
      data-controller="turbo">

    <a href="#admin-main"
       class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 focus:rounded focus:bg-brand focus:px-4 focus:py-2 focus:text-smoky-black">
        {{ __('Skip to content') }}
    </a>

    <div class="flex min-h-screen flex-col lg:flex-row">

        {{-- Sidebar --}}
        <aside class="shrink-0 border-b border-jet/60 bg-eerie-black-2 lg:w-64 lg:border-b-0 lg:border-r">
            <div class="flex items-center justify-between gap-4 px-5 py-4 lg:block lg:py-6">
                <a href="{{ route('admin.dashboard') }}"
                   class="block text-fs-3 font-semibold tracking-wide text-white-1 transition-colors hover:text-brand">
                    {{ __('Artupski CMS') }}
                </a>

                <p class="hidden text-fs-7 text-light-gray/60 lg:mt-1 lg:block">
                    {{ __('Content management') }}
                </p>
            </div>

            <nav class="px-3 pb-4 lg:pb-6" aria-label="{{ __('Admin navigation') }}">
                <x-admin.nav />
            </nav>
        </aside>

        {{-- Main column --}}
        <div class="flex min-w-0 flex-1 flex-col">

            <header class="flex flex-wrap items-center justify-between gap-3 border-b border-jet/60 bg-eerie-black-2/60 px-5 py-3 backdrop-blur">
                <div class="min-w-0">
                    <h1 class="truncate text-fs-2 font-semibold text-white-1">
                        {{ $heading ?? ($title ?? __('Dashboard')) }}
                    </h1>

                    @isset($subheading)
                        <p class="mt-0.5 truncate text-fs-7 text-light-gray/70">{{ $subheading }}</p>
                    @endisset
                </div>

                <div class="flex items-center gap-3">
                    @auth
                        <span class="hidden text-fs-7 text-light-gray/70 sm:inline">
                            {{ auth()->user()->name }}
                        </span>

                        <form method="POST" action="{{ route('logout') }}" data-turbo="false">
                            @csrf
                            <x-ui.button type="submit" variant="ghost" size="sm">
                                {{ __('Log out') }}
                            </x-ui.button>
                        </form>
                    @endauth
                </div>
            </header>

            <main id="admin-main" class="flex-1 px-5 py-6">
                <x-ui.flash />
                {{ $slot }}
            </main>

        </div>
    </div>

</body>
</html>
