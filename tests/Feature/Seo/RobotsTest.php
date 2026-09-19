<?php

namespace Tests\Feature\Seo;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * /robots.txt — deterministic rules, environment-aware, sitemap reference.
 */
class RobotsTest extends TestCase
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

    public function test_robots_endpoint_is_plain_text(): void
    {
        $response = $this->get('/robots.txt')->assertOk();

        $this->assertStringContainsString('text/plain', $response->headers->get('Content-Type'));
        $this->assertStringStartsWith('User-agent: *', $response->getContent());
    }

    public function test_robots_references_the_sitemap_from_the_configured_url(): void
    {
        $body = $this->get('/robots.txt')->assertOk()->getContent();

        $this->assertStringContainsString('Sitemap: '.$this->base().'/sitemap.xml', $body);
    }

    public function test_production_disallows_admin_and_login(): void
    {
        $this->app['env'] = 'production';

        $body = $this->get('/robots.txt')->assertOk()->getContent();

        $this->assertStringContainsString('Disallow: /admin', $body);
        $this->assertStringContainsString('Disallow: /login', $body);
        $this->assertStringNotContainsString("Disallow: /\n", $body);
    }

    public function test_non_production_blocks_indexing_entirely(): void
    {
        $this->assertNotSame('production', app()->environment());

        $body = $this->get('/robots.txt')->assertOk()->getContent();

        $this->assertStringContainsString('Disallow: /', $body);
    }

    public function test_sitemap_url_follows_a_configured_site_url(): void
    {
        Setting::query()->updateOrCreate(['key' => 'seo.site_url'], ['value' => 'https://artupski.com']);

        $this->get('/robots.txt')
            ->assertOk()
            ->assertSee('Sitemap: https://artupski.com/sitemap.xml', escape: false);
    }

    public function test_static_file_does_not_shadow_the_route(): void
    {
        // A committed public/robots.txt would win over the route and freeze the
        // rules; the generated document must be what is actually served.
        $this->assertFileDoesNotExist(public_path('robots.txt'));

        $this->get('/robots.txt')->assertOk()->assertSee('Sitemap:', escape: false);
    }

    public function test_admin_route_is_still_protected_by_auth_not_robots(): void
    {
        // robots.txt is advisory; the security boundary is the middleware.
        $this->get('/admin')->assertRedirect(route('login'));
    }
}
