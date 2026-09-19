<x-admin.form-card
    :action="route('admin.profile.update')"
    method="PUT"
    :heading="__('Profile')"
    :subheading="__('Sidebar identity and About page copy. Values render verbatim.')">

    <div class="grid gap-4 sm:grid-cols-2">
        <x-admin.field :label="__('Name')" name="name" :value="$profile->name" required />
        <x-admin.field :label="__('Title / role')" name="title" :value="$profile->title" required />
    </div>

    <x-admin.media-picker :label="__('Avatar')" name="avatar_path" :value="$profile->avatar_path" required />

    <div class="grid gap-4 sm:grid-cols-2">
        <x-admin.field :label="__('Email')" name="email" type="email" :value="$profile->email" required />
        <x-admin.field :label="__('Birthday')" name="birthday" type="date" :value="$profile->birthday->format('Y-m-d')" required />
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <x-admin.field :label="__('Phone (display)')" name="phone" :value="$profile->phone" required
                       :help="__('Rendered text, e.g. +1 (213) 352-2795')" />
        <x-admin.field :label="__('Phone href')" name="phone_href" :value="$profile->phone_href" required
                       :help="__('tel: link target, e.g. tel:+121****2795 — kept exactly as provided.')" />
    </div>

    <x-admin.field :label="__('Location')" name="location" :value="$profile->location" required />

    <x-admin.field :label="__('About')" name="about" :value="$profile->about" rows="8"
                   :help="__('Separate paragraphs with a blank line.')" />
</x-admin.form-card>
