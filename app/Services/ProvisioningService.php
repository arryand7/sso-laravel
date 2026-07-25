<?php

namespace App\Services;

use App\Models\Application;
use App\Models\ApplicationUserSyncStatus;
use App\Models\User;
use App\Models\UserApplicationAccess;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

class ProvisioningService
{
    /**
     * Schema version for Provisioning API.
     */
    public const SCHEMA_VERSION = '1.0';

    /**
     * Get active canonical users for a connected application.
     */
    public function getCanonicalUsers(Application $app): array
    {
        $accesses = UserApplicationAccess::where('application_id', $app->id)
            ->where('status', 'active')
            ->whereHas('user', function ($q) {
                $q->where('status', 'active');
            })
            ->with('user')
            ->get();

        $users = [];
        foreach ($accesses as $access) {
            if ($access->user) {
                $users[] = $this->buildCanonicalUser($access->user, $app, $access);
            }
        }

        return [
            'schema_version' => self::SCHEMA_VERSION,
            'application' => [
                'uuid' => $app->client_id,
                'slug' => $app->slug,
                'name' => $app->name,
            ],
            'generated_at' => now()->toIso8601String(),
            'total_users' => count($users),
            'users' => $users,
        ];
    }

    /**
     * Get a single canonical user by UUID for a connected application.
     */
    public function getCanonicalUserByUuid(Application $app, string $uuid): ?array
    {
        $user = User::where('uuid', $uuid)->first();
        if (! $user) {
            return null;
        }

        $access = UserApplicationAccess::where('application_id', $app->id)
            ->where('user_id', $user->id)
            ->first();

        if (! $access || $access->status !== 'active' || $user->status !== 'active') {
            return null;
        }

        return [
            'schema_version' => self::SCHEMA_VERSION,
            'application' => [
                'uuid' => $app->client_id,
                'slug' => $app->slug,
                'name' => $app->name,
            ],
            'generated_at' => now()->toIso8601String(),
            'user' => $this->buildCanonicalUser($user, $app, $access),
        ];
    }

    /**
     * Get changed users since a given ISO timestamp.
     */
    public function getChangedUsers(Application $app, ?string $since = null): array
    {
        $query = UserApplicationAccess::where('application_id', $app->id)
            ->with('user');

        if ($since) {
            try {
                $sinceTime = \Carbon\Carbon::parse($since);
                $query->where(function ($q) use ($sinceTime) {
                    $q->where('updated_at', '>=', $sinceTime)
                        ->orWhereHas('user', function ($uq) use ($sinceTime) {
                            $uq->where('updated_at', '>=', $sinceTime);
                        });
                });
            } catch (\Exception $e) {
                // Ignore invalid date, return all
            }
        }

        $accesses = $query->get();

        $users = [];
        foreach ($accesses as $access) {
            if ($access->user) {
                $users[] = $this->buildCanonicalUser($access->user, $app, $access);
            }
        }

        return [
            'schema_version' => self::SCHEMA_VERSION,
            'application' => [
                'uuid' => $app->client_id,
                'slug' => $app->slug,
                'name' => $app->name,
            ],
            'since' => $since,
            'generated_at' => now()->toIso8601String(),
            'total_changes' => count($users),
            'users' => $users,
        ];
    }

    /**
     * Record sync results reported back by a connected application.
     */
    public function recordSyncResults(Application $app, array $syncItems): array
    {
        $processed = 0;
        $failed = 0;

        foreach ($syncItems as $item) {
            $gateUserUuid = $item['gate_user_uuid'] ?? $item['uuid'] ?? null;
            if (! $gateUserUuid) {
                $failed++;

                continue;
            }

            $user = User::where('uuid', $gateUserUuid)->first();
            if (! $user) {
                $failed++;

                continue;
            }

            $status = $item['status'] ?? 'matched';
            $validStatuses = ['matched', 'needs_update', 'missing_in_application', 'suspended', 'conflict', 'failed', 'never_synced'];
            if (! in_array($status, $validStatuses, true)) {
                $status = 'failed';
            }

            ApplicationUserSyncStatus::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'application_id' => $app->id,
                ],
                [
                    'status' => $status,
                    'external_user_id' => $item['external_user_id'] ?? null,
                    'last_sync_at' => now(),
                    'last_reported_at' => now(),
                    'last_error_code' => $item['error_code'] ?? null,
                    'last_error_message' => $item['error_message'] ?? null,
                    'local_checksum' => $item['local_checksum'] ?? null,
                    'gate_checksum' => $item['gate_checksum'] ?? null,
                ]
            );

            // Update last_synced_at in access relation
            UserApplicationAccess::where('user_id', $user->id)
                ->where('application_id', $app->id)
                ->update(['last_synced_at' => now()]);

            $processed++;
        }

        return [
            'status' => 'success',
            'processed' => $processed,
            'failed' => $failed,
            'timestamp' => now()->toIso8601String(),
        ];
    }

    /**
     * Build canonical user array respecting application capabilities.
     */
    public function buildCanonicalUser(User $user, Application $app, UserApplicationAccess $access): array
    {
        $data = [
            'uuid' => $user->uuid,
            'username' => $user->username,
            'name' => $user->name,
            'email' => $user->email,
            'type' => $user->type,
            'nis' => $user->nis,
            'nip' => $user->nip,
            'status' => $user->status,
            'application_access' => [
                'status' => $access->status,
                'role' => $app->supportsRole() ? $access->application_role : null,
            ],
            'updated_at' => $user->updated_at->toIso8601String(),
        ];

        // Photo handling based on capability
        if ($app->supportsPhoto() && $user->photo_path && Storage::disk('public')->exists($user->photo_path)) {
            $fullPath = Storage::disk('public')->path($user->photo_path);
            $checksum = file_exists($fullPath) ? hash_file('sha256', $fullPath) : null;
            $data['photo'] = [
                'available' => true,
                'url' => URL::temporarySignedRoute(
                    'api.provisioning.photo',
                    now()->addMinutes(30),
                    ['user' => $user->id]
                ),
                'checksum' => $checksum,
            ];
        } else {
            $data['photo'] = [
                'available' => false,
                'url' => null,
                'checksum' => null,
            ];
        }

        // QR Code handling based on capability
        if ($app->supportsQr()) {
            $data['qr_code'] = $user->qr_code;
        }

        return $data;
    }
}
