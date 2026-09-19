<x-admin.form-card
    :action="route('admin.settings.update')"
    method="PUT"
    :heading="__('Settings')"
    :subheading="__('Site-wide values with a live frontend effect.')">

    <x-admin.field :label="__('Site name')" name="settings[site.name]" :value="$settings['site.name']" :help="__('Used in every page title and the admin sidebar brand.')" />

    <x-admin.field :label="__('Map embed URL')" name="settings[contact.map_embed_url]" :value="$settings['contact.map_embed_url']" :help="__('Google Maps embed src for the Contact page.')" />
</x-admin.form-card>
