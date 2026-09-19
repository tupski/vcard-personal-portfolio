<?php

namespace Tests\Feature\Seo;

use App\Models\BlogPost;
use App\Models\Setting;
use App\Support\Seo\SeoManager;
use App\Support\Seo\SitemapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * /sitemap.xml — indexable public URLs only, deterministic and valid XML.
 */
class SitemapTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    private function base(): string
    {
        return rtrim((string) config('app.url'), '/');
    }

    public function test_sitemap_endpoint_returns_valid_xml(): void
    {
        $response = $this->get('/sitemap.xml')->assertOk();

        $this->assertStringContainsString('application/xml', $response->headers->get('Content-Type'));

        $xml = simplexml_load_string($response->getContent());

        $this->assertNotFalse($xml, 'Sitemap must be well-formed XML.');
        $this->assertSame('urlset', $xml->getName());
    }

    public function test_sitemap_contains_every_public_route(): void
    {
        $body = $this->get('/sitemap.xml')->assertOk()->getContent();

        foreach (SeoManager::pageNames() as $name) {
            $this->assertStringContainsString(
                '<loc>'.app(SeoManager::class)->canonical($name).'</loc>',
                $body,
            );
        }
    }

    public function test_sitemap_uses_the_configured_site_url(): void
    {
        Setting::query()->updateOrCreate(['key' => 'seo.site_url'], ['value' => 'https://artupski.com']);

        $body = $this->get('/sitemap.xml')->assertOk()->getContent();

        $this->assertStringContainsString('<loc>https://artupski.com/blog</loc>', $body);
        $this->assertStringNotContainsString('localhost', $body);
    }

    public function test_sitemap_excludes_non_indexable_urls(): void
    {
        $body = $this->get('/sitemap.xml')->assertOk()->getContent();

        foreach (['/admin', '/login', '/logout', '/up', '?', '&amp;'] as $needle) {
            $this->assertStringNotContainsString('<loc>'.rtrim($this->base(), '/').$needle, $body);
        }
    }

    public function test_sitemap_has_no_duplicate_urls(): void
    {
        preg_match_all('#<loc>([^<]+)</loc>#', $this->get('/sitemap.xml')->getContent(), $matches);

        $locs = $matches[1];

        $this->assertNotEmpty($locs);
        $this->assertSame(array_values(array_unique($locs)), $locs, 'Sitemap URLs must be unique.');
    }

    public function test_sitemap_ordering_is_deterministic(): void
    {
        $first = $this->get('/sitemap.xml')->getContent();
        $second = $this->get('/sitemap.xml')->getContent();

        $this->assertSame($first, $second, 'Repeated requests must produce identical XML.');

        preg_match_all('#<loc>([^<]+)</loc>#', $first, $matches);

        $sorted = $matches[1];
        sort($sorted, SORT_STRING);

        $this->assertSame($sorted, $matches[1], 'URLs must be in a stable, sorted order.');
    }

    public function test_sitemap_has_no_fabricated_lastmod(): void
    {
        // No page has a meaningful per-URL modification time yet, so lastmod
        // must be absent rather than invented from "now".
        $body = $this->get('/sitemap.xml')->assertOk()->getContent();

        $this->assertStringNotContainsString('<lastmod>', $body);
    }

    public function test_urls_are_absolute(): void
    {
        foreach (app(SitemapService::class)->urls() as $entry) {
            $this->assertStringStartsWith('http', $entry['loc']);
            $this->assertStringStartsWith($this->base(), $entry['loc']);
        }
    }

    public function test_published_blog_detail_urls_are_included(): void
    {
        // Phase 8 publishes real detail pages, so their canonical URLs belong
        // in the sitemap — one per published post, no duplicates.
        $body = $this->get('/sitemap.xml')->assertOk()->getContent();

        $posts = BlogPost::query()->published()->get();

        $this->assertNotEmpty($posts);

        foreach ($posts as $post) {
            $this->assertStringContainsString(
                '<loc>'.$this->base().'/blog/'.$post->slug.'</loc>',
                $body,
            );
        }

        preg_match_all('#<loc>([^<]+)</loc>#', $body, $matches);
        $this->assertSame(array_values(array_unique($matches[1])), $matches[1]);
    }

    public function test_draft_blog_posts_are_excluded_from_the_sitemap(): void
    {
        $draft = BlogPost::query()->firstOrFail();
        $draft->update(['is_visible' => false, 'slug' => 'a-draft-post']);

        $body = $this->get('/sitemap.xml')->assertOk()->getContent();

        $this->assertStringNotContainsString('a-draft-post', $body);
    }

    public function test_extension_point_accepts_future_urls(): void
    {
        // The service is designed to grow: the URL list is the only seam.
        $service = app(SitemapService::class);

        $this->assertIsArray($service->urls());
        $this->assertCount(
            count(SeoManager::pageNames()) + BlogPost::query()->published()->count(),
            $service->urls(),
        );
    }

    public function test_lastmod_is_absent_until_a_post_is_genuinely_edited(): void
    {
        // A freshly seeded post must not claim it changed today.
        $this->assertStringNotContainsString('<lastmod>', $this->get('/sitemap.xml')->getContent());

        $post = BlogPost::query()->firstOrFail();
        $post->forceFill(['created_at' => now()->subDays(10)])->saveQuietly();
        $post->update(['title' => 'Edited after publication']);

        $body = $this->get('/sitemap.xml')->getContent();

        $this->assertStringContainsString('<lastmod>'.now()->toDateString().'</lastmod>', $body);
    }
}
