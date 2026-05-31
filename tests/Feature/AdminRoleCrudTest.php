<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminRoleCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_roles_but_cannot_access_role_crud(): void
    {
        $admin = $this->userWithRole('admin');

        $this->actingAs($admin)
            ->get(route('admin.roles.index'))
            ->assertOk()
            ->assertDontSee(route('admin.roles.create'), false);

        $this->actingAs($admin)
            ->get(route('admin.roles.create'))
            ->assertForbidden();

        $this->actingAs($admin)
            ->post(route('admin.roles.store'), ['name' => 'finance'])
            ->assertForbidden();
    }

    public function test_superadmin_can_create_update_and_delete_custom_role(): void
    {
        $superadmin = $this->userWithRole('superadmin');

        $this->actingAs($superadmin)
            ->post(route('admin.roles.store'), ['name' => 'Finance_Staff'])
            ->assertRedirect(route('admin.roles.index'));

        $role = Role::where('name', 'finance_staff')->firstOrFail();

        $this->actingAs($superadmin)
            ->put(route('admin.roles.update', $role), ['name' => 'finance-admin'])
            ->assertRedirect(route('admin.roles.index'));

        $role->refresh();
        $this->assertSame('finance-admin', $role->name);

        $this->actingAs($superadmin)
            ->delete(route('admin.roles.destroy', $role))
            ->assertRedirect(route('admin.roles.index'));

        $this->assertDatabaseMissing('roles', ['name' => 'finance-admin']);
    }

    public function test_system_roles_cannot_be_edited_or_deleted(): void
    {
        $superadmin = $this->userWithRole('superadmin');
        $systemRole = Role::where('name', 'superadmin')->firstOrFail();

        $this->actingAs($superadmin)
            ->get(route('admin.roles.edit', $systemRole))
            ->assertForbidden();

        $this->actingAs($superadmin)
            ->delete(route('admin.roles.destroy', $systemRole))
            ->assertForbidden();

        $this->assertDatabaseHas('roles', ['name' => 'superadmin']);
    }

    public function test_role_with_users_cannot_be_deleted(): void
    {
        $superadmin = $this->userWithRole('superadmin');
        $role = Role::create(['name' => 'finance', 'guard_name' => 'web']);

        $user = User::create([
            'name' => 'Finance User',
            'username' => 'finance-user',
            'email' => 'finance-user@example.com',
            'password' => Hash::make('password'),
            'type' => 'staff',
            'status' => 'active',
        ]);
        $user->assignRole($role);

        $this->actingAs($superadmin)
            ->delete(route('admin.roles.destroy', $role))
            ->assertSessionHasErrors('role');

        $this->assertDatabaseHas('roles', ['name' => 'finance']);
    }

    private function userWithRole(string $roleName): User
    {
        $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);

        $user = User::create([
            'name' => ucfirst($roleName),
            'username' => $roleName.'-role-crud',
            'email' => $roleName.'-role-crud@example.com',
            'password' => Hash::make('password'),
            'type' => 'admin',
            'status' => 'active',
        ]);

        $user->assignRole($role);

        return $user;
    }
}
