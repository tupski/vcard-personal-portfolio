<?php

namespace Tests\Feature\Performance;

use App\Models\BlogPost;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Sitemap caching: it is cached, it invalidates, and the Phase 8 contract
 * (published only, deterministic, no fabricated dates) is unchanged.
 */
class SitemapCacheTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_repeated_requests_return_an_identical_document(): void
    {
        $first = $this->get('/sitemap.xml')->assertOk()->getContent();
        $second = $this->get('/sitemap.xml')->assertOk()->getContent();
        $third = $this->get('/sitemap.xml')->assertOk()->getContent();

        $this->assertSame($first, $second);
        $this->assertSame($second, $third);
    }

    public function test_publishing_a_post_appears_in_the_sitemap_immediately(): void
    {
        $draft = BlogPost::query()->firstOrFail();
        $draft->update(['is_visible' => false, 'slug' => 'a-newly-published-post']);

        $this->get('/sitemap.xml')->assertOk()->assertDontSee('a-newly-published-post', escape: false);

        $draft->update(['is_visible' => true]);

        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertSee('<loc>'.rtrim(config('app.url'), '/').'/blog/a-newly-published-post</loc>', escape: false);
    }

    public function test_unpublishing_a_post_removes_it_from_the_sitemap_immediately(): void
    {
        $post = BlogPost::query()->published()->firstOrFail();

        $this->get('/sitemap.xml')->assertOk()->assertSee($post->slug, escape: false);

        $post->update(['is_visible' => false]);

        $this->get('/sitemap.xml')->assertOk()->assertDontSee($post->slug, escape: false);
    }

    public function test_deleting_a_post_removes_it_from_the_sitemap(): void
    {
        $post = BlogPost::query()->published()->firstOrFail();
        $slug = $post->slug;

        $this->get('/sitemap.xml')->assertOk()->assertSee($slug, escape: false);

        $post->delete();

        $this->get('/sitemap.xml')->assertOk()->assertDontSee($slug, escape: false);
    }

    public function test_changing_the_site_url_rebuilds_the_sitemap(): void
    {
        $this->get('/sitemap.xml')->assertOk()->assertSee(rtrim(config('app.url'), '/').'/blog', escape: false);

        Setting::query()->updateOrCreate(['key' => 'seo.site_url'], ['value' => 'https://artupski.com']);

        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertSee('<loc>https://artupski.com/blog</loc>', escape: false);
    }

    public function test_cached_sitemap_keeps_the_phase_eight_contract(): void
    {
        // Warm the cache, then verify every guarantee still holds.
        $this->get('/sitemap.xml')->assertOk();

        $body = $this->get('/sitemap.xml')->assertOk()->getContent();

        preg_match_all('#<loc>([^<]+)</loc>#', $body, $matches);
        $locs = $matches[1];

        $this->assertNotEmpty($locs);
        $this->assertSame(array_values(array_unique($locs)), $locs, 'URLs must stay unique.');

        $sorted = $locs;
        sort($sorted, SORT_STRING);
        $this->assertSame($sorted, $locs, 'Ordering must stay deterministic.');

        foreach ($locs as $loc) {
            $this->assertStringStartsWith('http', $loc);
            $this->assertStringNotContainsString('/admin', $loc);
            $this->assertStringNotContainsString('/login', $loc);
            $this->assertStringNotContainsString('?', $loc);
        }

        // Still no fabricated timestamps.
        $this->assertStringNotContainsString('<lastmod>', $body);

        // And only published posts are listed.
        foreach (BlogPost::query()->where('is_visible', false)->pluck('slug') as $draftSlug) {
            $this->assertStringNotContainsString($draftSlug, $body);
        }
    }

    public function test_sitemap_is_served_from_cache_on_a_warm_request(): void
    {
        $this->get('/sitemap.xml')->assertOk();

        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->get('/sitemap.xml')->assertOk();

        $statements = array_column(DB::getQueryLog(), 'query');

        DB::disableQueryLog();

        // No content table is touched when the cached document is reused.
        $this->assertEmpty(
            array_filter($statements, fn (string $sql) => str_contains($sql, 'from "blog_posts"')),
            'The sitemap queried blog_posts on a warm request.',
        );
    }

    public function test_disabling_the_cache_still_serves_a_correct_sitemap(): void
    {
        config(['content.cache' => false]);

        $body = $this->get('/sitemap.xml')->assertOk()->getContent();

        $this->assertStringContainsString('<urlset', $body);
        $this->assertStringContainsString('/blog', $body);

        foreach (BlogPost::query()->published()->pluck('slug') as $slug) {
            $this->assertStringContainsString($slug, $body);
        }
    }
}
