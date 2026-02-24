<?php

use App\Enums\RecurrenceType;
use App\Models\ChoreTemplate;
use App\Models\Household;
use App\Models\User;
use App\Models\WeeklyClaim;
use Carbon\CarbonImmutable;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\seed;

uses(RefreshDatabase::class);

beforeEach(function () {
    seed(RoleSeeder::class);
});

it('allows kid to create weekly claim for another kid in current week', function () {
    $household = Household::factory()->create();
    $kidOne = User::factory()->create(['household_id' => $household->id]);
    $kidTwo = User::factory()->create(['household_id' => $household->id]);
    $kidOne->syncRoles(['kid']);
    $kidTwo->syncRoles(['kid']);

    $template = ChoreTemplate::factory()->create([
        'household_id' => $household->id,
        'created_by_user_id' => $kidOne->id,
        'recurrence_type' => RecurrenceType::Weekly->value,
    ]);

    $this->actingAs($kidOne)
        ->post(route('weekly-claims.store'), [
            'chore_template_id' => $template->id,
            'assigned_to_user_id' => $kidTwo->id,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('weekly_claims', [
        'chore_template_id' => $template->id,
        'assigned_to_user_id' => $kidTwo->id,
        'claimed_by_user_id' => $kidOne->id,
    ]);
});

it('allows assigning weekly claim to any household user role', function () {
    $household = Household::factory()->create();
    $kid = User::factory()->create(['household_id' => $household->id]);
    $parent = User::factory()->create(['household_id' => $household->id]);
    $kid->syncRoles(['kid']);
    $parent->syncRoles(['parent']);

    $template = ChoreTemplate::factory()->create([
        'household_id' => $household->id,
        'created_by_user_id' => $kid->id,
        'recurrence_type' => RecurrenceType::Weekly->value,
    ]);

    $this->actingAs($kid)
        ->post(route('weekly-claims.store'), [
            'chore_template_id' => $template->id,
            'assigned_to_user_id' => $parent->id,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('weekly_claims', [
        'chore_template_id' => $template->id,
        'assigned_to_user_id' => $parent->id,
        'claimed_by_user_id' => $kid->id,
    ]);
});

it('prevents kid from overriding existing weekly claim', function () {
    $household = Household::factory()->create();
    $kidOne = User::factory()->create(['household_id' => $household->id]);
    $kidTwo = User::factory()->create(['household_id' => $household->id]);
    $kidOne->syncRoles(['kid']);
    $kidTwo->syncRoles(['kid']);

    $template = ChoreTemplate::factory()->create([
        'household_id' => $household->id,
        'created_by_user_id' => $kidOne->id,
        'recurrence_type' => RecurrenceType::Weekly->value,
    ]);

    WeeklyClaim::factory()->create([
        'household_id' => $household->id,
        'chore_template_id' => $template->id,
        'assigned_to_user_id' => $kidOne->id,
        'claimed_by_user_id' => $kidOne->id,
        'week_start_at' => CarbonImmutable::now()->startOfWeek()->toDateString(),
        'week_end_at' => CarbonImmutable::now()->endOfWeek()->toDateString(),
    ]);

    $this->actingAs($kidTwo)
        ->post(route('weekly-claims.store'), [
            'chore_template_id' => $template->id,
            'assigned_to_user_id' => $kidTwo->id,
        ])
        ->assertForbidden();
});

it('allows supervisor to override existing weekly claim', function () {
    $household = Household::factory()->create();
    $supervisor = User::factory()->create(['household_id' => $household->id]);
    $kidOne = User::factory()->create(['household_id' => $household->id]);
    $kidTwo = User::factory()->create(['household_id' => $household->id]);
    $supervisor->syncRoles(['supervisor']);
    $kidOne->syncRoles(['kid']);
    $kidTwo->syncRoles(['kid']);

    $template = ChoreTemplate::factory()->create([
        'household_id' => $household->id,
        'created_by_user_id' => $supervisor->id,
        'recurrence_type' => RecurrenceType::Weekly->value,
    ]);

    $weeklyClaim = WeeklyClaim::factory()->create([
        'household_id' => $household->id,
        'chore_template_id' => $template->id,
        'assigned_to_user_id' => $kidOne->id,
        'claimed_by_user_id' => $kidOne->id,
        'week_start_at' => CarbonImmutable::now()->startOfWeek()->toDateString(),
        'week_end_at' => CarbonImmutable::now()->endOfWeek()->toDateString(),
    ]);

    $this->actingAs($supervisor)
        ->put(route('weekly-claims.update', $weeklyClaim), [
            'assigned_to_user_id' => $kidTwo->id,
        ])
        ->assertRedirect();

    $weeklyClaim->refresh();

    expect($weeklyClaim->assigned_to_user_id)->toBe($kidTwo->id)
        ->and($weeklyClaim->overridden_by_user_id)->toBe($supervisor->id);
});
