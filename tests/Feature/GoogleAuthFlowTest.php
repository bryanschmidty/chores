<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Role::findOrCreate('parent', 'web');
    Role::findOrCreate('kid', 'web');
    Role::findOrCreate('supervisor', 'web');
});

it('redirects to google oauth provider', function () {
    Socialite::fake('google');

    $this->get(route('auth.google.redirect'))
        ->assertRedirect();
});

it('links an existing user by google id', function () {
    $existingUser = User::factory()->create([
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
    $existingUser = User::factory()->create([
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

it('blocks unknown google users from logging in', function () {
    Socialite::fake('google', (new SocialiteUser)->map([
        'id' => 'google-not-approved-1',
        'name' => 'Not Approved',
        'email' => 'no.access@example.com',
        'avatar' => 'https://example.com/no-access-avatar.png',
    ]));

    $this->get(route('auth.google.callback'))
        ->assertRedirect('/')
        ->assertSessionHas('error', 'The Google account no.access@example.com is not approved for this app.');

    expect(User::query()->count())->toBe(0);
    $this->assertGuest();
});

it('logs out and invalidates authenticated session', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $this->post(route('logout'))
        ->assertRedirect('/');

    $this->assertGuest();
});
