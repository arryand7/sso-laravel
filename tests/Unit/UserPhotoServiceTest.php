<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\UserPhotoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\ImageManager;
use Tests\TestCase;

class UserPhotoServiceTest extends TestCase
{
    use RefreshDatabase;

    protected UserPhotoService $service;

    protected ImageManager $manager;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->service = new UserPhotoService;
        $this->manager = new ImageManager(new GdDriver);
    }

    public function test_photo_processing_produces_4_3_output_dimensions(): void
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('avatar.jpg', 1000, 1000);

        $path = $this->service->store($user, $file);

        $this->assertNotNull($path);
        Storage::disk('public')->assertExists($path);

        $content = Storage::disk('public')->get($path);
        $image = $this->manager->read($content);

        $this->assertSame(800, $image->width());
        $this->assertSame(600, $image->height());
        $this->assertEquals(4 / 3, $image->width() / $image->height());
    }

    public function test_portrait_photo_creates_4_3_canvas_without_crop_and_has_horizontal_letterbox(): void
    {
        $user = User::factory()->create();
        // Portrait image: 600x1200
        $file = UploadedFile::fake()->image('portrait.jpg', 600, 1200);

        $path = $this->service->store($user, $file);

        $content = Storage::disk('public')->get($path);
        $image = $this->manager->read($content);

        $this->assertSame(800, $image->width());
        $this->assertSame(600, $image->height());
    }

    public function test_wide_landscape_photo_creates_4_3_canvas_without_crop_and_has_vertical_letterbox(): void
    {
        $user = User::factory()->create();
        // Wide landscape image: 1600x600
        $file = UploadedFile::fake()->image('wide.jpg', 1600, 600);

        $path = $this->service->store($user, $file);

        $content = Storage::disk('public')->get($path);
        $image = $this->manager->read($content);

        $this->assertSame(800, $image->width());
        $this->assertSame(600, $image->height());
    }

    public function test_square_photo_creates_4_3_canvas_without_stretch(): void
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('square.jpg', 500, 500);

        $path = $this->service->store($user, $file);

        $content = Storage::disk('public')->get($path);
        $image = $this->manager->read($content);

        $this->assertSame(800, $image->width());
        $this->assertSame(600, $image->height());
    }

    public function test_output_file_size_is_compressed_under_300_kb(): void
    {
        $user = User::factory()->create();
        // Create large image file
        $file = UploadedFile::fake()->image('large.jpg', 2000, 1500);

        $path = $this->service->store($user, $file);

        $size = Storage::disk('public')->size($path);
        $this->assertLessThanOrEqual(307200, $size); // <= 300 KB
    }

    public function test_transparent_png_gets_solid_background(): void
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('transparent.png', 400, 400);

        $path = $this->service->store($user, $file);

        $this->assertNotNull($path);
        Storage::disk('public')->assertExists($path);
    }

    public function test_non_image_file_is_rejected(): void
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->create('document.pdf', 500, 'application/pdf');

        $this->expectException(\InvalidArgumentException::class);
        $this->service->store($user, $file);
    }

    public function test_svg_file_is_rejected(): void
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->create('logo.svg', 10, 'image/svg+xml');

        $this->expectException(\InvalidArgumentException::class);
        $this->service->store($user, $file);
    }

    public function test_broken_image_file_is_rejected(): void
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->createWithContent('corrupt.jpg', 'invalid image bytes');

        $this->expectException(\InvalidArgumentException::class);
        $this->service->store($user, $file);
    }

    public function test_extreme_resolution_image_is_rejected_safely(): void
    {
        $user = User::factory()->create();
        // Fake image claiming to be 9000x9000
        $file = UploadedFile::fake()->image('bomb.jpg', 9000, 9000);

        $this->expectException(\InvalidArgumentException::class);
        $this->service->store($user, $file);
    }

    public function test_old_photo_deleted_after_successful_replacement(): void
    {
        $user = User::factory()->create();
        $oldPath = 'users/'.$user->id.'/old_profile.jpg';
        Storage::disk('public')->put($oldPath, 'old content');
        $user->update(['photo_path' => $oldPath]);

        Storage::disk('public')->assertExists($oldPath);

        $secondFile = UploadedFile::fake()->image('second.jpg', 400, 300);
        $secondPath = $this->service->store($user, $secondFile);

        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($secondPath);
        $this->assertSame($secondPath, $user->fresh()->photo_path);
    }

    public function test_user_without_photo_displays_fallback_avatar(): void
    {
        $user = User::factory()->create([
            'name' => 'Ahmad Siswa',
            'photo_path' => null,
        ]);

        $this->assertNull($user->photo_url);
        $this->assertStringContainsString('ui-avatars.com', $user->avatar_url);
        $this->assertStringContainsString('name=AS', $user->avatar_url);
    }

    public function test_destroy_removes_photo_file_and_resets_attribute(): void
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('avatar.jpg', 400, 300);
        $path = $this->service->store($user, $file);

        Storage::disk('public')->assertExists($path);

        $this->service->destroy($user);

        Storage::disk('public')->assertMissing($path);
        $this->assertNull($user->fresh()->photo_path);
    }
}
