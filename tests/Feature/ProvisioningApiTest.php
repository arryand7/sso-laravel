<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\User;
use App\Models\UserApplicationAccess;
use App\Services\ApplicationAccessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProvisioningApiTest extends TestCase
{
    use RefreshDatabase;

    protected Application $targetApp;

    protected User $user1;

    protected User $user2;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'student', 'guard_name' => 'web']);

        $this->targetApp = Application::create([
            'name' => 'Smart Sabira Pilot',
            'slug' => 'smart-pilot',
            'base_url' => 'https://smart.sabira.id',
            'client_id' => 'pilot-client-id-12345',
            'client_secret' => 'pilot-client-secret-12345678901234567890123456789012345678901234567890',
            'redirect_uri' => 'https://smart.sabira.id/callback',
            'is_active' => true,
            'sync_enabled' => true,
            'sync_capabilities' => [
                'sync_photo' => true,
                'sync_qr' => true,
                'sync_role' => true,
            ],
        ]);

        $this->user1 = User::create([
            'name' => 'Ahmad Santri',
            'username' => 'ahmad01',
            'email' => 'ahmad@sabira.id',
            'email_verified_at' => now(),
            'password' => 'secret123',
            'type' => 'student',
            'nis' => '22001001',
            'qr_code' => '00001001',
            'status' => 'active',
        ]);
        $this->user1->assignRole('student');

        $this->user2 = User::create([
            'name' => 'Budi Santri',
            'username' => 'budi02',
            'email' => 'budi@sabira.id',
            'password' => 'secret123',
            'type' => 'student',
            'nis' => '22001002',
            'status' => 'active',
        ]);
        $this->user2->assignRole('student');

        // Grant access for user1 only
        $service = app(ApplicationAccessService::class);
        $service->grantAccess($this->user1, $this->targetApp, 'santri', 'active');
    }

    public function test_provisioning_api_requires_client_credentials(): void
    {
        $response = $this->getJson(route('api.provisioning.users.index'));
        $response->assertStatus(401);
        $response->assertJsonFragment(['error' => 'unauthorized']);
    }

    public function test_provisioning_api_rejects_invalid_credentials(): void
    {
        $response = $this->withHeaders([
            'X-Client-Id' => 'wrong-client-id',
            'X-Client-Secret' => 'wrong-secret',
        ])->getJson(route('api.provisioning.users.index'));

        $response->assertStatus(401);
    }

    public function test_provisioning_api_returns_active_users_with_access(): void
    {
        $response = $this->withHeaders([
            'X-Client-Id' => $this->targetApp->client_id,
            'X-Client-Secret' => $this->targetApp->client_secret,
        ])->getJson(route('api.provisioning.users.index'));

        $response->assertStatus(200);
        $response->assertJsonFragment(['total_users' => 1]);
        $response->assertJsonFragment(['uuid' => $this->user1->uuid]);
        $response->assertJsonFragment(['username' => 'ahmad01']);
        $response->assertJsonMissing(['username' => 'budi02']);

        $user = $response->json('users.0');
        $this->assertSame($this->user1->uuid, $user['uuid']);
        $this->assertSame($this->user1->uuid, $user['gate_user_uuid']);
        $this->assertSame('student', $user['type']);
        $this->assertSame('student', $user['user_type']);
        $this->assertSame('22001001', $user['nis']);
        $this->assertTrue($user['email_verified']);
        $this->assertSame((string) $this->user1->id, $user['legacy_oidc_subject']);
        $this->assertSame('active', $user['application_access']['status']);
    }

    public function test_unverified_email_is_explicitly_false(): void
    {
        $this->user1->update(['email_verified_at' => null]);

        $response = $this->withProvisioningCredentials()
            ->getJson(route('api.provisioning.users.index'));

        $response->assertOk()->assertJsonPath('users.0.email_verified', false);
    }

    public function test_payload_remains_additive_and_contains_no_sensitive_fields(): void
    {
        $user = $this->withProvisioningCredentials()
            ->getJson(route('api.provisioning.users.index'))
            ->assertOk()
            ->json('users.0');

        foreach (['uuid', 'username', 'name', 'email', 'type', 'nis', 'nip', 'status', 'application_access', 'updated_at', 'photo'] as $legacyField) {
            $this->assertArrayHasKey($legacyField, $user);
        }

        foreach (['password', 'password_hash', 'remember_token', 'token', 'client_secret', 'recovery_codes'] as $sensitiveField) {
            $this->assertArrayNotHasKey($sensitiveField, $user);
        }
    }

    public function test_empty_assignment_returns_empty_population(): void
    {
        UserApplicationAccess::query()->delete();

        $this->withProvisioningCredentials()
            ->getJson(route('api.provisioning.users.index'))
            ->assertOk()
            ->assertJsonPath('total_users', 0)
            ->assertJsonPath('users', []);
    }

    public function test_changes_endpoint_only_returns_active_assignments_and_users(): void
    {
        $service = app(ApplicationAccessService::class);
        $service->grantAccess($this->user2, $this->targetApp, 'santri', 'inactive');

        $response = $this->withProvisioningCredentials()
            ->getJson(route('api.provisioning.changes'));

        $response->assertOk()
            ->assertJsonPath('total_changes', 1)
            ->assertJsonMissing(['username' => 'budi02']);
    }

    public function test_provisioning_api_does_not_return_revoked_or_inactive_users(): void
    {
        $service = app(ApplicationAccessService::class);
        $service->revokeAccess($this->user1, $this->targetApp);

        $response = $this->withHeaders([
            'X-Client-Id' => $this->targetApp->client_id,
            'X-Client-Secret' => $this->targetApp->client_secret,
        ])->getJson(route('api.provisioning.users.index'));

        $response->assertStatus(200);
        $response->assertJsonFragment(['total_users' => 0]);
    }

    public function test_provisioning_api_respects_app_capabilities(): void
    {
        // Disabling QR capability
        $this->targetApp->update([
            'sync_capabilities' => [
                'sync_photo' => true,
                'sync_qr' => false,
                'sync_role' => true,
            ],
        ]);

        $response = $this->withHeaders([
            'X-Client-Id' => $this->targetApp->client_id,
            'X-Client-Secret' => $this->targetApp->client_secret,
        ])->getJson(route('api.provisioning.users.index'));

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertArrayNotHasKey('qr_code', $data['users'][0]);
    }

    public function test_provisioning_api_records_sync_results(): void
    {
        $payload = [
            'items' => [
                [
                    'gate_user_uuid' => $this->user1->uuid,
                    'status' => 'matched',
                    'external_user_id' => '99',
                ],
            ],
        ];

        $response = $this->withHeaders([
            'X-Client-Id' => $this->targetApp->client_id,
            'X-Client-Secret' => $this->targetApp->client_secret,
        ])->postJson(route('api.provisioning.sync-results'), $payload);

        $response->assertStatus(200);
        $response->assertJsonFragment(['status' => 'success', 'processed' => 1]);

        $this->assertDatabaseHas('application_user_sync_statuses', [
            'user_id' => $this->user1->id,
            'application_id' => $this->targetApp->id,
            'status' => 'matched',
            'external_user_id' => '99',
        ]);
    }

    private function withProvisioningCredentials(): static
    {
        return $this->withHeaders([
            'X-Client-Id' => $this->targetApp->client_id,
            'X-Client-Secret' => $this->targetApp->client_secret,
        ]);
    }
}
