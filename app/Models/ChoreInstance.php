<?php

namespace App\Models;

use App\Enums\ChoreInstanceStatus;
use App\Enums\ChoreSourceType;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChoreInstance extends Model
{
    /** @use HasFactory<\Database\Factories\ChoreInstanceFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'household_id',
        'chore_template_id',
        'created_by_user_id',
        'assigned_to_user_id',
        'claimed_by_user_id',
        'source_type',
        'title',
        'description',
        'due_at',
        'deadline_at',
        'claimed_at',
        'base_points',
        'adjusted_points',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'source_type' => ChoreSourceType::class,
            'status' => ChoreInstanceStatus::class,
            'due_at' => 'datetime',
            'deadline_at' => 'datetime',
            'claimed_at' => 'datetime',
        ];
    }

    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(ChoreTemplate::class, 'chore_template_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    public function claimedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'claimed_by_user_id');
    }

    public function completions(): HasMany
    {
        return $this->hasMany(ChoreCompletion::class);
    }

    public function scopeForHousehold(Builder $query, int $householdId): Builder
    {
        return $query->where('household_id', $householdId);
    }

    public function scopeAssignedToUser(Builder $query, int $userId): Builder
    {
        return $query->where('assigned_to_user_id', $userId);
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereNull('assigned_to_user_id');
    }

    public function scopeOpenDueOrOverdue(Builder $query): Builder
    {
        return $query->open()->whereIn('status', [
            ChoreInstanceStatus::Due->value,
            ChoreInstanceStatus::Overdue->value,
        ]);
    }

    public function scopeFutureRecurringWindow(Builder $query, CarbonInterface $asOf, int $days = 7): Builder
    {
        return $query
            ->where('source_type', ChoreSourceType::Recurring->value)
            ->where('due_at', '>', $asOf)
            ->where('due_at', '<=', $asOf->copy()->addDays($days));
    }
}
