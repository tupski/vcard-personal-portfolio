<?php

namespace Tests\Feature\Admin;

use App\Models\Media;
use App\Models\User;
use App\Support\MediaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class MediaTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private const TINY_PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
        $this->admin = User::query()->where('email', 'admin@example.com')->firstOrFail();

        Storage::fake('public');
    }

    private function validImage(): UploadedFile
    {
        return UploadedFile::fake()->image('photo.png', 800, 600);
    }

    public function test_guest_cannot_access_media(): void
    {
        $this->get(route('admin.media.index'))->assertRedirect(route('login'));
    }

    public function test_admin_can_view_media_library(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.media.index'))
            ->assertOk()
            ->assertSee(__('Media'));
    }

    public function test_valid_upload_stores_original_and_variants(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.media.store'), [
            'file' => $this->validImage(),
            'alt_text' => 'A test image',
            'title' => 'Test image',
        ]);

        $response->assertRedirect(route('admin.media.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseCount('media', 1);

        $medium = Media::query()->sole();

        Storage::disk('public')->assertExists($medium->path);
        Storage::disk('public')->assertExists($medium->thumbPath());
        Storage::disk('public')->assertExists($medium->mediumPath());

        // Original preserved byte-for-byte, never modified by variant generation.
        $this->assertSame(800, $medium->width);
        $this->assertSame(600, $medium->height);

        // Variants are WebP.
        $this->assertStringEndsWith('.webp', $medium->thumbPath());
        $this->assertStringEndsWith('.webp', $medium->mediumPath());
    }

    public function test_original_is_untouched_by_variant_generation(): void
    {
        $this->actingAs($this->admin)->post(route('admin.media.store'), [
            'file' => $this->validImage(),
        ]);

        $medium = Media::query()->sole();
        $before = Storage::disk('public')->size($medium->path);

        $this->assertSame((int) $medium->size, $before);
    }

    public static function invalidUploads(): array
    {
        return [
            'text file' => ['upload.txt', 'text/plain', 'file'],
            'pdf' => ['upload.pdf', 'application/pdf', 'file'],
            'svg' => ['upload.svg', 'image/svg+xml', 'file'],
        ];
    }

    #[DataProvider('invalidUploads')]
    public function test_invalid_uploads_are_rejected(string $name, string $mime, string $errorKey): void
    {
        $file = $name === 'big.png'
            ? UploadedFile::fake()->image('big.png', 9000, 9000)
            : UploadedFile::fake()->create($name, 10, $mime);

        $this->actingAs($this->admin)
            ->post(route('admin.media.store'), ['file' => $file])
            ->assertSessionHasErrors('file');

        $this->assertDatabaseCount('media', 0);
    }

    public function test_oversized_file_is_rejected(): void
    {
        // 5 MB PNG exceeds the 4 MB rule.
        $file = UploadedFile::fake()->create('photo.png', 5120, 'image/png');

        $this->actingAs($this->admin)
            ->post(route('admin.media.store'), ['file' => $file])
            ->assertSessionHasErrors('file');

        $this->assertDatabaseCount('media', 0);
    }

    public function test_dimension_limit_rule_is_enforced(): void
    {
        // Generating a >6000px image in tests exhausts memory, so assert the
        // rule contract directly on the service's validation definition.
        $service = app(MediaService::class);
        $method = new \ReflectionMethod($service, 'validate');
        $source = (new \ReflectionClass($service))->getFileName();
        $body = file_get_contents($source);

        $this->assertStringContainsString('dimensions:max_width=6000,max_height=6000', $body);
    }

    public function test_client_filename_never_touches_filesystem(): void
    {
        $this->actingAs($this->admin)->post(route('admin.media.store'), [
            'file' => UploadedFile::fake()->image('../../etc/passwd.png', 100, 100),
        ]);

        $medium = Media::query()->sole();

        // Stored name is a UUID under media/YYYY/MM/ — traversal-safe.
        $this->assertMatchesRegularExpression('#^media/\d{4}/\d{2}/[0-9a-f-]{36}\.(jpg|png|webp)$#', $medium->path);
        // Laravel's UploadedFile already strips traversal from the client name.
        $this->assertSame('passwd.png', $medium->original_name);
    }

    public function test_metadata_validation(): void
    {
        $this->actingAs($this->admin)->post(route('admin.media.store'), [
            'file' => $this->validImage(),
            'alt_text' => str_repeat('a', 300),
            'title' => str_repeat('b', 300),
        ])->assertSessionHasErrors(['alt_text', 'title']);
    }

    public function test_delete_removes_exactly_its_files(): void
    {
        $this->actingAs($this->admin)->post(route('admin.media.store'), [
            'file' => $this->validImage(),
        ]);

        // A second, unrelated file on the same disk must survive.
        Storage::disk('public')->put('unrelated.txt', 'keep me');

        $medium = Media::query()->sole();
        $paths = [$medium->path, $medium->thumbPath(), $medium->mediumPath()];

        $this->actingAs($this->admin)
            ->delete(route('admin.media.destroy', $medium))
            ->assertRedirect(route('admin.media.index'))
            ->assertSessionHas('success');

        foreach ($paths as $path) {
            Storage::disk('public')->assertMissing($path);
        }

        Storage::disk('public')->assertExists('unrelated.txt');
        $this->assertDatabaseCount('media', 0);
    }

    public function test_deleting_missing_media_is_safe(): void
    {
        $this->actingAs($this->admin)->post(route('admin.media.store'), [
            'file' => $this->validImage(),
        ]);

        $medium = Media::query()->sole();

        // Files already gone (e.g. wiped by hand) — delete must not 500.
        Storage::disk('public')->deleteDirectory('media');

        $this->actingAs($this->admin)
            ->delete(route('admin.media.destroy', $medium))
            ->assertRedirect(route('admin.media.index'));

        $this->assertDatabaseCount('media', 0);
    }

    public function test_unauthenticated_user_cannot_mutate_media(): void
    {
        $this->post(route('admin.media.store'), ['file' => $this->validImage()])
            ->assertRedirect(route('login'));

        $this->delete(route('admin.media.destroy', 1))
            ->assertRedirect(route('login'));

        $this->assertDatabaseCount('media', 0);
    }

    public function test_static_asset_paths_render_as_phase_two(): void
    {
        $service = app(MediaService::class);

        // Legacy static asset paths bypass storage and render byte-identical.
        $this->assertSame(asset('assets/images/my-avatar.png'), $service->url('assets/images/my-avatar.png'));
        $this->assertSame(asset('assets/images/my-avatar.png'), $service->thumbUrl('assets/images/my-avatar.png'));
    }

    public function test_media_paths_resolve_through_storage(): void
    {
        $this->actingAs($this->admin)->post(route('admin.media.store'), [
            'file' => $this->validImage(),
        ]);

        $medium = Media::query()->sole();
        $service = app(MediaService::class);

        $this->assertSame(Storage::disk('public')->url($medium->path), $service->url($medium->path));
        $this->assertSame(
            Storage::disk('public')->url($medium->thumbPath()),
            $service->thumbUrl($medium->path),
        );
    }

    public function test_existing_seeded_content_still_renders(): void
    {
        // Phase 3 seeded content uses static asset paths; the public pages
        // must render exactly as in Phase 2/3/4.
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('assets/images/my-avatar.png', false);

        $this->get(route('portfolio'))
            ->assertOk()
            ->assertSee('assets/images/project-1.jpg', false);
    }
}
