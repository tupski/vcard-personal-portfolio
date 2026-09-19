<x-admin.page
    :heading="__('Messages')"
    :subheading="__('Messages submitted through the public contact form.')">

    <div class="mb-4 flex flex-wrap items-center gap-2">
        <x-ui.button :href="route('admin.contact-messages.index')" variant="{{ request('filter') !== 'unread' ? 'brand' : 'secondary' }}" size="sm">
            {{ __('All') }}
        </x-ui.button>

        <x-ui.button :href="route('admin.contact-messages.index', ['filter' => 'unread'])"
                     variant="{{ request('filter') === 'unread' ? 'brand' : 'secondary' }}" size="sm">
            {{ __('Unread') }} ({{ $unreadCount }})
        </x-ui.button>
    </div>

    <div class="overflow-hidden rounded-xl border border-jet/50">
        @forelse ($messages as $msg)
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-jet/30 bg-eerie-black-2/60 px-4 py-3 last:border-0 {{ $msg->is_read ? '' : 'bg-brand/[0.04]' }}">
                <div class="min-w-0 flex-1">
                    <p class="flex items-center gap-2 text-fs-6 font-medium text-white-2">
                        @unless ($msg->is_read)
                            <span class="inline-block size-2 rounded-full bg-brand" aria-hidden="true"></span>
                        @endunless
                        {{ $msg->name }}
                        <span class="text-fs-8 font-normal text-light-gray/60">{{ $msg->email }}</span>
                    </p>

                    <p class="mt-1 truncate text-fs-6 text-light-gray">{{ $msg->displaySubject() }}</p>

                    <p class="mt-1 line-clamp-1 text-fs-7 text-light-gray/60">{{ $msg->message }}</p>
                </div>

                <div class="flex shrink-0 items-center gap-2">
                    <span class="text-fs-8 text-light-gray/50">{{ $msg->created_at->format('M j, Y H:i') }}</span>

                    <x-ui.button :href="route('admin.contact-messages.show', $msg)" variant="secondary" size="sm">
                        {{ __('View') }}
                    </x-ui.button>
                </div>
            </div>
        @empty
            <p class="bg-eerie-black-2/60 px-4 py-10 text-center text-fs-6 text-light-gray/50">
                {{ __('No messages here.') }}
            </p>
        @endforelse
    </div>

    @if ($messages->hasPages())
        <div class="mt-5">
            {{ $messages->links() }}
        </div>
    @endif
</x-admin.page>
