<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Exam extends Model
{
    use HasFactory;
    protected $fillable = [
        'teacher_id',
        'subject_id',
        'class_id',
        'academic_year_id',
        'title',
        'description',
        'type',
        'total_marks',
        'pass_marks',
        'duration_minutes',
        'start_time',
        'end_time',
        'is_published',
        'show_results',
        'shuffle_questions',
        'shuffle_answers',
        'attempts_allowed',
        'max_exit_attempts',
        'status',
    ];

    protected $casts = [
        'total_marks' => 'decimal:2',
        'pass_marks' => 'decimal:2',
        'duration_minutes' => 'integer',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'is_published' => 'boolean',
        'show_results' => 'boolean',
        'shuffle_questions' => 'boolean',
        'shuffle_answers' => 'boolean',
        'attempts_allowed' => 'integer',
        'max_exit_attempts' => 'integer',
    ];

    public const TYPES = [
        'quiz' => 'اختبار قصير',
        'midterm' => 'اختبار نصفي',
        'final' => 'اختبار نهائي',
        'assignment' => 'تكليف',
        'homework' => 'واجب منزلي',
    ];

    public const STATUSES = [
        'draft' => 'مسودة',
        'scheduled' => 'مجدول',
        'active' => 'جاري',
        'completed' => 'منتهي',
        'cancelled' => 'ملغي',
    ];

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function classRoom(): BelongsTo
    {
        return $this->belongsTo(ClassRoom::class, 'class_id');
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(ExamQuestion::class)->orderBy('order');
    }

    /** Specific students this exam is limited to; empty = every student of the class. */
    public function targetStudents(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'exam_students', 'exam_id', 'student_id')->withTimestamps();
    }

    /** Exams open to this student: class-wide ones plus those that name them. */
    public function scopeVisibleToStudent($query, int $studentId)
    {
        // Straight on the pivot: going through the users relation would apply its
        // soft-delete scope, turning an exam whose targets were all deleted class-wide.
        $pivot = fn ($q) => $q->selectRaw('1')->from('exam_students')->whereColumn('exam_students.exam_id', 'exams.id');

        return $query->where(fn ($q) => $q->whereNotExists($pivot)
            ->orWhereExists(fn ($e) => $pivot($e)->where('exam_students.student_id', $studentId)));
    }

    public function isVisibleTo(User $student): bool
    {
        return self::whereKey($this->id)->visibleToStudent($student->id)->exists();
    }

    public function studentExams(): HasMany
    {
        return $this->hasMany(StudentExam::class);
    }

    public function exitAttempts(): HasMany
    {
        return $this->hasMany(ExamExitAttempt::class);
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getStatusClassAttribute(): string
    {
        return match($this->status) {
            'draft' => 'bg-secondary',
            'scheduled' => 'bg-info',
            'active' => 'bg-success',
            'completed' => 'bg-primary',
            'cancelled' => 'bg-danger',
            default => 'bg-secondary',
        };
    }

    public function isAvailable(): bool
    {
        if (!$this->is_published || $this->status !== 'active') {
            return false;
        }

        $now = now();
        if ($this->start_time && $now->lt($this->start_time)) {
            return false;
        }
        if ($this->end_time && $now->gt($this->end_time)) {
            return false;
        }

        return true;
    }

    public function canTake(User $student): bool
    {
        if (!$this->isAvailable()) {
            return false;
        }

        $attempts = $this->studentExams()->where('student_id', $student->id)->count();
        return $attempts < $this->attempts_allowed;
    }

    public function getQuestionsCount(): int
    {
        return $this->questions()->count();
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeForTeacher($query, $teacherId)
    {
        return $query->where('teacher_id', $teacherId);
    }

    public function scopeForClass($query, $classId)
    {
        return $query->where('class_id', $classId);
    }

    public function scopeForSubject($query, $subjectId)
    {
        return $query->where('subject_id', $subjectId);
    }
}
