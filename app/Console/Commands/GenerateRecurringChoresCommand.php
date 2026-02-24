<?php

namespace App\Console\Commands;

use App\Enums\ChoreSourceType;
use App\Enums\RecurrenceType;
use App\Models\ChoreInstance;
use App\Models\ChoreTemplate;
use App\Models\Household;
use App\Services\ChoreRecurrenceService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class GenerateRecurringChoresCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'chores:generate-recurring
        {--days-ahead=7 : Number of days ahead to pre-generate recurring chores}
        {--days-behind=0 : Number of days behind to backfill recurring chores}
        {--household-id= : Restrict generation to a single household id}
        {--dry-run : Show what would be generated without writing records}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate and backfill recurring chore instances from active templates';

    /**
     * Execute the console command.
     */
    public function handle(ChoreRecurrenceService $choreRecurrenceService): int
    {
        $daysAhead = max((int) $this->option('days-ahead'), 0);
        $daysBehind = max((int) $this->option('days-behind'), 0);
        $dryRun = (bool) $this->option('dry-run');
        $householdId = $this->option('household-id');
        $householdId = $householdId !== null ? (int) $householdId : null;

        $households = Household::query()
            ->select(['id', 'timezone'])
            ->when($householdId !== null, function ($query) use ($householdId) {
                $query->where('id', $householdId);
            })
            ->orderBy('id')
            ->get();

        if ($households->isEmpty()) {
            $this->warn('No households found for recurring chore generation.');

            return self::SUCCESS;
        }

        $createdCount = 0;
        $skippedCount = 0;

        foreach ($households as $household) {
            $timezone = $household->timezone ?: 'UTC';
            $asOfDate = CarbonImmutable::now($timezone)->startOfDay();
            $windowStart = $asOfDate->subDays($daysBehind);
            $windowEnd = $asOfDate->addDays($daysAhead);

            $templates = ChoreTemplate::query()
                ->where('household_id', $household->id)
                ->where('is_active', true)
                ->orderBy('id')
                ->get();

            foreach ($templates as $template) {
                $dueDates = $this->dueDatesForTemplate(
                    $template,
                    $choreRecurrenceService,
                    $asOfDate,
                    $windowStart,
                    $windowEnd
                );

                foreach ($dueDates as $dueDate) {
                    $alreadyExists = ChoreInstance::query()
                        ->where('household_id', $template->household_id)
                        ->where('chore_template_id', $template->id)
                        ->whereDate('due_at', $dueDate->toDateString())
                        ->exists();

                    if ($alreadyExists) {
                        $skippedCount++;

                        continue;
                    }

                    if (! $dryRun) {
                        ChoreInstance::query()->create([
                            'household_id' => $template->household_id,
                            'chore_template_id' => $template->id,
                            'created_by_user_id' => $template->created_by_user_id,
                            'assigned_to_user_id' => $template->default_assignee_user_id,
                            'claimed_by_user_id' => null,
                            'source_type' => ChoreSourceType::Recurring->value,
                            'title' => $template->title,
                            'description' => $template->description,
                            'due_at' => $dueDate->startOfDay(),
                            'deadline_at' => $dueDate->endOfDay(),
                            'claimed_at' => null,
                            'base_points' => $template->points,
                            'adjusted_points' => null,
                            'status' => $choreRecurrenceService->statusForDate($dueDate, $asOfDate)->value,
                        ]);
                    }

                    $createdCount++;
                }
            }
        }

        $this->info(sprintf(
            'Recurring chores generation complete (created: %d, skipped-existing: %d, dry-run: %s).',
            $createdCount,
            $skippedCount,
            $dryRun ? 'yes' : 'no'
        ));

        return self::SUCCESS;
    }

    /**
     * @return Collection<int, CarbonImmutable>
     */
    private function dueDatesForTemplate(
        ChoreTemplate $template,
        ChoreRecurrenceService $choreRecurrenceService,
        CarbonImmutable $asOfDate,
        CarbonImmutable $windowStart,
        CarbonImmutable $windowEnd
    ): Collection {
        $recurrenceType = $template->recurrence_type;
        $recurrenceTypeValue = $recurrenceType instanceof RecurrenceType ? $recurrenceType->value : (string) $recurrenceType;

        if ($recurrenceTypeValue !== RecurrenceType::EveryNDays->value) {
            return $choreRecurrenceService->dueDatesInWindow($template, $windowStart, $windowEnd);
        }

        $interval = max((int) ($template->recurrence_interval ?? 1), 1);
        $lastCompletedAt = $template->last_completed_at?->copy()->startOfDay();

        if ($lastCompletedAt === null) {
            return collect([$asOfDate]);
        }

        if ($lastCompletedAt->diffInDays($asOfDate) >= $interval) {
            return collect([$asOfDate]);
        }

        return collect();
    }
}
