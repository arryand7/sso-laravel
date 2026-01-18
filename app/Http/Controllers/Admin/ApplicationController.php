<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
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
     * Display the specified application.
     */
    public function show(Application $application)
    {
        $application->load('roles');
        
        // Get users with access (first page)
        $users = $application->getUsersWithAccess([], 10);

        return view('admin.applications.show', [
            'application' => $application,
            'users' => $users,
        ]);
    }

    /**
     * Display users with access to this application.
     */
    public function users(Request $request, Application $application)
    {
        $filters = $request->only(['search', 'type', 'status']);
        $users = $application->getUsersWithAccess($filters, 15);

        return view('admin.applications.users', [
            'application' => $application,
            'users' => $users,
            'filters' => $filters,
        ]);
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
            'slug' => 'required|string|max:255|unique:applications,slug,' . $application->id . '|alpha_dash',
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
        if (!$file) {
            return $existingPath;
        }

        $image = imagecreatefromstring(file_get_contents($file->getRealPath()));
        if (!$image) {
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

        $filename = 'app-logos/' . Str::uuid() . '.png';
        Storage::disk('public')->put($filename, $pngData);

        if ($existingPath && Storage::disk('public')->exists($existingPath)) {
            Storage::disk('public')->delete($existingPath);
        }

        return $filename;
    }
}
