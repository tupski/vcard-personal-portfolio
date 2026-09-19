@php
    $columns = [__('Order'), __('Name'), __('Slug'), __('Posts')];

    $rows = $items->map(fn ($item) => [
        'key' => $item->getKey(),
        'label' => $item->name,
        'cells' => [__('#:n', ['n' => $item->sort_order]),
            e($item->name),
            '<span class="font-mono text-fs-8">'.e($item->slug).'</span>',
            $item->posts_count],
    ])->all();
@endphp

<x-admin.page
    :heading="__('Blog Categories')"
    :subheading="__('Blog taxonomy.')"
    :create-route="'admin.blog-categories.create'"
    :create-label="__('New category')">

    <x-admin.table
        :columns="$columns"
        :rows="$rows"
        :edit-route="'admin.blog-categories.edit'"
        :delete-route="'admin.blog-categories.destroy'"
        :empty-message="__('No categories yet.')">
    </x-admin.table>
</x-admin.page>
