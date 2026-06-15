<?php

namespace App\Modules\SmsServices\Models;

use App\Models\School;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmsCreditRechargeRequest extends Model
{
    protected $table = 'sms_credit_recharge_requests';

    public const STATUSES = [
        'pending'  => 'قيد المراجعة',
        'approved' => 'مقبول',
        'rejected' => 'مرفوض',
    ];

    protected $fillable = [
        'school_id',
        'requested_by',
        'bank_name',
        'amount_transferred',
        'transfer_date',
        'from_bank',
        'from_account_no',
        'from_account_name',
        'receipt_path',
        'granted_credit',
        'status',
        'admin_note',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'transfer_date' => 'date',
        'reviewed_at'   => 'datetime',
        'amount_transferred' => 'decimal:2',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }
}
