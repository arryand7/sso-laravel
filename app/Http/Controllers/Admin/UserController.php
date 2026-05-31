<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Maatwebsite\Excel\Concerns\ToArray;
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
        if (! in_array($sort, $sortable, true)) {
            $sort = 'name';
        }
        if (! in_array($direction, ['asc', 'desc'], true)) {
            $direction = 'asc';
        }

        $perPage = (int) $request->input('per_page', 15);
        if (! in_array($perPage, [10, 15, 25, 50, 100], true)) {
            $perPage = 15;
        }

        $users = $query->orderBy($sort, $direction)->paginate($perPage)->withQueryString();
        $roles = $this->roleOptionsForCurrentUser();

        return view('admin.users.index', [
            'users' => $users,
            'roles' => $roles,
            'sort' => $sort,
            'direction' => $direction,
            'perPage' => $perPage,
        ]);
    }

    /**
     * Show the form for creating a new user.
     */
    public function create()
    {
        $roles = $this->roleOptionsForCurrentUser();

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
        $this->ensureAssignableRoles($roleIds);
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
        $this->ensureCanManageUser($user);

        $roles = $this->roleOptionsForCurrentUser();

        return view('admin.users.edit', compact('user', 'roles'));
    }

    /**
     * Update the specified user.
     */
    public function update(Request $request, User $user)
    {
        $this->ensureCanManageUser($user);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username,'.$user->id,
            'email' => 'nullable|email|max:255|unique:users,email,'.$user->id,
            'type' => 'required|in:student,teacher,parent,staff,admin',
            'nis' => 'nullable|string|max:255',
            'nip' => 'nullable|string|max:255',
            'status' => 'required|in:active,suspended,pending',
            'roles' => 'required|array',
            'roles.*' => 'exists:roles,id',
        ]);

        $roleIds = $validated['roles'];
        $this->ensureAssignableRoles($roleIds);
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
        $this->ensureCanManageUser($user);

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
        $this->ensureCanManageUser($user);

        return view('admin.users.reset-password', compact('user'));
    }

    /**
     * Reset user password.
     */
    public function resetPassword(Request $request, User $user)
    {
        $this->ensureCanManageUser($user);

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
            $rows = $this->readImportRows($request->file('file'));
            $result = $this->importRows($rows);

            $message = "Import selesai. Dibuat: {$result['created']}, diperbarui: {$result['updated']}, dilewati: {$result['skipped']}.";

            if (! empty($result['errors'])) {
                return back()
                    ->withErrors(['file' => implode(' ', array_slice($result['errors'], 0, 5))])
                    ->with('status', $message);
            }

            return redirect()->route('admin.users.index')
                ->with('status', $message);
        } catch (\Exception $e) {
            return back()->withErrors(['file' => 'Gagal import: '.$e->getMessage()]);
        }
    }

    protected function readImportRows($file): Collection
    {
        $sheets = Excel::toArray(new class implements ToArray
        {
            public function array(array $array) {}
        }, $file);

        return collect($sheets[0] ?? []);
    }

    protected function importRows(Collection $rows): array
    {
        $result = [
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => [],
        ];

        if ($rows->isEmpty()) {
            throw new \RuntimeException('File import kosong.');
        }

        $header = $this->buildImportHeader((array) $rows->shift());
        if ($header === []) {
            throw new \RuntimeException('Header kolom tidak ditemukan.');
        }

        DB::transaction(function () use ($rows, $header, &$result): void {
            foreach ($rows->values() as $index => $row) {
                $rowNumber = $index + 2;
                $row = (array) $row;

                if ($this->isEmptyImportRow($row)) {
                    continue;
                }

                $mapped = $this->mapImportRow($row, $header, $rowNumber, $result);
                if ($mapped === null) {
                    $result['skipped']++;

                    continue;
                }

                $status = $this->upsertImportedUser($mapped);
                $result[$status]++;
            }
        });

        return $result;
    }

    protected function buildImportHeader(array $row): array
    {
        $header = [];

        foreach ($row as $index => $value) {
            $key = $this->normalizeImportHeader((string) $value);

            if ($key !== '') {
                $header[$key] = $index;
            }
        }

        return $header;
    }

    protected function mapImportRow(array $row, array $header, int $rowNumber, array &$result): ?array
    {
        $name = $this->importValue($row, $header, ['name', 'nama']);
        $username = $this->importValue($row, $header, ['username', 'user']);
        $email = $this->importValue($row, $header, ['email', 'emailaddress']);
        $type = strtolower($this->importValue($row, $header, ['type', 'tipe', 'jenis']) ?: 'staff');
        $nis = $this->importValue($row, $header, ['nis']);
        $nip = $this->importValue($row, $header, ['nip']);
        $role = $this->importValue($row, $header, ['role', 'roles']);
        $status = strtolower($this->importValue($row, $header, ['status']) ?: 'active');
        $password = $this->importValue($row, $header, ['password', 'katasandi']);

        $username = $username ?: ($nis ?: $nip);

        if ($name === '' || $username === '') {
            $result['errors'][] = "Baris {$rowNumber}: name dan username wajib diisi.";

            return null;
        }

        if ($email !== '' && ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $result['errors'][] = "Baris {$rowNumber}: email tidak valid.";

            return null;
        }

        if (! in_array($type, ['student', 'teacher', 'parent', 'staff', 'admin'], true)) {
            $result['errors'][] = "Baris {$rowNumber}: type tidak valid.";

            return null;
        }

        if (! in_array($status, ['active', 'suspended', 'pending'], true)) {
            $result['errors'][] = "Baris {$rowNumber}: status tidak valid.";

            return null;
        }

        return [
            'name' => $name,
            'username' => $username,
            'email' => $email !== '' ? $email : null,
            'type' => $type,
            'nis' => $nis !== '' ? $nis : null,
            'nip' => $nip !== '' ? $nip : null,
            'role_names' => $this->parseImportRoles($role ?: $type),
            'status' => $status,
            'password' => $password,
        ];
    }

    protected function upsertImportedUser(array $data): string
    {
        $user = User::where('username', $data['username'])->first();

        if (! $user && $data['email']) {
            $user = User::where('email', $data['email'])->first();
        }

        if ($user) {
            $this->ensureCanManageUser($user);
        }
        $this->ensureAssignableRoleNames($data['role_names']);

        $payload = [
            'name' => $data['name'],
            'username' => $data['username'],
            'email' => $data['email'],
            'type' => $data['type'],
            'nis' => $data['nis'],
            'nip' => $data['nip'],
            'status' => $data['status'],
        ];

        if (! $user) {
            $payload['password'] = Hash::make($data['password'] ?: Str::random(12));
            $user = User::create($payload);
            $status = 'created';
        } else {
            if ($data['password'] !== '') {
                $payload['password'] = Hash::make($data['password']);
            }

            $user->update($payload);
            $status = 'updated';
        }

        $roleIds = Role::whereIn('name', $data['role_names'])->pluck('id');
        if ($roleIds->isNotEmpty()) {
            $user->roles()->sync($roleIds);
        }

        return $status;
    }

    protected function importValue(array $row, array $header, array $keys): string
    {
        foreach ($keys as $key) {
            $normalized = $this->normalizeImportHeader($key);

            if (array_key_exists($normalized, $header)) {
                return trim((string) ($row[$header[$normalized]] ?? ''));
            }
        }

        return '';
    }

    protected function parseImportRoles(string $value): array
    {
        return collect(preg_split('/[,|;]/', $value) ?: [])
            ->map(fn (string $role): string => strtolower(trim($role)))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    protected function isEmptyImportRow(array $row): bool
    {
        return count(array_filter($row, fn ($value): bool => trim((string) $value) !== '')) === 0;
    }

    protected function normalizeImportHeader(string $value): string
    {
        return preg_replace('/[^a-z0-9]+/', '', strtolower(trim($value))) ?? '';
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
        $users->each(fn (User $user) => $this->ensureCanManageUser($user));

        if (in_array($action, ['roles_add', 'roles_replace'], true)) {
            $roleIds = $request->validate([
                'roles' => ['required', 'array'],
                'roles.*' => ['exists:roles,id'],
            ])['roles'];
            $this->ensureAssignableRoles($roleIds);

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

    protected function roleOptionsForCurrentUser()
    {
        $query = Role::query()->orderBy('name');

        if (! auth()->user()?->hasRole('superadmin')) {
            $query->where('name', '!=', 'superadmin');
        }

        return $query->get();
    }

    protected function ensureAssignableRoles(array $roleIds): void
    {
        if (auth()->user()?->hasRole('superadmin')) {
            return;
        }

        $containsSuperadmin = Role::whereIn('id', $roleIds)
            ->where('name', 'superadmin')
            ->exists();

        abort_if($containsSuperadmin, 403, 'Anda tidak memiliki izin untuk memberikan role superadmin.');
    }

    protected function ensureAssignableRoleNames(array $roleNames): void
    {
        if (auth()->user()?->hasRole('superadmin')) {
            return;
        }

        abort_if(in_array('superadmin', $roleNames, true), 403, 'Anda tidak memiliki izin untuk memberikan role superadmin.');
    }

    protected function ensureCanManageUser(User $user): void
    {
        if (auth()->user()?->hasRole('superadmin')) {
            return;
        }

        abort_if($user->hasRole('superadmin'), 403, 'Akun superadmin hanya dapat dikelola oleh superadmin.');
    }
}
