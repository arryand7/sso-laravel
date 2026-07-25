<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserQrCodeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::create(['name' => 'admin', 'guard_name' => 'web']);
        Role::create(['name' => 'staff', 'guard_name' => 'web']);
    }

    public function test_qr_code_can_be_stored(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'User QR Test',
            'username' => 'userqr',
            'email' => 'userqr@example.com',
            'password' => 'secret123',
            'type' => 'staff',
            'status' => 'active',
            'qr_code' => '00001234',
            'roles' => [Role::where('name', 'staff')->first()->id],
        ]);

        $response->assertRedirect(route('admin.users.index'));

        $user = User::where('username', 'userqr')->firstOrFail();
        $this->assertSame('00001234', $user->qr_code);
    }

    public function test_duplicate_qr_code_is_rejected(): void
    {
        $admin = $this->createAdmin();

        User::factory()->create([
            'qr_code' => '00001234',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'User Duplicate QR',
            'username' => 'userdup',
            'email' => 'userdup@example.com',
            'password' => 'secret123',
            'type' => 'staff',
            'status' => 'active',
            'qr_code' => '00001234',
            'roles' => [Role::where('name', 'staff')->first()->id],
        ]);

        $response->assertSessionHasErrors(['qr_code']);
    }

    public function test_same_user_can_retain_qr_code_when_editing(): void
    {
        $admin = $this->createAdmin();

        $user = User::factory()->create([
            'username' => 'useredit',
            'qr_code' => '00005678',
        ]);

        $response = $this->actingAs($admin)->put(route('admin.users.update', $user), [
            'name' => 'Updated Name',
            'username' => 'useredit',
            'email' => $user->email,
            'type' => 'staff',
            'status' => 'active',
            'qr_code' => '00005678',
            'roles' => [Role::where('name', 'staff')->first()->id],
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $this->assertSame('00005678', $user->fresh()->qr_code);
    }

    public function test_leading_zeros_in_qr_code_are_preserved(): void
    {
        $user = User::factory()->create();
        $user->qr_code = '00009999';
        $user->save();

        $this->assertSame('00009999', $user->fresh()->qr_code);
    }

    public function test_empty_string_qr_code_is_converted_to_null(): void
    {
        $user = User::factory()->create([
            'qr_code' => '00001234',
        ]);

        $user->qr_code = '   ';
        $user->save();

        $this->assertNull($user->fresh()->qr_code);
    }

    public function test_full_qr_code_value_is_masked_in_listing_helper(): void
    {
        $user = User::factory()->create([
            'qr_code' => '00001234',
        ]);

        $this->assertSame('••••1234', $user->masked_qr_code);
    }

    public function test_qr_status_displayed_in_admin_user_index(): void
    {
        $admin = $this->createAdmin();

        $userWithQr = User::factory()->create(['qr_code' => '00001111']);
        $userWithoutQr = User::factory()->create(['qr_code' => null]);

        $response = $this->actingAs($admin)->get(route('admin.users.index'));

        $response->assertStatus(200);
        $response->assertSee('Terdaftar');
        $response->assertSee('Belum terdaftar');
    }

    private function createAdmin(): User
    {
        $role = Role::where('name', 'admin')->first();
        $admin = User::factory()->create(['type' => 'admin']);
        $admin->assignRole($role);

        return $admin;
    }
}
