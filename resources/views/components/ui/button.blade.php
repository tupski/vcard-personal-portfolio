@props([
    'variant' => 'primary',
    'size' => 'md',
    'type' => 'button',
    'href' => null,
    'icon' => null,
])

@php
    $variants = [
        'primary' => 'bg-gradient-onyx text-brand border border-jet/60 hover:border-brand/60 hover:text-white-1',
        'brand' => 'bg-brand text-smoky-black border border-brand hover:brightness-110 font-semibold',
        'secondary' => 'bg-eerie-black-2 text-light-gray border border-jet/60 hover:text-white-1 hover:border-brand/50',
        'ghost' => 'bg-transparent text-light-gray/80 border border-transparent hover:text-brand',
        'danger' => 'bg-transparent text-bittersweet border border-bittersweet/50 hover:bg-bittersweet hover:text-white-1',
    ];

    $sizes = [
        'sm' => 'px-3 py-1.5 text-fs-7',
        'md' => 'px-4 py-2 text-fs-6',
        'lg' => 'px-6 py-2.5 text-fs-5',
    ];

    $classes = 'inline-flex items-center justify-center gap-2 rounded-lg transition-all duration-200 '
        .($variants[$variant] ?? $variants['primary']).' '
        .($sizes[$size] ?? $sizes['md']);
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class($classes) }}>
        @if ($icon)<span aria-hidden="true">{{ $icon }}</span>@endif
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->class($classes) }}>
        @if ($icon)<span aria-hidden="true">{{ $icon }}</span>@endif
        {{ $slot }}
    </button>
@endif
