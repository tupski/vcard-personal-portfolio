@props([
    'variant' => 'card',
    'dismissible' => false,
])

@php
    $variants = [
        'card' => 'border-jet/50 bg-gradient-onyx text-light-gray',
        'success' => 'border-brand/40 bg-brand/10 text-brand',
        'error' => 'border-bittersweet/40 bg-bittersweet/10 text-bittersweet',
        'warning' => 'border-vegas-gold/40 bg-vegas-gold/10 text-vegas-gold',
        'info' => 'border-jet/60 bg-eerie-black-2 text-light-gray',
    ];

    $classes = 'flex items-start gap-3 rounded-xl border px-4 py-3 text-fs-6 '
        .($variants[$variant] ?? $variants['card']);
@endphp

<div {{ $attributes->class($classes) }} role="{{ $variant === 'error' ? 'alert' : 'status' }}">
    <div class="min-w-0 flex-1">
        @if (isset($title))
            <p class="font-semibold text-white-1">{{ $title }}</p>
        @endif

        {{ $slot }}
    </div>

    @if ($dismissible)
        <button type="button"
                class="shrink-0 text-current/70 transition-opacity hover:opacity-100"
                data-action="flash#dismiss"
                aria-label="{{ __('Dismiss') }}">
            <span aria-hidden="true">&times;</span>
        </button>
    @endif
</div>
