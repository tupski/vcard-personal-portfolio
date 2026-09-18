@props([
    'name' => 'remember',
    'label' => null,
    'checked' => false,
])

@php
    $id = $attributes->get('id') ?? $name.'-'.Str::random(4);
@endphp

<label for="{{ $id }}" class="flex cursor-pointer items-center gap-2 text-fs-7 text-light-gray">
    <input
        type="checkbox"
        name="{{ $name }}"
        id="{{ $id }}"
        value="1"
        @checked(old($name, $checked))
        {{ $attributes->except(['id'])->class([
            'size-4 rounded border-jet bg-smoky-black/60 text-brand',
            'focus:ring-2 focus:ring-brand focus:ring-offset-0',
        ]) }}
    >

    @if ($label)
        <span>{{ $label }}</span>
    @endif

    {{ $slot }}
</label>
