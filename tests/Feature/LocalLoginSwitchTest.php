<?php

use App\Models\Household;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('does not render switch user controls when local switch is disabled', function () {
    config()->set('auth.local_login_switch_enabled', false);

    $household = Household::factory()->create();
    $user = User::factory()->create(['household_id' => $household->id]);

    $this->actingAs($user)
        ->get('/')
        ->assertSuccessful()
        ->assertDontSee('Switch User');
});

it('renders switch user controls when local switch is enabled', function () {
    config()->set('auth.local_login_switch_enabled', true);

    $household = Household::factory()->create();
    $user = User::factory()->create(['household_id' => $household->id]);

    $response = $this->actingAs($user)->get('/');

    $response->assertSuccessful()
        ->assertSee('Switch User');
});

it('forbids switching user when the feature flag is disabled', function () {
    config()->set('auth.local_login_switch_enabled', false);

    $household = Household::factory()->create();
    $user = User::factory()->create(['household_id' => $household->id]);
    $targetUser = User::factory()->create(['household_id' => $household->id]);

    $this->actingAs($user)
        ->post(route('local-login.switch'), [
            'switch_user_id' => $targetUser->id,
        ])
        ->assertForbidden();
});

it('switches authenticated user when enabled', function () {
    config()->set('auth.local_login_switch_enabled', true);

    $household = Household::factory()->create();
    $user = User::factory()->create(['household_id' => $household->id]);
    $targetUser = User::factory()->create(['household_id' => $household->id]);

    $this->actingAs($user)
        ->from(route('app.home'))
        ->post(route('local-login.switch'), [
            'switch_user_id' => $targetUser->id,
        ])
        ->assertRedirect('/');

    $this->assertAuthenticatedAs($targetUser);
});

it('rejects switch target from another household', function () {
    config()->set('auth.local_login_switch_enabled', true);

    $household = Household::factory()->create();
    $otherHousehold = Household::factory()->create();
    $user = User::factory()->create(['household_id' => $household->id]);
    $otherUser = User::factory()->create(['household_id' => $otherHousehold->id]);

    $this->actingAs($user)
        ->post(route('local-login.switch'), [
            'switch_user_id' => $otherUser->id,
        ])
        ->assertSessionHasErrors('switch_user_id');
});

it('keeps switched session user even when local login user id is configured', function () {
    config()->set('auth.local_login_switch_enabled', true);

    $household = Household::factory()->create();
    $defaultUser = User::factory()->create(['household_id' => $household->id]);
    $switchedUser = User::factory()->create(['household_id' => $household->id]);
    config()->set('auth.local_login_user_id', $defaultUser->id);

    $this->actingAs($defaultUser)
        ->post(route('local-login.switch'), [
            'switch_user_id' => $switchedUser->id,
        ])
        ->assertRedirect();

    $this->get('/')
        ->assertSuccessful();

    $this->assertAuthenticatedAs($switchedUser);
});
