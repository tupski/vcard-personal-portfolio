<?php

namespace Tests\Feature\Seo;

use App\Models\Profile;
use App\Models\SocialLink;
use App\Support\Seo\SeoManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * JSON-LD structured data: valid JSON, real content, safe escaping.
 */
class StructuredDataTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    /**
     * Decode every ld+json block in a rendered document.
     *
     * @return list<array<string, mixed>>
     */
    private function graphs(string $route): array
    {
        $html = $this->get(route($route))->assertOk()->getContent();

        preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $html, $matches);

        $this->assertNotEmpty($matches[1], "No JSON-LD found on {$route}.");

        return array_map(function (string $json): array {
            $decoded = json_decode($json, true);

            $this->assertIsArray($decoded, 'JSON-LD must decode as valid JSON.');

            return $decoded;
        }, $matches[1]);
    }

    /**
     * @param  list<array<string, mixed>>  $graphs
     * @return array<string, mixed>|null
     */
    private function findType(array $graphs, string $type): ?array
    {
        foreach ($graphs as $graph) {
            if (($graph['@type'] ?? null) === $type) {
                return $graph;
            }
        }

        return null;
    }

    public function test_home_page_emits_website_and_person_graphs(): void
    {
        $graphs = $this->graphs('home');

        $this->assertNotNull($this->findType($graphs, 'WebSite'));
        $this->assertNotNull($this->findType($graphs, 'Person'));
    }

    public function test_website_graph_describes_the_real_site(): void
    {
        $website = $this->findType($this->graphs('home'), 'WebSite');

        $this->assertSame('Artupski Portfolio', $website['name']);
        $this->assertSame(config('app.url').'/', $website['url']);
    }

    public function test_person_graph_uses_actual_profile_data(): void
    {
        $profile = Profile::query()->firstOrFail();

        $person = $this->findType($this->graphs('home'), 'Person');

        $this->assertSame($profile->name, $person['name']);
        $this->assertSame($profile->title, $person['jobTitle']);
        $this->assertSame($profile->email, $person['email']);
        $this->assertSame($profile->phone, $person['telephone']);
        $this->assertSame($profile->location, $person['address']);
    }

    public function test_person_same_as_is_absent_for_placeholder_urls(): void
    {
        // Seeded social links are placeholders ('#'), so `sameAs` must be
        // absent rather than listing a meaningless anchor.
        SocialLink::query()->update(['url' => '#']);

        $person = $this->findType($this->graphs('home'), 'Person');

        $this->assertArrayNotHasKey('sameAs', $person);
    }

    public function test_person_same_as_contains_only_real_urls(): void
    {
        SocialLink::query()->update(['url' => '#']);
        SocialLink::query()->where('label', 'Facebook')->update(['url' => 'https://facebook.com/example']);

        $person = $this->findType($this->graphs('home'), 'Person');

        $this->assertSame(['https://facebook.com/example'], $person['sameAs']);
    }

    public function test_breadcrumbs_are_emitted_for_sub_pages_only(): void
    {
        $this->assertNull($this->findType($this->graphs('home'), 'BreadcrumbList'));

        $crumbs = $this->findType($this->graphs('resume'), 'BreadcrumbList');

        $this->assertNotNull($crumbs);
        $this->assertCount(2, $crumbs['itemListElement']);
        $this->assertSame('Resume', $crumbs['itemListElement'][1]['name']);
        $this->assertSame(config('app.url').'/resume', $crumbs['itemListElement'][1]['item']);
    }

    public function test_blog_posting_is_not_emitted_on_listing_or_static_pages(): void
    {
        // BlogPosting describes an individual article, so it belongs on the
        // detail page only — never on the listing or the static pages.
        foreach (SeoManager::pageNames() as $name) {
            $this->assertNull(
                $this->findType($this->graphs($name), 'BlogPosting'),
                "BlogPosting must not appear on {$name}.",
            );
        }
    }

    public function test_json_ld_escaping_survives_hostile_content(): void
    {
        // Quotes, apostrophes, ampersands, angle brackets and Unicode in
        // database content must never break out of the script element.
        $hostile = '"><script>alert(1)</script> & \'quote\' "double" — ünïcødé </script>';

        Profile::query()->update(['name' => $hostile, 'title' => $hostile]);

        $html = $this->get(route('home'))->assertOk()->getContent();

        // The raw payload must not appear unescaped inside the document.
        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);

        // And every ld+json block must still be valid JSON with the real value.
        preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $html, $matches);

        foreach ($matches[1] as $json) {
            $this->assertIsArray(json_decode($json, true), 'Escaped JSON-LD must remain valid JSON.');
        }

        $person = $this->findType($this->graphs('home'), 'Person');
        $this->assertSame($hostile, $person['name']);
    }

    public function test_ampersands_and_unicode_round_trip(): void
    {
        $value = 'Design & Development — “quoted” 日本語';

        Profile::query()->update(['name' => $value]);

        $person = $this->findType($this->graphs('home'), 'Person');

        $this->assertSame($value, $person['name']);
    }

    public function test_json_ld_renders_inside_the_body_for_turbo(): void
    {
        // Turbo swaps <body> on navigation; the graph must live there so it
        // always matches the page currently in the DOM.
        $html = $this->get(route('blog'))->assertOk()->getContent();

        $bodyStart = strpos($html, '<body');

        $this->assertGreaterThan(0, $bodyStart);
        $this->assertGreaterThan($bodyStart, strpos($html, 'application/ld+json'));
    }

    public function test_no_profile_degrades_gracefully(): void
    {
        Profile::query()->delete();

        // The public page itself is a Phase 3 concern (the sidebar requires a
        // profile row). What Phase 6 owns is the SEO layer: it must still
        // produce a usable snapshot, with the site graph and no person graph.
        $seo = app(SeoManager::class)->forPage('home');

        $this->assertNotSame('', $seo->title);
        $this->assertNotNull($this->findType($seo->schemas, 'WebSite'));
        $this->assertNull($this->findType($seo->schemas, 'Person'));
    }
}
