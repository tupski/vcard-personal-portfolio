@php
    /*
     * Admin navigation. Only Dashboard exists in Phase 1; the remaining
     * entries are declared with `route` => null until their Phase 4 CRUD
     * routes land, so the sidebar never links to a 404.
     */
    $items = [
        ['label' => __('Dashboard'), 'route' => 'admin.dashboard', 'icon' => '▦'],
        ['label' => __('Profile'), 'route' => null, 'icon' => '◉'],
        ['label' => __('Experiences'), 'route' => null, 'icon' => '▤'],
        ['label' => __('Education'), 'route' => null, 'icon' => '◈'],
        ['label' => __('Skills'), 'route' => null, 'icon' => '▰'],
        ['label' => __('Projects'), 'route' => null, 'icon' => '▣'],
        ['label' => __('Blog'), 'route' => null, 'icon' => '▧'],
        ['label' => __('Testimonials'), 'route' => null, 'icon' => '❝'],
        ['label' => __('Services'), 'route' => null, 'icon' => '✦'],
        ['label' => __('Social Links'), 'route' => null, 'icon' => '⇗'],
        ['label' => __('Messages'), 'route' => null, 'icon' => '✉'],
        ['label' => __('SEO'), 'route' => null, 'icon' => '⌕'],
        ['label' => __('Media'), 'route' => null, 'icon' => '▩'],
        ['label' => __('Settings'), 'route' => null, 'icon' => '⚙'],
    ];
@endphp

<ul class="flex flex-row gap-1 overflow-x-auto lg:flex-col lg:gap-0.5">
    @foreach ($items as $item)
        <li class="shrink-0 lg:shrink">
            @if ($item['route'] && Route::has($item['route']))
                <x-admin.nav-link
                    :href="route($item['route'])"
                    :icon="$item['icon']"
                    :active="request()->routeIs($item['route'])">
                    {{ $item['label'] }}
                </x-admin.nav-link>
            @else
                <span class="flex cursor-not-allowed items-center gap-3 rounded-lg px-3 py-2 text-fs-6 text-light-gray/30"
                      title="{{ __('Available in a later phase') }}"
                      aria-disabled="true">
                    <span class="shrink-0 text-fs-7" aria-hidden="true">{{ $item['icon'] }}</span>
                    <span class="truncate">{{ $item['label'] }}</span>
                </span>
            @endif
        </li>
    @endforeach
</ul>
