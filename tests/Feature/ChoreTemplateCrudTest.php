<?php

use App\Enums\RecurrenceType;
use App\Models\ChoreTemplate;
use App\Models\Household;
use App\Models\User;
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
