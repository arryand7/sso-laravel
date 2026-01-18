<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    /**
     * Display a listing of users.
     */
    public function index(Request $request)
    {
        $query = User::with('roles');

        // Search
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('nis', 'like', "%{$search}%")
                  ->orWhere('nip', 'like', "%{$search}%");
            });
        }

        // Filter by type
        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }

        // Filter by status
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        // Filter by role
        if ($role = $request->input('role')) {
            $query->role($role);
        }

        $sortable = ['name', 'username', 'type', 'status', 'email'];
        $sort = $request->input('sort', 'name');
        $direction = $request->input('direction', 'asc');
        if (!in_array($sort, $sortable, true)) {
            $sort = 'name';
        }
        if (!in_array($direction, ['asc', 'desc'], true)) {
            $direction = 'asc';
        }

        $users = $query->orderBy($sort, $direction)->paginate(15)->withQueryString();
        $roles = Role::all();

        return view('admin.users.index', [
            'users' => $users,
            'roles' => $roles,
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    /**
     * Show the form for creating a new user.
     */
    public function create()
    {
        $roles = Role::all();
        return view('admin.users.create', compact('roles'));
    }

    /**
     * Store a newly created user.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users',
            'email' => 'nullable|email|max:255|unique:users',
            'password' => ['required', Password::defaults()],
            'type' => 'required|in:student,teacher,parent,staff,admin',
            'nis' => 'nullable|string|max:255',
            'nip' => 'nullable|string|max:255',
            'status' => 'required|in:active,suspended,pending',
            'roles' => 'required|array',
            'roles.*' => 'exists:roles,id',
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $roleIds = $validated['roles'];
        unset($validated['roles']);

        $user = User::create($validated);
        $user->roles()->sync($roleIds);

        return redirect()->route('admin.users.index')
            ->with('status', 'User berhasil dibuat.');
    }

    /**
     * Display the specified user.
     */
    public function show(User $user)
    {
        $user->load('roles', 'loginLogs');
        return view('admin.users.show', compact('user'));
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit(User $user)
    {
        $roles = Role::all();
        return view('admin.users.edit', compact('user', 'roles'));
    }

    /**
     * Update the specified user.
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username,' . $user->id,
            'email' => 'nullable|email|max:255|unique:users,email,' . $user->id,
            'type' => 'required|in:student,teacher,parent,staff,admin',
            'nis' => 'nullable|string|max:255',
            'nip' => 'nullable|string|max:255',
            'status' => 'required|in:active,suspended,pending',
            'roles' => 'required|array',
            'roles.*' => 'exists:roles,id',
        ]);

        $roleIds = $validated['roles'];
        unset($validated['roles']);

        $user->update($validated);
        $user->roles()->sync($roleIds);

        return redirect()->route('admin.users.index')
            ->with('status', 'User berhasil diperbarui.');
    }

    /**
     * Remove the specified user.
     */
    public function destroy(User $user)
    {
        // Prevent self-deletion
        if ($user->id === auth()->id()) {
            return back()->withErrors(['error' => 'Tidak dapat menghapus akun sendiri.']);
        }

        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('status', 'User berhasil dihapus.');
    }

    /**
     * Show reset password form.
     */
    public function showResetPassword(User $user)
    {
        return view('admin.users.reset-password', compact('user'));
    }

    /**
     * Reset user password.
     */
    public function resetPassword(Request $request, User $user)
    {
        $request->validate([
            'password' => ['required', Password::defaults()],
        ]);

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return redirect()->route('admin.users.show', $user)
            ->with('status', 'Password berhasil direset.');
    }

    /**
     * Show import form.
     */
    public function showImportForm()
    {
        return view('admin.users.import');
    }

    /**
     * Import users from file.
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:10240',
        ]);

        try {
            // Import logic will be implemented with Maatwebsite Excel
            // Excel::import(new UsersImport, $request->file('file'));
            
            return redirect()->route('admin.users.index')
                ->with('status', 'Import berhasil. (Import handler perlu diimplementasi)');
        } catch (\Exception $e) {
            return back()->withErrors(['file' => 'Gagal import: ' . $e->getMessage()]);
        }
    }

    /**
     * Bulk update selected users (roles/type).
     */
    public function bulkUpdate(Request $request)
    {
        $baseRules = [
            'user_ids' => ['required', 'array'],
            'user_ids.*' => ['exists:users,id'],
            'action' => ['required', 'in:roles_add,roles_replace,type_change'],
        ];

        $validated = $request->validate($baseRules);

        $action = $validated['action'];
        $users = User::whereIn('id', $validated['user_ids'])->get();

        if (in_array($action, ['roles_add', 'roles_replace'], true)) {
            $roleIds = $request->validate([
                'roles' => ['required', 'array'],
                'roles.*' => ['exists:roles,id'],
            ])['roles'];

            DB::transaction(function () use ($users, $roleIds, $action): void {
                foreach ($users as $user) {
                    if ($action === 'roles_add') {
                        $user->roles()->syncWithoutDetaching($roleIds);
                    } else {
                        $user->roles()->sync($roleIds);
                    }
                }
            });

            $message = $action === 'roles_add'
                ? 'Role berhasil ditambahkan ke user terpilih.'
                : 'Role user terpilih berhasil diperbarui.';

            return redirect()->route('admin.users.index')->with('status', $message);
        }

        $type = $request->validate([
            'type' => ['required', 'in:student,teacher,parent,staff,admin'],
        ])['type'];

        User::whereIn('id', $validated['user_ids'])->update(['type' => $type]);

        return redirect()->route('admin.users.index')
            ->with('status', 'Tipe user berhasil diperbarui.');
    }
}
