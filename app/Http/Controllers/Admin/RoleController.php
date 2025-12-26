<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    /**
     * Display a listing of roles.
     */
    public function index()
    {
        $roles = Role::withCount('users')->get();
        
        return view('admin.roles.index', compact('roles'));
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
        ]);
    }
}
