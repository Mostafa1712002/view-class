<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A single narrative/upload entry in a teacher or student "ملف إنجاز"
 * (achievement portfolio). Auto-derivable sections (CV, certificates, grades,
 * attendance, class visits) are computed elsewhere and never stored as rows
 * here — see SECTIONS_TEACHER_ENTRY / SECTIONS_STUDENT_ENTRY for which of the
 * section keys below are editable entries vs. the rest, which are auto.
 */
class PortfolioEntry extends Model
{
    use SoftDeletes;

    /** Teacher sections (10), in display order, key => Arabic label. */
    public const SECTIONS_TEACHER = [
        'cv'                   => 'السيرة الذاتية',
        'vision_mission'       => 'الرؤية والرسالة',
        'ethics_charter'       => 'ميثاق أخلاقيات المهنة',
        'achievements'         => 'الإنجازات المهنية (دورات/شهادات/جوائز)',
        'activities_resources' => 'الأنشطة والوسائل التعليمية',
        'projects'             => 'المشاريع التعليمية',
        'self_evaluation'      => 'التقويم الذاتي',
        'student_motivation'   => 'تحفيز الطلاب',
        'exemplary_work'       => 'نماذج وأعمال مميزة',
        'testimonials'         => 'خطابات وآراء الآخرين',
    ];

    /** Teacher section keys that are stored entries (the rest are auto). */
    public const SECTIONS_TEACHER_ENTRY = [
        'vision_mission', 'ethics_charter', 'activities_resources',
        'projects', 'self_evaluation', 'student_motivation', 'exemplary_work',
    ];

    /** Student sections (5), in display order, key => Arabic label. */
    public const SECTIONS_STUDENT = [
        'cv'               => 'السيرة الذاتية',
        'achievements'     => 'الإنجازات (شهادات/تكريمات)',
        'subject_sections' => 'أقسام المواد (أوراق عمل/اختبارات قصيرة/واجبات)',
        'projects'         => 'المشاريع التعليمية',
        'results'          => 'نتائج الطالب (اشعارات الدرجات)',
    ];

    /** Student section keys that are stored entries (the rest are auto). */
    public const SECTIONS_STUDENT_ENTRY = ['subject_sections', 'projects'];

    protected $fillable = [
        'user_id', 'section', 'title', 'body', 'attachment_path', 'entry_date', 'created_by',
    ];

    protected $casts = [
        'entry_date' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Multi-tenant scope: only entries whose owner belongs to $schoolId. */
    public function scopeForSchool(Builder $query, ?int $schoolId): Builder
    {
        return $query->when($schoolId, fn ($q) => $q->whereHas('user', fn ($u) => $u->where('school_id', $schoolId)));
    }
}
