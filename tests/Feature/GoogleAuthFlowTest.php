<?php

use App\Models\Household;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

it('redirects to google oauth provider', function () {
    Socialite::fake('google');

    $this->get(route('auth.google.redirect'))
        ->assertRedirect();
});

it('bootstraps first household user as parent and supervisor', function () {
    Socialite::fake('google', (new SocialiteUser)->map([
        'id' => 'google-first-1',
        'name' => 'First Parent',
        'email' => 'first.parent@example.com',
        'avatar' => 'https://example.com/avatar-first.png',
    ]));

    $this->get(route('auth.google.callback'))
        ->assertRedirect('/');

    $user = User::query()
        ->where('google_id', 'google-first-1')
        ->firstOrFail();

    expect(Household::query()->count())->toBe(1)
        ->and($user->hasRole('parent'))->toBeTrue()
        ->and($user->hasRole('supervisor'))->toBeTrue()
        ->and($user->household_id)->not->toBeNull();

    $this->assertAuthenticatedAs($user);
});

it('links an existing user by google id', function () {
    $household = Household::factory()->create();
    $existingUser = User::factory()->create([
        'household_id' => $household->id,
        'email' => 'existing.id@example.com',
        'google_id' => 'google-existing-1',
        'google_email' => 'existing.id@example.com',
    ]);

    Socialite::fake('google', (new SocialiteUser)->map([
        'id' => 'google-existing-1',
        'name' => 'Existing Id User',
        'email' => 'new-email@example.com',
        'avatar' => 'https://example.com/new-avatar.png',
    ]));

    $this->get(route('auth.google.callback'))
        ->assertRedirect('/');

    $existingUser->refresh();

    expect(User::query()->count())->toBe(1)
        ->and($existingUser->google_email)->toBe('new-email@example.com')
        ->and($existingUser->google_avatar_url)->toBe('https://example.com/new-avatar.png');

    $this->assertAuthenticatedAs($existingUser);
});

it('links an existing user by email when google id is missing', function () {
    $household = Household::factory()->create();
    $existingUser = User::factory()->create([
        'household_id' => $household->id,
        'email' => 'email.match@example.com',
        'google_id' => null,
    ]);

    Socialite::fake('google', (new SocialiteUser)->map([
        'id' => 'google-linked-2',
        'name' => 'Email Match User',
        'email' => 'email.match@example.com',
        'avatar' => 'https://example.com/email-match-avatar.png',
    ]));

    $this->get(route('auth.google.callback'))
        ->assertRedirect('/');

    $existingUser->refresh();

    expect(User::query()->count())->toBe(1)
        ->and($existingUser->google_id)->toBe('google-linked-2')
        ->and($existingUser->google_email)->toBe('email.match@example.com');

    $this->assertAuthenticatedAs($existingUser);
});

it('auto creates kid user for unknown google account after bootstrap', function () {
    Role::findOrCreate('parent', 'web');
    Role::findOrCreate('kid', 'web');
    Role::findOrCreate('supervisor', 'web');

    $household = Household::factory()->create();
    $bootstrapParent = User::factory()->create([
        'household_id' => $household->id,
        'email' => 'parent.bootstrap@example.com',
    ]);
    $bootstrapParent->syncRoles(['parent', 'supervisor']);

    Socialite::fake('google', (new SocialiteUser)->map([
        'id' => 'google-new-kid-1',
        'name' => 'New Kid',
        'email' => 'new.kid@example.com',
        'avatar' => 'https://example.com/new-kid-avatar.png',
    ]));

    $this->get(route('auth.google.callback'))
        ->assertRedirect('/');

    $newUser = User::query()
        ->where('google_id', 'google-new-kid-1')
        ->firstOrFail();

    expect($newUser->household_id)->toBe($household->id)
        ->and($newUser->hasRole('kid'))->toBeTrue();
});

it('logs out and invalidates authenticated session', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $this->post(route('logout'))
        ->assertRedirect('/');

    $this->assertGuest();
});
