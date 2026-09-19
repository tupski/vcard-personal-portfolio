@php($item = $item ?? null)

<x-admin.form-card
    :action="$item ? route('admin.skills.update', $item) : route('admin.skills.store')"
    :method="$item ? 'PUT' : 'POST'"
    :heading="$item ? __('Edit :label', ['label' => __('skill')]) : __('New skill')"
    :subheading="__('Progress bars on the resume page.')"
    :back-route="'admin.skills.index'">

        <x-admin.field :label="__('Title')" name="title" :value="$item?->title" required />

        <x-admin.field :label="__('Percent')" name="percent" :value="$item?->percent" required type="number" :help="__('1-100; drives the progress bar width.')" />

        <x-admin.field :label="__('Sort order')" name="sort_order" :value="$item?->sort_order" required type="number" />

        <x-admin.toggle :label="__('Visible on the frontend')" name="is_visible" :checked="$item?->is_visible ?? true" />
</x-admin.form-card>
