@props([
    'label',
    'name',
    'checked' => false,
    'help' => null,
])

@php
    $id = $attributes->get('id') ?? 'field-'.$name;
@endphp

<div class="flex items-start gap-3">
    <input
        type="checkbox"
        name="{{ $name }}"
        id="{{ $id }}"
        value="1"
        @checked(old($name, $checked))
        {{ $attributes->except(['id'])->class([
            'mt-1 size-4 rounded border-jet bg-smoky-black/60 text-brand',
            'focus:ring-2 focus:ring-brand focus:ring-offset-0',
        ]) }}>

    <div>
        <label for="{{ $id }}" class="cursor-pointer text-fs-6 text-white-2">{{ $label }}</label>

        @if ($help)
            <p class="text-fs-8 text-light-gray/50">{{ $help }}</p>
        @endif
    </div>
</div>
