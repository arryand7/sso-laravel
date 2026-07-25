<?php

namespace Tests\Feature;

use App\Services\SyncReconciliationService;
use Tests\TestCase;

class SyncReconciliationTest extends TestCase
{
    public function test_reconciliation_engine_categorizes_into_8_categories(): void
    {
        $service = new SyncReconciliationService;

        $gateUsers = [
            // 1. Matched
            [
                'uuid' => 'uuid-matched-01',
                'username' => 'matched01',
                'name' => 'User Matched',
                'email' => 'matched@sabira.id',
                'type' => 'student',
                'status' => 'active',
                'application_access' => ['status' => 'active', 'role' => 'santri'],
            ],
            // 2. Needs Update
            [
                'uuid' => 'uuid-update-02',
                'username' => 'update02',
                'name' => 'Nama Baru Gate',
                'email' => 'update@sabira.id',
                'type' => 'student',
                'status' => 'active',
                'application_access' => ['status' => 'active', 'role' => 'santri'],
            ],
            // 3. Missing in Application
            [
                'uuid' => 'uuid-missing-03',
                'username' => 'missing03',
                'name' => 'User Baru Gate',
                'email' => 'missing@sabira.id',
                'type' => 'student',
                'status' => 'active',
                'application_access' => ['status' => 'active', 'role' => 'santri'],
            ],
            // 4. Access Revoked
            [
                'uuid' => 'uuid-revoked-04',
                'username' => 'revoked04',
                'name' => 'User Revoked',
                'email' => 'revoked@sabira.id',
                'type' => 'student',
                'status' => 'active',
                'application_access' => ['status' => 'revoked', 'role' => 'santri'],
            ],
            // 5. Inactive in Gate
            [
                'uuid' => 'uuid-inactive-05',
                'username' => 'inactive05',
                'name' => 'User Inactive Gate',
                'email' => 'inactive@sabira.id',
                'type' => 'student',
                'status' => 'suspended',
                'application_access' => ['status' => 'active', 'role' => 'santri'],
            ],
            // 6. Conflict (matching email without UUID)
            [
                'uuid' => 'uuid-conflict-06',
                'username' => 'gate_conflict',
                'name' => 'User Conflict',
                'email' => 'conflict@sabira.id',
                'type' => 'student',
                'status' => 'active',
                'application_access' => ['status' => 'active', 'role' => 'santri'],
            ],
            // 7. Reactivation Required
            [
                'uuid' => 'uuid-reactivate-07',
                'username' => 'reactivate07',
                'name' => 'User Reactivate',
                'email' => 'reactivate@sabira.id',
                'type' => 'student',
                'status' => 'active',
                'application_access' => ['status' => 'active', 'role' => 'santri'],
            ],
        ];

        $localUsers = [
            // Matched local
            [
                'id' => 1,
                'gate_user_uuid' => 'uuid-matched-01',
                'username' => 'matched01',
                'name' => 'User Matched',
                'email' => 'matched@sabira.id',
                'type' => 'student',
                'status' => 'active',
                'application_role' => 'santri',
            ],
            // Needs Update local
            [
                'id' => 2,
                'gate_user_uuid' => 'uuid-update-02',
                'username' => 'update02',
                'name' => 'Nama Lama Lokal',
                'email' => 'update@sabira.id',
                'type' => 'student',
                'status' => 'active',
                'application_role' => 'santri',
            ],
            // Access Revoked local
            [
                'id' => 4,
                'gate_user_uuid' => 'uuid-revoked-04',
                'username' => 'revoked04',
                'name' => 'User Revoked',
                'email' => 'revoked@sabira.id',
                'type' => 'student',
                'status' => 'active',
                'application_role' => 'santri',
            ],
            // Inactive in Gate local
            [
                'id' => 5,
                'gate_user_uuid' => 'uuid-inactive-05',
                'username' => 'inactive05',
                'name' => 'User Inactive Gate',
                'email' => 'inactive@sabira.id',
                'type' => 'student',
                'status' => 'active',
                'application_role' => 'santri',
            ],
            // Conflict local (same email, no UUID)
            [
                'id' => 6,
                'gate_user_uuid' => null,
                'username' => 'local_conflict',
                'name' => 'User Conflict',
                'email' => 'conflict@sabira.id',
                'type' => 'student',
                'status' => 'active',
            ],
            // Reactivation Required local
            [
                'id' => 7,
                'gate_user_uuid' => 'uuid-reactivate-07',
                'username' => 'reactivate07',
                'name' => 'User Reactivate',
                'email' => 'reactivate@sabira.id',
                'type' => 'student',
                'status' => 'suspended',
            ],
            // Local Only
            [
                'id' => 8,
                'gate_user_uuid' => 'uuid-local-only-08',
                'username' => 'localonly08',
                'name' => 'Local Only User',
                'email' => 'localonly@sabira.id',
                'type' => 'student',
                'status' => 'active',
            ],
        ];

        $report = $service->generatePreviewReport($gateUsers, $localUsers);

        $this->assertSame(1, $report['summary'][SyncReconciliationService::CAT_MATCHED]);
        $this->assertSame(1, $report['summary'][SyncReconciliationService::CAT_NEEDS_UPDATE]);
        $this->assertSame(1, $report['summary'][SyncReconciliationService::CAT_MISSING_IN_APP]);
        $this->assertSame(1, $report['summary'][SyncReconciliationService::CAT_ACCESS_REVOKED]);
        $this->assertSame(1, $report['summary'][SyncReconciliationService::CAT_INACTIVE_IN_GATE]);
        $this->assertSame(1, $report['summary'][SyncReconciliationService::CAT_CONFLICT]);
        $this->assertSame(1, $report['summary'][SyncReconciliationService::CAT_REACTIVATION_REQ]);
        $this->assertSame(1, $report['summary'][SyncReconciliationService::CAT_LOCAL_ONLY]);
    }
}
