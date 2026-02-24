<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChoreCompletion extends Model
{
    protected $fillable = [
        'assigned_chore_id',
        'user_id',
        'completed_at',
        'verified_by',
        'verification_status',
        'completion_percentage',
        'before_photo',
        'after_photo',
        'notes',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
    ];

    // Relationships
    public function assignedChore(): BelongsTo
    {
        return $this->belongsTo(AssignedChore::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('verification_status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('verification_status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('verification_status', 'rejected');
    }

    public function scopeForFamily($query, $familyId)
    {
        return $query->whereHas('assignedChore', function ($q) use ($familyId) {
            $q->where('family_id', $familyId);
        });
    }

    // Helper methods
    public function isPending(): bool
    {
        return $this->verification_status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->verification_status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->verification_status === 'rejected';
    }

    public function getPointsAwarded(): int
    {
        if ($this->isApproved()) {
            return (int) round(($this->assignedChore->chore->points * $this->completion_percentage) / 100);
        }

        return 0;
    }

    public function approve(int $percentage = 100, ?int $verifiedBy = null): void
    {
        $this->update([
            'verification_status' => 'approved',
            'completion_percentage' => $percentage,
            'verified_by' => $verifiedBy,
        ]);
    }

    public function reject(?int $verifiedBy = null): void
    {
        $this->update([
            'verification_status' => 'rejected',
            'verified_by' => $verifiedBy,
        ]);
    }

    public function getStatusText(): string
    {
        return match ($this->verification_status) {
            'pending' => 'Pending Verification',
            'approved' => "Approved ({$this->completion_percentage}%)",
            'rejected' => 'Rejected',
            default => 'Unknown',
        };
    }
}
