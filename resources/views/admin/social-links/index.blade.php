@php
    $columns = [__('Order'), __('Label'), __('URL'), __('Status')];

    $rows = $items->map(fn ($item) => [
        'key' => $item->getKey(),
        'label' => $item->label,
        'cells' => [__('#:n', ['n' => $item->sort_order]),
            e($item->label),
            '<span class="font-mono text-fs-8">'.e($item->url).'</span>',
            $item->is_visible
            ? '<span class="text-brand">'.__('Visible').'</span>'
            : '<span class="text-light-gray/40">'.__('Hidden').'</span>'],
    ])->all();
@endphp

<x-admin.page
    :heading="__('Social Links')"
    :subheading="__('Sidebar profile links.')"
    :create-route="'admin.social-links.create'"
    :create-label="__('New link')">

    <x-admin.table
        :columns="$columns"
        :rows="$rows"
        :edit-route="'admin.social-links.edit'"
        :delete-route="'admin.social-links.destroy'"
        :empty-message="__('No links yet.')">
    </x-admin.table>
</x-admin.page>
