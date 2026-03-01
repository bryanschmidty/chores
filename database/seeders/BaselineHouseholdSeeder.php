<?php

namespace Database\Seeders;

use App\Models\Household;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class BaselineHouseholdSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $household = Household::query()->firstOrCreate(
            ['name' => 'Demo Household'],
            ['timezone' => 'UTC']
        );

        Role::findOrCreate('parent', 'web');
        Role::findOrCreate('kid', 'web');
        Role::findOrCreate('supervisor', 'web');

        $approvedUsers = config('approved-users.users', []);

        if ($approvedUsers === []) {
            $approvedUsers = [
                [
                    'name' => 'Parent User',
                    'email' => 'parent@example.com',
                    'roles' => ['parent', 'supervisor'],
                ],
                [
                    'name' => 'Kid User',
                    'email' => 'kid@example.com',
                    'roles' => ['kid'],
                ],
                [
                    'name' => 'Supervisor Kid',
                    'email' => 'supervisor.kid@example.com',
                    'roles' => ['kid', 'supervisor'],
                ],
            ];
        }

        foreach ($approvedUsers as $approvedUser) {
            $user = User::query()->updateOrCreate(
                ['email' => $approvedUser['email']],
                [
                    'household_id' => $household->id,
                    'name' => $approvedUser['name'],
                    'password' => Hash::make('password'),
                ]
            );

            $user->syncRoles($approvedUser['roles']);
        }
    }
}
