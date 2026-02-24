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

it('allows parent to create recurring chore template', function () {
    $household = Household::factory()->create();
    $parent = User::factory()->create(['household_id' => $household->id]);
    $kid = User::factory()->create(['household_id' => $household->id]);
    $parent->syncRoles(['parent', 'supervisor']);
    $kid->syncRoles(['kid']);

    $this->actingAs($parent)
        ->post(route('chore-templates.store'), [
            'title' => 'Clean kitchen',
            'description' => 'Wipe counters',
            'points' => 10,
            'recurrence_type' => RecurrenceType::Weekly->value,
            'recurrence_interval' => 1,
            'default_assignee_user_id' => $kid->id,
            'is_active' => true,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('chore_templates', [
        'household_id' => $household->id,
        'created_by_user_id' => $parent->id,
        'title' => 'Clean kitchen',
    ]);
});

it('prevents kid from creating recurring chore template', function () {
    $household = Household::factory()->create();
    $kid = User::factory()->create(['household_id' => $household->id]);
    $kid->syncRoles(['kid']);

    $this->actingAs($kid)
        ->post(route('chore-templates.store'), [
            'title' => 'Should fail',
            'points' => 5,
            'recurrence_type' => RecurrenceType::Daily->value,
        ])
        ->assertForbidden();
});

it('allows parent to update and archive a recurring template', function () {
    $household = Household::factory()->create();
    $parent = User::factory()->create(['household_id' => $household->id]);
    $parent->syncRoles(['parent', 'supervisor']);

    $template = ChoreTemplate::factory()->create([
        'household_id' => $household->id,
        'created_by_user_id' => $parent->id,
        'is_active' => true,
    ]);

    $this->actingAs($parent)
        ->put(route('chore-templates.update', $template), [
            'title' => 'Updated title',
            'description' => 'Updated description',
            'points' => 12,
            'recurrence_type' => RecurrenceType::Daily->value,
            'recurrence_interval' => 1,
        ])
        ->assertRedirect();

    $this->actingAs($parent)
        ->patch(route('chore-templates.archive', $template))
        ->assertRedirect();

    $template->refresh();

    expect($template->title)->toBe('Updated title')
        ->and($template->is_active)->toBeFalse();
});

it('updates current week claim assignee from recurring edit payload', function () {
    CarbonImmutable::setTestNow('2026-06-10 09:00:00');

    $household = Household::factory()->create();
    $parent = User::factory()->create(['household_id' => $household->id]);
    $kid = User::factory()->create(['household_id' => $household->id]);
    $parent->syncRoles(['parent', 'supervisor']);
    $kid->syncRoles(['kid']);

    $template = ChoreTemplate::factory()->create([
        'household_id' => $household->id,
        'created_by_user_id' => $parent->id,
    ]);

    $this->actingAs($parent)
        ->put(route('chore-templates.update', $template), [
            'title' => $template->title,
            'description' => $template->description,
            'points' => $template->points,
            'recurrence_type' => $template->recurrence_type->value,
            'recurrence_interval' => $template->recurrence_interval,
            'current_week_claim_user_id' => $kid->id,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('weekly_claims', [
        'chore_template_id' => $template->id,
        'assigned_to_user_id' => $kid->id,
    ]);

    $this->actingAs($parent)
        ->put(route('chore-templates.update', $template), [
            'title' => $template->title,
            'description' => $template->description,
            'points' => $template->points,
            'recurrence_type' => $template->recurrence_type->value,
            'recurrence_interval' => $template->recurrence_interval,
            'current_week_claim_user_id' => null,
        ])
        ->assertRedirect();

    expect(WeeklyClaim::query()->where('chore_template_id', $template->id)->count())->toBe(0);

    CarbonImmutable::setTestNow();
});
