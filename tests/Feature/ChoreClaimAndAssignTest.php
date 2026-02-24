<?php

use App\Enums\ChoreInstanceStatus;
use App\Enums\ChoreSourceType;
use App\Models\ChoreInstance;
use App\Models\Household;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\seed;

uses(RefreshDatabase::class);

beforeEach(function () {
    seed(RoleSeeder::class);
});

it('allows kid to claim open due chore', function () {
    $household = Household::factory()->create();
    $kid = User::factory()->create(['household_id' => $household->id]);
    $kid->syncRoles(['kid']);

    $chore = ChoreInstance::factory()->create([
        'household_id' => $household->id,
        'assigned_to_user_id' => null,
        'claimed_by_user_id' => null,
        'status' => ChoreInstanceStatus::Due->value,
        'source_type' => ChoreSourceType::AdHoc->value,
    ]);

    $this->actingAs($kid)
        ->post(route('chore-instances.claim', $chore))
        ->assertRedirect();

    $chore->refresh();

    expect($chore->assigned_to_user_id)->toBe($kid->id)
        ->and($chore->claimed_by_user_id)->toBe($kid->id);
});

it('prevents claim when chore is already assigned', function () {
    $household = Household::factory()->create();
    $kid = User::factory()->create(['household_id' => $household->id]);
    $otherKid = User::factory()->create(['household_id' => $household->id]);
    $kid->syncRoles(['kid']);
    $otherKid->syncRoles(['kid']);

    $chore = ChoreInstance::factory()->create([
        'household_id' => $household->id,
        'assigned_to_user_id' => $otherKid->id,
        'status' => ChoreInstanceStatus::Due->value,
    ]);

    $this->actingAs($kid)
        ->post(route('chore-instances.claim', $chore))
        ->assertForbidden();
});

it('allows supervisor to reassign and blocks non supervisor', function () {
    $household = Household::factory()->create();
    $supervisor = User::factory()->create(['household_id' => $household->id]);
    $kid = User::factory()->create(['household_id' => $household->id]);
    $targetKid = User::factory()->create(['household_id' => $household->id]);
    $supervisor->syncRoles(['supervisor']);
    $kid->syncRoles(['kid']);
    $targetKid->syncRoles(['kid']);

    $chore = ChoreInstance::factory()->create([
        'household_id' => $household->id,
        'assigned_to_user_id' => $kid->id,
        'status' => ChoreInstanceStatus::Due->value,
    ]);

    $this->actingAs($supervisor)
        ->post(route('chore-instances.assign', $chore), [
            'assigned_to_user_id' => $targetKid->id,
        ])
        ->assertRedirect();

    $chore->refresh();

    expect($chore->assigned_to_user_id)->toBe($targetKid->id);

    $this->actingAs($kid)
        ->post(route('chore-instances.assign', $chore), [
            'assigned_to_user_id' => $kid->id,
        ])
        ->assertForbidden();
});
