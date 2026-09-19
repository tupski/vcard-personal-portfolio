<?php

namespace Tests\Feature\Blog;

use App\Models\BlogPost;
use App\Models\Media;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Blog detail: content safety, SEO metadata, BlogPosting JSON-LD and the
 * media integration — the parts where the detail page differs from a listing.
 */
class BlogDetailSeoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    private function published(): BlogPost
    {
        return BlogPost::query()->published()->ordered()->firstOrFail();
    }

    private function base(): string
    {
        return rtrim((string) config('app.url'), '/');
    }

    /**
     * Every ld+json graph on a URL, decoded.
     *
     * @return list<array<string, mixed>>
     */
    private function graphs(string $url): array
    {
        $html = $this->get($url)->assertOk()->getContent();

        preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $html, $matches);

        $this->assertNotEmpty($matches[1], "No JSON-LD on {$url}.");

        return array_map(function (string $json): array {
            $decoded = json_decode($json, true);

            $this->assertIsArray($decoded, 'JSON-LD must be valid JSON.');

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

    // ---------------------------------------------------------------------
    // Content rendering and security
    // ---------------------------------------------------------------------

    public function test_stored_markup_is_rendered_as_inert_text(): void
    {
        $post = $this->published();

        $post->update(['content' => '<script>alert(1)</script> and <img src=x onerror=alert(1)>']);

        $html = $this->get('/blog/'.$post->slug)->assertOk()->getContent();

        // No live element may be produced from stored content.
        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringNotContainsString('<img src=x onerror=alert(1)>', $html);

        // ...but the text itself is present, escaped.
        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $html);
        $this->assertStringContainsString('&lt;img src=x onerror=alert(1)&gt;', $html);
    }

    public function test_html_like_content_cannot_break_out_of_the_article(): void
    {
        $post = $this->published();

        $hostile = '</div></article></main><script>alert("xss")</script><div>';

        $post->update(['content' => $hostile]);

        $html = $this->get('/blog/'.$post->slug)->assertOk()->getContent();

        $this->assertStringNotContainsString('<script>alert("xss")</script>', $html);
        $this->assertStringContainsString('&lt;/div&gt;', $html);
    }

    public function test_quotes_ampersands_and_unicode_round_trip(): void
    {
        $post = $this->published();

        $body = "Quotes: \"double\" and 'single' & ampersands — plus 日本語 and emoji 🎨";

        $post->update(['content' => $body]);

        $html = $this->get('/blog/'.$post->slug)->assertOk()->getContent();

        $this->assertStringContainsString('&amp; ampersands', $html);
        $this->assertStringContainsString('日本語', $html);
        $this->assertStringContainsString('🎨', $html);
    }

    public function test_line_breaks_are_preserved_without_parsing_html(): void
    {
        $post = $this->published();

        $post->update(['content' => "First paragraph.\n\nSecond paragraph."]);

        $html = $this->get('/blog/'.$post->slug)->assertOk()->getContent();

        // The body keeps its newlines and is styled with pre-line, so no
        // <br> or <p> is fabricated from user text.
        $this->assertMatchesRegularExpression(
            '#<div class="blog-post-body">First paragraph\.\s+Second paragraph\.</div>#',
            $html,
        );
    }

    public function test_a_post_without_content_falls_back_to_its_excerpt(): void
    {
        $post = $this->published();
        $post->update(['content' => null]);

        $this->get('/blog/'.$post->slug)
            ->assertOk()
            ->assertSee($post->excerpt, escape: false);
    }

    public function test_the_body_is_rendered_escaped_never_raw(): void
    {
        // The body is plain text by contract, so it must go through Blade's
        // escaping output rather than a raw echo.
        $view = file_get_contents(resource_path('views/pages/blog-post.blade.php'));

        $this->assertStringContainsString('{{ $post[\'body\'] }}', $view);

        // No raw echo is applied to the body specifically. (The file's doc
        // comment mentions the syntax in prose, so match the echo form.)
        $this->assertDoesNotMatchRegularExpression('/\{!!\s*\$post\[\'body\'\]\s*!!\}/', $view);
    }

    // ---------------------------------------------------------------------
    // SEO metadata
    // ---------------------------------------------------------------------

    public function test_detail_page_has_unique_metadata_from_the_post(): void
    {
        $post = $this->published();

        $this->get('/blog/'.$post->slug)
            ->assertOk()
            ->assertSee('<title>'.$post->title.' - Artupski Portfolio</title>', escape: false)
            ->assertSee('<meta property="og:title" content="'.$post->title.'">', escape: false)
            ->assertSee('<meta name="description" content="'.$post->excerpt.'">', escape: false);
    }

    public function test_detail_page_uses_the_article_og_type(): void
    {
        $this->get('/blog/'.$this->published()->slug)
            ->assertOk()
            ->assertSee('<meta property="og:type" content="article">', escape: false);
    }

    public function test_detail_page_has_open_graph_and_twitter_metadata(): void
    {
        $response = $this->get('/blog/'.$this->published()->slug)->assertOk();

        foreach (['og:type', 'og:title', 'og:description', 'og:url', 'og:site_name', 'og:image'] as $property) {
            $response->assertSee('property="'.$property.'"', escape: false);
        }

        $response->assertSee('<meta name="twitter:card" content="summary_large_image">', escape: false);
        $response->assertSee('name="twitter:title"', escape: false);
        $response->assertSee('name="twitter:image"', escape: false);
    }

    public function test_canonical_is_the_configured_url_for_the_slug(): void
    {
        $post = $this->published();

        $this->get('/blog/'.$post->slug)
            ->assertOk()
            ->assertSee('<link rel="canonical" href="'.$this->base().'/blog/'.$post->slug.'">', escape: false);
    }

    public function test_hostile_request_host_cannot_alter_the_canonical(): void
    {
        $post = $this->published();

        $html = $this->get('http://evil.example.com/blog/'.$post->slug)->assertOk()->getContent();

        preg_match('#<link rel="canonical" href="([^"]*)">#', $html, $m);

        $this->assertSame($this->base().'/blog/'.$post->slug, $m[1] ?? '');
        $this->assertStringNotContainsString('evil.example.com', $m[1] ?? '');
    }

    public function test_query_strings_do_not_pollute_the_canonical(): void
    {
        $post = $this->published();

        $this->get('/blog/'.$post->slug.'?utm_source=newsletter')
            ->assertOk()
            ->assertSee('<link rel="canonical" href="'.$this->base().'/blog/'.$post->slug.'">', escape: false)
            ->assertDontSee('utm_source', escape: false);
    }

    public function test_robots_follows_the_configured_policy(): void
    {
        $this->app['env'] = 'production';

        $this->get('/blog/'.$this->published()->slug)
            ->assertOk()
            ->assertSee('<meta name="robots" content="index, follow">', escape: false);
    }

    // ---------------------------------------------------------------------
    // BlogPosting JSON-LD
    // ---------------------------------------------------------------------

    public function test_blog_posting_graph_uses_real_post_data(): void
    {
        $post = $this->published();
        $profile = Profile::query()->firstOrFail();

        $graph = $this->findType($this->graphs('/blog/'.$post->slug), 'BlogPosting');

        $this->assertNotNull($graph);
        $this->assertSame($post->title, $graph['headline']);
        $this->assertSame($post->excerpt, $graph['description']);
        $this->assertSame($post->published_at->toDateString(), $graph['datePublished']);
        $this->assertSame($this->base().'/blog/'.$post->slug, $graph['url']);
        $this->assertSame($this->base().'/blog/'.$post->slug, $graph['mainEntityOfPage']['@id']);
        $this->assertSame($post->category->name, $graph['articleSection']);

        // Author is the real profile, not an invented name.
        $this->assertSame($profile->name, $graph['author']['name']);
        $this->assertSame('Person', $graph['author']['@type']);
    }

    public function test_blog_posting_omits_date_modified_when_never_edited(): void
    {
        // A freshly seeded row has updated_at === created_at, so claiming it
        // was modified would be fabrication.
        $graph = $this->findType($this->graphs('/blog/'.$this->published()->slug), 'BlogPosting');

        $this->assertArrayNotHasKey('dateModified', $graph);
    }

    public function test_blog_posting_includes_date_modified_after_a_real_edit(): void
    {
        $post = $this->published();

        $post->forceFill(['created_at' => now()->subDays(10)])->saveQuietly();
        $post->update(['title' => 'An edited headline']);

        $graph = $this->findType($this->graphs('/blog/'.$post->slug), 'BlogPosting');

        $this->assertSame(now()->toDateString(), $graph['dateModified']);
    }

    public function test_blog_posting_has_no_fabricated_dates(): void
    {
        $graph = $this->findType($this->graphs('/blog/'.$this->published()->slug), 'BlogPosting');

        foreach (['datePublished', 'dateModified'] as $key) {
            if (isset($graph[$key])) {
                $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $graph[$key]);
            }
        }
    }

    public function test_detail_page_emits_a_three_level_breadcrumb(): void
    {
        $post = $this->published();

        $crumbs = $this->findType($this->graphs('/blog/'.$post->slug), 'BreadcrumbList');

        $this->assertNotNull($crumbs);
        $this->assertCount(3, $crumbs['itemListElement']);
        $this->assertSame('Blog', $crumbs['itemListElement'][1]['name']);
        $this->assertSame($this->base().'/blog', $crumbs['itemListElement'][1]['item']);
        $this->assertSame($post->title, $crumbs['itemListElement'][2]['name']);
        $this->assertSame($this->base().'/blog/'.$post->slug, $crumbs['itemListElement'][2]['item']);
    }

    public function test_json_ld_survives_hostile_post_content(): void
    {
        $post = $this->published();

        $hostile = '"><script>alert(1)</script> & \'quote\' "double" — 日本語 </script>';

        $post->update(['title' => $hostile, 'excerpt' => $hostile]);

        $html = $this->get('/blog/'.$post->slug)->assertOk()->getContent();

        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);

        foreach ($this->graphs('/blog/'.$post->slug) as $graph) {
            $this->assertIsArray($graph);
        }

        $graph = $this->findType($this->graphs('/blog/'.$post->slug), 'BlogPosting');

        $this->assertSame($hostile, $graph['headline']);
    }

    public function test_no_blog_posting_graph_for_a_draft(): void
    {
        $post = $this->published();
        $post->update(['is_visible' => false]);

        $response = $this->get('/blog/'.$post->slug)->assertNotFound();

        $this->assertStringNotContainsString('BlogPosting', $response->getContent());
    }

    // ---------------------------------------------------------------------
    // Media integration
    // ---------------------------------------------------------------------

    public function test_uploaded_featured_image_resolves_through_the_media_layer(): void
    {
        Storage::fake('public');

        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();

        $this->actingAs($admin)->post(route('admin.media.store'), [
            'file' => UploadedFile::fake()->image('featured.png', 1200, 675),
        ]);

        $media = Media::query()->sole();

        $post = $this->published();
        $post->update(['image_path' => $media->path]);

        $html = $this->get('/blog/'.$post->slug)->assertOk()->getContent();

        $this->assertStringContainsString(Storage::disk('public')->url($media->path), $html);
    }

    public function test_static_asset_image_paths_still_render(): void
    {
        // Seeded posts use static template assets; those must keep working
        // without any /storage/ assumption.
        $post = $this->published();

        $this->get('/blog/'.$post->slug)
            ->assertOk()
            ->assertSee(asset($post->image_path), escape: false)
            ->assertDontSee('/storage//', escape: false);
    }

    public function test_blog_code_never_concatenates_a_storage_path(): void
    {
        $files = [
            resource_path('views/pages/blog-post.blade.php'),
            resource_path('views/components/portfolio/blog-posts.blade.php'),
            app_path('Support/ContentRepository.php'),
            app_path('Support/Seo/SeoManager.php'),
            app_path('Support/Seo/SitemapService.php'),
        ];

        foreach ($files as $file) {
            $source = file_get_contents($file);

            $this->assertStringNotContainsString("'storage/'", $source, basename($file).' concatenates a storage path.');
            $this->assertStringNotContainsString('"/storage/"', $source, basename($file).' concatenates a storage path.');
        }
    }

    // ---------------------------------------------------------------------
    // Performance
    // ---------------------------------------------------------------------

    public function test_detail_page_uses_a_small_predictable_number_of_queries(): void
    {
        $post = $this->published();

        // Warm any first-request bootstrapping so the count reflects the page.
        $this->get('/blog/'.$post->slug);

        DB::enableQueryLog();
        $this->get('/blog/'.$post->slug)->assertOk();
        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        // Post + category, related posts + their categories, profile for the
        // author/person graph, settings, social links, and the view composer.
        // A generous ceiling that still catches an N+1 across related posts.
        $this->assertLessThan(14, $count, "Blog detail issued {$count} queries.");
    }

    public function test_repository_is_the_only_eloquent_consumer_for_the_detail_page(): void
    {
        // The view must not query Eloquent directly; it reads the repository
        // shape through the facade.
        $view = file_get_contents(resource_path('views/pages/blog-post.blade.php'));

        $this->assertStringNotContainsString('BlogPost::', $view);
        $this->assertStringNotContainsString('->query()', $view);
        $this->assertStringNotContainsString('PortfolioContent::post(', $view);
    }
}
