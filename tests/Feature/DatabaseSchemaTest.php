<?php

namespace Tests\Feature;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\ContactMessage;
use App\Models\Project;
use App\Models\ProjectCategory;
use App\Models\Setting;
use App\Models\SocialLink;
use App\Models\User;
use App\Support\PortfolioContent;
use App\Support\SeedContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DatabaseSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_seed_reproduces_the_phase_two_content(): void
    {
        $this->seed();

        $this->assertDatabaseCount('profiles', 1);
        $this->assertDatabaseCount('social_links', 3);
        $this->assertDatabaseCount('services', 4);
        $this->assertDatabaseCount('testimonials', 4);
        $this->assertDatabaseCount('clients', 6);
        $this->assertDatabaseCount('educations', 3);
        $this->assertDatabaseCount('experiences', 3);
        $this->assertDatabaseCount('skills', 4);
        $this->assertDatabaseCount('project_categories', 3);
        $this->assertDatabaseCount('projects', 9);
        $this->assertDatabaseCount('blog_categories', 1);
        $this->assertDatabaseCount('blog_posts', 6);
        $this->assertDatabaseCount('settings', 2);
    }

    public function test_repository_shapes_match_the_phase_two_contract(): void
    {
        $this->seed();

        $profile = PortfolioContent::profile();

        $this->assertSame('Richard hanrick', $profile['name']);
        $this->assertSame(
            SeedContent::profile()['phone_href'],
            $profile['phone_href'],
            'Rendered phone href must match the Phase 2 fixture byte-for-byte.',
        );
        $this->assertSame('June 23, 1982', $profile['birthday']);

        $this->assertCount(2, PortfolioContent::about());
        $this->assertSame(
            [80, 70, 90, 50],
            array_column(PortfolioContent::skills(), 'percent'),
        );

        // Display strings are stored verbatim so output is byte-identical
        // with Phase 2 (including the template's original typo).
        $this->assertSame('Fab 23, 2022', PortfolioContent::posts()[0]['date']);
        $this->assertSame('14 June, 2021', PortfolioContent::testimonials()[0]['date']);

        $this->assertCount(9, PortfolioContent::projects());
        $this->assertSame(
            ['All', 'Web design', 'Applications', 'Web development'],
            PortfolioContent::projectCategories(),
        );
        $this->assertSame('Web development', PortfolioContent::projects()[0]['category']);
        $this->assertCount(6, PortfolioContent::posts());
        $this->assertSame('Design', PortfolioContent::posts()[0]['category']);
    }

    public function test_projects_belong_to_categories_and_eager_load(): void
    {
        $this->seed();

        $project = Project::query()->with('category')->first();

        $this->assertNotNull($project->category);
        $this->assertSame(
            ProjectCategory::find($project->project_category_id)->name,
            $project->category->name,
        );

        $queries = 0;
        \DB::listen(function () use (&$queries): void {
            $queries++;
        });

        PortfolioContent::projects();
        PortfolioContent::posts();

        // One query for projects + one for categories, one for posts + one for
        // categories. No N+1 per row.
        $this->assertLessThanOrEqual(4, $queries);
    }

    public function test_slug_is_generated_and_unique(): void
    {
        $this->seed();

        // The seeded set already contains 'Web design' (slug: web-design), so
        // this row collides and receives a numeric suffix.
        $category = ProjectCategory::query()->create(['name' => 'Web Design']);

        $this->assertSame('web-design-2', $category->slug);

        $second = ProjectCategory::query()->create(['name' => 'Web Design']);

        $this->assertSame('web-design-3', $second->slug);

        // An unused base slug is taken verbatim.
        $fresh = ProjectCategory::query()->create(['name' => 'Photography']);

        $this->assertSame('photography', $fresh->slug);
    }

    public function test_hidden_content_is_excluded_from_the_frontend(): void
    {
        $this->seed();

        SocialLink::query()->firstOrFail()->update(['is_visible' => false]);

        $this->assertCount(2, PortfolioContent::socialLinks());
    }

    public function test_display_order_follows_sort_order(): void
    {
        $this->seed();

        $first = Project::query()->ordered()->first();
        $first->update(['sort_order' => 999]);

        $titles = array_column(PortfolioContent::projects(), 'title');

        $this->assertNotSame($first->title, $titles[0]);
        $this->assertSame($first->title, end($titles));
    }

    public function test_settings_fall_back_and_cache_flushes(): void
    {
        $this->seed();

        $this->assertSame('Artupski Portfolio', Setting::get('site.name'));
        $this->assertNull(Setting::get('not.a.key'));
        $this->assertSame('fallback', Setting::get('not.a.key', 'fallback'));

        Setting::query()->where('key', 'site.name')->firstOrFail()->update(['value' => 'Changed']);

        // The Eloquent update path flushes the per-request memo.
        $this->assertSame('Changed', Setting::get('site.name'));
    }

    public function test_blog_categories_own_posts(): void
    {
        $this->seed();

        $category = BlogCategory::query()->with('posts')->firstOrFail();

        $this->assertCount(6, $category->posts);
        $this->assertTrue($category->posts->every(
            fn (BlogPost $post) => $post->blog_category_id === $category->id,
        ));
    }

    public function test_contact_message_round_trips(): void
    {
        $this->seed();

        ContactMessage::query()->create([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'message' => 'Hello from the contact form.',
        ]);

        $this->assertDatabaseHas('contact_messages', [
            'email' => 'jane@example.com',
            'is_read' => false,
        ]);
    }

    public function test_seeded_admin_account_authenticates(): void
    {
        $this->seed();

        $this->assertDatabaseHas('users', ['email' => 'admin@example.com']);

        $this->post(route('login'), [
            'email' => 'admin@example.com',
            'password' => 'password',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticated();
    }

    public function test_hash_cast_of_seeded_password_round_trips(): void
    {
        $this->seed();

        $user = User::query()->where('email', 'admin@example.com')->firstOrFail();

        $this->assertTrue(Hash::check('password', $user->password));
    }
}
