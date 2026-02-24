<?php

use App\Enums\ChoreSourceType;
use App\Enums\RecurrenceType;
use App\Models\ChoreInstance;
use App\Models\ChoreTemplate;
use App\Models\Household;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

it('generates recurring chores within window and remains idempotent', function () {
    Carbon::setTestNow('2026-03-10 09:00:00');

    $household = Household::factory()->create(['timezone' => 'UTC']);
    $parent = User::factory()->create(['household_id' => $household->id]);
    $template = ChoreTemplate::factory()->create([
        'household_id' => $household->id,
        'created_by_user_id' => $parent->id,
        'default_assignee_user_id' => null,
        'recurrence_type' => RecurrenceType::Daily->value,
        'is_active' => true,
        'created_at' => Carbon::now()->subDays(5),
        'title' => 'Daily dishes',
        'points' => 6,
    ]);

    $this->artisan('chores:generate-recurring', [
        '--days-ahead' => 2,
    ])->assertSuccessful();

    $generated = ChoreInstance::query()
        ->where('household_id', $household->id)
        ->where('chore_template_id', $template->id)
        ->orderBy('due_at')
        ->get();

    expect($generated)->toHaveCount(3)
        ->and($generated->every(fn (ChoreInstance $instance): bool => $instance->source_type === ChoreSourceType::Recurring))
        ->and($generated->pluck('due_at')->map(fn ($dueAt) => $dueAt?->toDateString())->all())
        ->toBe(['2026-03-10', '2026-03-11', '2026-03-12']);

    $this->artisan('chores:generate-recurring', [
        '--days-ahead' => 2,
    ])->assertSuccessful();

    expect(
        ChoreInstance::query()
            ->where('household_id', $household->id)
            ->where('chore_template_id', $template->id)
            ->count()
    )->toBe(3);

    Carbon::setTestNow();
});

it('supports backfill and household scoping while ignoring inactive templates', function () {
    Carbon::setTestNow('2026-04-20 09:00:00');

    $householdA = Household::factory()->create(['timezone' => 'UTC']);
    $householdB = Household::factory()->create(['timezone' => 'UTC']);
    $userA = User::factory()->create(['household_id' => $householdA->id]);
    $userB = User::factory()->create(['household_id' => $householdB->id]);

    $activeTemplateA = ChoreTemplate::factory()->create([
        'household_id' => $householdA->id,
        'created_by_user_id' => $userA->id,
        'recurrence_type' => RecurrenceType::Daily->value,
        'is_active' => true,
        'created_at' => Carbon::now()->subDays(10),
    ]);

    ChoreTemplate::factory()->create([
        'household_id' => $householdA->id,
        'created_by_user_id' => $userA->id,
        'recurrence_type' => RecurrenceType::Daily->value,
        'is_active' => false,
        'created_at' => Carbon::now()->subDays(10),
    ]);

    ChoreTemplate::factory()->create([
        'household_id' => $householdB->id,
        'created_by_user_id' => $userB->id,
        'recurrence_type' => RecurrenceType::Daily->value,
        'is_active' => true,
        'created_at' => Carbon::now()->subDays(10),
    ]);

    $this->artisan('chores:generate-recurring', [
        '--household-id' => $householdA->id,
        '--days-behind' => 1,
        '--days-ahead' => 1,
    ])->assertSuccessful();

    $createdForHouseholdA = ChoreInstance::query()
        ->where('household_id', $householdA->id)
        ->where('source_type', ChoreSourceType::Recurring->value)
        ->get();

    expect($createdForHouseholdA)->toHaveCount(3)
        ->and($createdForHouseholdA->pluck('chore_template_id')->unique()->all())
        ->toBe([$activeTemplateA->id]);

    expect(
        ChoreInstance::query()
            ->where('household_id', $householdB->id)
            ->where('source_type', ChoreSourceType::Recurring->value)
            ->count()
    )->toBe(0);

    Carbon::setTestNow();
});

it('creates only a today instance for every-n-days templates without completion history', function () {
    Carbon::setTestNow('2026-05-10 09:00:00');

    $household = Household::factory()->create(['timezone' => 'UTC']);
    $user = User::factory()->create(['household_id' => $household->id]);
    $template = ChoreTemplate::factory()->create([
        'household_id' => $household->id,
        'created_by_user_id' => $user->id,
        'recurrence_type' => RecurrenceType::EveryNDays->value,
        'recurrence_interval' => 3,
        'last_completed_at' => null,
        'is_active' => true,
        'created_at' => Carbon::now()->subDays(20),
    ]);

    $this->artisan('chores:generate-recurring', [
        '--days-ahead' => 7,
    ])->assertSuccessful();

    $generated = ChoreInstance::query()
        ->where('household_id', $household->id)
        ->where('chore_template_id', $template->id)
        ->orderBy('due_at')
        ->get();

    expect($generated)->toHaveCount(1)
        ->and($generated->first()?->due_at?->toDateString())->toBe('2026-05-10');

    Carbon::setTestNow();
});

it('creates a today instance for every-n-days templates when last completion is stale', function () {
    Carbon::setTestNow('2026-05-10 09:00:00');

    $household = Household::factory()->create(['timezone' => 'UTC']);
    $user = User::factory()->create(['household_id' => $household->id]);
    $template = ChoreTemplate::factory()->create([
        'household_id' => $household->id,
        'created_by_user_id' => $user->id,
        'recurrence_type' => RecurrenceType::EveryNDays->value,
        'recurrence_interval' => 3,
        'last_completed_at' => Carbon::now()->subDays(5),
        'is_active' => true,
    ]);

    $this->artisan('chores:generate-recurring', [
        '--days-ahead' => 7,
    ])->assertSuccessful();

    $generated = ChoreInstance::query()
        ->where('household_id', $household->id)
        ->where('chore_template_id', $template->id)
        ->orderBy('due_at')
        ->get();

    expect($generated)->toHaveCount(1)
        ->and($generated->first()?->due_at?->toDateString())->toBe('2026-05-10');

    Carbon::setTestNow();
});
