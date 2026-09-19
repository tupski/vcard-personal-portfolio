@props([
    'heading',
    'subheading' => null,
    'createRoute' => null,
    'createLabel' => null,
])

<x-layouts.admin :title="$heading" :heading="$heading">
    <x-slot:subheading>
        {{ $subheading }}
    </x-slot:subheading>

    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-2">
            {{ $breadcrumb ?? '' }}
        </div>

        @if ($createRoute)
            <x-ui.button :href="route($createRoute)" variant="brand" size="sm" icon="+">
                {{ $createLabel ?? __('New') }}
            </x-ui.button>
        @endif
    </div>

    {{ $slot }}
</x-layouts.admin>
