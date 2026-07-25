<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\User;
use App\Services\ProvisioningService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProvisioningController extends Controller
{
    /**
     * Get current authenticated application details & capabilities.
     */
    public function me(Request $request)
    {
        /** @var Application $app */
        $app = $request->attributes->get('provisioning_app');

        return response()->json([
            'schema_version' => ProvisioningService::SCHEMA_VERSION,
            'application' => [
                'name' => $app->name,
                'slug' => $app->slug,
                'client_id' => $app->client_id,
                'category' => $app->category,
                'is_active' => $app->is_active,
                'sync_enabled' => $app->sync_enabled,
                'capabilities' => $app->getEffectiveCapabilities(),
                'api_rate_limit' => $app->api_rate_limit,
            ],
        ]);
    }

    /**
     * Get canonical list of active users for this application.
     */
    public function index(Request $request, ProvisioningService $service)
    {
        /** @var Application $app */
        $app = $request->attributes->get('provisioning_app');

        return response()->json($service->getCanonicalUsers($app));
    }

    /**
     * Get single canonical user by Gate User UUID.
     */
    public function show(Request $request, string $uuid, ProvisioningService $service)
    {
        /** @var Application $app */
        $app = $request->attributes->get('provisioning_app');

        $result = $service->getCanonicalUserByUuid($app, $uuid);

        if (! $result) {
            return response()->json([
                'error' => 'not_found',
                'message' => 'User tidak ditemukan atau tidak memiliki akses aktif ke aplikasi ini.',
            ], 404);
        }

        return response()->json($result);
    }

    /**
     * Get user changes since a given timestamp.
     */
    public function changes(Request $request, ProvisioningService $service)
    {
        /** @var Application $app */
        $app = $request->attributes->get('provisioning_app');
        $since = $request->query('since');

        return response()->json($service->getChangedUsers($app, $since));
    }

    /**
     * Serve temporary signed photo download.
     */
    public function photo(Request $request, User $user)
    {
        if (! $request->hasValidSignature()) {
            abort(401, 'URL foto tidak valid atau sudah kadaluarsa.');
        }

        if (! $user->photo_path || ! Storage::disk('public')->exists($user->photo_path)) {
            abort(404, 'Foto profil tidak ditemukan.');
        }

        return Storage::disk('public')->response($user->photo_path);
    }

    /**
     * Record sync results reported back by the application.
     */
    public function syncResults(Request $request, ProvisioningService $service)
    {
        /** @var Application $app */
        $app = $request->attributes->get('provisioning_app');

        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.gate_user_uuid' => 'nullable|string',
            'items.*.uuid' => 'nullable|string',
            'items.*.status' => 'required|string',
            'items.*.external_user_id' => 'nullable|string',
            'items.*.error_code' => 'nullable|string',
            'items.*.error_message' => 'nullable|string',
            'items.*.local_checksum' => 'nullable|string',
            'items.*.gate_checksum' => 'nullable|string',
        ]);

        $result = $service->recordSyncResults($app, $validated['items']);

        return response()->json($result);
    }
}
