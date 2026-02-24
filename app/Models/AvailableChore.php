<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AvailableChore extends Model
{
    protected $fillable = [
        'chore_id',
        'family_id',
        'available_date',
    ];

    protected $casts = [
        'available_date' => 'date',
    ];

    // Relationships
    public function chore(): BelongsTo
    {
        return $this->belongsTo(Chore::class);
    }

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    // Scopes
    public function scopeForFamily($query, $familyId)
    {
        return $query->where('family_id', $familyId);
    }

    public function scopeForDate($query, $date)
    {
        return $query->whereDate('available_date', $date);
    }

    public function scopeForToday($query)
    {
        return $query->whereDate('available_date', today());
    }
}
