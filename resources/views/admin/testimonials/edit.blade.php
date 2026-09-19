@php($item = $item ?? null)

<x-admin.form-card
    :action="$item ? route('admin.testimonials.update', $item) : route('admin.testimonials.store')"
    :method="$item ? 'PUT' : 'POST'"
    :heading="$item ? __('Edit :label', ['label' => __('testimonial')]) : __('New testimonial')"
    :subheading="__('Quotes and the About page modal.')"
    :back-route="'admin.testimonials.index'">

        <x-admin.field :label="__('Author name')" name="name" :value="$item?->name" required />

        <x-admin.field :label="__('Avatar path')" name="avatar_path" :value="$item?->avatar_path" required :help="__('Path under public/, e.g. assets/images/avatar-1.png')" />

        <x-admin.field :label="__('Date (ISO)')" name="testimonial_date" :value="$item?->testimonial_date?->format('Y-m-d") required type="date" />

        <x-admin.field :label="__('Display date')" name="display_date" :value="$item?->display_date" required :help="__('Rendered verbatim in the modal, e.g. 14 June, 2021')" />

        <x-admin.field :label="__('Quote')" name="content" :value="$item?->content" required rows="5" />

        <x-admin.field :label="__('Sort order')" name="sort_order" :value="$item?->sort_order" required type="number" />

        <x-admin.toggle :label="__('Visible on the frontend')" name="is_visible" :checked="$item?->is_visible ?? true" />
</x-admin.form-card>
