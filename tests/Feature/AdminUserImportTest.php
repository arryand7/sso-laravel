<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminUserImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_import_users_from_csv(): void
    {
        $admin = $this->adminUser();
        Role::create(['name' => 'student', 'guard_name' => 'web']);

        $file = UploadedFile::fake()->createWithContent(
            'users.csv',
            "name,username,email,type,nis,role,status,password\n".
            "Siswa Baru,student-new,student-new@example.com,student,2026001,student,active,secret123\n"
        );

        $response = $this->actingAs($admin)->post(route('admin.users.import.store'), [
            'file' => $file,
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHas('status', 'Import selesai. Dibuat: 1, diperbarui: 0, dilewati: 0.');

        $user = User::where('username', 'student-new')->firstOrFail();

        $this->assertSame('Siswa Baru', $user->name);
        $this->assertSame('student', $user->type);
        $this->assertSame('2026001', $user->nis);
        $this->assertTrue(Hash::check('secret123', $user->password));
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

        $file = UploadedFile::fake()->createWithContent(
            'users.csv',
            "name,username,email,type,role\n".
            "Updated Name,existing,new@example.com,staff,staff\n"
        );

        $this->actingAs($admin)->post(route('admin.users.import.store'), [
            'file' => $file,
        ]);

        $existing->refresh();

        $this->assertSame('Updated Name', $existing->name);
        $this->assertSame('new@example.com', $existing->email);
        $this->assertTrue(Hash::check('old-password', $existing->password));
        $this->assertTrue($existing->hasRole('staff'));
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
