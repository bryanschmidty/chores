<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChoreTemplate extends Model
{
    protected $fillable = [
        'family_id',
        'name',
        'description',
        'points',
        'photo_requirements',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Relationships
    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    public function chores(): HasMany
    {
        return $this->hasMany(Chore::class, 'template_id');
    }

    // Scopes
    public function scopeForFamily($query, $familyId)
    {
        return $query->where('family_id', $familyId);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Helper methods
    public function getPhotoRequirementsText(): string
    {
        return match ($this->photo_requirements) {
            'none' => 'No photos required',
            'after' => 'After photo required',
            'both' => 'Before and after photos required',
            default => 'No photos required',
        };
    }
}
