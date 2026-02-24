<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'family_id',
        'role',
        'notification_preferences',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_logged_in' => 'datetime',
            'password' => 'hashed',
            'notification_preferences' => 'array',
        ];
    }

    // Relationships
    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    public function choreCompletions(): HasMany
    {
        return $this->hasMany(ChoreCompletion::class);
    }

    public function assignedChores(): HasMany
    {
        return $this->hasMany(AssignedChore::class, 'assigned_to');
    }

    public function assignedByMe(): HasMany
    {
        return $this->hasMany(AssignedChore::class, 'assigned_by');
    }

    public function redemptions(): HasMany
    {
        return $this->hasMany(Redemption::class);
    }

    public function pointTransactions(): HasMany
    {
        return $this->hasMany(PointTransaction::class);
    }

    public function verifiedCompletions(): HasMany
    {
        return $this->hasMany(ChoreCompletion::class, 'verified_by');
    }

    // Scopes
    public function scopeForFamily($query, $familyId)
    {
        return $query->where('family_id', $familyId);
    }

    public function scopeAdmins($query)
    {
        return $query->whereIn('role', ['admin', 'super-admin']);
    }

    public function scopeMembers($query)
    {
        return $query->where('role', 'member');
    }

    // Helper methods
    public function isSuperAdmin(): bool
    {
        return $this->role === 'super-admin';
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, ['admin', 'super-admin']);
    }

    public function isMember(): bool
    {
        return $this->role === 'member';
    }

    public function canManageFamily(): bool
    {
        return $this->isAdmin();
    }

    public function getPointsBalance(): int
    {
        return $this->pointTransactions()->sum('amount');
    }

    public function getNotificationPreference($type, $default = true): bool
    {
        return data_get($this->notification_preferences, $type, $default);
    }

    public function setNotificationPreference($type, $value): void
    {
        $preferences = $this->notification_preferences ?? [];
        data_set($preferences, $type, $value);
        $this->update(['notification_preferences' => $preferences]);
    }
}
