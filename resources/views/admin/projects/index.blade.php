@php
    $columns = [__('Order'), __('Title'), __('Category'), __('Featured'), __('Status')];

    $rows = $items->map(fn ($item) => [
        'key' => $item->getKey(),
        'label' => $item->title,
        'cells' => [__('#:n', ['n' => $item->sort_order]),
            e($item->title),
            '<span class="font-mono text-fs-8">'.e($item->category->name).'</span>',
            $item->featured ? '<span class="text-brand">'.__('Yes').'</span>' : '—',
            $item->is_visible
                ? '<span class="text-brand">'.__('Visible').'</span>'
                : '<span class="text-light-gray/40">'.__('Hidden').'</span>'],
    ])->all();
@endphp

<x-admin.page
    :heading="__('Projects')"
    :subheading="__('Portfolio cards. The category drives the frontend filter.')"
    :create-route="'admin.projects.create'"
    :create-label="__('New project')">

    <x-admin.table
        :columns="$columns"
        :rows="$rows"
        :edit-route="'admin.projects.edit'"
        :delete-route="'admin.projects.destroy'"
        :empty-message="__('No projects yet.')">
    </x-admin.table>
</x-admin.page>
