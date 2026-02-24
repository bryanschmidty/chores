<?php

use App\Enums\ApprovalStatus;
use App\Enums\ChoreInstanceStatus;
use App\Models\ChoreCompletion;
use App\Models\ChoreCompletionParticipant;
use App\Models\ChoreInstance;
use App\Models\Household;
use App\Models\PointsLedger;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;

use function Pest\Laravel\seed;

uses(RefreshDatabase::class);

beforeEach(function () {
    seed(RoleSeeder::class);
});

it('keeps approval attribution aligned across completion and points ledger', function () {
    Log::spy();

    $household = Household::factory()->create();
    $kid = User::factory()->create(['household_id' => $household->id]);
    $helper = User::factory()->create(['household_id' => $household->id]);
    $supervisor = User::factory()->create(['household_id' => $household->id]);
    $kid->syncRoles(['kid']);
    $helper->syncRoles(['kid']);
    $supervisor->syncRoles(['supervisor']);

    $chore = ChoreInstance::factory()->create([
        'household_id' => $household->id,
        'assigned_to_user_id' => $kid->id,
        'base_points' => 6,
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
    ]);
    ChoreCompletionParticipant::factory()->create([
        'chore_completion_id' => $completion->id,
        'user_id' => $helper->id,
        'is_primary' => false,
    ]);

    $this->actingAs($supervisor)
        ->post(route('chore-completions.approve', $completion), [])
        ->assertRedirect(route('supervisor.queue'));

    $approvedCompletion = $completion->fresh();
    expect($approvedCompletion->approval_status)->toBe(ApprovalStatus::Approved)
        ->and($approvedCompletion->approved_by_user_id)->toBe($supervisor->id)
        ->and($approvedCompletion->approved_at)->not->toBeNull();

    $ledgerEntries = PointsLedger::query()
        ->where('chore_completion_id', $completion->id)
        ->get();

    expect($ledgerEntries)->toHaveCount(2)
        ->and($ledgerEntries->pluck('awarded_by_user_id')->unique()->all())
        ->toBe([$supervisor->id]);

    Log::shouldHaveReceived('info')
        ->with('chore_completion.approved', \Mockery::on(function (array $context) use ($completion, $supervisor): bool {
            return $context['chore_completion_id'] === $completion->id
                && $context['approved_by_user_id'] === $supervisor->id;
        }))
        ->once();

    Log::shouldHaveReceived('info')
        ->with('points.awarded', \Mockery::on(function (array $context) use ($completion, $supervisor): bool {
            return $context['chore_completion_id'] === $completion->id
                && $context['awarded_by_user_id'] === $supervisor->id
                && is_array($context['entries'])
                && count($context['entries']) === 2;
        }))
        ->once();
});

it('clears approval attribution fields when a pending completion is rejected', function () {
    Log::spy();

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
        'approved_by_user_id' => $supervisor->id,
        'approved_at' => now()->subMinute(),
        'supervisor_adjusted_points' => 99,
        'approval_comment' => 'Needs edits',
    ]);

    $this->actingAs($supervisor)
        ->post(route('chore-completions.reject', $completion), [
            'rejection_reason' => 'Please improve quality.',
        ])
        ->assertRedirect(route('supervisor.queue'));

    $rejectedCompletion = $completion->fresh();
    expect($rejectedCompletion->approval_status)->toBe(ApprovalStatus::Rejected)
        ->and($rejectedCompletion->approved_by_user_id)->toBeNull()
        ->and($rejectedCompletion->approved_at)->toBeNull()
        ->and($rejectedCompletion->supervisor_adjusted_points)->toBeNull()
        ->and($rejectedCompletion->approval_comment)->toBeNull()
        ->and($rejectedCompletion->rejection_reason)->toBe('Please improve quality.');

    Log::shouldHaveReceived('info')
        ->with('chore_completion.rejected', \Mockery::on(function (array $context) use ($completion, $supervisor): bool {
            return $context['chore_completion_id'] === $completion->id
                && $context['rejected_by_user_id'] === $supervisor->id;
        }))
        ->once();
});
