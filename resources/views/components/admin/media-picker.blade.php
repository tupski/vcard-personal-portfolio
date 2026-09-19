@props([
    'label',
    'name',
    'value' => null,
    'help' => null,
])

@php
    $id = $attributes->get('id') ?? 'field-'.$name;
    $hasError = $errors->has($name);
    $media = \App\Models\Media::query()->latest()->limit(50)->get(['id', 'path', 'title']);
@endphp

<div class="space-y-1.5">
    <label for="{{ $id }}" class="block text-fs-7 font-medium text-light-gray">
        {{ $label }}
    </label>

    <input
        type="text"
        name="{{ $name }}"
        id="{{ $id }}"
        list="{{ $id }}-options"
        value="{{ old($name, $value) }}"
        @if ($hasError) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif
        {{ $attributes->except(['id'])->class([
            'block w-full rounded-lg border bg-smoky-black/40 px-3 py-2 text-fs-6 font-mono text-white-1',
            'transition-colors focus:outline-none focus:ring-1',
            'border-bittersweet/70 focus:border-bittersweet focus:ring-bittersweet' => $hasError,
            'border-jet/70 focus:border-brand focus:ring-brand' => ! $hasError,
        ]) }}>

    <datalist id="{{ $id }}-options">
        @foreach ($media as $medium)
            <option value="{{ $medium->path }}">{{ $medium->title ?? $medium->original_name }}</option>
        @endforeach
    </datalist>

    <p class="text-fs-8 text-light-gray/50">
        {{ __('Paste a media path or pick one from the library — or upload new media in the') }}
        <a href="{{ route('admin.media.index') }}" class="text-brand hover:underline">{{ __('media library') }}</a>.
        @if ($help)
            {{ $help }}
        @endif
    </p>

    @error($name)
        <p id="{{ $id }}-error" class="text-fs-7 text-bittersweet">{{ $message }}</p>
    @enderror
</div>
