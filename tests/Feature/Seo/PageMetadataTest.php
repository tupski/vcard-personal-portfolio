<?php

namespace Tests\Feature\Seo;

use App\Models\Setting;
use App\Models\User;
use App\Support\Seo\SeoManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Page metadata: titles, descriptions, canonicals, robots, Open Graph and
 * Twitter cards for every public route.
 */
class PageMetadataTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    /**
     * Every public route with its Phase 2 copy.
     *
     * @return array<string, array{string, string, string}>
     */
    public static function pages(): array
    {
        return [
            'home' => ['home', 'About', 'About Richard Hanrick'],
            'resume' => ['resume', 'Resume', 'Education, experience and skills'],
            'portfolio' => ['portfolio', 'Portfolio', 'Selected projects by Richard Hanrick'],
            'blog' => ['blog', 'Blog', 'Articles on design, typography'],
            'contact' => ['contact', 'Contact', 'Get in touch with Richard Hanrick'],
        ];
    }

    #[DataProvider('pages')]
    public function test_each_page_has_deterministic_metadata(string $route, string $title, string $description): void
    {
        $response = $this->get(route($route))->assertOk();

        $response->assertSee("<title>{$title} - Artupski Portfolio</title>", escape: false);
        $response->assertSee('<meta name="description" content="'.$description, escape: false);
        $response->assertSee('<meta property="og:title" content="'.$title.'">', escape: false);
        $response->assertSee('<meta name="twitter:card"', escape: false);
    }

    #[DataProvider('pages')]
    public function test_canonical_is_absolute_and_matches_the_page(string $route, string $title): void
    {
        $expected = $route === 'home'
            ? $this->base().'/'
            : $this->base().'/'.$route;

        $this->get(route($route))
            ->assertOk()
            ->assertSee('<link rel="canonical" href="'.$expected.'">', escape: false);
    }

    public function test_canonical_never_uses_the_request_host(): void
    {
        // A preview/staging host must never leak into a production canonical.
        $html = $this->get('http://evil.example.com/resume')->assertOk()->getContent();

        $canonical = $this->canonicalOf($html);

        $this->assertSame($this->base().'/resume', $canonical);
        $this->assertStringNotContainsString('evil.example.com', $canonical);
    }

    /**
     * The canonical href from a rendered document, or '' when absent.
     */
    private function canonicalOf(string $html): string
    {
        preg_match('#<link rel="canonical" href="([^"]*)">#', $html, $matches);

        return $matches[1] ?? '';
    }

    public function test_configured_site_url_overrides_the_canonical_base(): void
    {
        Setting::query()->updateOrCreate(['key' => 'seo.site_url'], ['value' => 'https://artupski.com/']);

        $this->get(route('blog'))
            ->assertOk()
            ->assertSee('<link rel="canonical" href="https://artupski.com/blog">', escape: false);
    }

    public function test_trailing_slash_on_the_configured_url_is_normalised(): void
    {
        Setting::query()->updateOrCreate(['key' => 'seo.site_url'], ['value' => 'https://artupski.com///']);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('<link rel="canonical" href="https://artupski.com/">', escape: false);
    }

    public function test_query_strings_do_not_pollute_the_canonical(): void
    {
        $this->get(route('blog').'?page=2&utm_source=newsletter')
            ->assertOk()
            ->assertSee('<link rel="canonical" href="'.$this->base().'/blog">', escape: false)
            ->assertDontSee('utm_source', escape: false);
    }

    public function test_open_graph_tags_are_complete(): void
    {
        $response = $this->get(route('portfolio'))->assertOk();

        foreach (['og:type', 'og:title', 'og:description', 'og:url', 'og:site_name', 'og:image'] as $property) {
            $response->assertSee('property="'.$property.'"', escape: false);
        }

        $response->assertSee('<meta property="og:type" content="website">', escape: false);
        $response->assertSee('<meta property="og:site_name" content="Artupski Portfolio">', escape: false);
        $response->assertSee('<meta property="og:url" content="'.$this->base().'/portfolio">', escape: false);
    }

    public function test_twitter_card_metadata_is_complete(): void
    {
        $response = $this->get(route('contact'))->assertOk();

        $response->assertSee('<meta name="twitter:card" content="summary_large_image">', escape: false);
        $response->assertSee('<meta name="twitter:title" content="Contact">', escape: false);
        $response->assertSee('name="twitter:description"', escape: false);
        $response->assertSee('name="twitter:image"', escape: false);
    }

    public function test_public_pages_are_indexable_in_production(): void
    {
        $this->app['env'] = 'production';

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('<meta name="robots" content="index, follow">', escape: false);
    }

    public function test_non_production_pages_are_noindex(): void
    {
        $this->assertNotSame('production', app()->environment());

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('<meta name="robots" content="noindex, nofollow">', escape: false);
    }

    public function test_robots_setting_overrides_the_environment_default(): void
    {
        Setting::query()->updateOrCreate(['key' => 'seo.robots'], ['value' => 'noindex, follow']);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('<meta name="robots" content="noindex, follow">', escape: false);
    }

    public function test_admin_pages_are_always_noindex_and_nofollow(): void
    {
        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('<meta name="robots" content="noindex, nofollow">', escape: false);
    }

    public function test_admin_urls_never_become_public_canonicals(): void
    {
        foreach (SeoManager::pageNames() as $name) {
            $this->assertStringNotContainsString('/admin', app(SeoManager::class)->canonical($name));
        }
    }

    public function test_site_name_setting_flows_into_the_title(): void
    {
        Setting::query()->updateOrCreate(['key' => 'site.name'], ['value' => 'Renamed Studio']);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('<title>About - Renamed Studio</title>', escape: false)
            ->assertSee('<meta property="og:site_name" content="Renamed Studio">', escape: false);
    }

    public function test_page_title_falls_back_to_the_site_name(): void
    {
        // A route the manager does not know about still yields a usable title.
        $seo = app(SeoManager::class)->forPage('does-not-exist');

        $this->assertSame('Artupski Portfolio', $seo->title);
        $this->assertNotNull($seo->canonical);
    }

    public function test_description_falls_back_to_the_configured_default(): void
    {
        Setting::query()->updateOrCreate(['key' => 'seo.default_description'], ['value' => 'Configured fallback copy.']);

        // Unknown page => no page copy => configured default.
        $this->assertSame('Configured fallback copy.', app(SeoManager::class)->forPage('unknown')->description);
    }

    public function test_description_falls_back_to_real_profile_content(): void
    {
        // No SEO rows at all: the profile's about text is used, not invented copy.
        $seo = app(SeoManager::class)->forPage('unknown');

        $this->assertNotNull($seo->description);
        $this->assertStringContainsString('Creative Director', $seo->description);
    }

    public function test_missing_optional_settings_do_not_break_the_head(): void
    {
        Setting::query()->where('key', 'like', 'seo.%')->delete();

        $this->get(route('resume'))
            ->assertOk()
            ->assertSee('<link rel="canonical" href="'.$this->base().'/resume">', escape: false)
            ->assertSee('name="description"', escape: false);
    }

    public function test_metadata_is_built_from_the_route_not_the_view(): void
    {
        // Two different routes must never share metadata.
        $home = $this->get(route('home'))->getContent();
        $blog = $this->get(route('blog'))->getContent();

        $this->assertNotSame(
            $this->titleOf($home),
            $this->titleOf($blog),
        );
    }

    /**
     * Canonical base the app is configured with — never a hardcoded host, so
     * the assertion tests the contract rather than the developer's .env.
     */
    private function base(): string
    {
        return rtrim((string) config('app.url'), '/');
    }

    private function titleOf(string $html): string
    {
        preg_match('#<title>(.*?)</title>#s', $html, $matches);

        return $matches[1] ?? '';
    }
}
