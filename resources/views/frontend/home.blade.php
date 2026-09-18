<x-layouts.app :title="__('Home')"
              :description="__('Personal portfolio of Richard Hanrick — web developer and creative director.')">
    <main id="main" class="mx-auto flex min-h-screen max-w-3xl flex-col items-center justify-center gap-6 px-6 text-center">
        <p class="text-fs-7 uppercase tracking-[0.3em] text-brand">{{ config('app.name') }}</p>

        <h1 class="text-4xl font-semibold text-white-1 sm:text-5xl">Richard Hanrick</h1>

        <p class="text-fs-5 text-light-gray/70">Web developer</p>

        <x-ui.alert variant="info" class="text-left">
            {{ __('Phase 1 complete: the Laravel 13 + Turbo + Stimulus foundation is running. The original vCard design is ported in Phase 2.') }}
        </x-ui.alert>

        <div class="flex flex-wrap items-center justify-center gap-3">
            <x-ui.button :href="route('login')" variant="brand">
                {{ __('Admin login') }}
            </x-ui.button>

            <x-ui.button href="https://github.com/tupski/vcard-personal-portfolio" variant="secondary">
                {{ __('Source') }}
            </x-ui.button>
        </div>
    </main>
</x-layouts.app>
