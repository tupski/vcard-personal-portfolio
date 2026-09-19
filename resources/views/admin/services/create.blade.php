@php($item = $item ?? null)

<x-admin.form-card
    :action="$item ? route('admin.services.update', $item) : route('admin.services.store')"
    :method="$item ? 'PUT' : 'POST'"
    :heading="$item ? __('Edit :label', ['label' => __('service')]) : __('New service')"
    :subheading="__('Cards in the About page grid.')"
    :back-route="'admin.services.index'">

        <x-admin.field :label="__('Title')" name="title" :value="$item?->title" required />

        <x-admin.field :label="__('Icon path')" name="icon_path" :value="$item?->icon_path" required :help="__('Path under public/, e.g. assets/images/icon-design.svg')" />

        <x-admin.field :label="__('Icon alt text')" name="icon_alt" :value="$item?->icon_alt" required />

        <x-admin.field :label="__('Description')" name="description" :value="$item?->description" required rows="3" />

        <x-admin.field :label="__('Sort order')" name="sort_order" :value="$item?->sort_order" required type="number" />

        <x-admin.toggle :label="__('Visible on the frontend')" name="is_visible" :checked="$item?->is_visible ?? true" />
</x-admin.form-card>
