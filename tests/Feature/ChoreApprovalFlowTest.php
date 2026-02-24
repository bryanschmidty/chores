<?php

use App\Enums\ApprovalStatus;
use App\Enums\ChoreInstanceStatus;
use App\Enums\ChoreSourceType;
use App\Models\ChoreCompletion;
use App\Models\ChoreCompletionParticipant;
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

it('allows supervisor to approve pending completion with optional comment and adjustment', function () {
    $household = Household::factory()->create();
    $kid = User::factory()->create(['household_id' => $household->id]);
    $helper = User::factory()->create(['household_id' => $household->id]);
    $supervisor = User::factory()->create(['household_id' => $household->id]);
    $kid->syncRoles(['kid']);
    $helper->syncRoles(['kid']);
    $supervisor->syncRoles(['supervisor']);

    $chore = ChoreInstance::factory()->create([
        'household_id' => $household->id,
        'created_by_user_id' => $supervisor->id,
        'assigned_to_user_id' => $kid->id,
        'source_type' => ChoreSourceType::AdHoc->value,
        'base_points' => 10,
        'status' => ChoreInstanceStatus::PendingApproval->value,
    ]);

    $completion = ChoreCompletion::factory()->create([
        'household_id' => $household->id,
        'chore_instance_id' => $chore->id,
        'completed_by_user_id' => $kid->id,
        'approval_status' => ApprovalStatus::Pending->value,
    ]);

    ChoreCompletionParticipant::factory()->create([
        'chore_completion_id' => $completion->id,
        'user_id' => $kid->id,
        'is_primary' => true,
        'points_awarded' => null,
    ]);
    ChoreCompletionParticipant::factory()->create([
        'chore_completion_id' => $completion->id,
        'user_id' => $helper->id,
        'is_primary' => false,
        'points_awarded' => null,
    ]);

    $this->actingAs($supervisor)
        ->post(route('chore-completions.approve', $completion), [
            'supervisor_adjusted_points' => 7,
            'approval_comment' => 'Great teamwork.',
        ])
        ->assertRedirect();

    expect($completion->fresh()->approval_status)->toBe(ApprovalStatus::Approved)
        ->and($completion->fresh()->approval_comment)->toBe('Great teamwork.')
        ->and($completion->fresh()->supervisor_adjusted_points)->toBe(7)
        ->and($chore->fresh()->status)->toBe(ChoreInstanceStatus::Approved);

    $participants = $completion->fresh('participants')->participants->keyBy('user_id');

    expect($participants[$kid->id]->points_awarded)->toBe(4)
        ->and($participants[$helper->id]->points_awarded)->toBe(3);
});

it('blocks self approval for supervisors', function () {
    $household = Household::factory()->create();
    $supervisor = User::factory()->create(['household_id' => $household->id]);
    $supervisor->syncRoles(['supervisor']);

    $chore = ChoreInstance::factory()->create([
        'household_id' => $household->id,
        'assigned_to_user_id' => $supervisor->id,
        'status' => ChoreInstanceStatus::PendingApproval->value,
    ]);

    $completion = ChoreCompletion::factory()->create([
        'household_id' => $household->id,
        'chore_instance_id' => $chore->id,
        'completed_by_user_id' => $supervisor->id,
        'approval_status' => ApprovalStatus::Pending->value,
    ]);

    ChoreCompletionParticipant::factory()->create([
        'chore_completion_id' => $completion->id,
        'user_id' => $supervisor->id,
        'is_primary' => true,
        'points_awarded' => null,
    ]);

    $this->actingAs($supervisor)
        ->post(route('chore-completions.approve', $completion), [])
        ->assertForbidden();
});

it('requires rejection reason when rejecting', function () {
    $household = Household::factory()->create();
    $kid = User::factory()->create(['household_id' => $household->id]);
    $supervisor = User::factory()->create(['household_id' => $household->id]);
    $kid->syncRoles(['kid']);
    $supervisor->syncRoles(['supervisor']);

    $chore = ChoreInstance::factory()->create([
        'household_id' => $household->id,
        'assigned_to_user_id' => $kid->id,
        'status' => ChoreInstanceStatus::PendingApproval->value,
    ]);

    $completion = ChoreCompletion::factory()->create([
        'household_id' => $household->id,
        'chore_instance_id' => $chore->id,
        'completed_by_user_id' => $kid->id,
        'approval_status' => ApprovalStatus::Pending->value,
    ]);

    $this->actingAs($supervisor)
        ->post(route('chore-completions.reject', $completion), [])
        ->assertSessionHasErrors('rejection_reason');
});
