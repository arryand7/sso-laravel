<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserImportBatch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserImportFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Role::create(['name' => 'admin', 'guard_name' => 'web']);
        Role::create(['name' => 'student', 'guard_name' => 'web']);
        Role::create(['name' => 'teacher', 'guard_name' => 'web']);
        Role::create(['name' => 'staff', 'guard_name' => 'web']);
    }

    public function test_admin_can_upload_and_commit_valid_import_batch(): void
    {
        $admin = $this->createAdmin();

        $rows = [
            ['username', 'name', 'email', 'type', 'nis', 'nip', 'status', 'qr_code', 'password', 'user_id', 'SABIRA_USER_IMPORT_V1'],
            ['student01', 'Siswa Utama', 'siswa01@sabira.id', 'student', '22001001', '', 'active', '00001001', 'custompass123', ''],
            ['teacher01', 'Guru Utama', 'guru01@sabira.id', 'teacher', '', '19850101', 'active', '00002001', '', ''],
        ];

        $file = $this->createXlsxFile($rows, 'users.xlsx');

        $response = $this->actingAs($admin)->post(route('admin.users.import.store'), [
            'file' => $file,
            'mode' => 'create_only',
        ]);

        $batch = UserImportBatch::firstOrFail();
        $response->assertRedirect(route('admin.users.import.show', $batch));

        $this->assertSame('ready', $batch->status);
        $this->assertSame(2, $batch->total_rows);
        $this->assertSame(2, $batch->valid_rows);
        $this->assertSame(0, $batch->invalid_rows);

        // Commit batch
        $commitResponse = $this->actingAs($admin)->post(route('admin.users.import.commit', $batch));

        $commitResponse->assertRedirect(route('admin.users.import.show', $batch));
        $batch->refresh();

        $this->assertSame('completed', $batch->status);
        $this->assertSame(2, $batch->created_rows);

        // Verify users created in DB
        $siswa = User::where('username', 'student01')->firstOrFail();
        $this->assertSame('Siswa Utama', $siswa->name);
        $this->assertSame('student', $siswa->type);
        $this->assertSame('22001001', $siswa->nis);
        $this->assertSame('00001001', $siswa->qr_code);
        $this->assertTrue(Hash::check('custompass123', $siswa->password));
        $this->assertTrue($siswa->hasRole('student'));

        $guru = User::where('username', 'teacher01')->firstOrFail();
        $this->assertSame('Guru Utama', $guru->name);
        $this->assertSame('teacher', $guru->type);
        $this->assertSame('19850101', $guru->nip);
        $this->assertTrue($guru->hasRole('teacher'));
    }

    public function test_non_xlsx_file_is_rejected_on_upload(): void
    {
        $admin = $this->createAdmin();

        $file = UploadedFile::fake()->create('invalid.csv', 100, 'text/csv');

        $response = $this->actingAs($admin)->post(route('admin.users.import.store'), [
            'file' => $file,
        ]);

        $response->assertSessionHasErrors(['file']);
        $this->assertSame(0, UserImportBatch::count());
    }

    public function test_validation_reports_errors_for_invalid_email_enum_and_missing_required(): void
    {
        $admin = $this->createAdmin();

        $rows = [
            ['username', 'name', 'email', 'type', 'nis', 'nip', 'status', 'qr_code', 'user_id', 'SABIRA_USER_IMPORT_V1'],
            ['student01', 'Siswa One', 'invalid-email', 'student', '22001001', '', 'active', '', ''],
            ['student02', '', 'siswa02@sabira.id', 'student', '22001002', '', 'active', '', ''], // missing name
            ['student03', 'Siswa Three', 'siswa03@sabira.id', 'invalid_type', '22001003', '', 'active', '', ''], // invalid type
        ];

        $file = $this->createXlsxFile($rows, 'invalid_users.xlsx');

        $this->actingAs($admin)->post(route('admin.users.import.store'), [
            'file' => $file,
            'mode' => 'create_only',
        ]);

        $batch = UserImportBatch::firstOrFail();
        $this->assertSame('validation_failed', $batch->status);
        $this->assertSame(3, $batch->invalid_rows);
        $this->assertSame(0, $batch->valid_rows);

        // Preview non-write: users count in database remains unchanged
        $this->assertSame(1, User::count()); // only admin
    }

    public function test_in_file_duplicate_and_database_duplicate_are_reported(): void
    {
        $admin = $this->createAdmin();

        // Existing user in DB
        User::factory()->create([
            'username' => 'existing_user',
            'email' => 'existing@sabira.id',
            'qr_code' => '00008888',
        ]);

        $rows = [
            ['username', 'name', 'email', 'type', 'nis', 'nip', 'status', 'qr_code', 'user_id', 'SABIRA_USER_IMPORT_V1'],
            ['existing_user', 'Dup DB User', 'new@sabira.id', 'staff', '', '1001', 'active', '', ''], // DB username duplicate
            ['user_b', 'User B', 'existing@sabira.id', 'staff', '', '1002', 'active', '', ''], // DB email duplicate
            ['user_c', 'User C', 'c@sabira.id', 'staff', '', '1003', 'active', '00008888', ''], // DB QR duplicate
            ['user_d', 'User D', 'd@sabira.id', 'staff', '', '1004', 'active', '00009999', ''], // In-file QR first
            ['user_e', 'User E', 'e@sabira.id', 'staff', '', '1005', 'active', '00009999', ''], // In-file QR duplicate
        ];

        $file = $this->createXlsxFile($rows, 'dups.xlsx');

        $this->actingAs($admin)->post(route('admin.users.import.store'), [
            'file' => $file,
            'mode' => 'create_only',
        ]);

        $batch = UserImportBatch::firstOrFail();
        $this->assertSame('validation_failed', $batch->status);
        $this->assertGreaterThan(0, $batch->invalid_rows);
    }

    public function test_scientific_notation_in_nis_or_nip_is_rejected(): void
    {
        $admin = $this->createAdmin();

        $rows = [
            ['username', 'name', 'email', 'type', 'nis', 'nip', 'status', 'qr_code', 'user_id', 'SABIRA_USER_IMPORT_V1'],
            ['student_sci', 'Sci Student', 'sci@sabira.id', 'student', '2.2001E+07', '', 'active', '', ''],
        ];

        $file = $this->createXlsxFile($rows, 'scientific.xlsx');

        $this->actingAs($admin)->post(route('admin.users.import.store'), [
            'file' => $file,
        ]);

        $batch = UserImportBatch::firstOrFail();
        $this->assertSame('validation_failed', $batch->status);

        $rowFail = $batch->rows()->first();
        $this->assertSame('invalid', $rowFail->status);
        $this->assertSame('SCIENTIFIC_NOTATION', $rowFail->errors[0]['code']);
    }

    public function test_admin_role_cannot_be_assigned_via_regular_import(): void
    {
        $admin = $this->createAdmin();

        $rows = [
            ['username', 'name', 'email', 'type', 'nis', 'nip', 'status', 'qr_code', 'user_id', 'SABIRA_USER_IMPORT_V1'],
            ['hacker_admin', 'Fake Admin', 'fakeadmin@sabira.id', 'admin', '', '9999', 'active', '', ''],
        ];

        $file = $this->createXlsxFile($rows, 'admin_hack.xlsx');

        $this->actingAs($admin)->post(route('admin.users.import.store'), [
            'file' => $file,
            'mode' => 'create_only',
        ]);

        $batch = UserImportBatch::firstOrFail();
        $this->assertSame('validation_failed', $batch->status);

        $rowFail = $batch->rows()->first();
        $this->assertSame('UNAUTHORIZED_ROLE_CHANGE', $rowFail->errors[0]['code']);
    }

    public function test_admin_can_download_error_report_xlsx(): void
    {
        $admin = $this->createAdmin();

        $rows = [
            ['username', 'name', 'email', 'type', 'nis', 'nip', 'status', 'qr_code', 'user_id', 'SABIRA_USER_IMPORT_V1'],
            ['bad_user', '', 'invalid-email', 'invalid_type', '', '', 'active', '', ''],
        ];

        $file = $this->createXlsxFile($rows, 'bad.xlsx');

        $this->actingAs($admin)->post(route('admin.users.import.store'), [
            'file' => $file,
        ]);

        $batch = UserImportBatch::firstOrFail();

        $response = $this->actingAs($admin)->get(route('admin.users.import.report', $batch));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    protected function createXlsxFile(array $rows, string $filename): UploadedFile
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Users');

        foreach ($rows as $rowIndex => $row) {
            foreach ($row as $colIndex => $value) {
                $sheet->setCellValueExplicitByColumnAndRow(
                    $colIndex + 1,
                    $rowIndex + 1,
                    (string) $value,
                    \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING
                );
            }
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'test_xlsx_');
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempPath);

        return new UploadedFile(
            $tempPath,
            $filename,
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );
    }

    private function createAdmin(): User
    {
        $role = Role::where('name', 'admin')->first();
        $admin = User::factory()->create(['type' => 'admin']);
        $admin->assignRole($role);

        return $admin;
    }
}
