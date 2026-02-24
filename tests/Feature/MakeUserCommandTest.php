<?php

use App\Models\Household;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

it('creates a user with explicit arguments and defaults password when omitted', function () {
    $this->artisan('make:user', [
        'name' => 'CLI Kid',
        'email' => 'cli.kid@example.com',
    ])
        ->expectsQuestion('Password (leave blank for default "password")', '')
        ->expectsChoice('Select role(s) to assign', ['kid'], ['parent', 'kid', 'supervisor'])
        ->assertSuccessful();

    $user = User::query()->where('email', 'cli.kid@example.com')->firstOrFail();

    expect($user->hasRole('kid'))->toBeTrue()
        ->and(Hash::check('password', $user->password))->toBeTrue()
        ->and(Household::query()->count())->toBe(1);
});

it('prompts for missing values and adds supervisor when parent role selected', function () {
    $this->artisan('make:user')
        ->expectsQuestion('Name', 'Prompt Parent')
        ->expectsQuestion('Email', 'prompt.parent@example.com')
        ->expectsQuestion('Password (leave blank for default "password")', 'secret-pass')
        ->expectsChoice('Select role(s) to assign', ['parent'], ['parent', 'kid', 'supervisor'])
        ->assertSuccessful();

    $user = User::query()->where('email', 'prompt.parent@example.com')->firstOrFail();

    expect(Hash::check('secret-pass', $user->password))->toBeTrue()
        ->and($user->hasRole('parent'))->toBeTrue()
        ->and($user->hasRole('supervisor'))->toBeTrue();
});

it('blocks command outside local development environments', function () {
    config()->set('app.env', 'production');

    $this->artisan('make:user', [
        'name' => 'Blocked User',
        'email' => 'blocked@example.com',
        'password' => 'blocked-pass',
    ])
        ->expectsOutput('The make:user command is only available in local/development environments.')
        ->assertFailed();

    expect(User::query()->where('email', 'blocked@example.com')->exists())->toBeFalse();
});

it('ensures phase roles exist when command runs', function () {
    $this->artisan('make:user', [
        'name' => 'Role Check',
        'email' => 'role.check@example.com',
        'password' => 'role-pass',
    ])
        ->expectsChoice('Select role(s) to assign', ['kid'], ['parent', 'kid', 'supervisor'])
        ->assertSuccessful();

    expect(Role::query()->where('name', 'parent')->exists())->toBeTrue()
        ->and(Role::query()->where('name', 'kid')->exists())->toBeTrue()
        ->and(Role::query()->where('name', 'supervisor')->exists())->toBeTrue();
});
