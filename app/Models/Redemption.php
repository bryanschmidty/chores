<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Redemption extends Model
{
    protected $fillable = [
        'shop_item_id',
        'user_id',
        'status',
        'approved_by',
        'redeemed_at',
        'notes',
    ];

    protected $casts = [
        'redeemed_at' => 'datetime',
    ];

    // Relationships
    public function shopItem(): BelongsTo
    {
        return $this->belongsTo(ShopItem::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeRedeemed($query)
    {
        return $query->where('status', 'redeemed');
    }

    public function scopeForFamily($query, $familyId)
    {
        return $query->whereHas('shopItem', function ($q) use ($familyId) {
            $q->where('family_id', $familyId);
        });
    }

    // Helper methods
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function isRedeemed(): bool
    {
        return $this->status === 'redeemed';
    }

    public function approve(?int $approvedBy = null): void
    {
        $this->update([
            'status' => 'approved',
            'approved_by' => $approvedBy,
        ]);
    }

    public function reject(?int $approvedBy = null): void
    {
        $this->update([
            'status' => 'rejected',
            'approved_by' => $approvedBy,
        ]);
    }

    public function markRedeemed(): void
    {
        $this->update([
            'status' => 'redeemed',
            'redeemed_at' => now(),
        ]);
    }

    public function getStatusText(): string
    {
        return match ($this->status) {
            'pending' => 'Pending Approval',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'redeemed' => 'Redeemed',
            default => 'Unknown',
        };
    }
}
