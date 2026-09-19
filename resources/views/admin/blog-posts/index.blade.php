@php
    $columns = [__('Order'), __('Title'), __('Category'), __('Published'), __('Status')];

    $rows = $items->map(fn ($item) => [
        'key' => $item->getKey(),
        'label' => $item->title,
        'cells' => [__('#:n', ['n' => $item->sort_order]),
            e($item->title),
            '<span class="font-mono text-fs-8">'.e($item->category->name).'</span>',
            e($item->display_date),
            $item->is_visible
                ? '<span class="text-brand">'.__('Visible').'</span>'
                : '<span class="text-light-gray/40">'.__('Hidden').'</span>'],
    ])->all();
@endphp

<x-admin.page
    :heading="__('Blog Posts')"
    :subheading="__('Blog cards on the public blog page.')"
    :create-route="'admin.blog-posts.create'"
    :create-label="__('New post')">

    <x-admin.table
        :columns="$columns"
        :rows="$rows"
        :edit-route="'admin.blog-posts.edit'"
        :delete-route="'admin.blog-posts.destroy'"
        :empty-message="__('No posts yet.')">
    </x-admin.table>
</x-admin.page>
