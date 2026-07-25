<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\User;
use App\Services\ApplicationAccessService;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class ApplicationController extends Controller
{
    /**
     * Display a listing of applications.
     */
    public function index(Request $request)
    {
        $query = Application::with('roles');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('base_url', 'like', "%{$search}%");
            });
        }

        if ($category = $request->input('category')) {
            $query->where('category', $category);
        }

        $applications = $query->orderBy('name')->paginate(15)->withQueryString();
        $categories = Application::distinct()->pluck('category')->filter();

        return view('admin.applications.index', [
            'applications' => $applications,
            'categories' => $categories,
        ]);
    }

    /**
     * Show the form for creating a new application.
     */
    public function create()
    {
        $roles = Role::all();

        return view('admin.applications.create', compact('roles'));
    }

    /**
     * Store a newly created application.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:applications|alpha_dash',
            'base_url' => 'required|url|max:255',
            'redirect_uri' => 'required|string',
            'sso_login_url' => 'nullable|url|max:255',
            'category' => 'nullable|string|max:255',
            'logo' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:2048',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'roles' => 'required|array',
            'roles.*' => 'exists:roles,id',
        ]);

        // Generate OAuth credentials
        $credentials = Application::generateCredentials();
        $validated = array_merge($validated, $credentials);
        $validated['is_active'] = $request->boolean('is_active', true);

        $roleIds = $validated['roles'];
        unset($validated['roles']);

        $logoPath = $this->storeLogo($request->file('logo'));
        if ($logoPath) {
            $validated['logo_path'] = $logoPath;
        }

        $application = Application::create($validated);
        $application->roles()->sync($roleIds);
        $application->syncPassportClient();

        return redirect()->route('admin.applications.show', $application)
            ->with('status', 'Aplikasi berhasil dibuat.')
            ->with('client_secret', $credentials['client_secret']); // Show once
    }

    /**
     * Display the specified application with user accesses & capabilities.
     */
    public function show(Application $application, Request $request, ApplicationAccessService $accessService)
    {
        $application->load('roles');

        $filters = $request->only(['search', 'type', 'application_role', 'status', 'sync_status']);
        $userAccesses = $accessService->getApplicationUsers($application, $filters, 15);
        $allUsers = User::active()->orderBy('name')->get();

        return view('admin.applications.show', [
            'application' => $application,
            'userAccesses' => $userAccesses,
            'allUsers' => $allUsers,
            'filters' => $filters,
        ]);
    }

    /**
     * Display users with access to this application.
     */
    public function users(Request $request, Application $application, ApplicationAccessService $accessService)
    {
        $filters = $request->only(['search', 'type', 'application_role', 'status', 'sync_status']);
        $userAccesses = $accessService->getApplicationUsers($application, $filters, 20);

        return view('admin.applications.users', [
            'application' => $application,
            'userAccesses' => $userAccesses,
            'filters' => $filters,
        ]);
    }

    /**
     * Grant access to a user for this application.
     */
    public function grantUserAccess(Request $request, Application $application, ApplicationAccessService $accessService)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'application_role' => 'nullable|string|max:255',
            'status' => 'required|in:active,inactive',
        ]);

        $user = User::findOrFail($validated['user_id']);
        $accessService->grantAccess(
            $user,
            $application,
            $validated['application_role'] ?? null,
            $validated['status'],
            auth()->id()
        );

        return redirect()->route('admin.applications.show', $application)
            ->with('status', "Akses untuk {$user->name} berhasil diberikan.");
    }

    /**
     * Bulk grant access to multiple users for this application.
     */
    public function bulkGrantUserAccess(Request $request, Application $application, ApplicationAccessService $accessService)
    {
        $validated = $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
            'application_role' => 'nullable|string|max:255',
        ]);

        $count = $accessService->bulkGrantAccess(
            $validated['user_ids'],
            $application,
            $validated['application_role'] ?? null,
            auth()->id()
        );

        return redirect()->route('admin.applications.show', $application)
            ->with('status', "Akses berhasil diberikan kepada {$count} user.");
    }

    /**
     * Update access role/status for a user on this application.
     */
    public function updateUserAccess(Request $request, Application $application, User $user, ApplicationAccessService $accessService)
    {
        $validated = $request->validate([
            'application_role' => 'nullable|string|max:255',
            'status' => 'required|in:active,inactive,revoked',
        ]);

        $validated['updated_by'] = auth()->id();
        $accessService->updateAccess($user, $application, $validated);

        return redirect()->route('admin.applications.show', $application)
            ->with('status', "Akses user {$user->name} berhasil diperbarui.");
    }

    /**
     * Revoke access for a user on this application.
     */
    public function revokeUserAccess(Request $request, Application $application, User $user, ApplicationAccessService $accessService)
    {
        $accessService->revokeAccess($user, $application, auth()->id());

        return redirect()->route('admin.applications.show', $application)
            ->with('status', "Akses user {$user->name} berhasil dicabut.");
    }

    /**
     * Bulk revoke access for multiple users on this application.
     */
    public function bulkRevokeUserAccess(Request $request, Application $application, ApplicationAccessService $accessService)
    {
        $validated = $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
        ]);

        $count = $accessService->bulkRevokeAccess($validated['user_ids'], $application, auth()->id());

        return redirect()->route('admin.applications.show', $application)
            ->with('status', "Akses berhasil dicabut dari {$count} user.");
    }

    /**
     * Update application sync capabilities & settings.
     */
    public function updateCapabilities(Request $request, Application $application)
    {
        $validated = $request->validate([
            'sync_enabled' => 'boolean',
            'api_rate_limit' => 'required|integer|min:1|max:1000',
            'capabilities' => 'required|array',
            'capabilities.create_user' => 'boolean',
            'capabilities.update_user' => 'boolean',
            'capabilities.suspend_user' => 'boolean',
            'capabilities.reactivate_user' => 'boolean',
            'capabilities.sync_photo' => 'boolean',
            'capabilities.sync_qr' => 'boolean',
            'capabilities.sync_role' => 'boolean',
        ]);

        $capabilities = [
            'create_user' => $request->boolean('capabilities.create_user'),
            'update_user' => $request->boolean('capabilities.update_user'),
            'suspend_user' => $request->boolean('capabilities.suspend_user'),
            'reactivate_user' => $request->boolean('capabilities.reactivate_user'),
            'sync_photo' => $request->boolean('capabilities.sync_photo'),
            'sync_qr' => $request->boolean('capabilities.sync_qr'),
            'sync_role' => $request->boolean('capabilities.sync_role'),
        ];

        $application->update([
            'sync_enabled' => $request->boolean('sync_enabled'),
            'api_rate_limit' => $validated['api_rate_limit'],
            'sync_capabilities' => $capabilities,
        ]);

        return redirect()->route('admin.applications.show', $application)
            ->with('status', 'Konfigurasi sinkronisasi & capabilities aplikasi berhasil diperbarui.');
    }

    /**
     * Show the form for editing the specified application.
     */
    public function edit(Application $application)
    {
        $roles = Role::all();

        return view('admin.applications.edit', compact('application', 'roles'));
    }

    /**
     * Update the specified application.
     */
    public function update(Request $request, Application $application)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:applications,slug,'.$application->id.'|alpha_dash',
            'base_url' => 'required|url|max:255',
            'redirect_uri' => 'required|string',
            'sso_login_url' => 'nullable|url|max:255',
            'category' => 'nullable|string|max:255',
            'logo' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:2048',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'roles' => 'required|array',
            'roles.*' => 'exists:roles,id',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        $roleIds = $validated['roles'];
        unset($validated['roles']);

        if ($request->hasFile('logo')) {
            $validated['logo_path'] = $this->storeLogo($request->file('logo'), $application->logo_path);
        }

        $application->update($validated);
        $application->roles()->sync($roleIds);
        $application->syncPassportClient();

        return redirect()->route('admin.applications.show', $application)
            ->with('status', 'Aplikasi berhasil diperbarui.');
    }

    /**
     * Remove the specified application.
     */
    public function destroy(Application $application)
    {
        if ($application->logo_path && Storage::disk('public')->exists($application->logo_path)) {
            Storage::disk('public')->delete($application->logo_path);
        }

        $application->passportClient()->delete();
        $application->delete();

        return redirect()->route('admin.applications.index')
            ->with('status', 'Aplikasi berhasil dihapus.');
    }

    /**
     * Regenerate client secret.
     */
    public function regenerateSecret(Application $application)
    {
        $newSecret = Str::random(64);
        $application->update(['client_secret' => $newSecret]);
        $application->syncPassportClient();

        return redirect()->route('admin.applications.show', $application)
            ->with('status', 'Client secret berhasil digenerate ulang.')
            ->with('client_secret', $newSecret); // Show once
    }

    protected function storeLogo(?UploadedFile $file, ?string $existingPath = null): ?string
    {
        if (! $file) {
            return $existingPath;
        }

        $image = imagecreatefromstring(file_get_contents($file->getRealPath()));
        if (! $image) {
            return $existingPath;
        }

        $width = imagesx($image);
        $height = imagesy($image);
        $side = min($width, $height);
        $srcX = (int) floor(($width - $side) / 2);
        $srcY = (int) floor(($height - $side) / 2);

        $targetSize = 256;
        $canvas = imagecreatetruecolor($targetSize, $targetSize);
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
        imagefill($canvas, 0, 0, $transparent);

        imagecopyresampled($canvas, $image, 0, 0, $srcX, $srcY, $targetSize, $targetSize, $side, $side);

        ob_start();
        imagepng($canvas);
        $pngData = (string) ob_get_clean();

        imagedestroy($image);
        imagedestroy($canvas);

        $filename = 'app-logos/'.Str::uuid().'.png';
        Storage::disk('public')->put($filename, $pngData);

        if ($existingPath && Storage::disk('public')->exists($existingPath)) {
            Storage::disk('public')->delete($existingPath);
        }

        return $filename;
    }
}
