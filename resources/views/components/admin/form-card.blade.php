@props([
    'action',
    'method' => 'POST',
    'heading',
    'subheading' => null,
    'backRoute' => null,
])

<x-layouts.admin :title="$heading" :heading="$heading">
    <x-slot:subheading>
        {{ $subheading }}
    </x-slot:subheading>

    <form method="POST"
          action="{{ $action }}"
          class="max-w-2xl space-y-6">
        @csrf
        @if (in_array(strtoupper($method), ['PUT', 'PATCH', 'DELETE']))
            @method($method)
        @endif

        {{ $slot }}

        <div class="flex items-center gap-3 border-t border-jet/40 pt-5">
            <x-ui.button type="submit" variant="brand">
                {{ __('Save') }}
            </x-ui.button>

            @if ($backRoute)
                <x-ui.button :href="route($backRoute)" variant="ghost">
                    {{ __('Cancel') }}
                </x-ui.button>
            @endif
        </div>
    </form>
</x-layouts.admin>
