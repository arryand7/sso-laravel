<?php

namespace App\Services;

use App\Models\Application;
use App\Models\ApplicationUserSyncStatus;
use App\Models\User;
use App\Models\UserApplicationAccess;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ApplicationAccessService
{
    /**
     * Grant access for a user to an application.
     */
    public function grantAccess(
        User $user,
        Application $app,
        ?string $role = null,
        string $status = 'active',
        ?int $grantedBy = null
    ): UserApplicationAccess {
        return DB::transaction(function () use ($user, $app, $role, $status, $grantedBy) {
            $access = UserApplicationAccess::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'application_id' => $app->id,
                ],
                [
                    'application_role' => $role ? trim($role) : null,
                    'status' => $status,
                    'granted_at' => now(),
                    'granted_by' => $grantedBy,
                    'revoked_at' => null,
                    'revoked_by' => null,
                ]
            );

            // Initialize sync status if not exists
            ApplicationUserSyncStatus::firstOrCreate(
                [
                    'user_id' => $user->id,
                    'application_id' => $app->id,
                ],
                [
                    'status' => 'never_synced',
                ]
            );

            return $access;
        });
    }

    /**
     * Grant access to multiple users for an application.
     */
    public function bulkGrantAccess(
        array $userIds,
        Application $app,
        ?string $role = null,
        ?int $grantedBy = null
    ): int {
        $count = 0;
        $users = User::whereIn('id', $userIds)->get();

        foreach ($users as $user) {
            $this->grantAccess($user, $app, $role, 'active', $grantedBy);
            $count++;
        }

        return $count;
    }

    /**
     * Update application access for a user.
     */
    public function updateAccess(User $user, Application $app, array $data): UserApplicationAccess
    {
        $access = UserApplicationAccess::where('user_id', $user->id)
            ->where('application_id', $app->id)
            ->firstOrFail();

        $updateData = [];
        if (array_key_exists('application_role', $data)) {
            $updateData['application_role'] = $data['application_role'] ? trim($data['application_role']) : null;
        }
        if (isset($data['status'])) {
            $updateData['status'] = $data['status'];
            if ($data['status'] === 'revoked') {
                $updateData['revoked_at'] = now();
                $updateData['revoked_by'] = $data['updated_by'] ?? null;
            }
        }

        $access->update($updateData);

        return $access;
    }

    /**
     * Revoke access for a user to an application.
     */
    public function revokeAccess(User $user, Application $app, ?int $revokedBy = null): UserApplicationAccess
    {
        $access = UserApplicationAccess::firstOrCreate(
            [
                'user_id' => $user->id,
                'application_id' => $app->id,
            ],
            [
                'granted_at' => now(),
            ]
        );

        $access->update([
            'status' => 'revoked',
            'revoked_at' => now(),
            'revoked_by' => $revokedBy,
        ]);

        return $access;
    }

    /**
     * Bulk revoke access for multiple users to an application.
     */
    public function bulkRevokeAccess(array $userIds, Application $app, ?int $revokedBy = null): int
    {
        $count = 0;
        $users = User::whereIn('id', $userIds)->get();

        foreach ($users as $user) {
            $this->revokeAccess($user, $app, $revokedBy);
            $count++;
        }

        return $count;
    }

    /**
     * Deactivate (suspend) access for a user to an application.
     */
    public function deactivateAccess(User $user, Application $app): UserApplicationAccess
    {
        $access = UserApplicationAccess::where('user_id', $user->id)
            ->where('application_id', $app->id)
            ->firstOrFail();

        $access->update(['status' => 'inactive']);

        return $access;
    }

    /**
     * Get all application accesses for a user with relations and sync status.
     */
    public function getUserAccesses(User $user)
    {
        return Application::where('is_active', true)
            ->get()
            ->map(function (Application $app) use ($user) {
                $access = UserApplicationAccess::where('user_id', $user->id)
                    ->where('application_id', $app->id)
                    ->with(['grantedBy', 'revokedBy'])
                    ->first();

                $syncStatus = ApplicationUserSyncStatus::where('user_id', $user->id)
                    ->where('application_id', $app->id)
                    ->first();

                return [
                    'application' => $app,
                    'access' => $access,
                    'sync_status' => $syncStatus,
                ];
            });
    }

    /**
     * Get paginated users for an application with search/filter for application detail view.
     */
    public function getApplicationUsers(Application $app, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = UserApplicationAccess::where('application_id', $app->id)
            ->with(['user.roles', 'grantedBy', 'revokedBy', 'syncStatus']);

        // Search in User fields
        if ($search = $filters['search'] ?? null) {
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('nis', 'like', "%{$search}%")
                    ->orWhere('nip', 'like', "%{$search}%");
            });
        }

        // Filter by user type
        if ($type = $filters['type'] ?? null) {
            $query->whereHas('user', function ($q) use ($type) {
                $q->where('type', $type);
            });
        }

        // Filter by application_role
        if ($appRole = $filters['application_role'] ?? null) {
            $query->where('application_role', $appRole);
        }

        // Filter by access status
        if ($accessStatus = $filters['status'] ?? null) {
            $query->where('status', $accessStatus);
        }

        // Filter by sync status
        if ($syncStatus = $filters['sync_status'] ?? null) {
            $query->whereHas('syncStatus', function ($q) use ($syncStatus) {
                $q->where('status', $syncStatus);
            });
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage)->withQueryString();
    }
}
