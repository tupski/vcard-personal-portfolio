@props([
    'name',
    'label' => null,
    'type' => 'text',
    'value' => null,
    'placeholder' => null,
    'required' => false,
    'help' => null,
])

@php
    $id = $attributes->get('id') ?? $name.'-'.Str::random(4);
    $hasError = $errors->has($name);
@endphp

<div {{ $attributes->only('class')->class('space-y-1.5') }}>
    @if ($label)
        <label for="{{ $id }}" class="block text-fs-7 font-medium text-light-gray">
            {{ $label }}
            @if ($required)<span class="text-bittersweet" aria-hidden="true">*</span>@endif
        </label>
    @endif

    <input
        type="{{ $type }}"
        name="{{ $name }}"
        id="{{ $id }}"
        value="{{ old($name, $value) }}"
        @if ($placeholder) placeholder="{{ $placeholder }}" @endif
        @if ($required) required @endif
        @if ($hasError) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif
        {{ $attributes->except(['class', 'id'])->class([
            'block w-full rounded-lg border bg-smoky-black/40 px-3 py-2 text-fs-6 text-white-1 placeholder:text-light-gray/40',
            'transition-colors focus:outline-none focus:ring-1',
            'border-bittersweet/70 focus:border-bittersweet focus:ring-bittersweet' => $hasError,
            'border-jet/70 focus:border-brand focus:ring-brand' => ! $hasError,
        ]) }}
    >

    @if ($help && ! $hasError)
        <p class="text-fs-8 text-light-gray/50">{{ $help }}</p>
    @endif

    @error($name)
        <p id="{{ $id }}-error" class="text-fs-7 text-bittersweet">{{ $message }}</p>
    @enderror
</div>
