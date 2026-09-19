@php($item = $item ?? null)

<x-admin.form-card
    :action="$item ? route('admin.projects.update', $item) : route('admin.projects.store')"
    :method="$item ? 'PUT' : 'POST'"
    :heading="$item ? __('Edit project') : __('New project')"
    :subheading="__('Portfolio card. The category drives the frontend filter.')"
    :back-route="'admin.projects.index'">

    <x-admin.select
        :label="__('Category')"
        name="project_category_id"
        :options="$categories->pluck('name', 'id')->all()"
        :value="$item?->project_category_id"
        required />

    <x-admin.field :label="__('Title')" name="title" :value="$item?->title" required />

    <x-admin.field :label="__('Slug')" name="slug" :value="$item?->slug"
                   :help="__('Optional; generated from the title when empty.')" />

    <x-admin.field :label="__('Image path')" name="image_path" :value="$item?->image_path" required
                   :help="__('Path under public/, e.g. assets/images/project-1.jpg')" />

    <x-admin.field :label="__('Image alt text')" name="image_alt" :value="$item?->image_alt" required />

    <x-admin.field :label="__('Description')" name="description" :value="$item?->description" rows="4"
                   :help="__('Admin-side detail; not rendered on the public card yet.')" />

    <div class="grid gap-4 sm:grid-cols-2">
        <x-admin.field :label="__('Client')" name="client" :value="$item?->client" />
        <x-admin.field :label="__('Technologies')" name="technologies" :value="$item?->technologies"
                       :help="__('Comma-separated.')" />
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <x-admin.field :label="__('Display date')" name="display_date" :value="$item?->display_date"
                       :help="__('Rendered verbatim when set.')" />
        <x-admin.field :label="__('Project URL')" name="url" type="url" :value="$item?->url" />
    </div>

    <x-admin.field :label="__('Sort order')" name="sort_order" type="number" :value="$item?->sort_order ?? 0" required />

    <div class="grid gap-3">
        <x-admin.toggle :label="__('Visible on the frontend')" name="is_visible" :checked="$item?->is_visible ?? true" />
        <x-admin.toggle :label="__('Featured')" name="featured" :checked="$item?->featured ?? false"
                        :help="__('Highlights the card for future sections; no frontend effect yet.')" />
    </div>
</x-admin.form-card>
