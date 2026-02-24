<?php

use App\Enums\RecurrenceType;
use App\Models\ChoreTemplate;
use App\Services\ChoreRecurrenceService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('skips missed weekly occurrences instead of carrying backlog', function () {
    $service = new ChoreRecurrenceService;

    $template = ChoreTemplate::factory()->create([
        'recurrence_type' => RecurrenceType::Weekly->value,
        'created_at' => CarbonImmutable::parse('2026-02-02'), // Monday anchor.
        'last_completed_at' => null,
    ]);

    $asOf = CarbonImmutable::parse('2026-02-04'); // Wednesday.
    $nextDue = $service->nextDueDate($template, $asOf);

    expect($service->isDueOn($template, $asOf))->toBeFalse()
        ->and($nextDue?->toDateString())->toBe('2026-02-09');
});

it('keeps every n days chores due after missed date until completion', function () {
    $service = new ChoreRecurrenceService;

    $template = ChoreTemplate::factory()->create([
        'recurrence_type' => RecurrenceType::EveryNDays->value,
        'recurrence_interval' => 2,
        'created_at' => CarbonImmutable::parse('2026-02-01'),
        'last_completed_at' => null,
    ]);

    expect($service->isDueOn($template, CarbonImmutable::parse('2026-02-03')))->toBeTrue()
        ->and($service->isDueOn($template, CarbonImmutable::parse('2026-02-06')))->toBeTrue();
});

it('resets every n days due calculation from completion date', function () {
    $service = new ChoreRecurrenceService;

    $template = ChoreTemplate::factory()->create([
        'recurrence_type' => RecurrenceType::EveryNDays->value,
        'recurrence_interval' => 4,
        'created_at' => CarbonImmutable::parse('2026-02-01'),
        'last_completed_at' => CarbonImmutable::parse('2026-02-10'),
    ]);

    expect($service->isDueOn($template, CarbonImmutable::parse('2026-02-12')))->toBeFalse()
        ->and($service->isDueOn($template, CarbonImmutable::parse('2026-02-14')))->toBeTrue();
});
