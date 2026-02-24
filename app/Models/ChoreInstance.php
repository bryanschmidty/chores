<?php

namespace App\Models;

use App\Enums\ChoreInstanceStatus;
use App\Enums\ChoreSourceType;
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
}
