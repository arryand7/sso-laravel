<?php

namespace Database\Seeders;

use App\Models\Application;
use Spatie\Permission\Models\Role;
use Illuminate\Database\Seeder;

class ApplicationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $applications = [
            [
                'name' => 'Smart School System',
                'slug' => 'sss',
                'base_url' => 'https://sss.sabira-iibs.id',
                'redirect_uri' => 'https://sss.sabira-iibs.id/site/sso_callback',
                'sso_login_url' => 'https://sss.sabira-iibs.id/site/sso_login',
                'category' => 'Akademik',
                'icon' => 'fa-graduation-cap',
                'description' => 'Sistem informasi akademik sekolah',
                'roles' => ['superadmin', 'admin', 'teacher', 'student', 'parent'],
            ],
            [
                'name' => 'Laptop Borrowing',
                'slug' => 'laptop',
                'base_url' => 'https://laptop.sabira-iibs.id',
                'redirect_uri' => 'https://laptop.sabira-iibs.id/auth/callback',
                'category' => 'Sarana',
                'icon' => 'fa-laptop',
                'description' => 'Sistem peminjaman laptop',
                'roles' => ['superadmin', 'admin', 'teacher', 'staff', 'student'],
            ],
            [
                'name' => 'Learning Management System',
                'slug' => 'lms',
                'base_url' => 'https://lms.sabira-iibs.id',
                'redirect_uri' => 'https://lms.sabira-iibs.id/admin/oauth2callback.php',
                'sso_login_url' => 'https://lms.sabira-iibs.id/sso_login.php?id=2&wantsurl=/my/',
                'category' => 'LMS',
                'icon' => 'fa-book-open',
                'description' => 'Platform pembelajaran online (Moodle)',
                'roles' => ['superadmin', 'admin', 'teacher', 'student'],
            ],
            [
                'name' => 'Smart POS',
                'slug' => 'smart',
                'base_url' => 'https://smart.sabira-iibs.id',
                'redirect_uri' => 'https://smart.sabira-iibs.id/auth/callback',
                'category' => 'Keuangan',
                'icon' => 'fa-cash-register',
                'description' => 'Point of Sale koperasi sekolah',
                'roles' => ['superadmin', 'admin', 'staff'],
            ],
        ];

        foreach ($applications as $appData) {
            $roles = $appData['roles'];
            unset($appData['roles']);
            
            $credentials = Application::generateCredentials();
            
            $app = Application::firstOrCreate(
                ['slug' => $appData['slug']],
                array_merge($appData, $credentials, ['is_active' => true])
            );

            // Attach roles
            $roleIds = Role::whereIn('name', $roles)->pluck('id');
            $app->roles()->sync($roleIds);
            $app->syncPassportClient();
        }
    }
}
