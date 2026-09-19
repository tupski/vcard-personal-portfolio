@php
    $columns = [__('Order'), __('Logo'), __('Name'), __('Status')];

    $rows = $items->map(fn ($item) => [
        'key' => $item->getKey(),
        'label' => $item->name ?? __('Client'),
        'cells' => [__('#:n', ['n' => $item->sort_order]),
            '<img src="'.asset($item->logo_path).'" alt="" class="h-8 w-auto opacity-80">',
            e($item->name ?? '—'),
            $item->is_visible
            ? '<span class="text-brand">'.__('Visible').'</span>'
            : '<span class="text-light-gray/40">'.__('Hidden').'</span>'],
    ])->all();
@endphp

<x-admin.page
    :heading="__('Clients')"
    :subheading="__('Logo carousel on the About page.')"
    :create-route="'admin.clients.create'"
    :create-label="__('New client')">

    <x-admin.table
        :columns="$columns"
        :rows="$rows"
        :edit-route="'admin.clients.edit'"
        :delete-route="'admin.clients.destroy'"
        :empty-message="__('No clients yet.')">
    </x-admin.table>
</x-admin.page>
