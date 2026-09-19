<?php

namespace Tests\Feature\Seo;

use App\Models\Media;
use App\Models\Setting;
use App\Models\User;
use App\Support\Seo\SeoDefaults;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * SEO + media integration, and the admin settings surface that drives it.
 */
class SeoMediaAndSettingsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
        $this->admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
    }

    private function base(): string
    {
        return rtrim((string) config('app.url'), '/');
    }

    public function test_uploaded_og_image_resolves_through_the_media_layer(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin)->post(route('admin.media.store'), [
            'file' => UploadedFile::fake()->image('social.png', 1200, 630),
            'alt_text' => 'Social card',
        ]);

        $media = Media::query()->sole();

        Setting::query()->updateOrCreate(['key' => 'seo.og_image'], ['value' => $media->path]);

        $html = $this->get(route('home'))->assertOk()->getContent();

        // Resolved by the media service — never string-concatenated.
        $this->assertStringContainsString(Storage::disk('public')->url($media->path), $html);
    }

    public function test_uploaded_og_image_dimensions_come_from_the_media_record(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin)->post(route('admin.media.store'), [
            'file' => UploadedFile::fake()->image('social.png', 1200, 630),
        ]);

        $media = Media::query()->sole();

        Setting::query()->updateOrCreate(['key' => 'seo.og_image'], ['value' => $media->path]);

        $html = $this->get(route('home'))->assertOk()->getContent();

        $this->assertStringContainsString('<meta property="og:image:width" content="1200">', $html);
        $this->assertStringContainsString('<meta property="og:image:height" content="630">', $html);
    }

    public function test_static_fallback_image_remains_functional(): void
    {
        // Seeded content uses static template asset paths; those must keep
        // resolving without any /storage/ assumption.
        $html = $this->get(route('home'))->assertOk()->getContent();

        $this->assertStringContainsString(asset('assets/images/my-avatar.png'), $html);
        $this->assertStringNotContainsString('/storage//', $html);
    }

    public function test_og_image_falls_back_to_the_profile_avatar(): void
    {
        Setting::query()->where('key', 'seo.og_image')->delete();

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('property="og:image"', escape: false)
            ->assertSee(asset('assets/images/my-avatar.png'), escape: false);
    }

    public function test_no_hardcoded_storage_concatenation_in_seo_code(): void
    {
        // Guards the Phase 5 rule: SEO code must never build storage paths.
        $files = array_merge(
            glob(app_path('Support/Seo/*.php')) ?: [],
            [app_path('Http/Controllers/SeoController.php')],
        );

        foreach ($files as $file) {
            $source = file_get_contents($file);

            $this->assertStringNotContainsString("'storage/'", $source, basename($file).' concatenates a storage path.');
            $this->assertStringNotContainsString('"/storage/"', $source, basename($file).' concatenates a storage path.');
        }
    }

    public function test_admin_can_see_and_save_seo_settings(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.settings.edit'))
            ->assertOk()
            ->assertSee('seo.default_description', escape: false)
            ->assertSee('seo.site_url', escape: false);

        $this->actingAs($this->admin)->put(route('admin.settings.update'), [
            'settings' => [
                'site.name' => 'Artupski Portfolio',
                'contact.map_embed_url' => 'https://maps.example.com/embed',
                'seo.default_description' => 'Studio copy.',
                'seo.site_url' => 'https://artupski.com',
                'seo.robots' => 'index, follow',
                'seo.og_image' => '',
            ],
        ])->assertRedirect(route('admin.settings.edit'))->assertSessionHas('success');

        $this->assertSame('https://artupski.com', Setting::get('seo.site_url'));
        $this->assertSame('Studio copy.', Setting::get('seo.default_description'));

        // The saved values take effect on the public pages immediately.
        $this->get(route('blog'))
            ->assertOk()
            ->assertSee('<link rel="canonical" href="https://artupski.com/blog">', escape: false);
    }

    public function test_clearing_a_seo_setting_restores_the_fallback(): void
    {
        Setting::query()->updateOrCreate(['key' => 'seo.site_url'], ['value' => 'https://artupski.com']);

        $this->actingAs($this->admin)->put(route('admin.settings.update'), [
            'settings' => [
                'site.name' => 'Artupski Portfolio',
                'contact.map_embed_url' => 'https://maps.example.com/embed',
                'seo.site_url' => '',
            ],
        ])->assertSessionHasNoErrors();

        $this->assertSame('', (string) Setting::get('seo.site_url'));

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('<link rel="canonical" href="'.$this->base().'/">', escape: false);
    }

    public function test_invalid_seo_site_url_is_rejected(): void
    {
        $this->actingAs($this->admin)->put(route('admin.settings.update'), [
            'settings' => [
                'site.name' => 'Artupski Portfolio',
                'contact.map_embed_url' => 'https://maps.example.com/embed',
                'seo.site_url' => 'not-a-url',
            ],
        ])->assertSessionHasErrors();

        $this->assertNull(Setting::get('seo.site_url'));
    }

    public function test_seo_settings_are_editable_and_declared_once(): void
    {
        // The admin form, the validator and the reader share one declaration.
        $this->assertSame(
            ['seo.default_description', 'seo.og_image', 'seo.site_url', 'seo.robots'],
            array_keys(SeoDefaults::EDITABLE),
        );
    }

    public function test_guest_cannot_edit_seo_settings(): void
    {
        $this->get(route('admin.settings.edit'))->assertRedirect(route('login'));
        $this->put(route('admin.settings.update'), ['settings' => []])->assertRedirect(route('login'));
    }

    public function test_seo_generation_does_not_duplicate_queries_per_tag(): void
    {
        // The repository memoises, so rendering a page must not issue a query
        // per meta tag. A generous bound catches an accidental N+1.
        DB::enableQueryLog();

        $this->get(route('home'))->assertOk();

        $count = count(DB::getQueryLog());

        DB::disableQueryLog();

        $this->assertLessThan(15, $count, "Home page issued {$count} queries; SEO must not add N+1.");
    }
}
