<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AssignedChore extends Model
{
    protected $fillable = [
        'chore_id',
        'family_id',
        'assigned_to',
        'assigned_by',
        'due_date',
        'week_start_date',
        'status',
        'completed_at',
    ];

    protected $casts = [
        'due_date' => 'date',
        'week_start_date' => 'date',
        'completed_at' => 'datetime',
    ];

    // Note: Dates are cast as 'date' which returns Carbon instances
    // For API serialization, Laravel will automatically format them as YYYY-MM-DD

    // Relationships
    public function chore(): BelongsTo
    {
        return $this->belongsTo(Chore::class);
    }

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function completion(): HasOne
    {
        return $this->hasOne(ChoreCompletion::class, 'assigned_chore_id');
    }

    // Scopes
    public function scopeForFamily($query, $familyId)
    {
        return $query->where('family_id', $familyId);
    }

    public function scopeAssignedTo($query, $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    public function scopeDueThisWeek($query, $weekStartDate)
    {
        return $query->whereDate('week_start_date', $weekStartDate);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', 'overdue');
    }

    public function scopeDueToday($query)
    {
        return $query->whereDate('due_date', today());
    }

    public function scopeDueBefore($query, $date)
    {
        return $query->where('due_date', '<=', $date);
    }

    // Helper methods
    public function isOverdue(): bool
    {
        // Only mark as overdue if due_date is strictly before today (not today or future)
        if (!$this->due_date || $this->status !== 'pending') {
            return false;
        }
        // due_date is cast as 'date' so it's already a Carbon instance
        return $this->due_date->startOfDay()->lt(today()->startOfDay());
    }

    public function isDueToday(): bool
    {
        return $this->due_date && $this->due_date->isToday() && $this->status === 'pending';
    }

    public function canBeCompleted(): bool
    {
        return $this->status === 'pending' && $this->due_date <= today();
    }

    public function updateStatus(): void
    {
        if ($this->isOverdue()) {
            $this->update(['status' => 'overdue']);
        }
    }

    public function markCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    public function getPointsValue(): int
    {
        return $this->chore->points;
    }

    public function getStatusText(): string
    {
        return match ($this->status) {
            'pending' => 'Pending',
            'completed' => 'Completed',
            'overdue' => 'Overdue',
            default => 'Unknown',
        };
    }
}
