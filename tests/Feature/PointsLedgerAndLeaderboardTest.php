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

use function Pest\Laravel\seed;

uses(RefreshDatabase::class);

beforeEach(function () {
    seed(RoleSeeder::class);
});

it('writes ledger entries only when completion is approved', function () {
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
        'base_points' => 3,
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

    expect(PointsLedger::query()->count())->toBe(0);

    $this->actingAs($supervisor)
        ->post(route('chore-completions.approve', $completion), [])
        ->assertRedirect();

    $entries = PointsLedger::query()
        ->where('chore_completion_id', $completion->id)
        ->orderBy('user_id')
        ->get();

    expect($entries)->toHaveCount(2)
        ->and($entries->sum('points'))->toBe(3);
});

it('returns household leaderboard totals and completion history', function () {
    $household = Household::factory()->create();
    $kid = User::factory()->create(['household_id' => $household->id, 'name' => 'Kid A']);
    $helper = User::factory()->create(['household_id' => $household->id, 'name' => 'Kid B']);
    $supervisor = User::factory()->create(['household_id' => $household->id]);
    $kid->syncRoles(['kid']);
    $helper->syncRoles(['kid']);
    $supervisor->syncRoles(['supervisor']);

    $chore = ChoreInstance::factory()->create([
        'household_id' => $household->id,
        'assigned_to_user_id' => $kid->id,
        'base_points' => 5,
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
        ->post(route('chore-completions.approve', $completion), [])
        ->assertRedirect();

    $leaderboardResponse = $this->actingAs($kid)
        ->get(route('leaderboard.index'))
        ->assertSuccessful();

    $leaderboard = collect($leaderboardResponse->json());
    $kidRow = $leaderboard->firstWhere('id', $kid->id);
    $helperRow = $leaderboard->firstWhere('id', $helper->id);

    expect((int) $kidRow['total_points'])->toBeGreaterThan((int) $helperRow['total_points']);

    $historyResponse = $this->actingAs($kid)
        ->get(route('completion-history.index'))
        ->assertSuccessful();

    $history = collect($historyResponse->json());
    expect($history)->toHaveCount(1)
        ->and($history->first()['participants'])->toHaveCount(2);
});
