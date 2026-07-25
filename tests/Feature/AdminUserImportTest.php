<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserImportBatch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminUserImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_import_users_from_xlsx(): void
    {
        $admin = $this->adminUser();
        Role::create(['name' => 'student', 'guard_name' => 'web']);

        $rows = [
            ['username', 'name', 'email', 'type', 'nis', 'nip', 'status', 'qr_code', 'password', 'user_id', 'SABIRA_USER_IMPORT_V1'],
            ['student-new', 'Siswa Baru', 'student-new@example.com', 'student', '2026001', '', 'active', '', 'secret123', ''],
        ];

        $file = $this->createXlsxFile($rows, 'users.xlsx');

        $response = $this->actingAs($admin)->post(route('admin.users.import.store'), [
            'file' => $file,
            'mode' => 'create_only',
        ]);

        $batch = UserImportBatch::firstOrFail();
        $response->assertRedirect(route('admin.users.import.show', $batch));

        // Commit batch
        $this->actingAs($admin)->post(route('admin.users.import.commit', $batch));

        $user = User::where('username', 'student-new')->firstOrFail();

        $this->assertSame('Siswa Baru', $user->name);
        $this->assertSame('student', $user->type);
        $this->assertSame('2026001', $user->nis);
        $this->assertTrue($user->hasRole('student'));
    }

    public function test_admin_import_updates_existing_user_without_replacing_password_when_blank(): void
    {
        $admin = $this->adminUser();
        Role::create(['name' => 'staff', 'guard_name' => 'web']);

        $existing = User::create([
            'name' => 'Old Name',
            'username' => 'existing',
            'email' => 'old@example.com',
            'password' => Hash::make('old-password'),
            'type' => 'staff',
            'status' => 'active',
        ]);

        $rows = [
            ['username', 'name', 'email', 'type', 'nis', 'nip', 'status', 'qr_code', 'password', 'user_id', 'SABIRA_USER_IMPORT_V1'],
            ['existing', 'Updated Name', 'new@example.com', 'staff', '', '1001', 'active', '', '', (string) $existing->id],
        ];

        $file = $this->createXlsxFile($rows, 'users_update.xlsx');

        $this->actingAs($admin)->post(route('admin.users.import.store'), [
            'file' => $file,
            'mode' => 'update_only',
        ]);

        $batch = UserImportBatch::firstOrFail();
        $this->actingAs($admin)->post(route('admin.users.import.commit', $batch));

        $existing->refresh();

        $this->assertSame('Updated Name', $existing->name);
        $this->assertSame('new@example.com', $existing->email);
        $this->assertTrue(Hash::check('old-password', $existing->password));
        $this->assertTrue($existing->hasRole('staff'));
    }

    protected function createXlsxFile(array $rows, string $filename): UploadedFile
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Users');

        foreach ($rows as $rowIndex => $row) {
            foreach ($row as $colIndex => $value) {
                $sheet->setCellValueByColumnAndRow($colIndex + 1, $rowIndex + 1, $value);
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

    private function adminUser(): User
    {
        $role = Role::create(['name' => 'admin', 'guard_name' => 'web']);

        $user = User::create([
            'name' => 'Admin',
            'username' => 'admin-test',
            'email' => 'admin-test@example.com',
            'password' => Hash::make('password'),
            'type' => 'admin',
            'status' => 'active',
        ]);

        $user->assignRole($role);

        return $user;
    }
}
