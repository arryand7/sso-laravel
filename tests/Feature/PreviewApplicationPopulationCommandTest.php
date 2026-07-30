<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\User;
use App\Models\UserApplicationAccess;
use App\Services\ApplicationPopulationPreviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PreviewApplicationPopulationCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_preview_is_read_only_and_reports_duplicate_student_identity_candidate(): void
    {
        $application = Application::create([
            'name' => 'Smart',
            'slug' => 'smart',
            'base_url' => 'https://smart.example.test',
            'client_id' => 'smart-client',
            'client_secret' => 'test-only-secret',
            'redirect_uri' => 'https://smart.example.test/callback',
            'is_active' => true,
            'sync_enabled' => true,
        ]);

        $first = $this->student('student-one', 'same-nis', 'one@example.test', true);
        $second = $this->student('student-two', 'same-nis', 'two@example.test', false);

        foreach ([$first, $second] as $user) {
            UserApplicationAccess::create([
                'user_id' => $user->id,
                'application_id' => $application->id,
                'status' => 'active',
            ]);
        }

        $before = UserApplicationAccess::query()->get()->map->getAttributes()->all();
        $preview = app(ApplicationPopulationPreviewService::class)->preview($application);

        $this->assertSame(2, $preview['active_assignments']);
        $this->assertSame(2, $preview['student']);
        $this->assertSame(2, $preview['users_with_nis']);
        $this->assertSame(1, $preview['users_with_verified_email']);
        $this->assertSame(1, $preview['duplicate_identity']);
        $this->assertSame('nis', $preview['duplicate_identity_groups'][0]['identity_type']);
        $this->assertSame('same-nis', $preview['duplicate_identity_groups'][0]['identity_value']);

        $this->artisan('gate:preview-application-population', ['application' => 'smart'])
            ->expectsOutputToContain('Read-only preview complete')
            ->assertSuccessful();

        $after = UserApplicationAccess::query()->get()->map->getAttributes()->all();
        $this->assertSame($before, $after);
    }

    private function student(string $username, string $nis, string $email, bool $verified): User
    {
        return User::create([
            'name' => 'Same Display Name',
            'username' => $username,
            'email' => $email,
            'email_verified_at' => $verified ? now() : null,
            'password' => 'secret123',
            'type' => 'student',
            'nis' => $nis,
            'status' => 'active',
        ]);
    }
}
