<?php

namespace App\Modules\Qr\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QrScan extends Model
{
    protected $table = 'qr_scans';

    protected $fillable = [
        'qr_card_id', 'student_id', 'school_id', 'group_id', 'scan_date',
        'scanned_at', 'result_status', 'channel', 'device_name', 'error_code',
        'recorded_by',
    ];

    protected $casts = [
        'scan_date'  => 'date',
        'scanned_at' => 'datetime',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function card(): BelongsTo
    {
        return $this->belongsTo(QrCard::class, 'qr_card_id');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(QrAttendanceGroup::class, 'group_id');
    }
}
