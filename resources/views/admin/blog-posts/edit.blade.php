@php($item = $item ?? null)

<x-admin.form-card
    :action="$item ? route('admin.blog-posts.update', $item) : route('admin.blog-posts.store')"
    :method="$item ? 'PUT' : 'POST'"
    :heading="$item ? __('Edit post') : __('New post')"
    :subheading="__('Blog card. Content becomes the full-post body in Phase 8.')"
    :back-route="'admin.blog-posts.index'">

    <x-admin.select
        :label="__('Category')"
        name="blog_category_id"
        :options="$categories->pluck('name', 'id')->all()"
        :value="$item?->blog_category_id"
        required />

    <x-admin.field :label="__('Title')" name="title" :value="$item?->title" required />

    <x-admin.field :label="__('Slug')" name="slug" :value="$item?->slug"
                   :help="__('Optional; generated from the title when empty.')" />

    <x-admin.field :label="__('Excerpt')" name="excerpt" :value="$item?->excerpt" rows="3" required />

    <x-admin.field :label="__('Content')" name="content" :value="$item?->content" rows="10"
                   :help="__('Article body. Plain text: line breaks are preserved, markup is shown as text, not rendered.')" />

    <x-admin.media-picker :label="__('Image')" name="image_path" :value="$item?->image_path" required />

    <x-admin.field :label="__('Image alt text')" name="image_alt" :value="$item?->image_alt" required />

    <div class="grid gap-4 sm:grid-cols-2">
        <x-admin.field :label="__('Published on')" name="published_at" type="date" :value="$item?->published_at?->format('Y-m-d')" required />
        <x-admin.field :label="__('Display date')" name="display_date" :value="$item?->display_date" required
                       :help="__('Rendered verbatim on the card, e.g. Fab 23, 2022')" />
    </div>

    <x-admin.field :label="__('Sort order')" name="sort_order" type="number" :value="$item?->sort_order ?? 0" required />

    <x-admin.toggle :label="__('Visible on the frontend')" name="is_visible" :checked="$item?->is_visible ?? true" />
</x-admin.form-card>
