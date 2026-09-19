<?php

namespace Tests\Feature\Performance;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Query-count regression guard for the public pages.
 *
 * The ceilings are set from the measured, optimized implementation plus
 * headroom — they exist to catch an N+1 being reintroduced (a single missing
 * eager load multiplies the count by the number of rows), not to pin the
 * exact number of statements, which would make the suite brittle for no
 * benefit.
 */
class QueryCountTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        // Warm the caches so the measurement reflects a real page view rather
        // than the one-off cost of populating them.
        foreach (['/', '/resume', '/portfolio', '/blog', '/contact'] as $uri) {
            $this->get($uri);
        }
    }

    /**
     * @return array<string, array{string, int}>
     */
    public static function pages(): array
    {
        return [
            'home' => ['/', 6],
            'resume' => ['/resume', 6],
            'portfolio' => ['/portfolio', 6],
            'blog' => ['/blog', 6],
            'contact' => ['/contact', 6],
        ];
    }

    /**
     * Every public page must render from the cache with only the session
     * bookkeeping queries left.
     */
    #[DataProvider('pages')]
    public function test_public_page_stays_within_its_query_budget(string $uri, int $ceiling): void
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->get($uri)->assertOk();

        $count = count(DB::getQueryLog());

        DB::disableQueryLog();

        $this->assertLessThanOrEqual(
            $ceiling,
            $count,
            "{$uri} issued {$count} queries; the budget is {$ceiling}. This usually means a cache miss or a new N+1.",
        );
    }

    public function test_blog_detail_stays_within_its_query_budget(): void
    {
        $slug = BlogPost::query()->published()->value('slug');

        $this->get('/blog/'.$slug)->assertOk();

        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->get('/blog/'.$slug)->assertOk();

        $count = count(DB::getQueryLog());

        DB::disableQueryLog();

        $this->assertLessThanOrEqual(6, $count, "Blog detail issued {$count} queries.");
    }

    public function test_blog_detail_does_not_reload_the_post_for_related_content(): void
    {
        $slug = BlogPost::query()->published()->value('slug');

        // Warm.
        $this->get('/blog/'.$slug)->assertOk();

        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->get('/blog/'.$slug)->assertOk();

        $statements = array_column(DB::getQueryLog(), 'query');

        DB::disableQueryLog();

        // The post and its category are each resolved once, not once per
        // consumer (detail metadata, related posts, JSON-LD).
        $postSelects = count(array_filter(
            $statements,
            fn (string $sql) => str_contains($sql, 'from "blog_posts"'),
        ));

        $this->assertLessThanOrEqual(1, $postSelects, 'The blog post was queried more than once.');
    }

    public function test_settings_are_read_in_a_single_query_per_request(): void
    {
        // A page reads several settings (site name, map embed, SEO defaults).
        // They must come from one query, not one per key.
        Setting::flushCache();

        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->get('/contact')->assertOk();

        $statements = array_column(DB::getQueryLog(), 'query');

        DB::disableQueryLog();

        $settingQueries = count(array_filter(
            $statements,
            fn (string $sql) => str_contains($sql, 'from "settings"'),
        ));

        $this->assertLessThanOrEqual(1, $settingQueries, "Settings were queried {$settingQueries} times.");
    }

    public function test_cached_pages_do_not_grow_with_content_volume(): void
    {
        // Add a batch of posts; the cached listing must not start issuing a
        // query per row.
        $category = BlogCategory::query()->firstOrFail();

        foreach (range(1, 12) as $i) {
            BlogPost::query()->create([
                'blog_category_id' => $category->id,
                'title' => "Bulk post {$i}",
                'excerpt' => "Excerpt {$i}.",
                'content' => "Body {$i}.",
                'image_path' => 'assets/images/blog-1.jpg',
                'image_alt' => 'alt',
                'published_at' => '2022-02-23',
                'display_date' => 'Fab 23, 2022',
                'sort_order' => 100 + $i,
            ]);
        }

        $this->get('/blog')->assertOk();

        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->get('/blog')->assertOk();

        $count = count(DB::getQueryLog());

        DB::disableQueryLog();

        $this->assertLessThanOrEqual(6, $count, "Blog listing issued {$count} queries with 18 posts.");
    }
}
