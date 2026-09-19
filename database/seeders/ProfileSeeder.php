<?php

namespace Database\Seeders;

use App\Models\Profile;
use App\Models\Setting;
use App\Support\SeedContent;
use Illuminate\Database\Seeder;

/**
 * Seeds the single profile row and the site settings that have a real
 * frontend consumer today.
 */
class ProfileSeeder extends Seeder
{
    /**
     * Seed identity + settings.
     */
    public function run(): void
    {
        $profile = SeedContent::profile();

        Profile::query()->updateOrCreate(
            ['email' => $profile['email']],
            [
                'name' => $profile['name'],
                'title' => $profile['title'],
                'avatar_path' => $profile['avatar'],
                'phone' => $profile['phone'],
                'phone_href' => $profile['phone_href'],
                'birthday' => $profile['birthday_iso'],
                'location' => $profile['location'],
                'about' => implode("\n\n", SeedContent::about()),
            ],
        );

        Setting::query()->updateOrCreate(
            ['key' => 'site.name'],
            ['value' => config('app.name')],
        );

        Setting::query()->updateOrCreate(
            ['key' => 'contact.map_embed_url'],
            ['value' => SeedContent::mapEmbedUrl()],
        );

        // SEO defaults (seo.default_description, seo.og_image, seo.site_url,
        // seo.robots) are deliberately NOT seeded: the SEO layer falls back to
        // the profile row, APP_URL and the template's own assets, so a fresh
        // install already emits a complete, valid head. An admin only adds a
        // row when they want to override that behaviour.
    }
}
