<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Chore extends Model
{
    protected $fillable = [
        'family_id',
        'template_id',
        'assigned_to',
        'created_by',
        'name',
        'description',
        'points',
        'recurrence_type',
        'recurrence_interval',
        'next_due_date',
        'requires_verification',
        'status',
    ];

    protected $casts = [
        'next_due_date' => 'date',
        'requires_verification' => 'boolean',
    ];

    // Relationships
    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(ChoreTemplate::class, 'template_id');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function completions(): HasMany
    {
        return $this->hasMany(ChoreCompletion::class);
    }

    public function latestCompletion(): HasMany
    {
        return $this->hasMany(ChoreCompletion::class)->latest();
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
        return $query->whereDate('next_due_date', today());
    }

    public function scopeDueBefore($query, $date)
    {
        return $query->where('next_due_date', '<=', $date);
    }

    public function scopeRecurring($query)
    {
        return $query->where('recurrence_type', '!=', 'none');
    }

    // Helper methods
    public function isOverdue(): bool
    {
        return $this->next_due_date < today() && $this->status === 'pending';
    }

    public function isDueToday(): bool
    {
        return $this->next_due_date->isToday() && $this->status === 'pending';
    }

    public function isRecurring(): bool
    {
        return $this->recurrence_type !== 'none';
    }

    public function calculateNextDueDate(): Carbon
    {
        $currentDate = $this->next_due_date;

        return match ($this->recurrence_type) {
            'daily' => $currentDate->addDay(),
            'weekly' => $currentDate->addWeek(),
            'monthly' => $currentDate->addMonth(),
            'custom' => $currentDate->addDays($this->recurrence_interval ?? 1),
            default => $currentDate,
        };
    }

    public function updateStatus(): void
    {
        if ($this->isOverdue()) {
            $this->update(['status' => 'overdue']);
        }
    }

    public function markCompleted(): void
    {
        $this->update(['status' => 'completed']);

        if ($this->isRecurring()) {
            $this->update([
                'next_due_date' => $this->calculateNextDueDate(),
                'status' => 'pending',
            ]);
        }
    }

    public function getPhotoRequirements(): string
    {
        if ($this->template) {
            return $this->template->photo_requirements;
        }

        return 'none'; // Default for ad-hoc chores
    }

    public function requiresPhotos(): bool
    {
        return in_array($this->getPhotoRequirements(), ['after', 'both']);
    }

    public function requiresBeforePhoto(): bool
    {
        return $this->getPhotoRequirements() === 'both';
    }

    public function getRecurrenceText(): string
    {
        return match ($this->recurrence_type) {
            'daily' => 'Daily',
            'weekly' => 'Weekly',
            'monthly' => 'Monthly',
            'custom' => "Every {$this->recurrence_interval} days",
            default => 'One-time',
        };
    }
}
