<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PointTransaction extends Model
{
    protected $fillable = [
        'user_id',
        'family_id',
        'amount',
        'type',
        'related_model_type',
        'related_model_id',
        'description',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    public function relatedModel(): MorphTo
    {
        return $this->morphTo('related_model');
    }

    // Scopes
    public function scopeForFamily($query, $familyId)
    {
        return $query->where('family_id', $familyId);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeEarned($query)
    {
        return $query->where('amount', '>', 0);
    }

    public function scopeSpent($query)
    {
        return $query->where('amount', '<', 0);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    // Helper methods
    public function isEarned(): bool
    {
        return $this->amount > 0;
    }

    public function isSpent(): bool
    {
        return $this->amount < 0;
    }

    public function getAbsoluteAmount(): int
    {
        return abs($this->amount);
    }

    public function getTypeText(): string
    {
        return match ($this->type) {
            'earned' => 'Earned',
            'spent' => 'Spent',
            'bonus' => 'Bonus',
            'contributed' => 'Contributed',
            'refunded' => 'Refunded',
            default => 'Unknown',
        };
    }

    // Static helper methods for creating transactions
    public static function createEarned(User $user, int $amount, string $description, $relatedModel = null): self
    {
        return static::create([
            'user_id' => $user->id,
            'family_id' => $user->family_id,
            'amount' => $amount,
            'type' => 'earned',
            'related_model_type' => $relatedModel ? get_class($relatedModel) : null,
            'related_model_id' => $relatedModel?->id,
            'description' => $description,
        ]);
    }

    public static function createSpent(User $user, int $amount, string $description, $relatedModel = null): self
    {
        return static::create([
            'user_id' => $user->id,
            'family_id' => $user->family_id,
            'amount' => -$amount,
            'type' => 'spent',
            'related_model_type' => $relatedModel ? get_class($relatedModel) : null,
            'related_model_id' => $relatedModel?->id,
            'description' => $description,
        ]);
    }

    public static function createBonus(User $user, int $amount, string $description): self
    {
        return static::create([
            'user_id' => $user->id,
            'family_id' => $user->family_id,
            'amount' => $amount,
            'type' => 'bonus',
            'description' => $description,
        ]);
    }

    public static function createContributed(User $user, int $amount, string $description, $relatedModel = null): self
    {
        return static::create([
            'user_id' => $user->id,
            'family_id' => $user->family_id,
            'amount' => -$amount,
            'type' => 'contributed',
            'related_model_type' => $relatedModel ? get_class($relatedModel) : null,
            'related_model_id' => $relatedModel?->id,
            'description' => $description,
        ]);
    }
}
