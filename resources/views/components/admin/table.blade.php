@props([
    'columns',
    'rows',
    'emptyMessage' => null,
    'editRoute' => null,
    'deleteRoute' => null,
])

@php
    $hasActions = $editRoute || $deleteRoute;
@endphp

<div class="overflow-x-auto rounded-xl border border-jet/50 bg-eerie-black-2/60">
    <table class="w-full min-w-max text-left text-fs-6">
        <thead>
            <tr class="border-b border-jet/50 text-fs-8 uppercase tracking-wider text-light-gray/60">
                @foreach ($columns as $column)
                    <th class="px-4 py-3 font-medium" scope="col">{{ $column }}</th>
                @endforeach

                @if ($hasActions)
                    <th class="px-4 py-3 text-right font-medium" scope="col">{{ __('Actions') }}</th>
                @endif
            </tr>
        </thead>

        <tbody>
            @forelse ($rows as $row)
                <tr class="border-b border-jet/30 last:border-0 hover:bg-white-1/[0.02]">
                    @foreach ($row['cells'] as $cell)
                        <td class="px-4 py-3 align-middle text-light-gray">{!! $cell !!}</td>
                    @endforeach

                    @if ($hasActions)
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-2">
                                @if ($editRoute)
                                    <x-ui.button :href="route($editRoute, $row['key'])" variant="secondary" size="sm">
                                        {{ __('Edit') }}
                                    </x-ui.button>
                                @endif

                                @if ($deleteRoute)
                                    <x-admin.delete-button :route="route($deleteRoute, $row['key'])" :label="$row['label'] ?? __('this item')" />
                                @endif
                            </div>
                        </td>
                    @endif
                </tr>
            @empty
                <tr>
                    <td class="px-4 py-10 text-center text-light-gray/50" colspan="{{ count($columns) + ($hasActions ? 1 : 0) }}">
                        {{ $emptyMessage ?? __('Nothing here yet.') }}
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
