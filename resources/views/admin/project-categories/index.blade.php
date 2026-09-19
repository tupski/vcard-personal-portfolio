@php
    $columns = [__('Order'), __('Name'), __('Slug'), __('Projects')];

    $rows = $items->map(fn ($item) => [
        'key' => $item->getKey(),
        'label' => $item->name,
        'cells' => [__('#:n', ['n' => $item->sort_order]),
            e($item->name),
            '<span class="font-mono text-fs-8">'.e($item->slug).'</span>',
            $item->projects_count],
    ])->all();
@endphp

<x-admin.page
    :heading="__('Project Categories')"
    :subheading="__('Portfolio filters. The All filter is a UI concept, not a row here.')"
    :create-route="'admin.project-categories.create'"
    :create-label="__('New category')">

    <x-admin.table
        :columns="$columns"
        :rows="$rows"
        :edit-route="'admin.project-categories.edit'"
        :delete-route="'admin.project-categories.destroy'"
        :empty-message="__('No categories yet.')">
    </x-admin.table>
</x-admin.page>
