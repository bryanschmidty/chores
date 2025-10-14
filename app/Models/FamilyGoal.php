<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FamilyGoal extends Model
{
    protected $fillable = [
        'family_id',
        'name',
        'description',
        'target_points',
        'current_points',
        'image',
        'is_achieved',
        'achieved_at',
    ];

    protected $casts = [
        'is_achieved' => 'boolean',
        'achieved_at' => 'datetime',
    ];

    // Relationships
    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    // Scopes
    public function scopeForFamily($query, $familyId)
    {
        return $query->where('family_id', $familyId);
    }

    public function scopeActive($query)
    {
        return $query->where('is_achieved', false);
    }

    public function scopeAchieved($query)
    {
        return $query->where('is_achieved', true);
    }

    // Helper methods
    public function getProgressPercentage(): float
    {
        if ($this->target_points <= 0) {
            return 0;
        }

        return min(100, ($this->current_points / $this->target_points) * 100);
    }

    public function addPoints(int $points): void
    {
        $this->increment('current_points', $points);
        $this->checkIfAchieved();
    }

    public function removePoints(int $points): void
    {
        $this->decrement('current_points', $points);
        
        // If we go below target, mark as not achieved
        if ($this->current_points < $this->target_points && $this->is_achieved) {
            $this->update([
                'is_achieved' => false,
                'achieved_at' => null,
            ]);
        }
    }

    private function checkIfAchieved(): void
    {
        if ($this->current_points >= $this->target_points && !$this->is_achieved) {
            $this->update([
                'is_achieved' => true,
                'achieved_at' => now(),
            ]);
        }
    }

    public function getProgressText(): string
    {
        $percentage = $this->getProgressPercentage();
        return "{$this->current_points}/{$this->target_points} points ({$percentage}%)";
    }

    public function getRemainingPoints(): int
    {
        return max(0, $this->target_points - $this->current_points);
    }
}
