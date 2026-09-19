<x-admin.page
    :heading="__('Media')"
    :subheading="__('Uploaded images and their generated variants.')">

    <form method="POST"
          action="{{ route('admin.media.store') }}"
          enctype="multipart/form-data"
          class="mb-6 rounded-xl border border-jet/50 bg-eerie-black-2/60 p-5">
        @csrf

        <div class="grid gap-4 sm:grid-cols-[1fr_1fr_auto] sm:items-end">
            <x-admin.field :label="__('Image')" name="file" type="file" accept="image/jpeg,image/png,image/webp" required
                           :help="__('JPG, PNG or WebP, up to 4 MB. The original is preserved; thumb and medium WebP variants are generated.')" />
            <x-admin.field :label="__('Alt text')" name="alt_text" :value="old('alt_text')"
                           :help="__('Accessibility description.')" />
            <div class="sm:pb-1">
                <x-ui.button type="submit" variant="brand">
                    {{ __('Upload') }}
                </x-ui.button>
            </div>
        </div>
    </form>

    @if ($media->isEmpty())
        <p class="rounded-xl border border-jet/50 bg-eerie-black-2/60 px-4 py-10 text-center text-fs-6 text-light-gray/50">
            {{ __('No media yet. Upload the first image above.') }}
        </p>
    @else
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($media as $medium)
                <div class="overflow-hidden rounded-xl border border-jet/50 bg-eerie-black-2/60">
                    <a href="{{ route('admin.media.edit', $medium) }}" class="block">
                        <img src="{{ $medium->url() }}"
                             alt="{{ $medium->alt_text ?? $medium->title }}"
                             class="h-40 w-full object-cover"
                             width="{{ $medium->width }}"
                             height="{{ $medium->height }}"
                             loading="lazy">
                    </a>

                    <div class="space-y-1 p-3">
                        <p class="truncate text-fs-6 text-white-2" title="{{ $medium->original_name }}">
                            {{ $medium->title ?? $medium->original_name }}
                        </p>

                        <p class="text-fs-8 text-light-gray/60">
                            {{ number_format($medium->size / 1024) }} KB
                            @if ($medium->width)
                                · {{ $medium->width }}×{{ $medium->height }}
                            @endif
                        </p>

                        <div class="flex items-center gap-2 pt-1">
                            <x-ui.button :href="route('admin.media.edit', $medium)" variant="secondary" size="sm">
                                {{ __('Edit') }}
                            </x-ui.button>

                            <x-admin.delete-button :route="route('admin.media.destroy', $medium)" :label="$medium->title ?? __('this file')" />
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        @if ($media->hasPages())
            <div class="mt-5">
                {{ $media->links() }}
            </div>
        @endif
    @endif
</x-admin.page>
