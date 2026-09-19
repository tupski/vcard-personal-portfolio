@php
    $columns = [__('Order'), __('Title'), __('Company'), __('Period'), __('Status')];

    $rows = $items->map(fn ($item) => [
        'key' => $item->getKey(),
        'label' => $item->title,
        'cells' => [__('#:n', ['n' => $item->sort_order]),
            e($item->title),
            e($item->company ?? '—'),
            e($item->period),
            $item->is_visible
            ? '<span class="text-brand">'.__('Visible').'</span>'
            : '<span class="text-light-gray/40">'.__('Hidden').'</span>'],
    ])->all();
@endphp

<x-admin.page
    :heading="__('Experience')"
    :subheading="__('Resume timeline entries.')"
    :create-route="'admin.experience.create'"
    :create-label="__('New entry')">

    <x-admin.table
        :columns="$columns"
        :rows="$rows"
        :edit-route="'admin.experience.edit'"
        :delete-route="'admin.experience.destroy'"
        :empty-message="__('No entries yet.')">
    </x-admin.table>
</x-admin.page>
