<?php

namespace Tests\Feature\Performance;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\Profile;
use App\Models\Project;
use App\Models\Service;
use App\Models\Setting;
use App\Models\User;
use App\Support\ContentCache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Cache behaviour: what is cached, that an admin edit is visible immediately,
 * and that nothing private or stale can leak.
 */
class ContentCacheTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    private function admin(): User
    {
        return User::query()->where('email', 'admin@example.com')->firstOrFail();
    }

    // ---------------------------------------------------------------------
    // It caches
    // ---------------------------------------------------------------------

    public function test_a_second_request_is_served_without_rebuilding_content(): void
    {
        $this->get('/')->assertOk();

        $before = app(ContentCache::class);

        // Count the work the repository does on a warm request by watching the
        // cache itself: a hit means no query was issued for that value.
        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->get('/')->assertOk();

        $statements = array_column(DB::getQueryLog(), 'query');

        DB::disableQueryLog();

        // None of the content tables are queried on a warm request.
        foreach (['services', 'testimonials', 'clients', 'social_links'] as $table) {
            $this->assertEmpty(
                array_filter($statements, fn (string $sql) => str_contains($sql, 'from "'.$table.'"')),
                "Table [{$table}] was queried on a warm request.",
            );
        }
    }

    public function test_cache_is_populated_on_the_first_request(): void
    {
        $cache = app(ContentCache::class);

        $probe = null;
        $cache->remember('probe-key', function () use (&$probe) {
            $probe = 'computed';

            return 'value';
        });

        $this->assertSame('computed', $probe);

        $second = null;
        $value = $cache->remember('probe-key', function () use (&$second) {
            $second = 'computed-again';

            return 'other';
        });

        $this->assertSame('value', $value);
        $this->assertNull($second, 'The cached value should have been reused.');
    }

    // ---------------------------------------------------------------------
    // It invalidates
    // ---------------------------------------------------------------------

    public function test_editing_a_service_is_visible_on_the_next_request(): void
    {
        $this->get('/')->assertOk();

        Service::query()->firstOrFail()->update(['title' => 'A freshly renamed service']);

        $this->get('/')
            ->assertOk()
            ->assertSee('A freshly renamed service', escape: false);
    }

    public function test_hiding_a_service_removes_it_immediately(): void
    {
        $service = Service::query()->firstOrFail();

        $this->get('/')->assertOk()->assertSee($service->title, escape: false);

        $service->update(['is_visible' => false]);

        $this->get('/')->assertOk()->assertDontSee($service->title, escape: false);
    }

    public function test_deleting_content_is_visible_immediately(): void
    {
        $project = Project::query()->firstOrFail();

        $this->get('/portfolio')->assertOk()->assertSee($project->title, escape: false);

        $project->delete();

        $this->get('/portfolio')->assertOk()->assertDontSee($project->title, escape: false);
    }

    public function test_editing_a_blog_post_is_visible_on_listing_and_detail(): void
    {
        $post = BlogPost::query()->published()->firstOrFail();

        $this->get('/blog')->assertOk();
        $this->get('/blog/'.$post->slug)->assertOk();

        $post->update(['title' => 'An updated headline', 'content' => 'An updated body.']);

        $this->get('/blog')->assertOk()->assertSee('An updated headline', escape: false);

        $this->get('/blog/'.$post->slug)
            ->assertOk()
            ->assertSee('An updated headline', escape: false)
            ->assertSee('An updated body.', escape: false);
    }

    public function test_unpublishing_a_post_removes_it_from_listing_and_detail(): void
    {
        $post = BlogPost::query()->published()->firstOrFail();

        $this->get('/blog')->assertOk()->assertSee($post->title, escape: false);

        $post->update(['is_visible' => false]);

        $this->get('/blog')->assertOk()->assertDontSee($post->title, escape: false);
        $this->get('/blog/'.$post->slug)->assertNotFound();
    }

    public function test_renaming_a_category_is_visible_on_every_post(): void
    {
        $category = BlogCategory::query()->firstOrFail();

        $this->get('/blog')->assertOk();

        $category->update(['name' => 'Renamed Category']);

        $this->get('/blog')->assertOk()->assertSee('Renamed Category', escape: false);
    }

    public function test_settings_change_is_visible_immediately(): void
    {
        $this->get('/')->assertOk();

        Setting::query()->updateOrCreate(['key' => 'site.name'], ['value' => 'Instant Rename']);

        $this->get('/')
            ->assertOk()
            ->assertSee('<title>About - Instant Rename</title>', escape: false);
    }

    public function test_editing_the_profile_is_visible_immediately(): void
    {
        $this->get('/')->assertOk();

        Profile::query()->firstOrFail()->update(['title' => 'Principal Designer']);

        $this->get('/')->assertOk()->assertSee('Principal Designer', escape: false);
    }

    public function test_an_admin_edit_through_the_http_interface_is_visible(): void
    {
        $service = Service::query()->firstOrFail();

        $this->get('/')->assertOk();

        $this->actingAs($this->admin())->put(route('admin.services.update', $service), [
            'title' => 'Edited through the admin',
            'description' => $service->description,
            'icon_path' => $service->icon_path,
            'icon_alt' => $service->icon_alt,
            'sort_order' => $service->sort_order,
            'is_visible' => '1',
        ])->assertRedirect(route('admin.services.index'));

        $this->get('/')->assertOk()->assertSee('Edited through the admin', escape: false);
    }

    public function test_creating_content_through_the_admin_is_visible(): void
    {
        $this->get('/')->assertOk();

        $this->actingAs($this->admin())->post(route('admin.services.store'), [
            'title' => 'A brand new service',
            'description' => 'Created by the test.',
            'icon_path' => 'assets/images/icon-dev.svg',
            'icon_alt' => 'icon',
            'sort_order' => 99,
            'is_visible' => '1',
        ])->assertRedirect(route('admin.services.index'));

        $this->get('/')->assertOk()->assertSee('A brand new service', escape: false);
    }

    // ---------------------------------------------------------------------
    // Nothing private leaks
    // ---------------------------------------------------------------------

    public function test_contact_messages_are_never_cached_publicly(): void
    {
        $this->post(route('contact.store'), [
            'name' => 'Cache Probe',
            'email' => 'cache-probe@example.com',
            'subject' => 'Must not be cached',
            'message' => 'This message body must never appear in a public page.',
        ])->assertRedirect(route('contact'));

        // The public pages must not contain the message, before or after the
        // cache is warm.
        foreach (['/', '/resume', '/portfolio', '/blog', '/contact'] as $uri) {
            $this->get($uri)->assertOk()->assertDontSee('This message body must never appear', escape: false);
        }

        // The inbox is reachable only when authenticated.
        $this->get(route('admin.contact-messages.index'))->assertRedirect(route('login'));
    }

    public function test_admin_pages_are_never_served_from_the_public_cache(): void
    {
        // Warm the public cache as a guest.
        $this->get('/')->assertOk();

        $this->actingAs($this->admin())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Dashboard', escape: false);
    }

    public function test_disabling_the_cache_still_renders_every_page(): void
    {
        config(['content.cache' => false]);

        foreach (['/', '/resume', '/portfolio', '/blog', '/contact'] as $uri) {
            $this->get($uri)->assertOk();
        }

        // And an edit is still immediately visible without any cache involved.
        Service::query()->firstOrFail()->update(['title' => 'No cache involved']);
        $this->get('/')->assertOk()->assertSee('No cache involved', escape: false);
    }

    public function test_cache_uses_the_configured_store_and_requires_no_redis(): void
    {
        // The deployment target is shared hosting, so the default store must
        // be something a plain PHP host can provide.
        $this->assertNotSame('redis', config('cache.default'));
    }
}
