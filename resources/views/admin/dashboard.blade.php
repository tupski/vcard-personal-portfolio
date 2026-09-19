<x-layouts.admin :title="__('Dashboard')" :heading="__('Dashboard')">
    <x-slot:subheading>
        {{ __('Portfolio content at a glance.') }}
    </x-slot:subheading>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @foreach ([
            ['label' => __('Projects'), 'value' => $totals['projects'], 'route' => 'admin.projects.index'],
            ['label' => __('Published blog posts'), 'value' => $totals['blogPosts'], 'route' => 'admin.blog-posts.index'],
            ['label' => __('Services'), 'value' => $totals['services'], 'route' => 'admin.services.index'],
            ['label' => __('Testimonials'), 'value' => $totals['testimonials'], 'route' => 'admin.testimonials.index'],
            ['label' => __('Clients'), 'value' => $totals['clients'], 'route' => 'admin.clients.index'],
            ['label' => __('Unread messages'), 'value' => $totals['unreadMessages'], 'route' => 'admin.contact-messages.index'],
        ] as $card)
            <a href="{{ route($card['route']) }}"
               class="group rounded-xl border border-jet/50 bg-gradient-onyx p-5 shadow-card-1 transition-colors hover:border-brand/50">
                <p class="text-fs-8 uppercase tracking-wider text-light-gray/60">{{ $card['label'] }}</p>

                <p class="mt-2 text-3xl font-semibold text-white-1 group-hover:text-brand">
                    {{ $card['value'] }}
                </p>
            </a>
        @endforeach
    </div>

    <div class="mt-8 grid gap-6 lg:grid-cols-2">
        <section class="rounded-xl border border-jet/50 bg-eerie-black-2/60 p-5">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-fs-2 font-semibold text-white-1">{{ __('Recent blog posts') }}</h2>

                <x-ui.button :href="route('admin.blog-posts.index')" variant="ghost" size="sm">
                    {{ __('All posts') }}
                </x-ui.button>
            </div>

            @forelse ($recentPosts as $post)
                <div class="flex items-center justify-between gap-3 border-b border-jet/30 py-2.5 last:border-0">
                    <div class="min-w-0">
                        <p class="truncate text-fs-6 text-white-2">{{ $post->title }}</p>
                        <p class="text-fs-8 text-light-gray/60">{{ $post->category->name }} · {{ $post->display_date }}</p>
                    </div>

                    <span class="shrink-0 text-fs-8 {{ $post->is_visible ? 'text-brand' : 'text-light-gray/40' }}">
                        {{ $post->is_visible ? __('Published') : __('Hidden') }}
                    </span>
                </div>
            @empty
                <p class="py-6 text-center text-fs-6 text-light-gray/50">{{ __('No posts yet.') }}</p>
            @endforelse
        </section>

        <section class="rounded-xl border border-jet/50 bg-eerie-black-2/60 p-5">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-fs-2 font-semibold text-white-1">{{ __('Unread messages') }}</h2>

                <x-ui.button :href="route('admin.contact-messages.index')" variant="ghost" size="sm">
                    {{ __('Inbox') }}
                </x-ui.button>
            </div>

            @forelse ($recentMessages as $message)
                <div class="flex items-center justify-between gap-3 border-b border-jet/30 py-2.5 last:border-0">
                    <div class="min-w-0">
                        <p class="truncate text-fs-6 text-white-2">{{ $message->name }}</p>
                        <p class="truncate text-fs-8 text-light-gray/60">{{ $message->email }}</p>
                    </div>

                    <x-ui.button :href="route('admin.contact-messages.show', $message)" variant="secondary" size="sm">
                        {{ __('Read') }}
                    </x-ui.button>
                </div>
            @empty
                <p class="py-6 text-center text-fs-6 text-light-gray/50">{{ __('Inbox zero.') }}</p>
            @endforelse
        </section>
    </div>
</x-layouts.admin>
