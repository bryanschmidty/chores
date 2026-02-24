<?php

namespace App\Console\Commands;

use App\Models\Household;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class MakeUserCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:user {name?} {email?} {password?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a user for the single-household MVP';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (! in_array(config('app.env'), ['local', 'development', 'testing'], true)) {
            $this->error('The make:user command is only available in local/development environments.');

            return self::FAILURE;
        }

        $this->ensureRolesExist();

        $name = (string) ($this->argument('name') ?? $this->ask('Name'));
        $email = (string) ($this->argument('email') ?? $this->ask('Email'));
        $passwordInput = $this->argument('password');
        $passwordInput = is_string($passwordInput) ? $passwordInput : null;

        if ($passwordInput === null) {
            $passwordInput = $this->secret('Password (leave blank for default "password")');
        }

        $password = filled($passwordInput) ? $passwordInput : 'password';

        $selectedRoles = $this->choice(
            'Select role(s) to assign',
            ['parent', 'kid', 'supervisor'],
            'kid',
            null,
            true
        );

        if (in_array('parent', $selectedRoles, true) && ! in_array('supervisor', $selectedRoles, true)) {
            $selectedRoles[] = 'supervisor';
        }

        $household = Household::query()->orderBy('id')->first()
            ?? Household::query()->create([
                'name' => 'Primary Household',
                'timezone' => 'UTC',
            ]);

        $user = User::query()->updateOrCreate(
            ['email' => $email],
            [
                'household_id' => $household->id,
                'name' => $name,
                'password' => Hash::make($password),
            ]
        );

        $user->syncRoles($selectedRoles);

        $this->info(sprintf('User %s created/updated with roles: %s', $user->email, implode(', ', $selectedRoles)));

        return self::SUCCESS;
    }

    private function ensureRolesExist(): void
    {
        Role::findOrCreate('parent', 'web');
        Role::findOrCreate('kid', 'web');
        Role::findOrCreate('supervisor', 'web');
    }
}
