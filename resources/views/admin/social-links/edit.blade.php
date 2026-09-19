@php($item = $item ?? null)

<x-admin.form-card
    :action="$item ? route('admin.social-links.update', $item) : route('admin.social-links.store')"
    :method="$item ? 'PUT' : 'POST'"
    :heading="$item ? __('Edit :label', ['label' => __('link')]) : __('New link')"
    :subheading="__('Sidebar profile links.')"
    :back-route="'admin.social-links.index'">

        <x-admin.field :label="__('Label')" name="label" :value="$item?->label" required :help="__('Accessible name, e.g. Facebook')" />

        <x-admin.field :label="__('Icon')" name="icon" :value="$item?->icon" required :help="__('Icon key from the inline set, e.g. logo-facebook')" />

        <x-admin.field :label="__('URL')" name="url" :value="$item?->url" required type="url" />

        <x-admin.field :label="__('Sort order')" name="sort_order" :value="$item?->sort_order" required type="number" />

        <x-admin.toggle :label="__('Visible on the frontend')" name="is_visible" :checked="$item?->is_visible ?? true" />
</x-admin.form-card>
