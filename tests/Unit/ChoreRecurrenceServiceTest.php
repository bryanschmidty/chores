<?php

use App\Enums\ChoreInstanceStatus;
use App\Enums\RecurrenceType;
use App\Models\ChoreTemplate;
use App\Services\ChoreRecurrenceService;
use Carbon\CarbonImmutable;
use Tests\TestCase;

uses(TestCase::class);

it('evaluates daily recurrence as due every day', function () {
    $service = new ChoreRecurrenceService;
    $template = new ChoreTemplate([
        'recurrence_type' => RecurrenceType::Daily->value,
    ]);
    $template->created_at = CarbonImmutable::parse('2026-02-01');

    expect($service->isDueOn($template, CarbonImmutable::parse('2026-02-02')))->toBeTrue()
        ->and($service->isDueOn($template, CarbonImmutable::parse('2026-02-03')))->toBeTrue();
});

it('evaluates weekly recurrence based on anchor weekday', function () {
    $service = new ChoreRecurrenceService;
    $template = new ChoreTemplate([
        'recurrence_type' => RecurrenceType::Weekly->value,
    ]);
    $template->created_at = CarbonImmutable::parse('2026-02-02'); // Monday.

    expect($service->isDueOn($template, CarbonImmutable::parse('2026-02-09')))->toBeTrue()
        ->and($service->isDueOn($template, CarbonImmutable::parse('2026-02-10')))->toBeFalse();
});

it('evaluates weekday recurrence from configured weekday values', function () {
    $service = new ChoreRecurrenceService;
    $template = new ChoreTemplate([
        'recurrence_type' => RecurrenceType::Weekdays->value,
        'recurrence_weekdays' => [1, 5], // Monday and Friday.
    ]);
    $template->created_at = CarbonImmutable::parse('2026-02-01');

    expect($service->isDueOn($template, CarbonImmutable::parse('2026-02-02')))->toBeTrue()
        ->and($service->isDueOn($template, CarbonImmutable::parse('2026-02-06')))->toBeTrue()
        ->and($service->isDueOn($template, CarbonImmutable::parse('2026-02-04')))->toBeFalse();
});

it('treats every n days as persistent due after due date until completion', function () {
    $service = new ChoreRecurrenceService;
    $template = new ChoreTemplate([
        'recurrence_type' => RecurrenceType::EveryNDays->value,
        'recurrence_interval' => 3,
    ]);
    $template->created_at = CarbonImmutable::parse('2026-02-01');
    $template->last_completed_at = null;

    expect($service->isDueOn($template, CarbonImmutable::parse('2026-02-04')))->toBeTrue()
        ->and($service->isDueOn($template, CarbonImmutable::parse('2026-02-08')))->toBeTrue();
});

it('resets every n days due date from last completion', function () {
    $service = new ChoreRecurrenceService;
    $template = new ChoreTemplate([
        'recurrence_type' => RecurrenceType::EveryNDays->value,
        'recurrence_interval' => 3,
    ]);
    $template->created_at = CarbonImmutable::parse('2026-02-01');
    $template->last_completed_at = CarbonImmutable::parse('2026-02-10');

    expect($service->isDueOn($template, CarbonImmutable::parse('2026-02-11')))->toBeFalse()
        ->and($service->isDueOn($template, CarbonImmutable::parse('2026-02-13')))->toBeTrue();
});

it('derives upcoming due and overdue statuses from due date', function () {
    $service = new ChoreRecurrenceService;
    $asOf = CarbonImmutable::parse('2026-02-10');

    expect($service->statusForDate(CarbonImmutable::parse('2026-02-11'), $asOf))->toBe(ChoreInstanceStatus::Upcoming)
        ->and($service->statusForDate(CarbonImmutable::parse('2026-02-10'), $asOf))->toBe(ChoreInstanceStatus::Due)
        ->and($service->statusForDate(CarbonImmutable::parse('2026-02-09'), $asOf))->toBe(ChoreInstanceStatus::Overdue);
});
