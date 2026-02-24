<?php

namespace App\Services;

use App\Enums\ChoreInstanceStatus;
use App\Enums\RecurrenceType;
use App\Models\ChoreTemplate;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class ChoreRecurrenceService
{
    public function isDueOn(ChoreTemplate $template, CarbonInterface $date): bool
    {
        $targetDate = CarbonImmutable::instance($date)->startOfDay();
        $recurrenceType = $this->recurrenceTypeValue($template);

        if ($recurrenceType === RecurrenceType::EveryNDays->value) {
            return $this->isEveryNDaysDue($template, $targetDate);
        }

        if ($recurrenceType === RecurrenceType::Daily->value) {
            return true;
        }

        if ($recurrenceType === RecurrenceType::Weekly->value) {
            return $targetDate->dayOfWeek === $this->anchorDate($template)->dayOfWeek;
        }

        if ($recurrenceType === RecurrenceType::Weekdays->value) {
            $allowedWeekdays = $this->weekdayValues($template);

            return in_array($targetDate->dayOfWeek, $allowedWeekdays, true);
        }

        return false;
    }

    public function nextDueDate(ChoreTemplate $template, CarbonInterface $fromDate): ?CarbonImmutable
    {
        $cursor = CarbonImmutable::instance($fromDate)->startOfDay();
        $recurrenceType = $this->recurrenceTypeValue($template);

        if ($recurrenceType === RecurrenceType::EveryNDays->value) {
            $interval = max((int) ($template->recurrence_interval ?? 1), 1);
            $lastCompletedAt = $template->last_completed_at?->copy()->startOfDay();
            $anchor = $lastCompletedAt?->toImmutable() ?? $this->anchorDate($template);

            return $anchor->addDays($interval);
        }

        for ($dayOffset = 0; $dayOffset <= 370; $dayOffset++) {
            $candidate = $cursor->addDays($dayOffset);
            if ($this->isDueOn($template, $candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * @return Collection<int, CarbonImmutable>
     */
    public function dueDatesInWindow(ChoreTemplate $template, CarbonInterface $windowStart, CarbonInterface $windowEnd): Collection
    {
        $start = CarbonImmutable::instance($windowStart)->startOfDay();
        $end = CarbonImmutable::instance($windowEnd)->startOfDay();
        $dates = collect();

        if ($end->lessThan($start)) {
            return $dates;
        }

        for ($cursor = $start; $cursor->lessThanOrEqualTo($end); $cursor = $cursor->addDay()) {
            if ($this->isDueOn($template, $cursor)) {
                $dates->push($cursor);
            }
        }

        return $dates;
    }

    public function statusForDate(CarbonInterface $dueAt, CarbonInterface $asOf): ChoreInstanceStatus
    {
        $dueDate = CarbonImmutable::instance($dueAt)->startOfDay();
        $asOfDate = CarbonImmutable::instance($asOf)->startOfDay();

        if ($dueDate->greaterThan($asOfDate)) {
            return ChoreInstanceStatus::Upcoming;
        }

        if ($dueDate->equalTo($asOfDate)) {
            return ChoreInstanceStatus::Due;
        }

        return ChoreInstanceStatus::Overdue;
    }

    public function hasMissedOccurrence(ChoreTemplate $template, CarbonInterface $asOf): bool
    {
        $asOfDate = CarbonImmutable::instance($asOf)->startOfDay();
        $nextDueDate = $this->nextDueDate($template, $this->anchorDate($template));

        if ($nextDueDate === null) {
            return false;
        }

        $recurrenceType = $this->recurrenceTypeValue($template);
        if ($recurrenceType === RecurrenceType::EveryNDays->value) {
            return $asOfDate->greaterThanOrEqualTo($nextDueDate);
        }

        return $asOfDate->greaterThan($nextDueDate);
    }

    private function isEveryNDaysDue(ChoreTemplate $template, CarbonImmutable $targetDate): bool
    {
        $interval = max((int) ($template->recurrence_interval ?? 1), 1);
        $lastCompletedAt = $template->last_completed_at?->copy()->startOfDay();
        $anchor = $lastCompletedAt?->toImmutable() ?? $this->anchorDate($template);
        $dueDate = $anchor->addDays($interval);

        return $targetDate->greaterThanOrEqualTo($dueDate);
    }

    private function anchorDate(ChoreTemplate $template): CarbonImmutable
    {
        return $template->created_at?->toImmutable()->startOfDay() ?? CarbonImmutable::now()->startOfDay();
    }

    /**
     * @return list<int>
     */
    private function weekdayValues(ChoreTemplate $template): array
    {
        $weekdayValues = $template->recurrence_weekdays;

        if (! is_array($weekdayValues) || $weekdayValues === []) {
            return [$this->anchorDate($template)->dayOfWeek];
        }

        return collect($weekdayValues)
            ->map(static fn (mixed $value): int => (int) $value)
            ->filter(static fn (int $day): bool => $day >= 0 && $day <= 6)
            ->values()
            ->all();
    }

    private function recurrenceTypeValue(ChoreTemplate $template): string
    {
        $recurrenceType = $template->recurrence_type;

        return $recurrenceType instanceof RecurrenceType ? $recurrenceType->value : (string) $recurrenceType;
    }
}
