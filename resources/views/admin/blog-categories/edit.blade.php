@php($item = $item ?? null)

<x-admin.form-card
    :action="$item ? route('admin.blog-categories.update', $item) : route('admin.blog-categories.store')"
    :method="$item ? 'PUT' : 'POST'"
    :heading="$item ? __('Edit :label', ['label' => __('category')]) : __('New category')"
    :subheading="__('Blog taxonomy.')"
    :back-route="'admin.blog-categories.index'">

        <x-admin.field :label="__('Name')" name="name" :value="$item?->name" required />

        <x-admin.field :label="__('Slug')" name="slug" :value="$item?->slug" :help="__('Optional; generated from the name when empty.')" />

        <x-admin.field :label="__('Sort order')" name="sort_order" :value="$item?->sort_order" required type="number" />
</x-admin.form-card>
