<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'household_id',
        'name',
        'email',
        'google_id',
        'google_email',
        'google_avatar_url',
        'password',
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
            'household_id' => 'integer',
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }

    public function createdChoreTemplates(): HasMany
    {
        return $this->hasMany(ChoreTemplate::class, 'created_by_user_id');
    }

    public function defaultAssignedTemplates(): HasMany
    {
        return $this->hasMany(ChoreTemplate::class, 'default_assignee_user_id');
    }

    public function createdChoreInstances(): HasMany
    {
        return $this->hasMany(ChoreInstance::class, 'created_by_user_id');
    }

    public function assignedChoreInstances(): HasMany
    {
        return $this->hasMany(ChoreInstance::class, 'assigned_to_user_id');
    }

    public function claimedChoreInstances(): HasMany
    {
        return $this->hasMany(ChoreInstance::class, 'claimed_by_user_id');
    }

    public function weeklyClaimsAssigned(): HasMany
    {
        return $this->hasMany(WeeklyClaim::class, 'assigned_to_user_id');
    }

    public function weeklyClaimsCreated(): HasMany
    {
        return $this->hasMany(WeeklyClaim::class, 'claimed_by_user_id');
    }

    public function completedChores(): HasMany
    {
        return $this->hasMany(ChoreCompletion::class, 'completed_by_user_id');
    }

    public function approvedChores(): HasMany
    {
        return $this->hasMany(ChoreCompletion::class, 'approved_by_user_id');
    }

    public function completionParticipants(): HasMany
    {
        return $this->hasMany(ChoreCompletionParticipant::class);
    }

    public function pointsLedgerEntries(): HasMany
    {
        return $this->hasMany(PointsLedger::class);
    }
}
