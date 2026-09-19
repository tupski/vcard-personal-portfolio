@props([
    'label',
    'name',
    'options',
    'value' => null,
    'required' => false,
    'placeholder' => null,
])

@php
    $id = $attributes->get('id') ?? 'field-'.$name;
    $hasError = $errors->has($name);
@endphp

<div class="space-y-1.5">
    <label for="{{ $id }}" class="block text-fs-7 font-medium text-light-gray">
        {{ $label }}
        @if ($required)<span class="text-bittersweet" aria-hidden="true">*</span>@endif
    </label>

    <select
        name="{{ $name }}"
        id="{{ $id }}"
        @if ($required) required @endif
        @if ($hasError) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif
        {{ $attributes->except(['class', 'id'])->class([
            'block w-full rounded-lg border bg-smoky-black/40 px-3 py-2 text-fs-6 text-white-1',
            'transition-colors focus:outline-none focus:ring-1',
            'border-bittersweet/70 focus:border-bittersweet focus:ring-bittersweet' => $hasError,
            'border-jet/70 focus:border-brand focus:ring-brand' => ! $hasError,
        ]) }}>
        @if ($placeholder)
            <option value="">{{ $placeholder }}</option>
        @endif

        @foreach ($options as $optionValue => $optionLabel)
            <option value="{{ $optionValue }}" @selected((string) old($name, $value) === (string) $optionValue)>
                {{ $optionLabel }}
            </option>
        @endforeach
    </select>

    @error($name)
        <p id="{{ $id }}-error" class="text-fs-7 text-bittersweet">{{ $message }}</p>
    @enderror
</div>
