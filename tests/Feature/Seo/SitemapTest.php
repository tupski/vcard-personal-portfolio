<?php

namespace Tests\Feature\Seo;

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

    public function test_blog_detail_urls_are_not_invented(): void
    {
        // Phase 8 owns blog detail routes; nothing should be guessed here.
        foreach (app(SitemapService::class)->urls() as $entry) {
            $this->assertStringNotContainsString('/blog/', $entry['loc']);
        }
    }

    public function test_extension_point_accepts_future_urls(): void
    {
        // The service is designed to grow: the URL list is the only seam.
        $service = app(SitemapService::class);

        $this->assertIsArray($service->urls());
        $this->assertCount(count(SeoManager::pageNames()), $service->urls());
    }
}
