@php
    /*
     * Admin navigation. Phase 4 wires every entry to its CRUD route.
     */
    $items = [
        ['label' => __('Dashboard'), 'route' => 'admin.dashboard', 'icon' => 'heroicon-m-square-3-stack-3d'],
        ['label' => __('Profile'), 'route' => 'admin.profile.edit', 'icon' => 'heroicon-m-user-circle'],
        ['label' => __('Services'), 'route' => 'admin.services.index', 'icon' => 'heroicon-m-sparkles'],
        ['label' => __('Experience'), 'route' => 'admin.experience.index', 'icon' => 'heroicon-m-briefcase'],
        ['label' => __('Education'), 'route' => 'admin.education.index', 'icon' => 'heroicon-m-academic-cap'],
        ['label' => __('Skills'), 'route' => 'admin.skills.index', 'icon' => 'heroicon-m-chart-bar'],
        ['label' => __('Projects'), 'route' => 'admin.projects.index', 'icon' => 'heroicon-m-squares-2x2'],
        ['label' => __('Project Categories'), 'route' => 'admin.project-categories.index', 'icon' => 'heroicon-m-tag'],
        ['label' => __('Blog Posts'), 'route' => 'admin.blog-posts.index', 'icon' => 'heroicon-m-pencil-square'],
        ['label' => __('Blog Categories'), 'route' => 'admin.blog-categories.index', 'icon' => 'heroicon-m-folder'],
        ['label' => __('Testimonials'), 'route' => 'admin.testimonials.index', 'icon' => 'heroicon-m-chat-bubble-left-ellipsis'],
        ['label' => __('Clients'), 'route' => 'admin.clients.index', 'icon' => 'heroicon-m-building-office-2'],
        ['label' => __('Social Links'), 'route' => 'admin.social-links.index', 'icon' => 'heroicon-m-link'],
        ['label' => __('Media'), 'route' => 'admin.media.index', 'icon' => 'heroicon-m-photo'],
        ['label' => __('Messages'), 'route' => 'admin.contact-messages.index', 'icon' => 'heroicon-m-inbox-arrow-down'],
        ['label' => __('Settings'), 'route' => 'admin.settings.edit', 'icon' => 'heroicon-m-cog-6-tooth'],
    ];

    $unread = \App\Models\ContactMessage::query()->unread()->count();
@endphp

<ul class="flex flex-row gap-1 overflow-x-auto lg:flex-col lg:gap-0.5">
    @foreach ($items as $item)
        <li class="shrink-0 lg:shrink">
            <x-admin.nav-link
                :href="route($item['route'])"
                :icon="$item['icon']"
                :active="request()->routeIs($item['route']) || request()->routeIs($item['route'].'.*')">
                {{ $item['label'] }}

                @if ($item['route'] === 'admin.contact-messages.index' && $unread > 0)
                    <span class="ml-auto rounded-full bg-bittersweet px-1.5 py-0.5 text-fs-8 font-semibold text-white-1">
                        {{ $unread }}
                    </span>
                @endif
            </x-admin.nav-link>
        </li>
    @endforeach
</ul>
