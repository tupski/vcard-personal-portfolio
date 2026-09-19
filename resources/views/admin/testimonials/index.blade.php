@php
    $columns = [__('Order'), __('Author'), __('Date'), __('Status')];

    $rows = $items->map(fn ($item) => [
        'key' => $item->getKey(),
        'label' => $item->name,
        'cells' => [__('#:n', ['n' => $item->sort_order]),
            e($item->name),
            e($item->display_date),
            $item->is_visible
            ? '<span class="text-brand">'.__('Visible').'</span>'
            : '<span class="text-light-gray/40">'.__('Hidden').'</span>'],
    ])->all();
@endphp

<x-admin.page
    :heading="__('Testimonials')"
    :subheading="__('Quotes and the About page modal.')"
    :create-route="'admin.testimonials.create'"
    :create-label="__('New testimonial')">

    <x-admin.table
        :columns="$columns"
        :rows="$rows"
        :edit-route="'admin.testimonials.edit'"
        :delete-route="'admin.testimonials.destroy'"
        :empty-message="__('No testimonials yet.')">
    </x-admin.table>
</x-admin.page>
