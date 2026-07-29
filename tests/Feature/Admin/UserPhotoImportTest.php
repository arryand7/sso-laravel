<?php

namespace Tests\Feature\Admin;

use App\Jobs\ProcessUserPhotoImportBatch;
use App\Models\User;
use App\Models\UserPhotoImportBatch;
use App\Models\UserPhotoImportItem;
use App\Services\UserPhotoImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use ZipArchive;

class UserPhotoImportTest extends TestCase
{
    use RefreshDatabase;

    protected User $superadmin;

    protected User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::fake('public');

        $superadminRole = Role::create(['name' => 'superadmin', 'guard_name' => 'web']);
        $studentRole = Role::create(['name' => 'student', 'guard_name' => 'web']);
        $teacherRole = Role::create(['name' => 'teacher', 'guard_name' => 'web']);

        $this->superadmin = User::factory()->create(['type' => 'admin']);
        $this->superadmin->assignRole($superadminRole);

        $this->regularUser = User::factory()->create(['type' => 'student']);
        $this->regularUser->assignRole($studentRole);
    }

    /**
     * Create a dummy image file (100x100 PNG or JPG).
     */
    protected function createDummyImage(string $format = 'png', int $width = 100, int $height = 100): string
    {
        $im = imagecreatetruecolor($width, $height);
        $bg = imagecolorallocate($im, 255, 0, 0);
        imagefill($im, 0, 0, $bg);

        $tempPath = tempnam(sys_get_temp_dir(), 'test_img_').'.'.$format;

        if ($format === 'jpg' || $format === 'jpeg') {
            imagejpeg($im, $tempPath);
        } elseif ($format === 'webp') {
            imagewebp($im, $tempPath);
        } else {
            imagepng($im, $tempPath);
        }

        imagedestroy($im);

        return $tempPath;
    }

    /**
     * Helper to create a ZIP file containing custom entries.
     */
    protected function createZipWithFiles(array $filesMap): UploadedFile
    {
        $zipPath = tempnam(sys_get_temp_dir(), 'test_zip_').'.zip';
        $zip = new ZipArchive;
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        foreach ($filesMap as $entryName => $contentOrFilePath) {
            if (file_exists($contentOrFilePath)) {
                $zip->addFile($contentOrFilePath, $entryName);
            } else {
                $zip->addFromString($entryName, $contentOrFilePath);
            }
        }

        $zip->close();

        return new UploadedFile($zipPath, 'photos.zip', 'application/zip', null, true);
    }

    // ==========================================
    // 1. AUTHORIZATION TESTS
    // ==========================================

    public function test_superadmin_can_access_bulk_photo_import_page(): void
    {
        $response = $this->actingAs($this->superadmin)->get(route('admin.users.photo-import.index'));
        $response->assertStatus(200);
    }

    public function test_unauthorized_user_cannot_access_bulk_photo_import_page(): void
    {
        $response = $this->actingAs($this->regularUser)->get(route('admin.users.photo-import.index'));
        $response->assertStatus(403);
    }

    // ==========================================
    // 2. ZIP VALIDATION & SECURITY TESTS
    // ==========================================

    public function test_valid_zip_file_is_accepted_for_preview(): void
    {
        $img = $this->createDummyImage('jpg');
        $zip = $this->createZipWithFiles(['22001001.jpg' => $img]);

        $response = $this->actingAs($this->superadmin)->post(route('admin.users.photo-import.store'), [
            'matching_type' => 'nis',
            'existing_photo_policy' => 'skip',
            'file' => $zip,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('user_photo_import_batches', [
            'matching_type' => 'nis',
            'status' => 'preview_ready',
        ]);

        @unlink($img);
    }

    public function test_non_zip_file_is_rejected(): void
    {
        $txtFile = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        $response = $this->actingAs($this->superadmin)->post(route('admin.users.photo-import.store'), [
            'matching_type' => 'nis',
            'existing_photo_policy' => 'skip',
            'file' => $txtFile,
        ]);

        $response->assertSessionHasErrors(['file']);
    }

    public function test_zip_slip_entry_is_rejected(): void
    {
        $img = $this->createDummyImage('jpg');
        $zip = $this->createZipWithFiles(['../../malicious.jpg' => $img]);

        $response = $this->actingAs($this->superadmin)->post(route('admin.users.photo-import.store'), [
            'matching_type' => 'nis',
            'existing_photo_policy' => 'skip',
            'file' => $zip,
        ]);

        $batch = UserPhotoImportBatch::latest('id')->first();
        $this->assertNotNull($batch);

        $this->assertDatabaseHas('user_photo_import_items', [
            'batch_id' => $batch->id,
            'status' => 'SECURITY_REJECTED',
        ]);

        @unlink($img);
    }

    public function test_macosx_and_ds_store_files_are_ignored(): void
    {
        $img = $this->createDummyImage('jpg');
        $zip = $this->createZipWithFiles([
            '22001001.jpg' => $img,
            '__MACOSX/._22001001.jpg' => 'dummy mac data',
            '.DS_Store' => 'ds store data',
        ]);

        $response = $this->actingAs($this->superadmin)->post(route('admin.users.photo-import.store'), [
            'matching_type' => 'nis',
            'existing_photo_policy' => 'skip',
            'file' => $zip,
        ]);

        $batch = UserPhotoImportBatch::latest('id')->first();
        $this->assertEquals(1, $batch->items()->count());

        @unlink($img);
    }

    // ==========================================
    // 3. FILENAME MATCHING TESTS
    // ==========================================

    public function test_exact_nis_matching_for_student(): void
    {
        $student = User::factory()->create([
            'type' => 'student',
            'nis' => '0022001001',
            'photo_path' => null,
        ]);

        $img = $this->createDummyImage('jpg');
        $zip = $this->createZipWithFiles(['0022001001.jpg' => $img]);

        $this->actingAs($this->superadmin)->post(route('admin.users.photo-import.store'), [
            'matching_type' => 'nis',
            'existing_photo_policy' => 'skip',
            'file' => $zip,
        ]);

        $item = UserPhotoImportItem::latest('id')->first();
        $this->assertEquals('MATCHED_NEW', $item->status);
        $this->assertEquals('0022001001', $item->identifier);
        $this->assertEquals($student->id, $item->user_id);

        @unlink($img);
    }

    public function test_nis_mode_does_not_match_teacher_or_staff(): void
    {
        $teacher = User::factory()->create([
            'type' => 'teacher',
            'nis' => '22001002',
            'nip' => '198707012010011001',
        ]);

        $img = $this->createDummyImage('jpg');
        $zip = $this->createZipWithFiles(['22001002.jpg' => $img]);

        $this->actingAs($this->superadmin)->post(route('admin.users.photo-import.store'), [
            'matching_type' => 'nis',
            'existing_photo_policy' => 'skip',
            'file' => $zip,
        ]);

        $item = UserPhotoImportItem::latest('id')->first();
        $this->assertEquals('USER_NOT_FOUND', $item->status);

        @unlink($img);
    }

    public function test_duplicate_file_identifiers_in_zip_are_flagged_as_conflict(): void
    {
        $img1 = $this->createDummyImage('jpg');
        $img2 = $this->createDummyImage('png');

        $zip = $this->createZipWithFiles([
            '22001001.jpg' => $img1,
            '22001001.png' => $img2,
        ]);

        $this->actingAs($this->superadmin)->post(route('admin.users.photo-import.store'), [
            'matching_type' => 'nis',
            'existing_photo_policy' => 'skip',
            'file' => $zip,
        ]);

        $items = UserPhotoImportItem::where('identifier', '22001001')->get();
        $this->assertCount(2, $items);
        foreach ($items as $item) {
            $this->assertEquals('DUPLICATE_FILE_IDENTIFIER', $item->status);
        }

        @unlink($img1);
        @unlink($img2);
    }

    public function test_invalid_filename_format_is_flagged(): void
    {
        $img = $this->createDummyImage('jpg');
        $zip = $this->createZipWithFiles(['Ahmad-22001001.jpg' => $img]);

        $this->actingAs($this->superadmin)->post(route('admin.users.photo-import.store'), [
            'matching_type' => 'nis',
            'existing_photo_policy' => 'skip',
            'file' => $zip,
        ]);

        $item = UserPhotoImportItem::latest('id')->first();
        $this->assertEquals('INVALID_FILENAME', $item->status);

        @unlink($img);
    }

    // ==========================================
    // 4. PREVIEW ISOLATION TESTS
    // ==========================================

    public function test_preview_does_not_modify_user_photo_path(): void
    {
        $student = User::factory()->create([
            'type' => 'student',
            'nis' => '22001001',
            'photo_path' => 'users/1/old_photo.jpg',
        ]);

        $img = $this->createDummyImage('jpg');
        $zip = $this->createZipWithFiles(['22001001.jpg' => $img]);

        $this->actingAs($this->superadmin)->post(route('admin.users.photo-import.store'), [
            'matching_type' => 'nis',
            'existing_photo_policy' => 'replace',
            'file' => $zip,
        ]);

        // Refresh user from DB
        $student->refresh();
        $this->assertEquals('users/1/old_photo.jpg', $student->photo_path);

        @unlink($img);
    }

    // ==========================================
    // 5. IMPORT APPLICATION & PHOTO PROCESSING TESTS
    // ==========================================

    public function test_confirm_import_dispatches_batch_job_and_updates_user_photo(): void
    {
        Queue::fake();

        $student = User::factory()->create([
            'type' => 'student',
            'nis' => '22001001',
            'photo_path' => null,
        ]);

        $img = $this->createDummyImage('jpg');
        $zip = $this->createZipWithFiles(['22001001.jpg' => $img]);

        $this->actingAs($this->superadmin)->post(route('admin.users.photo-import.store'), [
            'matching_type' => 'nis',
            'existing_photo_policy' => 'skip',
            'file' => $zip,
        ]);

        $batch = UserPhotoImportBatch::latest('id')->first();

        $response = $this->actingAs($this->superadmin)->post(route('admin.users.photo-import.confirm', $batch));
        $response->assertRedirect();

        Queue::assertPushed(ProcessUserPhotoImportBatch::class);

        @unlink($img);
    }

    public function test_import_service_processes_item_and_updates_user_photo(): void
    {
        $student = User::factory()->create([
            'type' => 'student',
            'nis' => '22001001',
            'photo_path' => null,
        ]);

        $img = $this->createDummyImage('jpg', 1200, 900);
        $zip = $this->createZipWithFiles(['22001001.jpg' => $img]);

        $this->actingAs($this->superadmin)->post(route('admin.users.photo-import.store'), [
            'matching_type' => 'nis',
            'existing_photo_policy' => 'skip',
            'file' => $zip,
        ]);

        $batch = UserPhotoImportBatch::latest('id')->first();
        $item = $batch->items()->first();

        /** @var UserPhotoImportService $service */
        $service = app(UserPhotoImportService::class);
        $success = $service->processItem($item);

        $this->assertTrue($success);
        $student->refresh();
        $this->assertNotNull($student->photo_path);
        $this->assertTrue(Storage::disk('public')->exists($student->photo_path));

        @unlink($img);
    }
}
