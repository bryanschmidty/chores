<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShopItem extends Model
{
    protected $fillable = [
        'family_id',
        'name',
        'description',
        'cost',
        'stock',
        'requires_approval',
        'is_active',
        'image',
    ];

    protected $casts = [
        'requires_approval' => 'boolean',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    public function redemptions(): HasMany
    {
        return $this->hasMany(Redemption::class);
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

    public function scopeAvailable($query)
    {
        return $query->active()->where(function ($q) {
            $q->whereNull('stock')->orWhere('stock', '>', 0);
        });
    }

    // Helper methods
    public function isAvailable(): bool
    {
        return $this->is_active && ($this->stock === null || $this->stock > 0);
    }

    public function decreaseStock(): void
    {
        if ($this->stock !== null && $this->stock > 0) {
            $this->decrement('stock');
        }
    }

    public function getStockText(): string
    {
        if ($this->stock === null) {
            return 'Unlimited';
        }

        return $this->stock . ' remaining';
    }
}
