<?php

namespace App\Console\Commands;

use App\Models\AssignedChore;
use App\Models\AvailableChore;
use App\Models\Chore;
use App\Models\Family;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

class GenerateAvailableChores extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'chores:generate-available';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate available chores for today based on chore frequencies and completion history';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $today = today();
        $this->info("Generating available chores for {$today->format('Y-m-d')}...");

        $families = Family::all();
        $totalCreated = 0;
        $totalSkipped = 0;

        DB::transaction(function () use ($today, $families, &$totalCreated, &$totalSkipped) {
            foreach ($families as $family) {
                $chores = Chore::forFamily($family->id)->get();
                
                foreach ($chores as $chore) {
                    if ($this->shouldBeAvailable($chore, $today)) {
                        // Check if record already exists
                        $exists = AvailableChore::where('chore_id', $chore->id)
                            ->where('family_id', $family->id)
                            ->where('available_date', $today->toDateString())
                            ->exists();
                        
                        if ($exists) {
                            // Already exists, skip
                            $totalSkipped++;
                        } else {
                            // Try to create, catch unique constraint violation in case of race condition
                            try {
                                AvailableChore::create([
                                    'chore_id' => $chore->id,
                                    'family_id' => $family->id,
                                    'available_date' => $today->toDateString(),
                                ]);
                                $totalCreated++;
                            } catch (UniqueConstraintViolationException $e) {
                                // Race condition - another process created it between exists() check and create()
                                // Treat as skipped
                                $totalSkipped++;
                            }
                        }
                    }
                }
            }
        });

        $this->info("Created {$totalCreated} new available chores, skipped {$totalSkipped} existing ones.");
        return Command::SUCCESS;
    }

    /**
     * Determine if a chore should be available today based on its frequency.
     */
    private function shouldBeAvailable(Chore $chore, Carbon $today): bool
    {
        return match ($chore->frequency) {
            'daily' => $this->shouldBeAvailableDaily($chore),
            'twice_weekly' => $this->shouldBeAvailableTwiceWeekly($chore, $today),
            'weekly' => $this->shouldBeAvailableWeekly($chore, $today),
            'adhoc' => $this->shouldBeAvailableAdhoc($chore, $today),
            default => false, // twice_monthly, monthly handled elsewhere or not needed
        };
    }

    /**
     * Daily chores are always available.
     */
    private function shouldBeAvailableDaily(Chore $chore): bool
    {
        return true;
    }

    /**
     * Twice weekly: Add if last completion was 2+ days ago (or never completed).
     */
    private function shouldBeAvailableTwiceWeekly(Chore $chore, Carbon $today): bool
    {
        $lastCompletion = AssignedChore::where('chore_id', $chore->id)
            ->whereNotNull('completed_at')
            ->orderBy('completed_at', 'desc')
            ->first();

        if (!$lastCompletion) {
            return true; // Never completed, make it available
        }

        $daysSinceCompletion = $today->diffInDays($lastCompletion->completed_at);
        return $daysSinceCompletion >= 2;
    }

    /**
     * Weekly: Add if not completed in current week (Monday-Sunday).
     */
    private function shouldBeAvailableWeekly(Chore $chore, Carbon $today): bool
    {
        $weekStart = $today->copy()->startOfWeek(Carbon::MONDAY);
        $weekEnd = $weekStart->copy()->endOfWeek(Carbon::SUNDAY);

        $completedThisWeek = AssignedChore::where('chore_id', $chore->id)
            ->whereNotNull('completed_at')
            ->whereBetween('completed_at', [$weekStart->startOfDay(), $weekEnd->endOfDay()])
            ->exists();

        return !$completedThisWeek;
    }

    /**
     * Adhoc: Add if chore.updated_at > last_completion_date (or never completed and updated today).
     */
    private function shouldBeAvailableAdhoc(Chore $chore, Carbon $today): bool
    {
        $lastCompletion = AssignedChore::where('chore_id', $chore->id)
            ->whereNotNull('completed_at')
            ->orderBy('completed_at', 'desc')
            ->first();

        if (!$lastCompletion) {
            // Never completed, only add if chore was updated today (parent requested it)
            return $chore->updated_at->isToday();
        }

        // Compare chore updated_at with last completion date
        // If chore was updated after last completion, parent wants it done again
        return $chore->updated_at->greaterThan($lastCompletion->completed_at);
    }
}
