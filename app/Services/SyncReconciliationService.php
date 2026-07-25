<?php

namespace App\Services;

class SyncReconciliationService
{
    /**
     * Category constants
     */
    public const CAT_MATCHED = 'matched';

    public const CAT_NEEDS_UPDATE = 'needs_update';

    public const CAT_MISSING_IN_APP = 'missing_in_application';

    public const CAT_ACCESS_REVOKED = 'access_revoked';

    public const CAT_INACTIVE_IN_GATE = 'inactive_in_gate';

    public const CAT_LOCAL_ONLY = 'local_only';

    public const CAT_CONFLICT = 'conflict';

    public const CAT_REACTIVATION_REQ = 'reactivation_required';

    /**
     * Generate dry-run reconciliation preview report comparing Gate canonical users with local app users.
     */
    public function generatePreviewReport(array $gateUsers, array $localUsers): array
    {
        $report = [
            'summary' => [
                self::CAT_MATCHED => 0,
                self::CAT_NEEDS_UPDATE => 0,
                self::CAT_MISSING_IN_APP => 0,
                self::CAT_ACCESS_REVOKED => 0,
                self::CAT_INACTIVE_IN_GATE => 0,
                self::CAT_LOCAL_ONLY => 0,
                self::CAT_CONFLICT => 0,
                self::CAT_REACTIVATION_REQ => 0,
            ],
            'categories' => [
                self::CAT_MATCHED => [],
                self::CAT_NEEDS_UPDATE => [],
                self::CAT_MISSING_IN_APP => [],
                self::CAT_ACCESS_REVOKED => [],
                self::CAT_INACTIVE_IN_GATE => [],
                self::CAT_LOCAL_ONLY => [],
                self::CAT_CONFLICT => [],
                self::CAT_REACTIVATION_REQ => [],
            ],
            'generated_at' => now()->toIso8601String(),
        ];

        // Index local users by gate_user_uuid, email, and username for fast matching
        $localByUuid = [];
        $localByEmail = [];
        $localByUsername = [];
        $uuidCounts = [];

        foreach ($localUsers as $local) {
            $uuid = $local['gate_user_uuid'] ?? null;
            if ($uuid) {
                $localByUuid[$uuid] = $local;
                $uuidCounts[$uuid] = ($uuidCounts[$uuid] ?? 0) + 1;
            }
            if (! empty($local['email'])) {
                $localByEmail[strtolower(trim($local['email']))] = $local;
            }
            if (! empty($local['username'])) {
                $localByUsername[strtolower(trim($local['username']))] = $local;
            }
        }

        $processedLocalIds = [];

        // Process each Gate user
        foreach ($gateUsers as $gateUser) {
            $uuid = $gateUser['uuid'];
            $email = strtolower(trim($gateUser['email'] ?? ''));
            $username = strtolower(trim($gateUser['username'] ?? ''));
            $accessStatus = $gateUser['application_access']['status'] ?? 'active';
            $gateStatus = $gateUser['status'] ?? 'active';

            // Check duplicate UUID conflict
            if (isset($uuidCounts[$uuid]) && $uuidCounts[$uuid] > 1) {
                $this->addReportItem($report, self::CAT_CONFLICT, [
                    'gate_user' => $gateUser,
                    'reason' => 'Gate UUID terhubung ke lebih dari 1 akun user lokal.',
                    'conflict_type' => 'duplicate_local_uuid',
                ]);

                continue;
            }

            // Case A: Found local user by gate_user_uuid
            if (isset($localByUuid[$uuid])) {
                $local = $localByUuid[$uuid];
                if (isset($local['id'])) {
                    $processedLocalIds[$local['id']] = true;
                }
                $localStatus = $local['status'] ?? 'active';

                // Subcase A1: Gate access revoked or inactive
                if ($accessStatus === 'revoked' || $accessStatus === 'inactive') {
                    $this->addReportItem($report, self::CAT_ACCESS_REVOKED, [
                        'gate_user' => $gateUser,
                        'local_user' => $local,
                        'action_suggested' => 'suspend_local_user',
                    ]);

                    continue;
                }

                // Subcase A2: Main user inactive in Gate
                if ($gateStatus !== 'active') {
                    $this->addReportItem($report, self::CAT_INACTIVE_IN_GATE, [
                        'gate_user' => $gateUser,
                        'local_user' => $local,
                        'action_suggested' => 'suspend_local_user',
                    ]);

                    continue;
                }

                // Subcase A3: Local user currently suspended but Gate access active -> Reactivation Required
                if ($localStatus === 'suspended' || $localStatus === 'inactive') {
                    $this->addReportItem($report, self::CAT_REACTIVATION_REQ, [
                        'gate_user' => $gateUser,
                        'local_user' => $local,
                        'action_suggested' => 'reactivate_local_user',
                    ]);

                    continue;
                }

                // Subcase A4: Check data differences
                $diffs = $this->calculateDataDifferences($gateUser, $local);
                if (! empty($diffs)) {
                    $this->addReportItem($report, self::CAT_NEEDS_UPDATE, [
                        'gate_user' => $gateUser,
                        'local_user' => $local,
                        'differences' => $diffs,
                        'action_suggested' => 'update_local_user',
                    ]);
                } else {
                    $this->addReportItem($report, self::CAT_MATCHED, [
                        'gate_user' => $gateUser,
                        'local_user' => $local,
                    ]);
                }

                continue;
            }

            // Case B: Not found by UUID. Check conflict by email or username
            $candidateEmail = $email ? ($localByEmail[$email] ?? null) : null;
            $candidateUsername = $username ? ($localByUsername[$username] ?? null) : null;
            $conflictUser = $candidateEmail ?? $candidateUsername;

            if ($conflictUser && empty($conflictUser['gate_user_uuid'])) {
                if (isset($conflictUser['id'])) {
                    $processedLocalIds[$conflictUser['id']] = true;
                }

                $this->addReportItem($report, self::CAT_CONFLICT, [
                    'gate_user' => $gateUser,
                    'local_user' => $conflictUser,
                    'reason' => 'Ditemukan kemiripan Email/Username tetapi gate_user_uuid belum terhubung.',
                    'conflict_type' => 'unlinked_matching_identifier',
                ]);

                continue;
            }

            // Case C: Missing in Application
            if ($accessStatus === 'active' && $gateStatus === 'active') {
                $this->addReportItem($report, self::CAT_MISSING_IN_APP, [
                    'gate_user' => $gateUser,
                    'action_suggested' => 'create_local_user',
                ]);
            }
        }

        // Case D: Local users not found in Gate (Local Only)
        foreach ($localUsers as $local) {
            $localId = $local['id'] ?? null;
            if ($localId !== null && ! isset($processedLocalIds[$localId])) {
                $this->addReportItem($report, self::CAT_LOCAL_ONLY, [
                    'local_user' => $local,
                    'action_suggested' => 'manual_review',
                ]);
            }
        }

        return $report;
    }

