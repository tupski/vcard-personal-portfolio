<?php

namespace Tests\Feature\Blog;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Public blog: the listing, the detail route, and the boundary that keeps
 * drafts off the public site.
 */
class BlogDetailTest extends TestCase
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

    // ---------------------------------------------------------------------
    // Listing
    // ---------------------------------------------------------------------

    public function test_listing_renders_published_posts(): void
    {
        $this->get(route('blog'))
            ->assertOk()
            ->assertSee('Design conferences in 2022', escape: false);
    }

    public function test_listing_links_each_post_to_its_detail_route(): void
    {
        $response = $this->get(route('blog'))->assertOk();

        foreach (BlogPost::query()->published()->get() as $post) {
            $response->assertSee('href="/blog/'.$post->slug.'"', escape: false);
        }

        // The Phase 2 markup shipped href="#" for every card; none may remain.
        $response->assertDontSee('<a href="#">', escape: false);
    }

    public function test_listing_excludes_draft_posts(): void
    {
        $draft = $this->published();
        $draft->update(['is_visible' => false, 'title' => 'Hidden Draft Title']);

        $this->get(route('blog'))
            ->assertOk()
            ->assertDontSee('Hidden Draft Title', escape: false);
    }

    public function test_listing_preserves_the_phase_two_card_contract(): void
    {
        $response = $this->get(route('blog'))->assertOk();

        $html = $response->getContent();

        foreach (['blog-posts-list', 'blog-post-item', 'blog-banner-box', 'blog-content', 'blog-meta', 'blog-item-title', 'blog-text'] as $class) {
            // The template composes some of these with utility classes
            // (e.g. class="h3 blog-item-title"), so match the token.
            $this->assertMatchesRegularExpression(
                '/class="[^"]*\b'.preg_quote($class, '/').'\b/',
                $html,
                "Missing class token [{$class}].",
            );
        }

        // The template's original display string (typo included) is untouched.
        $response->assertSee('Fab 23, 2022', escape: false);
    }

    // ---------------------------------------------------------------------
    // Detail route
    // ---------------------------------------------------------------------

    public function test_published_post_renders_at_its_slug(): void
    {
        $post = $this->published();

        $this->get('/blog/'.$post->slug)
            ->assertOk()
            ->assertSee($post->title, escape: false)
            ->assertSee($post->body(), escape: false);
    }

    public function test_detail_page_shows_category_date_and_featured_image(): void
    {
        $post = $this->published();

        $this->get('/blog/'.$post->slug)
            ->assertOk()
            ->assertSee($post->category->name, escape: false)
            ->assertSee($post->display_date, escape: false)
            ->assertSee($post->image_path, escape: false)
            ->assertSee('alt="'.$post->image_alt.'"', escape: false);
    }

    public function test_detail_page_offers_a_way_back_to_the_listing(): void
    {
        $this->get('/blog/'.$this->published()->slug)
            ->assertOk()
            ->assertSee('href="'.route('blog').'"', escape: false)
            ->assertSee(__('Back to all posts'), escape: false);
    }

    public function test_unknown_slug_returns_404(): void
    {
        $this->get('/blog/no-such-post')->assertNotFound();
    }

    public function test_unpublished_post_returns_404(): void
    {
        $post = $this->published();
        $post->update(['is_visible' => false]);

        $this->get('/blog/'.$post->slug)->assertNotFound();
    }

    public function test_missing_and_unpublished_responses_are_indistinguishable(): void
    {
        $post = $this->published();
        $post->update(['is_visible' => false]);

        $draft = $this->get('/blog/'.$post->slug);
        $missing = $this->get('/blog/definitely-not-a-post');

        // Same status and no body content that would reveal the draft exists.
        $this->assertSame($missing->getStatusCode(), $draft->getStatusCode());
        $this->assertSame(404, $draft->getStatusCode());
        $this->assertStringNotContainsString($post->title, $draft->getContent());
        $this->assertStringNotContainsString($post->body(), $draft->getContent());
    }

    public function test_numeric_id_is_not_a_public_identifier(): void
    {
        $post = $this->published();

        // The route pattern rejects a bare id, so it cannot resolve a post.
        $this->get('/blog/'.$post->getKey())->assertNotFound();
    }

    public function test_route_is_get_only(): void
    {
        $post = $this->published();

        $this->post('/blog/'.$post->slug)->assertMethodNotAllowed();
        $this->delete('/blog/'.$post->slug)->assertMethodNotAllowed();
    }

    // ---------------------------------------------------------------------
    // Slug behaviour
    // ---------------------------------------------------------------------

    public function test_slugs_are_unique_and_url_safe(): void
    {
        foreach (BlogPost::query()->get() as $post) {
            $this->assertMatchesRegularExpression('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $post->slug);
        }

        $slugs = BlogPost::query()->pluck('slug');
        $this->assertSame($slugs->unique()->count(), $slugs->count());
    }

    public function test_a_colliding_slug_gets_a_suffix_instead_of_failing(): void
    {
        $existing = $this->published();

        $clone = BlogPost::query()->create([
            'blog_category_id' => $existing->blog_category_id,
            'title' => $existing->title,
            'excerpt' => 'Another post with the same title.',
            'image_path' => $existing->image_path,
            'image_alt' => $existing->image_alt,
            'published_at' => $existing->published_at,
            'display_date' => $existing->display_date,
            'sort_order' => 99,
        ]);

        $this->assertNotSame($existing->slug, $clone->slug);
        $this->assertSame($existing->slug.'-2', $clone->slug);
    }

    public function test_an_explicit_slug_is_never_overwritten(): void
    {
        $post = $this->published();
        $original = $post->slug;

        $post->update(['title' => 'A completely different title']);

        // The published URL is stable: editing the title does not silently
        // move an already-published post.
        $this->assertSame($original, $post->fresh()->slug);
        $this->get('/blog/'.$original)->assertOk();
    }

    public function test_an_edited_slug_moves_the_post_and_404s_the_old_url(): void
    {
        $post = $this->published();
        $old = $post->slug;

        $post->update(['slug' => 'a-new-canonical-slug']);

        $this->get('/blog/a-new-canonical-slug')->assertOk();
        $this->get('/blog/'.$old)->assertNotFound();
    }

    // ---------------------------------------------------------------------
    // Related posts
    // ---------------------------------------------------------------------

    public function test_related_posts_come_from_the_same_category_only(): void
    {
        $post = $this->published();

        $other = BlogCategory::query()->create(['name' => 'Unrelated', 'slug' => 'unrelated']);

        BlogPost::query()->create([
            'blog_category_id' => $other->id,
            'title' => 'A post in another category',
            'excerpt' => 'Excerpt.',
            'image_path' => 'assets/images/blog-1.jpg',
            'image_alt' => 'alt',
            'published_at' => '2022-02-23',
            'display_date' => 'Fab 23, 2022',
            'sort_order' => 50,
        ]);

        $this->get('/blog/'.$post->slug)
            ->assertOk()
            ->assertDontSee('A post in another category', escape: false);
    }

    public function test_related_posts_exclude_the_current_post_and_drafts(): void
    {
        $post = $this->published();

        $draft = BlogPost::query()->published()->whereKeyNot($post->getKey())->firstOrFail();
        $draft->update(['is_visible' => false, 'title' => 'A draft that must not be related']);

        $response = $this->get('/blog/'.$post->slug)->assertOk();

        $response->assertDontSee('A draft that must not be related', escape: false);

        // The related list never links back to the current post.
        $content = $response->getContent();
        $related = substr($content, (int) strpos($content, 'blog-related'));

        $this->assertStringNotContainsString('href="/blog/'.$post->slug.'"', $related);
    }

    // ---------------------------------------------------------------------
    // Admin authorization (the existing boundary)
    // ---------------------------------------------------------------------

    public function test_guest_cannot_manage_blog_posts(): void
    {
        $post = $this->published();

        $this->get(route('admin.blog-posts.index'))->assertRedirect(route('login'));
        $this->get(route('admin.blog-posts.create'))->assertRedirect(route('login'));
        $this->get(route('admin.blog-posts.edit', $post))->assertRedirect(route('login'));
        $this->put(route('admin.blog-posts.update', $post), [])->assertRedirect(route('login'));
        $this->delete(route('admin.blog-posts.destroy', $post))->assertRedirect(route('login'));
    }

    public function test_admin_can_publish_and_unpublish_a_post(): void
    {
        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $post = $this->published();

        $payload = [
            'blog_category_id' => $post->blog_category_id,
            'title' => $post->title,
            'slug' => $post->slug,
            'excerpt' => $post->excerpt,
            'content' => 'A brand new body for the post.',
            'image_path' => $post->image_path,
            'image_alt' => $post->image_alt,
            'published_at' => $post->published_at->format('Y-m-d'),
            'display_date' => $post->display_date,
            'sort_order' => $post->sort_order,
        ];

        // Unpublish: the detail page disappears from the public site.
        $this->actingAs($admin)
            ->put(route('admin.blog-posts.update', $post), $payload)
            ->assertRedirect(route('admin.blog-posts.index'));

        $this->assertFalse($post->fresh()->is_visible);
        $this->get('/blog/'.$post->slug)->assertNotFound();

        // Republish.
        $this->actingAs($admin)
            ->put(route('admin.blog-posts.update', $post), $payload + ['is_visible' => '1'])
            ->assertRedirect(route('admin.blog-posts.index'));

        $this->assertTrue($post->fresh()->is_visible);
        $this->get('/blog/'.$post->slug)->assertOk();
    }

    public function test_admin_can_delete_a_post(): void
    {
        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $post = $this->published();

        $this->actingAs($admin)
            ->delete(route('admin.blog-posts.destroy', $post))
            ->assertRedirect(route('admin.blog-posts.index'));

        $this->assertDatabaseMissing('blog_posts', ['id' => $post->getKey()]);
        $this->get('/blog/'.$post->slug)->assertNotFound();
    }

    public function test_admin_cannot_create_a_post_with_a_duplicate_slug(): void
    {
        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $post = $this->published();

        $this->actingAs($admin)->post(route('admin.blog-posts.store'), [
            'blog_category_id' => $post->blog_category_id,
            'title' => 'Duplicate slug attempt',
            'slug' => $post->slug,
            'excerpt' => 'Excerpt.',
            'content' => 'Body.',
            'image_path' => 'assets/images/blog-1.jpg',
            'image_alt' => 'alt',
            'published_at' => '2022-02-23',
            'display_date' => 'Fab 23, 2022',
            'sort_order' => 1,
        ])->assertSessionHasErrors('slug');
    }
}
