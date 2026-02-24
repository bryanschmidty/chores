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
        'name',
        'description',
        'points',
        'frequency',
        'review_required',
        'photos_required',
    ];

    protected $casts = [
        'review_required' => 'boolean',
    ];

    // Relationships
    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    public function assignedChores(): HasMany
    {
        return $this->hasMany(AssignedChore::class);
    }

    // Scopes
    public function scopeForFamily($query, $familyId)
    {
        return $query->where('family_id', $familyId);
    }

    public function scopeByFrequency($query, $frequency)
    {
        return $query->where('frequency', $frequency);
    }

    public function scopeRecurring($query)
    {
        return $query->where('frequency', '!=', 'adhoc');
    }

    // Helper methods
    public function isRecurring(): bool
    {
        return $this->frequency !== 'adhoc';
    }

    public function getOccurrencesPerWeek(): int
    {
        return match ($this->frequency) {
            'daily' => 6, // Monday-Saturday
            'twice_weekly' => 2,
            'weekly' => 1,
            'twice_monthly' => 1, // Every 2 weeks
            'monthly' => 1,
            'adhoc' => 0,
            default => 0,
        };
    }

    public function requiresPhotos(): bool
    {
        return in_array($this->photos_required, ['before_and_after', 'only_after']);
    }

    public function requiresBeforePhoto(): bool
    {
        return $this->photos_required === 'before_and_after';
    }

    public function getFrequencyText(): string
    {
        return match ($this->frequency) {
            'daily' => 'Daily',
            'twice_weekly' => 'Twice Weekly',
            'weekly' => 'Weekly',
            'twice_monthly' => 'Twice Monthly',
            'monthly' => 'Monthly',
            'adhoc' => 'Ad Hoc',
            default => 'Unknown',
        };
    }

    public function getPhotosRequiredText(): string
    {
        return match ($this->photos_required) {
            'none' => 'No photos required',
            'before_and_after' => 'Before and after photos required',
            'only_after' => 'After photo required',
            default => 'No photos required',
        };
    }
}
