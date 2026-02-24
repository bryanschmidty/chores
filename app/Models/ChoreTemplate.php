<?php

namespace App\Models;

use App\Enums\RecurrenceType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChoreTemplate extends Model
{
    /** @use HasFactory<\Database\Factories\ChoreTemplateFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'household_id',
        'created_by_user_id',
        'default_assignee_user_id',
        'title',
        'description',
        'points',
        'recurrence_type',
        'recurrence_interval',
        'recurrence_weekdays',
        'is_active',
        'last_completed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'recurrence_type' => RecurrenceType::class,
            'recurrence_weekdays' => 'array',
            'is_active' => 'boolean',
            'last_completed_at' => 'datetime',
        ];
    }

    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function defaultAssignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'default_assignee_user_id');
    }

    public function choreInstances(): HasMany
    {
        return $this->hasMany(ChoreInstance::class);
    }

    public function weeklyClaims(): HasMany
    {
        return $this->hasMany(WeeklyClaim::class);
    }
}
