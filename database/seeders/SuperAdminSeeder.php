<?php

namespace Database\Seeders;

use App\Models\Family;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create super admin user
        $superAdmin = User::firstOrCreate(
            ['email' => 'admin@chores.app'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
                'role' => 'super-admin',
                'email_verified_at' => now(),
                'notification_preferences' => [
                    'email_chore_assigned' => false,
                    'email_chore_completed' => true,
                    'email_chore_verified' => false,
                    'email_overdue_reminder' => false,
                    'browser_chore_assigned' => false,
                    'browser_chore_completed' => true,
                    'browser_chore_verified' => false,
                    'browser_overdue_reminder' => false,
                ],
            ]
        );

        $this->command->info('Super admin created: admin@chores.app / password');
    }
}