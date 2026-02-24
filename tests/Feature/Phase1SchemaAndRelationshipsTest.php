<?php

use App\Enums\ApprovalStatus;
use App\Enums\ChoreInstanceStatus;
use App\Enums\ChoreSourceType;
use App\Enums\RecurrenceType;
use App\Models\ChoreCompletion;
use App\Models\ChoreCompletionParticipant;
use App\Models\ChoreInstance;
use App\Models\ChoreTemplate;
use App\Models\Household;
use App\Models\PointsLedger;
use App\Models\User;
use Database\Seeders\BaselineHouseholdSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

use function Pest\Laravel\seed;

uses(RefreshDatabase::class);

it('creates phase one tables and expected user columns', function () {
    expect(Schema::hasTable('households'))->toBeTrue()
        ->and(Schema::hasTable('chore_templates'))->toBeTrue()
        ->and(Schema::hasTable('chore_instances'))->toBeTrue()
        ->and(Schema::hasTable('weekly_claims'))->toBeTrue()
        ->and(Schema::hasTable('chore_completions'))->toBeTrue()
        ->and(Schema::hasTable('chore_completion_participants'))->toBeTrue()
        ->and(Schema::hasTable('points_ledger'))->toBeTrue()
        ->and(Schema::hasColumns('users', [
            'household_id',
            'google_id',
            'google_email',
            'google_avatar_url',
        ]))->toBeTrue();
});

it('seeds phase one baseline data and role combinations', function () {
    seed([RoleSeeder::class, BaselineHouseholdSeeder::class]);

    $parent = User::query()->where('email', 'parent@example.com')->firstOrFail();
    $kid = User::query()->where('email', 'kid@example.com')->firstOrFail();

    expect(Household::query()->count())->toBe(1)
        ->and($parent->hasRole('parent'))->toBeTrue()
        ->and($parent->hasRole('supervisor'))->toBeTrue()
        ->and($kid->hasRole('kid'))->toBeTrue();
});

it('persists and loads core phase one relationships', function () {
    $household = Household::factory()->create();
    $parent = User::factory()->create(['household_id' => $household->id]);
    $kid = User::factory()->create(['household_id' => $household->id]);

    $template = ChoreTemplate::factory()->create([
        'household_id' => $household->id,
        'created_by_user_id' => $parent->id,
        'default_assignee_user_id' => $kid->id,
        'recurrence_type' => RecurrenceType::Weekly->value,
    ]);

    $instance = ChoreInstance::factory()->create([
        'household_id' => $household->id,
        'chore_template_id' => $template->id,
        'created_by_user_id' => $parent->id,
        'assigned_to_user_id' => $kid->id,
        'source_type' => ChoreSourceType::Recurring->value,
        'status' => ChoreInstanceStatus::Due->value,
    ]);

    $completion = ChoreCompletion::factory()->create([
        'household_id' => $household->id,
        'chore_instance_id' => $instance->id,
        'completed_by_user_id' => $kid->id,
        'approval_status' => ApprovalStatus::Pending->value,
    ]);

    ChoreCompletionParticipant::factory()->create([
        'chore_completion_id' => $completion->id,
        'user_id' => $kid->id,
        'is_primary' => true,
    ]);

    PointsLedger::factory()->create([
        'household_id' => $household->id,
        'user_id' => $kid->id,
        'chore_completion_id' => $completion->id,
        'awarded_by_user_id' => $parent->id,
    ]);

    $instance->refresh();
    $completion->refresh();

    expect($template->household->is($household))->toBeTrue()
        ->and($instance->template?->is($template))->toBeTrue()
        ->and($instance->assignee?->is($kid))->toBeTrue()
        ->and($completion->participants)->toHaveCount(1)
        ->and($completion->pointsLedgerEntries)->toHaveCount(1)
        ->and($completion->completedBy->is($kid))->toBeTrue();
});
