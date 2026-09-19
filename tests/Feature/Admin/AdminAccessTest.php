<?php

namespace Tests\Feature\Admin;

use App\Models\ContactMessage;
use App\Models\ProjectCategory;
use App\Models\Service;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public static function adminPages(): array
    {
        return [
            ['/admin'],
            ['/admin/profile'],
            ['/admin/settings'],
            ['/admin/services'],
            ['/admin/experience'],
            ['/admin/education'],
            ['/admin/skills'],
            ['/admin/clients'],
            ['/admin/testimonials'],
            ['/admin/projects'],
            ['/admin/project-categories'],
            ['/admin/blog-posts'],
            ['/admin/blog-categories'],
            ['/admin/social-links'],
            ['/admin/contact-messages'],
        ];
    }

    #[DataProvider('adminPages')]
    public function test_guests_cannot_access_admin(string $url): void
    {
        $this->get($url)->assertRedirect(route('login'));
    }

    #[DataProvider('adminPages')]
    public function test_authenticated_users_can_access_admin(string $url): void
    {
        $this->actingAs(User::query()->where('email', 'admin@example.com')->firstOrFail())
            ->get($url)
            ->assertOk();
    }

    public function test_dashboard_shows_real_counts(): void
    {
        $this->actingAs(User::query()->where('email', 'admin@example.com')->firstOrFail())
            ->get('/admin')
            ->assertOk()
            ->assertSee('9', false)
            ->assertSee(__('Projects'));
    }

    public function test_service_crud_round_trip(): void
    {
        $user = User::query()->where('email', 'admin@example.com')->firstOrFail();

        // create
        $this->actingAs($user)->post('/admin/services', [
            'title' => 'Admin QA service',
            'icon_path' => 'assets/images/icon-dev.svg',
            'icon_alt' => 'qa icon',
            'description' => 'Created by the feature test.',
            'sort_order' => 99,
        ])->assertRedirect('/admin/services');

        $this->assertDatabaseHas('services', ['title' => 'Admin QA service']);

        // update
        $service = Service::query()->where('title', 'Admin QA service')->firstOrFail();
        $this->actingAs($user)->put("/admin/services/{$service->getKey()}", [
            'title' => 'Admin QA service updated',
            'icon_path' => $service->icon_path,
            'icon_alt' => $service->icon_alt,
            'description' => $service->description,
            'sort_order' => $service->sort_order,
            'is_visible' => '1',
        ])->assertRedirect('/admin/services');

        $this->assertDatabaseHas('services', ['title' => 'Admin QA service updated']);

        // delete
        $this->actingAs($user)->delete("/admin/services/{$service->getKey()}")
            ->assertRedirect('/admin/services');

        $this->assertDatabaseMissing('services', ['title' => 'Admin QA service updated']);
    }

    public function test_service_validation_rejects_bad_input(): void
    {
        $user = User::query()->where('email', 'admin@example.com')->firstOrFail();

        $this->actingAs($user)->post('/admin/services', [
            'title' => '',
            'sort_order' => -1,
        ])->assertSessionHasErrors(['title', 'icon_path', 'icon_alt', 'description', 'sort_order']);
    }

    public function test_skill_percent_must_be_between_1_and_100(): void
    {
        $user = User::query()->where('email', 'admin@example.com')->firstOrFail();

        $this->actingAs($user)->post('/admin/skills', [
            'title' => 'Zero skill',
            'percent' => 0,
            'sort_order' => 0,
        ])->assertSessionHasErrors('percent');

        $this->actingAs($user)->post('/admin/skills', [
            'title' => 'Over skill',
            'percent' => 101,
            'sort_order' => 0,
        ])->assertSessionHasErrors('percent');
    }

    public function test_project_category_with_projects_cannot_be_deleted(): void
    {
        $user = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $category = ProjectCategory::query()->has('projects')->firstOrFail();

        $this->actingAs($user)
            ->delete("/admin/project-categories/{$category->getKey()}")
            ->assertRedirect('/admin/project-categories')
            ->assertSessionHas('error');

        $this->assertDatabaseHas('project_categories', ['id' => $category->getKey()]);
    }

    public function test_profile_update_changes_public_sidebar(): void
    {
        $user = User::query()->where('email', 'admin@example.com')->firstOrFail();

        $this->actingAs($user)->put('/admin/profile', [
            'name' => 'Updated Person',
            'title' => 'Senior Developer',
            'avatar_path' => 'assets/images/my-avatar.png',
            'email' => 'updated@example.com',
            'phone' => '+1 (999) 555-0000',
            'phone_href' => 'tel:+19995550000',
            'birthday' => '1990-01-01',
            'location' => 'Test City',
            'about' => "First paragraph.\n\nSecond paragraph.",
        ])->assertRedirect('/admin/profile');

        // Public sidebar renders through ContentRepository, not the model.
        $this->get('/')
            ->assertOk()
            ->assertSee('Updated Person')
            ->assertSee('Senior Developer')
            ->assertSee('Test City');
    }

    public function test_settings_update_changes_public_title(): void
    {
        $user = User::query()->where('email', 'admin@example.com')->firstOrFail();

        $this->actingAs($user)->put('/admin/settings', [
            'settings' => [
                'site.name' => 'Renamed Site',
                'contact.map_embed_url' => 'https://maps.example.com/embed/test',
            ],
        ])->assertRedirect('/admin/settings');

        $this->assertSame('Renamed Site', Setting::get('site.name'));

        $this->get('/')
            ->assertOk()
            ->assertSee('<title>About - Renamed Site</title>', false);
    }

    public function test_settings_ignores_undeclared_keys(): void
    {
        $user = User::query()->where('email', 'admin@example.com')->firstOrFail();

        $this->actingAs($user)->put('/admin/settings', [
            'settings' => [
                'site.name' => 'Still Fine',
                'contact.map_embed_url' => 'https://maps.example.com/embed/unchanged',
                'evil.key' => 'should-not-persist',
            ],
        ])->assertRedirect('/admin/settings');

        $this->assertNull(Setting::get('evil.key'));
        $this->assertSame('Still Fine', Setting::get('site.name'));
    }

    public function test_contact_message_inbox_read_state(): void
    {
        $user = User::query()->where('email', 'admin@example.com')->firstOrFail();

        $message = ContactMessage::query()->create([
            'name' => 'Sender',
            'email' => 'sender@example.com',
            'message' => 'Hello from the test.',
        ]);

        $this->assertTrue($message->fresh()->is_read === false);

        // Viewing marks it read.
        $this->actingAs($user)
            ->get("/admin/contact-messages/{$message->getKey()}")
            ->assertOk();

        $this->assertTrue($message->fresh()->is_read);

        // Toggle back to unread.
        $this->actingAs($user)
            ->put("/admin/contact-messages/{$message->getKey()}", ['is_read' => '0'])
            ->assertRedirect('/admin/contact-messages');

        $this->assertFalse($message->fresh()->is_read);

        // Delete.
        $this->actingAs($user)
            ->delete("/admin/contact-messages/{$message->getKey()}")
            ->assertRedirect('/admin/contact-messages');

        $this->assertDatabaseMissing('contact_messages', ['id' => $message->getKey()]);
    }

    public function test_visibility_toggle_removes_item_from_public_listing(): void
    {
        $user = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $service = Service::query()->firstOrFail();

        $this->actingAs($user)->put("/admin/services/{$service->getKey()}", [
            'title' => $service->title,
            'icon_path' => $service->icon_path,
            'icon_alt' => $service->icon_alt,
            'description' => $service->description,
            'sort_order' => $service->sort_order,
            // checkbox absent = hidden
        ])->assertRedirect('/admin/services');

        $this->get('/')
            ->assertOk()
            ->assertDontSeeText($service->title);
    }
}