    /**
     * Calculate field differences between Gate user and local user.
     */
    protected function calculateDataDifferences(array $gateUser, array $localUser): array
    {
        $diffs = [];
        $fieldsToCompare = ['name', 'email', 'username', 'type', 'nis', 'nip'];

        foreach ($fieldsToCompare as $field) {
            $gateVal = trim((string) ($gateUser[$field] ?? ''));
            $localVal = trim((string) ($localUser[$field] ?? ''));

            if ($gateVal !== $localVal && ! ($gateVal === '' && $localVal === '')) {
                $diffs[$field] = [
                    'local' => $localUser[$field] ?? null,
                    'gate' => $gateUser[$field] ?? null,
                ];
            }
        }

        // Compare application role if available
        $gateRole = $gateUser['application_access']['role'] ?? null;
        $localRole = $localUser['application_role'] ?? $localUser['role'] ?? null;
        if ($gateRole && $gateRole !== $localRole) {
            $diffs['application_role'] = [
                'local' => $localRole,
                'gate' => $gateRole,
            ];
        }

        return $diffs;
    }

    /**
     * Format reconciliation preview report into sync result items for posting back to Gate SSO.
     */
    public function buildSyncResultItems(array $previewReport): array
    {
        $items = [];

        foreach ($previewReport['categories'] as $category => $list) {
            foreach ($list as $entry) {
                $gateUuid = $entry['gate_user']['uuid'] ?? null;
                if (! $gateUuid) {
                    continue;
                }

                $items[] = [
                    'gate_user_uuid' => $gateUuid,
                    'status' => $category,
                    'external_user_id' => (string) ($entry['local_user']['id'] ?? ''),
                    'error_code' => $entry['conflict_type'] ?? null,
                    'error_message' => $entry['reason'] ?? null,
                ];
            }
        }

        return $items;
    }

    protected function addReportItem(array &$report, string $category, array $item): void
    {
        $report['categories'][$category][] = $item;
        $report['summary'][$category]++;
    }
}
