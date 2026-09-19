@php($item = $item ?? null)

<x-admin.form-card
    :action="$item ? route('admin.education.update', $item) : route('admin.education.store')"
    :method="$item ? 'PUT' : 'POST'"
    :heading="$item ? __('Edit :label', ['label' => __('entry')]) : __('New entry')"
    :subheading="__('Resume timeline entry.')"
    :back-route="'admin.education.index'">

        <x-admin.field :label="__('Title')" name="title" :value="$item?->title" required />

        <x-admin.field :label="__('Institution')" name="institution" :value="$item?->institution" />

        <x-admin.field :label="__('Period')" name="period" :value="$item?->period" required :help="__('Displayed verbatim, e.g. 2007 - 2008')" />

        <x-admin.field :label="__('Description')" name="description" :value="$item?->description" required rows="3" />

        <x-admin.field :label="__('Sort order')" name="sort_order" :value="$item?->sort_order" required type="number" />

        <x-admin.toggle :label="__('Visible on the frontend')" name="is_visible" :checked="$item?->is_visible ?? true" />
</x-admin.form-card>
