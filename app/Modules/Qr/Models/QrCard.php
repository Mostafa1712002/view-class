<?php

namespace App\Modules\Qr\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class QrCard extends Model
{
    use SoftDeletes;

    protected $table = 'qr_cards';

    protected $fillable = [
        'student_id', 'school_id', 'group_id', 'token', 'card_code',
        'is_active', 'expires_at',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'expires_at' => 'date',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(QrAttendanceGroup::class, 'group_id');
    }

    /** Secure, non-guessable token (NOT the student id). */
    public static function generateToken(): string
    {
        return Str::random(48);
    }

    public static function generateCardCode(): string
    {
        return 'QR-'.strtoupper(Str::random(8));
    }

    public function isUsable(): bool
    {
        if (! $this->is_active) {
            return false;
        }
        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }
}
