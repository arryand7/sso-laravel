<?php

namespace App\Services;

use App\Models\Application;
use App\Models\UserApplicationAccess;
use Illuminate\Support\Collection;

class ApplicationPopulationPreviewService
{
    public function preview(Application $application): array
    {
        $accesses = UserApplicationAccess::query()
            ->where('application_id', $application->id)
            ->with('user:id,uuid,email,email_verified_at,type,nis,nip,status')
            ->get();

        $activeAssignments = $accesses->where('status', 'active');
        $users = $activeAssignments->pluck('user')->filter();
        $activeUsers = $users->where('status', 'active');

        $duplicateGroups = $this->duplicateIdentityGroups($activeUsers);

        return [
            'application' => $application->slug,
            'active_assignments' => $activeAssignments->count(),
            'provisioned_users' => $activeUsers->count(),
            'student' => $activeUsers->where('type', 'student')->count(),
            'staff' => $activeUsers->whereIn('type', ['teacher', 'staff'])->count(),
            'guardian' => $activeUsers->where('type', 'parent')->count(),
            'users_with_uuid' => $activeUsers->whereNotNull('uuid')->count(),
            'users_with_nis' => $activeUsers->where('type', 'student')->whereNotNull('nis')->count(),
            'users_with_verified_email' => $activeUsers
                ->filter(fn ($user) => $user->email !== null && $user->email_verified_at !== null)
                ->count(),
            // Current OIDC `sub` is users.id, so every persisted user has this legacy subject.
            'users_with_legacy_subject' => $activeUsers->count(),
            'incomplete_identity' => $activeUsers->filter(fn ($user) => empty($user->uuid))->count(),
            'duplicate_identity' => count($duplicateGroups),
            'duplicate_identity_groups' => $duplicateGroups,
            'inactive_users' => $users->where('status', '!=', 'active')->count(),
        ];
    }

    private function duplicateIdentityGroups(Collection $users): array
    {
        $identities = collect();

        foreach ($users as $user) {
            if ($user->type === 'student' && filled($user->nis)) {
                $identities->push(['kind' => 'nis', 'value' => $user->nis, 'user_id' => $user->id]);
            }

            if (filled($user->email) && $user->email_verified_at !== null) {
                $identities->push([
                    'kind' => 'verified_email',
                    'value' => mb_strtolower($user->email),
                    'user_id' => $user->id,
                ]);
            }
        }

        return $identities
            ->groupBy(fn (array $identity) => $identity['kind'].':'.$identity['value'])
            ->filter(fn (Collection $group) => $group->count() > 1)
            ->map(fn (Collection $group) => [
                'identity_type' => $group->first()['kind'],
                'identity_value' => $group->first()['value'],
                'user_ids' => $group->pluck('user_id')->values()->all(),
            ])
            ->values()
            ->all();
    }
}
