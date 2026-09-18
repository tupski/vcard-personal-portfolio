<x-layouts.admin :title="__('Dashboard')" :heading="__('Dashboard')">
    <x-slot:subheading>
        {{ __('Phase 1 — project setup complete. Content modules arrive in Phase 4.') }}
    </x-slot:subheading>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        <x-ui.alert variant="success" :title="__('Application online')">
            {{ __('Laravel :version with Turbo and Stimulus is running.', ['version' => app()->version()]) }}
        </x-ui.alert>

        <x-ui.alert variant="info" :title="__('Authentication active')">
            {{ __('Signed in as :name.', ['name' => auth()->user()->name]) }}
        </x-ui.alert>

        <x-ui.alert variant="warning" :title="__('Next up')">
            {{ __('Phase 2 ports the vCard template into Blade components.') }}
        </x-ui.alert>
    </div>
</x-layouts.admin>
