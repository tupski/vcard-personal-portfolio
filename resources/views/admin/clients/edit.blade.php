@php($item = $item ?? null)

<x-admin.form-card
    :action="$item ? route('admin.clients.update', $item) : route('admin.clients.store')"
    :method="$item ? 'PUT' : 'POST'"
    :heading="$item ? __('Edit :label', ['label' => __('client')]) : __('New client')"
    :subheading="__('Logo carousel on the About page.')"
    :back-route="'admin.clients.index'">

        <x-admin.field :label="__('Name')" name="name" :value="$item?->name" :help="__('Internal reference; optional.')" />

        <x-admin.field :label="__('Logo path')" name="logo_path" :value="$item?->logo_path" required :help="__('Path under public/, e.g. assets/images/logo-1-color.png')" />

        <x-admin.field :label="__('URL')" name="url" :value="$item?->url" type="url" />

        <x-admin.field :label="__('Sort order')" name="sort_order" :value="$item?->sort_order" required type="number" />

        <x-admin.toggle :label="__('Visible on the frontend')" name="is_visible" :checked="$item?->is_visible ?? true" />
</x-admin.form-card>
