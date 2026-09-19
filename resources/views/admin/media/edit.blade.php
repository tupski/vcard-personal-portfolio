<x-admin.form-card
    :action="route('admin.media.update', $medium)"
    method="PUT"
    :heading="__('Edit media')"
    :subheading="__('Metadata only — image files are immutable once uploaded.')"
    :back-route="'admin.media.index'">

    <div class="rounded-xl border border-jet/50 bg-eerie-black-2/60 p-4">
        <img src="{{ $medium->url() }}"
             alt="{{ $medium->alt_text ?? $medium->title }}"
             class="mx-auto max-h-64 rounded-lg object-contain"
             width="{{ $medium->width }}"
             height="{{ $medium->height }}">
    </div>

    <x-admin.field :label="__('Title')" name="title" :value="$medium->title"
                   :help="__('How the image is identified in the library.')" />

    <x-admin.field :label="__('Alt text')" name="alt_text" :value="$medium->alt_text"
                   :help="__('Accessibility description rendered on public pages.')" />

    <dl class="grid gap-2 rounded-xl border border-jet/50 bg-eerie-black-2/60 p-4 text-fs-7 text-light-gray/70">
        <div class="flex justify-between gap-4">
            <dt>{{ __('Original file') }}</dt>
            <dd class="truncate font-mono">{{ $medium->original_name }}</dd>
        </div>
        <div class="flex justify-between gap-4">
            <dt>{{ __('Type / size') }}</dt>
            <dd>{{ $medium->mime_type }} · {{ number_format($medium->size / 1024) }} KB</dd>
        </div>
        <div class="flex justify-between gap-4">
            <dt>{{ __('Dimensions') }}</dt>
            <dd>{{ $medium->width }}×{{ $medium->height }}</dd>
        </div>
        <div class="flex justify-between gap-4">
            <dt>{{ __('Storage path') }}</dt>
            <dd class="truncate font-mono" title="{{ $medium->path }}">{{ $medium->path }}</dd>
        </div>
    </dl>
</x-admin.form-card>
