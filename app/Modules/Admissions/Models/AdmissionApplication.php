<?php

namespace App\Modules\Admissions\Models;

use App\Models\School;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Admissions / Registration request (Sprint 10 — #268).
 */
class AdmissionApplication extends Model
{
    use SoftDeletes;

    protected $table = 'admission_applications';

    protected $fillable = [
        'code', 'school_id', 'educational_company_id', 'academic_year_id',
        'student_name', 'guardian_name', 'phone', 'email', 'national_id',
        'hijri_code', 'birth_date', 'city', 'track', 'stage', 'grade',
        'nationality', 'address', 'appointment_at', 'data', 'status',
        'status_note', 'converted_student_id', 'reviewed_by', 'submitted_ip',
    ];

    protected $casts = [
        'data'           => 'array',
        'birth_date'     => 'date',
        'appointment_at' => 'datetime',
    ];

    /** The 9 workflow states from the card, with Arabic labels + a status colour. */
    public const STATUSES = [
        'new'          => ['label' => 'جديد',           'color' => 'secondary'],
        'under_review' => ['label' => 'قيد المراجعة',    'color' => 'info'],
        'preliminary'  => ['label' => 'قبول مبدئي',      'color' => 'primary'],
        'waiting'      => ['label' => 'انتظار',          'color' => 'warning'],
        'scheduled'    => ['label' => 'تم تحديد موعد',   'color' => 'info'],
        'accepted'     => ['label' => 'مقبول',           'color' => 'success'],
        'rejected'     => ['label' => 'مرفوض',           'color' => 'danger'],
        'completed'    => ['label' => 'مكتمل',           'color' => 'success'],
        'cancelled'    => ['label' => 'ملغي',            'color' => 'dark'],
    ];

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status]['label'] ?? $this->status;
    }

    public function statusColor(): string
    {
        return self::STATUSES[$this->status]['color'] ?? 'secondary';
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class, 'school_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function convertedStudent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'converted_student_id');
    }
}
