<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    protected array $systemRoles = [
        'superadmin',
        'admin',
    ];

    /**
     * Display a listing of roles.
     */
    public function index()
    {
        $roles = Role::withCount('users')
            ->orderByRaw("case when name in ('superadmin', 'admin') then 0 else 1 end")
            ->orderBy('name')
            ->get();

        $applicationRoleCounts = DB::table('application_role')
            ->select('role_id', DB::raw('count(*) as total'))
            ->groupBy('role_id')
            ->pluck('total', 'role_id');

        return view('admin.roles.index', [
            'roles' => $roles,
            'systemRoles' => $this->systemRoles,
            'applicationRoleCounts' => $applicationRoleCounts,
        ]);
    }

    /**
     * Show the form for creating a new role.
     */
    public function create()
    {
        return view('admin.roles.create');
    }

    /**
     * Store a newly created role.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                'alpha_dash:ascii',
                Rule::unique('roles', 'name')->where('guard_name', 'web'),
            ],
        ]);

        Role::create([
            'name' => strtolower($validated['name']),
            'guard_name' => 'web',
        ]);

        return redirect()->route('admin.roles.index')
            ->with('status', 'Role berhasil dibuat.');
    }

    /**
     * Display the specified role.
     */
    public function show(Role $role)
    {
        $role->load('users');
        $users = $role->users()->paginate(15);

        return view('admin.roles.show', [
            'role' => $role,
            'users' => $users,
            'isSystemRole' => $this->isSystemRole($role),
        ]);
    }

    /**
     * Show the form for editing the specified role.
     */
    public function edit(Role $role)
    {
        $this->ensureRoleIsEditable($role);

        return view('admin.roles.edit', compact('role'));
    }

    /**
     * Update the specified role.
     */
    public function update(Request $request, Role $role)
    {
        $this->ensureRoleIsEditable($role);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                'alpha_dash:ascii',
                Rule::unique('roles', 'name')
                    ->where('guard_name', $role->guard_name)
                    ->ignore($role->id),
            ],
        ]);

        $role->update([
            'name' => strtolower($validated['name']),
        ]);

        return redirect()->route('admin.roles.index')
            ->with('status', 'Role berhasil diperbarui.');
    }

    /**
     * Remove the specified role.
     */
    public function destroy(Role $role)
    {
        $this->ensureRoleIsEditable($role);

        if ($role->users()->exists()) {
            return back()->withErrors(['role' => 'Role masih digunakan oleh user dan tidak dapat dihapus.']);
        }

        $usedByApplications = DB::table('application_role')
            ->where('role_id', $role->id)
            ->exists();

        if ($usedByApplications) {
            return back()->withErrors(['role' => 'Role masih digunakan pada mapping aplikasi dan tidak dapat dihapus.']);
        }

        $role->delete();

        return redirect()->route('admin.roles.index')
            ->with('status', 'Role berhasil dihapus.');
    }

    protected function ensureRoleIsEditable(Role $role): void
    {
        abort_if($this->isSystemRole($role), 403, 'Role sistem tidak dapat diubah atau dihapus.');
    }

    protected function isSystemRole(Role $role): bool
    {
        return in_array($role->name, $this->systemRoles, true);
    }
}
