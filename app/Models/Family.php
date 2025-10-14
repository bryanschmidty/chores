<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Family extends Model
{
    protected $fillable = [
        'name',
        'settings',
    ];

    protected $casts = [
        'settings' => 'array',
    ];

    // Relationships
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function choreTemplates(): HasMany
    {
        return $this->hasMany(ChoreTemplate::class);
    }

    public function chores(): HasMany
    {
        return $this->hasMany(Chore::class);
    }

    public function shopItems(): HasMany
    {
        return $this->hasMany(ShopItem::class);
    }

    public function familyGoals(): HasMany
    {
        return $this->hasMany(FamilyGoal::class);
    }


    public function pointTransactions(): HasMany
    {
        return $this->hasMany(PointTransaction::class);
    }

    // Scopes
    public function scopeForFamily($query, $familyId)
    {
        return $query->where('id', $familyId);
    }

    // Helper methods
    public function getSetting($key, $default = null)
    {
        return data_get($this->settings, $key, $default);
    }

    public function setSetting($key, $value)
    {
        $settings = $this->settings ?? [];
        data_set($settings, $key, $value);
        $this->update(['settings' => $settings]);
    }

    public function admins()
    {
        return $this->users()->whereIn('role', ['admin', 'super-admin']);
    }

    public function members()
    {
        return $this->users()->where('role', 'member');
    }
}
