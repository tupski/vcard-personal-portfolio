<x-admin.form-card
    :action="route('admin.settings.update')"
    method="PUT"
    :heading="__('Settings')"
    :subheading="__('Site-wide values with a live frontend effect.')">

    <x-admin.field :label="__('Site name')" name="settings[site.name]" :value="$settings['site.name']" :help="__('Used in every page title and the admin sidebar brand.')" />

    <x-admin.field :label="__('Map embed URL')" name="settings[contact.map_embed_url]" :value="$settings['contact.map_embed_url']" :help="__('Google Maps embed src for the Contact page.')" />
</x-admin.form-card>

<x-admin.form-card
    :action="route('admin.settings.update')"
    method="PUT"
    :heading="__('SEO')"
    :subheading="__('Discoverability defaults for every public page. Leave a field blank to fall back to content that already exists.')">

    <x-admin.field :label="__('Default description')" name="settings[seo.default_description]" :value="$settings['seo.default_description']"
                   :help="__('Used when a page supplies no description of its own. Falls back to the profile about text.')" />

    <x-admin.field :label="__('Social image')" name="settings[seo.og_image]" :value="$settings['seo.og_image']"
                   :help="__('Media library path or a static asset path. Falls back to the profile avatar.')" />

    <x-admin.field :label="__('Canonical site URL')" name="settings[seo.site_url]" :value="$settings['seo.site_url']"
                   :help="__('Absolute URL used for canonicals, the sitemap and robots.txt. Defaults to APP_URL.')" />

    <x-admin.field :label="__('Robots directive')" name="settings[seo.robots]" :value="$settings['seo.robots']"
                   :help="__('Public page robots value. Defaults to index, follow in production and noindex, nofollow elsewhere.')" />
</x-admin.form-card>
