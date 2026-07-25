<?php

namespace App\Http\Controllers\Admin;

use App\Exports\UserImportTemplateExport;
use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\User;
use App\Models\UserImportBatch;
use App\Services\ApplicationAccessService;
use App\Services\UserImportService;
use App\Services\UserPhotoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
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
                    ->orWhere('nip', 'like', "%{$search}%")
                    ->orWhere('qr_code', 'like', "%{$search}%");
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
    public function store(Request $request, UserPhotoService $photoService)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users',
            'email' => 'nullable|email|max:255|unique:users',
            'password' => ['required', Password::defaults()],
            'type' => 'required|in:student,teacher,parent,staff,admin',
            'nis' => 'nullable|string|max:255',
            'nip' => 'nullable|string|max:255',
            'qr_code' => 'nullable|string|max:255|unique:users,qr_code',
            'status' => 'required|in:active,suspended,pending',
            'roles' => 'required|array',
            'roles.*' => 'exists:roles,id',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:10240',
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $roleIds = $validated['roles'];
        $this->ensureAssignableRoles($roleIds);
        unset($validated['roles'], $validated['photo']);

        $user = User::create($validated);
        $user->roles()->sync($roleIds);

        if ($request->hasFile('photo')) {
            $photoService->store($user, $request->file('photo'));
        }

        return redirect()->route('admin.users.index')
            ->with('status', 'User berhasil dibuat.');
    }

    /**
     * Display the specified user.
     */
    public function show(User $user, ApplicationAccessService $accessService)
    {
        $user->load('roles', 'loginLogs');
        $applicationAccesses = $accessService->getUserAccesses($user);
        $availableApplications = Application::where('is_active', true)->orderBy('name')->get();

        return view('admin.users.show', compact('user', 'applicationAccesses', 'availableApplications'));
    }

    /**
     * Grant application access to user.
     */
    public function grantApplicationAccess(Request $request, User $user, ApplicationAccessService $accessService)
    {
        $this->ensureCanManageUser($user);

        $validated = $request->validate([
            'application_id' => 'required|exists:applications,id',
            'application_role' => 'nullable|string|max:255',
            'status' => 'required|in:active,inactive',
        ]);

        $app = Application::findOrFail($validated['application_id']);
        $accessService->grantAccess(
            $user,
            $app,
            $validated['application_role'] ?? null,
            $validated['status'],
            auth()->id()
        );

        return redirect()->route('admin.users.show', $user)
            ->with('status', "Akses aplikasi {$app->name} berhasil diberikan.");
    }

    /**
     * Update application access for user.
     */
    public function updateApplicationAccess(Request $request, User $user, Application $application, ApplicationAccessService $accessService)
    {
        $this->ensureCanManageUser($user);

        $validated = $request->validate([
            'application_role' => 'nullable|string|max:255',
            'status' => 'required|in:active,inactive,revoked',
        ]);

        $validated['updated_by'] = auth()->id();
        $accessService->updateAccess($user, $application, $validated);

        return redirect()->route('admin.users.show', $user)
            ->with('status', "Akses aplikasi {$application->name} berhasil diperbarui.");
    }

    /**
     * Revoke application access for user.
     */
    public function revokeApplicationAccess(Request $request, User $user, Application $application, ApplicationAccessService $accessService)
    {
        $this->ensureCanManageUser($user);

        $accessService->revokeAccess($user, $application, auth()->id());

        return redirect()->route('admin.users.show', $user)
            ->with('status', "Akses aplikasi {$application->name} berhasil dicabut.");
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
    public function update(Request $request, User $user, UserPhotoService $photoService)
    {
        $this->ensureCanManageUser($user);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username,'.$user->id,
            'email' => 'nullable|email|max:255|unique:users,email,'.$user->id,
            'type' => 'required|in:student,teacher,parent,staff,admin',
            'nis' => 'nullable|string|max:255',
            'nip' => 'nullable|string|max:255',
            'qr_code' => 'nullable|string|max:255|unique:users,qr_code,'.$user->id,
            'status' => 'required|in:active,suspended,pending',
            'roles' => 'required|array',
            'roles.*' => 'exists:roles,id',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:10240',
        ]);

        $roleIds = $validated['roles'];
        $this->ensureAssignableRoles($roleIds);
        unset($validated['roles'], $validated['photo']);

        $user->update($validated);
        $user->roles()->sync($roleIds);

        if ($request->hasFile('photo')) {
            $photoService->store($user, $request->file('photo'));
        }

        return redirect()->route('admin.users.index')
            ->with('status', 'User berhasil diperbarui.');
    }

    /**
     * Remove user photo.
     */
    public function destroyPhoto(User $user, UserPhotoService $photoService)
    {
        $this->ensureCanManageUser($user);
        $photoService->destroy($user);

        return back()->with('status', 'Foto profil berhasil dihapus.');
    }

    /**
     * Remove the specified user.
     */
    public function destroy(User $user, UserPhotoService $photoService)
    {
        $this->ensureCanManageUser($user);

        // Prevent self-deletion
        if ($user->id === auth()->id()) {
            return back()->withErrors(['error' => 'Tidak dapat menghapus akun sendiri.']);
        }

        $photoService->destroy($user);
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

    // ==========================================
    // IMPORT USER METHODS
    // ==========================================

    /**
     * Download official Excel template for import.
     */
    public function downloadImportTemplate()
    {
        return (new UserImportTemplateExport)->download('sabira-user-import-template.xlsx');
    }

    /**
     * Show import form & batch history.
     */
    public function showImportForm()
    {
        $batches = UserImportBatch::with('uploader')
            ->latest()
            ->paginate(10);

        return view('admin.users.import', compact('batches'));
    }

    /**
     * Upload file and create import batch.
     */
    public function uploadImport(Request $request, UserImportService $importService)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx|max:10240',
            'mode' => 'nullable|in:create_only,update_only,create_and_update',
        ]);

        $mode = $request->input('mode', 'create_only');

        if ($mode === 'create_and_update' && ! auth()->user()?->hasRole('superadmin')) {
            abort(403, 'Mode create and update hanya tersedia untuk superadmin.');
        }

        try {
            $batch = $importService->upload(
                $request->file('file'),
                auth()->id(),
                $mode
            );

            // Automatically validate after upload
            $importService->validate($batch);

            return redirect()->route('admin.users.import.show', $batch)
                ->with('status', 'File berhasil diupload dan divalidasi.');
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['file' => $e->getMessage()]);
        } catch (\Exception $e) {
            return back()->withErrors(['file' => 'Gagal mengupload file: '.$e->getMessage()]);
        }
    }

    /**
     * Show batch review and status.
     */
    public function showImportBatch(UserImportBatch $batch)
    {
        $batch->load('uploader');
        $rows = $batch->rows()->paginate(50);
        $invalidRows = $batch->rows()->where('status', 'invalid')->get();

        return view('admin.users.import-show', compact('batch', 'rows', 'invalidRows'));
    }

    /**
     * Trigger re-validation for a batch.
     */
    public function validateImportBatch(UserImportBatch $batch, UserImportService $importService)
    {
        if (! $batch->isEditable()) {
            return back()->withErrors(['error' => 'Batch tidak dalam status yang dapat divalidasi.']);
        }

        $importService->validate($batch);

        return redirect()->route('admin.users.import.show', $batch)
            ->with('status', 'Validasi ulang selesai.');
    }

    /**
     * Commit a ready import batch.
     */
    public function commitImportBatch(UserImportBatch $batch, UserImportService $importService)
    {
        if (! $batch->isCommittable()) {
            return back()->withErrors(['error' => 'Batch belum siap untuk diimport. Pastikan seluruh baris valid.']);
        }

        try {
            $importService->commit($batch);

            return redirect()->route('admin.users.import.show', $batch)
                ->with('status', "Import berhasil! {$batch->created_rows} user dibuat, {$batch->updated_rows} user diperbarui.");
        } catch (\Exception $e) {
            return redirect()->route('admin.users.import.show', $batch)
                ->withErrors(['error' => 'Gagal melakukan commit import: '.$e->getMessage()]);
        }
    }

    /**
     * Download error report XLSX.
     */
    public function downloadImportReport(UserImportBatch $batch, UserImportService $importService)
    {
        if ($batch->invalid_rows === 0 && $batch->status !== 'validation_failed') {
            return back()->withErrors(['error' => 'Tidak ada laporan error untuk batch ini.']);
        }

        $reportPath = $importService->generateReport($batch);

        return Storage::disk('local')->download(
            $reportPath,
            'laporan-error-import-'.$batch->uuid.'.xlsx'
        );
    }

    /**
     * Cancel batch import.
     */
    public function cancelImportBatch(UserImportBatch $batch, UserImportService $importService)
    {
        try {
            $importService->cancel($batch);

            return redirect()->route('admin.users.import')
                ->with('status', 'Batch import berhasil dibatalkan.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Bulk update selected users (roles/type/deactivate/delete).
     */
    public function bulkUpdate(Request $request, UserPhotoService $photoService)
    {
        $baseRules = [
            'user_ids' => ['required', 'array'],
            'user_ids.*' => ['exists:users,id'],
            'action' => ['required', 'in:roles_add,roles_replace,type_change,deactivate_selected,delete_selected'],
        ];

        $validated = $request->validate($baseRules);

        $action = $validated['action'];
        $users = User::whereIn('id', $validated['user_ids'])->get();
        $users->each(fn (User $user) => $this->ensureCanManageUser($user));

        if (in_array($action, ['deactivate_selected', 'delete_selected'], true)) {
            abort_if(! auth()->user()?->hasRole('superadmin'), 403, 'Aksi ini hanya tersedia untuk superadmin.');
        }

        if ($action === 'deactivate_selected') {
            $count = 0;
            DB::transaction(function () use ($users, &$count): void {
                foreach ($users as $user) {
                    if ($user->id === auth()->id()) {
                        continue;
                    }
                    $user->update(['status' => 'suspended']);
                    $count++;
                }
            });

            return redirect()->route('admin.users.index')
                ->with('status', "{$count} user berhasil dinonaktifkan.");
        }

        if ($action === 'delete_selected') {
            $count = 0;
            DB::transaction(function () use ($users, $photoService, &$count): void {
                foreach ($users as $user) {
                    if ($user->id === auth()->id()) {
                        continue;
                    }
                    $photoService->destroy($user);
                    $user->delete();
                    $count++;
                }
            });

            return redirect()->route('admin.users.index')
                ->with('status', "{$count} user berhasil dihapus.");
        }

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
