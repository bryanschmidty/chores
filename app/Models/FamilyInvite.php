<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class FamilyInvite extends Model
{
    protected $fillable = [
        'family_id',
        'created_by',
        'invite_code',
        'email',
        'status',
        'expires_at',
        'accepted_by',
        'accepted_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'accepted_at' => 'datetime',
    ];

    // Relationships
    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function acceptedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'accepted_by');
    }

    // Scopes
    public function scopeForFamily($query, $familyId)
    {
        return $query->where('family_id', $familyId);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeAccepted($query)
    {
        return $query->where('status', 'accepted');
    }

    public function scopeExpired($query)
    {
        return $query->where('status', 'expired');
    }

    public function scopeValid($query)
    {
        return $query->pending()->where('expires_at', '>', now());
    }

    // Helper methods
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isAccepted(): bool
    {
        return $this->status === 'accepted';
    }

    public function isExpired(): bool
    {
        return $this->status === 'expired' || $this->expires_at < now();
    }

    public function isValid(): bool
    {
        return $this->isPending() && !$this->isExpired();
    }

    public function accept(User $user): void
    {
        $this->update([
            'status' => 'accepted',
            'accepted_by' => $user->id,
            'accepted_at' => now(),
        ]);
    }

    public function expire(): void
    {
        $this->update(['status' => 'expired']);
    }

    public function getStatusText(): string
    {
        return match ($this->status) {
            'pending' => $this->isExpired() ? 'Expired' : 'Pending',
            'accepted' => 'Accepted',
            'expired' => 'Expired',
            default => 'Unknown',
        };
    }

    // Static helper methods
    public static function generateInviteCode(): string
    {
        do {
            $code = Str::upper(Str::random(8));
        } while (static::where('invite_code', $code)->exists());

        return $code;
    }

    public static function createForFamily(Family $family, User $createdBy, ?string $email = null, int $expiryDays = 7): self
    {
        return static::create([
            'family_id' => $family->id,
            'created_by' => $createdBy->id,
            'invite_code' => static::generateInviteCode(),
            'email' => $email,
            'expires_at' => now()->addDays($expiryDays),
        ]);
    }
}
