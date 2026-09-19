<x-admin.page :heading="$message->displaySubject()"
              :subheading="__('Message from :name', ['name' => $message->name])">
    <article class="max-w-2xl space-y-5 rounded-xl border border-jet/50 bg-eerie-black-2/60 p-6">
        <header class="space-y-1 border-b border-jet/40 pb-4">
            <p class="text-fs-2 font-semibold text-white-1">{{ $message->name }}</p>

            <p class="text-fs-6 text-light-gray/70">
                <a href="mailto:{{ $message->email }}" class="text-brand hover:underline">{{ $message->email }}</a>
                · {{ $message->created_at->format('M j, Y H:i') }}
            </p>
        </header>

        <p class="whitespace-pre-line text-fs-6 leading-relaxed text-light-gray">{{ $message->message }}</p>

        <footer class="flex flex-wrap items-center gap-3 border-t border-jet/40 pt-4">
            <form method="POST" action="{{ route('admin.contact-messages.update', $message) }}">
                @csrf
                @method('PUT')

                <input type="hidden" name="is_read" value="{{ $message->is_read ? '0' : '1' }}">

                <x-ui.button type="submit" variant="secondary" size="sm">
                    {{ $message->is_read ? __('Mark as unread') : __('Mark as read') }}
                </x-ui.button>
            </form>

            <form method="POST" action="{{ route('admin.contact-messages.destroy', $message) }}"
                  data-turbo-confirm="{{ __('Delete this message? This cannot be undone.') }}">
                @csrf
                @method('DELETE')

                <x-ui.button type="submit" variant="danger" size="sm">
                    {{ __('Delete') }}
                </x-ui.button>
            </form>
        </footer>
    </article>
</x-admin.page>
