@props([
    'active' => false,
    'href' => '#',
    'icon' => null,
])

<a href="{{ $href }}"
   @if ($active) aria-current="page" @endif
   {{ $attributes->class([
        'flex items-center gap-3 rounded-lg px-3 py-2 text-fs-6 transition-colors',
        'bg-brand/10 font-medium text-brand' => $active,
        'text-light-gray/80 hover:bg-white-1/5 hover:text-white-1' => ! $active,
   ]) }}>
    @if ($icon)
        <span class="shrink-0 text-fs-7" aria-hidden="true">{{ $icon }}</span>
    @endif

    <span class="truncate">{{ $slot }}</span>
</a>
