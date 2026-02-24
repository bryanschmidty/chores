<?php

namespace Database\Seeders;

use App\Models\Household;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

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

        $parent = User::query()->updateOrCreate(
            ['email' => 'parent@example.com'],
            [
                'household_id' => $household->id,
                'name' => 'Parent User',
                'password' => Hash::make('password'),
            ]
        );

        $kid = User::query()->updateOrCreate(
            ['email' => 'kid@example.com'],
            [
                'household_id' => $household->id,
                'name' => 'Kid User',
                'password' => Hash::make('password'),
            ]
        );

        $supervisorKid = User::query()->updateOrCreate(
            ['email' => 'supervisor.kid@example.com'],
            [
                'household_id' => $household->id,
                'name' => 'Supervisor Kid',
                'password' => Hash::make('password'),
            ]
        );

        $parent->syncRoles(['parent', 'supervisor']);
        $kid->syncRoles(['kid']);
        $supervisorKid->syncRoles(['kid', 'supervisor']);
    }
}
