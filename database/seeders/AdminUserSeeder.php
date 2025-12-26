<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create superadmin user
        $superadmin = User::firstOrCreate(
            ['username' => 'superadmin'],
            [
                'name' => 'Super Administrator',
                'email' => 'superadmin@sabira-iibs.id',
                'password' => bcrypt('password'),
                'type' => 'admin',
                'status' => 'active',
            ]
        );
        $superadmin->assignRole('superadmin');

        // Create admin user
        $admin = User::firstOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'Administrator',
                'email' => 'admin@sabira-iibs.id',
                'password' => bcrypt('password'),
                'type' => 'admin',
                'status' => 'active',
            ]
        );
        $admin->assignRole('admin');

        // Create demo users for each type
        $demoUsers = [
            [
                'username' => 'teacher001',
                'name' => 'Guru Demo',
                'email' => 'teacher@sabira-iibs.id',
                'type' => 'teacher',
                'nip' => '198501012010011001',
                'role' => 'teacher',
            ],
            [
                'username' => 'student001',
                'name' => 'Siswa Demo',
                'email' => 'student@sabira-iibs.id',
                'type' => 'student',
                'nis' => '2024001',
                'role' => 'student',
            ],
            [
                'username' => 'parent001',
                'name' => 'Wali Demo',
                'email' => 'parent@sabira-iibs.id',
                'type' => 'parent',
                'role' => 'parent',
            ],
            [
                'username' => 'staff001',
                'name' => 'Staff Demo',
                'email' => 'staff@sabira-iibs.id',
                'type' => 'staff',
                'nip' => '198612012015012001',
                'role' => 'staff',
            ],
        ];

        foreach ($demoUsers as $userData) {
            $role = $userData['role'];
            unset($userData['role']);
            
            $user = User::firstOrCreate(
                ['username' => $userData['username']],
                array_merge($userData, [
                    'password' => bcrypt('password'),
                    'status' => 'active',
                ])
            );
            $user->assignRole($role);
        }
    }
}
