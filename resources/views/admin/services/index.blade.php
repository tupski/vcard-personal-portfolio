@php
    $columns = [__('Order'), __('Icon'), __('Title'), __('Status')];

    $rows = $items->map(fn ($item) => [
        'key' => $item->getKey(),
        'label' => $item->title,
        'cells' => [__('#:n', ['n' => $item->sort_order]),
            '<span class="font-mono text-fs-8">'.e($item->icon_path).'</span>',
            e($item->title),
            $item->is_visible
            ? '<span class="text-brand">'.__('Visible').'</span>'
            : '<span class="text-light-gray/40">'.__('Hidden').'</span>'],
    ])->all();
@endphp

<x-admin.page
    :heading="__('Services')"
    :subheading="__('Cards in the About page grid.')"
    :create-route="'admin.services.create'"
    :create-label="__('New service')">

    <x-admin.table
        :columns="$columns"
        :rows="$rows"
        :edit-route="'admin.services.edit'"
        :delete-route="'admin.services.destroy'"
        :empty-message="__('No services yet.')">
    </x-admin.table>
</x-admin.page>
