<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminUserAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_cannot_assign_superadmin_role(): void
    {
        $admin = $this->userWithRole('admin');
        $superadminRole = Role::create(['name' => 'superadmin', 'guard_name' => 'web']);

        $response = $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Escalated User',
            'username' => 'escalated',
            'email' => 'escalated@example.com',
            'password' => 'password',
            'type' => 'admin',
            'status' => 'active',
            'roles' => [$superadminRole->id],
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('users', ['username' => 'escalated']);
    }

    public function test_admin_cannot_manage_superadmin_user(): void
    {
        $admin = $this->userWithRole('admin');
        $superadmin = $this->userWithRole('superadmin', [
            'username' => 'root',
            'email' => 'root@example.com',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.users.edit', $superadmin))
            ->assertForbidden();

        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $superadmin))
            ->assertForbidden();

        $this->assertDatabaseHas('users', ['username' => 'root']);
    }

    public function test_admin_user_index_hides_superadmin_management_actions(): void
    {
        $admin = $this->userWithRole('admin');
        $superadmin = $this->userWithRole('superadmin', [
            'username' => 'root',
            'email' => 'root@example.com',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.users.index'));

        $response->assertOk();
        $response->assertSee('root');
        $response->assertDontSee(route('admin.users.edit', $superadmin), false);
        $response->assertDontSee('method="POST" action="'.route('admin.users.destroy', $superadmin).'"', false);
    }

    public function test_admin_user_show_hides_superadmin_edit_action(): void
    {
        $admin = $this->userWithRole('admin');
        $superadmin = $this->userWithRole('superadmin', [
            'username' => 'root',
            'email' => 'root@example.com',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.users.show', $superadmin));

        $response->assertOk();
        $response->assertDontSee(route('admin.users.edit', $superadmin), false);
    }

    public function test_superadmin_can_manage_superadmin_role(): void
    {
        $superadmin = $this->userWithRole('superadmin');
        $superadminRole = Role::where('name', 'superadmin')->firstOrFail();

        $response = $this->actingAs($superadmin)->post(route('admin.users.store'), [
            'name' => 'Second Superadmin',
            'username' => 'second-root',
            'email' => 'second-root@example.com',
            'password' => 'password',
            'type' => 'admin',
            'status' => 'active',
            'roles' => [$superadminRole->id],
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $this->assertTrue(User::where('username', 'second-root')->firstOrFail()->hasRole('superadmin'));
    }

    public function test_superadmin_can_bulk_deactivate_and_delete_users(): void
    {
        $superadmin = $this->userWithRole('superadmin');
        $user1 = User::factory()->create(['status' => 'active']);
        $user2 = User::factory()->create(['status' => 'active']);

        // Bulk deactivate
        $response = $this->actingAs($superadmin)->post(route('admin.users.bulk-actions'), [
            'action' => 'deactivate_selected',
            'user_ids' => [$user1->id, $user2->id],
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $this->assertSame('suspended', $user1->fresh()->status);
        $this->assertSame('suspended', $user2->fresh()->status);

        // Bulk delete
        $response = $this->actingAs($superadmin)->post(route('admin.users.bulk-actions'), [
            'action' => 'delete_selected',
            'user_ids' => [$user1->id, $user2->id],
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseMissing('users', ['id' => $user1->id]);
        $this->assertDatabaseMissing('users', ['id' => $user2->id]);
    }

    public function test_regular_admin_cannot_bulk_deactivate_or_delete_users(): void
    {
        $admin = $this->userWithRole('admin');
        $user1 = User::factory()->create(['status' => 'active']);

        $this->actingAs($admin)->post(route('admin.users.bulk-actions'), [
            'action' => 'deactivate_selected',
            'user_ids' => [$user1->id],
        ])->assertForbidden();

        $this->actingAs($admin)->post(route('admin.users.bulk-actions'), [
            'action' => 'delete_selected',
            'user_ids' => [$user1->id],
        ])->assertForbidden();
    }

    private function userWithRole(string $roleName, array $attributes = []): User
    {
        $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);

        $user = User::create(array_merge([
            'name' => ucfirst($roleName),
            'username' => $roleName.'-user',
            'email' => $roleName.'@example.com',
            'password' => Hash::make('password'),
            'type' => 'admin',
            'status' => 'active',
        ], $attributes));

        $user->assignRole($role);

        return $user;
    }
}
