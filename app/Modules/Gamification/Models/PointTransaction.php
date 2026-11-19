<?php

namespace App\Modules\Gamification\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/** Immutable ledger row: inserted once, never updated or deleted. */
class PointTransaction extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = [];

    protected $casts = ['meta' => 'array', 'points' => 'integer'];

    protected static function booted(): void
    {
        static::updating(fn () => throw new \LogicException('Ledger rows are immutable.'));
        static::deleting(fn () => throw new \LogicException('Ledger rows are immutable.'));
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
