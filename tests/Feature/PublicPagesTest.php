<?php

namespace Tests\Feature;

use Database\Seeders\ContentSeeder;
use Database\Seeders\ProfileSeeder;
use Database\Seeders\PublicationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ProfileSeeder::class);
        $this->seed(ContentSeeder::class);
        $this->seed(PublicationSeeder::class);
    }

    /**
     * Every public route from PLAN.md, with a phrase that proves the right
     * template rendered.
     *
     * @return array<string, array{string, string}>
     */
    public static function pages(): array
    {
        return [
            'home' => ['home', 'About me'],
            'resume' => ['resume', 'University school of the arts'],
            'portfolio' => ['portfolio', 'Brawlhalla'],
            'blog' => ['blog', 'Design conferences in 2022'],
            'contact' => ['contact', 'Contact Form'],
        ];
    }

    #[DataProvider('pages')]
    public function test_public_pages_render(string $route, string $expected): void
    {
        $this->get(route($route))
            ->assertOk()
            ->assertSee($expected);
    }

    public function test_public_routes_are_named(): void
    {
        $routes = app('router')->getRoutes();

        foreach (array_keys(self::pages()) as $name) {
            $this->assertNotNull(
                $routes->getByName($name),
                "Missing named route [{$name}]"
            );
        }
    }

    public function test_navigation_links_to_every_public_route(): void
    {
        $response = $this->get(route('home'));

        foreach (array_keys(self::pages()) as $name) {
            $response->assertSee(route($name), escape: false);
        }
    }

    public function test_active_navigation_state_marks_the_current_page(): void
    {
        $this->get(route('resume'))
            ->assertOk()
            ->assertSee('aria-current="page"', escape: false);
    }

    public function test_layout_is_shared_across_pages(): void
    {
        // The sidebar and navbar must come from the shared layout, not be
        // duplicated per page.
        foreach (array_keys(self::pages()) as $name) {
            $this->get(route($name))
                ->assertSee('class="sidebar"', escape: false)
                ->assertSee('class="navbar"', escape: false);
        }
    }

    public function test_every_original_icon_renders_as_inline_svg(): void
    {
        $response = $this->get(route('home'));

        // Contact + social + modal icons the About page needs, each resolved to
        // real inline SVG shapes rather than a CDN web component.
        $response->assertSee('data-icon="mail-outline"', escape: false);
        $response->assertSee('data-icon="chevron-down"', escape: false);
        $response->assertSee('data-icon="close-outline"', escape: false);
        $response->assertSee('<svg', escape: false);

        $this->assertStringNotContainsString('<ion-icon', $response->getContent());
    }
}
