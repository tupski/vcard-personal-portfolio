@php
    $columns = [__('Order'), __('Skill'), __('Percent'), __('Status')];

    $rows = $items->map(fn ($item) => [
        'key' => $item->getKey(),
        'label' => $item->title,
        'cells' => [__('#:n', ['n' => $item->sort_order]),
            e($item->title),
            $item->percent.'%',
            $item->is_visible
            ? '<span class="text-brand">'.__('Visible').'</span>'
            : '<span class="text-light-gray/40">'.__('Hidden').'</span>'],
    ])->all();
@endphp

<x-admin.page
    :heading="__('Skills')"
    :subheading="__('Progress bars on the resume page.')"
    :create-route="'admin.skills.create'"
    :create-label="__('New skill')">

    <x-admin.table
        :columns="$columns"
        :rows="$rows"
        :edit-route="'admin.skills.edit'"
        :delete-route="'admin.skills.destroy'"
        :empty-message="__('No skills yet.')">
    </x-admin.table>
</x-admin.page>
