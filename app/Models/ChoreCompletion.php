<?php

namespace App\Models;

use App\Enums\ApprovalStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChoreCompletion extends Model
{
    /** @use HasFactory<\Database\Factories\ChoreCompletionFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'household_id',
        'chore_instance_id',
        'completed_by_user_id',
        'completed_at',
        'approval_status',
        'approved_by_user_id',
        'approved_at',
        'supervisor_adjusted_points',
        'approval_comment',
        'rejection_reason',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'approval_status' => ApprovalStatus::class,
            'completed_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }

    public function choreInstance(): BelongsTo
    {
        return $this->belongsTo(ChoreInstance::class);
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by_user_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(ChoreCompletionParticipant::class);
    }

    public function pointsLedgerEntries(): HasMany
    {
        return $this->hasMany(PointsLedger::class);
    }
}
