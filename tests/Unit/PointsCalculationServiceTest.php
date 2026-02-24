<?php

use App\Enums\ChoreSourceType;
use App\Models\ChoreCompletion;
use App\Models\ChoreCompletionParticipant;
use App\Models\ChoreInstance;
use App\Models\Household;
use App\Models\User;
use App\Services\PointsCalculationService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function completionWithParticipants(
    int $basePoints,
    ChoreSourceType $sourceType,
    array $participantUserIds,
    int $primaryUserId,
    ?CarbonImmutable $deadlineAt = null,
    ?CarbonImmutable $completedAt = null
): ChoreCompletion {
    $household = Household::factory()->create();
    $creator = User::factory()->create(['household_id' => $household->id]);

    $choreInstance = ChoreInstance::factory()->create([
        'household_id' => $household->id,
        'created_by_user_id' => $creator->id,
        'source_type' => $sourceType->value,
        'base_points' => $basePoints,
        'deadline_at' => $deadlineAt,
    ]);

    $completion = ChoreCompletion::factory()->create([
        'household_id' => $household->id,
        'chore_instance_id' => $choreInstance->id,
        'completed_by_user_id' => $primaryUserId,
        'completed_at' => $completedAt ?? CarbonImmutable::now(),
    ]);

    foreach ($participantUserIds as $participantUserId) {
        ChoreCompletionParticipant::factory()->create([
            'chore_completion_id' => $completion->id,
            'user_id' => $participantUserId,
            'is_primary' => $participantUserId === $primaryUserId,
            'points_awarded' => null,
        ]);
    }

    return $completion->fresh(['participants', 'choreInstance']);
}

it('splits points evenly when divisible', function () {
    $household = Household::factory()->create();
    $primary = User::factory()->create(['household_id' => $household->id]);
    $helper = User::factory()->create(['household_id' => $household->id]);
    $completion = completionWithParticipants(
        8,
        ChoreSourceType::AdHoc,
        [$primary->id, $helper->id],
        $primary->id
    );

    $result = app(PointsCalculationService::class)->calculate($completion);

    expect($result['final_points'])->toBe(8)
        ->and($result['allocations'][$primary->id])->toBe(4)
        ->and($result['allocations'][$helper->id])->toBe(4);
});

it('gives remainder to primary participant', function () {
    $household = Household::factory()->create();
    $primary = User::factory()->create(['household_id' => $household->id]);
    $helper = User::factory()->create(['household_id' => $household->id]);
    $completion = completionWithParticipants(
        9,
        ChoreSourceType::AdHoc,
        [$primary->id, $helper->id],
        $primary->id
    );

    $result = app(PointsCalculationService::class)->calculate($completion);

    expect($result['allocations'][$primary->id])->toBe(5)
        ->and($result['allocations'][$helper->id])->toBe(4);
});

it('applies overdue ad hoc penalty before split', function () {
    $household = Household::factory()->create();
    $primary = User::factory()->create(['household_id' => $household->id]);
    $helper = User::factory()->create(['household_id' => $household->id]);
    $completion = completionWithParticipants(
        9,
        ChoreSourceType::AdHoc,
        [$primary->id, $helper->id],
        $primary->id,
        CarbonImmutable::parse('2026-02-10 10:00:00'),
        CarbonImmutable::parse('2026-02-11 10:00:00')
    );

    $result = app(PointsCalculationService::class)->calculate($completion);

    expect($result['final_points'])->toBe(4)
        ->and($result['allocations'][$primary->id])->toBe(2)
        ->and($result['allocations'][$helper->id])->toBe(2);
});

it('awards all points to primary when participants exceed points', function () {
    $household = Household::factory()->create();
    $primary = User::factory()->create(['household_id' => $household->id]);
    $helperOne = User::factory()->create(['household_id' => $household->id]);
    $helperTwo = User::factory()->create(['household_id' => $household->id]);
    $completion = completionWithParticipants(
        2,
        ChoreSourceType::AdHoc,
        [$primary->id, $helperOne->id, $helperTwo->id],
        $primary->id
    );

    $result = app(PointsCalculationService::class)->calculate($completion);

    expect($result['allocations'][$primary->id])->toBe(2)
        ->and($result['allocations'][$helperOne->id])->toBe(0)
        ->and($result['allocations'][$helperTwo->id])->toBe(0);
});

it('uses supervisor adjusted points when provided', function () {
    $household = Household::factory()->create();
    $primary = User::factory()->create(['household_id' => $household->id]);
    $helper = User::factory()->create(['household_id' => $household->id]);
    $completion = completionWithParticipants(
        20,
        ChoreSourceType::AdHoc,
        [$primary->id, $helper->id],
        $primary->id
    );

    $result = app(PointsCalculationService::class)->calculate($completion, 7);

    expect($result['final_points'])->toBe(7)
        ->and($result['allocations'][$primary->id])->toBe(4)
        ->and($result['allocations'][$helper->id])->toBe(3);
});
