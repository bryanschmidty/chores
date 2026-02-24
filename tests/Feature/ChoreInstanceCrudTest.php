<?php

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

it('allows parent to create ad hoc chore', function () {
    $household = Household::factory()->create();
    $parent = User::factory()->create(['household_id' => $household->id]);
    $kid = User::factory()->create(['household_id' => $household->id]);
    $parent->syncRoles(['parent', 'supervisor']);
    $kid->syncRoles(['kid']);

    $this->actingAs($parent)
        ->post(route('chore-instances.store'), [
            'title' => 'Take out trash',
            'base_points' => 8,
            'assigned_to_user_id' => $kid->id,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('chore_instances', [
        'household_id' => $household->id,
        'created_by_user_id' => $parent->id,
        'assigned_to_user_id' => $kid->id,
        'source_type' => ChoreSourceType::AdHoc->value,
    ]);
});

it('defaults kid-created ad hoc chore assignee to self', function () {
    $household = Household::factory()->create();
    $kid = User::factory()->create(['household_id' => $household->id]);
    $kid->syncRoles(['kid']);

    $this->actingAs($kid)
        ->post(route('chore-instances.store'), [
            'title' => 'Clean room',
            'base_points' => 6,
        ])
        ->assertRedirect();

    $instance = ChoreInstance::query()->firstOrFail();

    expect($instance->assigned_to_user_id)->toBe($kid->id);
});

it('allows kid to create open ad hoc chore', function () {
    $household = Household::factory()->create();
    $kid = User::factory()->create(['household_id' => $household->id]);
    $kid->syncRoles(['kid']);

    $this->actingAs($kid)
        ->post(route('chore-instances.store'), [
            'title' => 'Rake leaves',
            'base_points' => 9,
            'is_open' => true,
        ])
        ->assertRedirect();

    $instance = ChoreInstance::query()->firstOrFail();

    expect($instance->assigned_to_user_id)->toBeNull();
});

it('allows creator kid to update ad hoc chore', function () {
    $household = Household::factory()->create();
    $kid = User::factory()->create(['household_id' => $household->id]);
    $kid->syncRoles(['kid']);

    $instance = ChoreInstance::factory()->create([
        'household_id' => $household->id,
        'created_by_user_id' => $kid->id,
        'source_type' => ChoreSourceType::AdHoc->value,
    ]);

    $this->actingAs($kid)
        ->put(route('chore-instances.update', $instance), [
            'title' => 'Updated ad hoc',
            'description' => 'Updated description',
            'base_points' => 7,
        ])
        ->assertRedirect();

    $instance->refresh();

    expect($instance->title)->toBe('Updated ad hoc');
});
