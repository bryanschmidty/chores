<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PointsLedger extends Model
{
    /** @use HasFactory<\Database\Factories\PointsLedgerFactory> */
    use HasFactory;

    /**
     * @var string
     */
    protected $table = 'points_ledger';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'household_id',
        'user_id',
        'chore_completion_id',
        'awarded_by_user_id',
        'points',
        'awarded_at',
        'entry_type',
        'description',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'awarded_at' => 'datetime',
        ];
    }

    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function choreCompletion(): BelongsTo
    {
        return $this->belongsTo(ChoreCompletion::class);
    }

    public function awardedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'awarded_by_user_id');
    }
}
