<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Grade extends Model
{
    use HasFactory;
    protected $fillable = [
        'student_id',
        'subject_id',
        'class_id',
        'academic_year_id',
        'teacher_id',
        'semester',
        'quiz_avg',
        'homework_avg',
        'midterm',
        'final',
        'participation',
        'total',
        'letter_grade',
        'comments',
        'is_published',
    ];

    protected $casts = [
        'quiz_avg' => 'decimal:2',
        'homework_avg' => 'decimal:2',
        'midterm' => 'decimal:2',
        'final' => 'decimal:2',
        'participation' => 'decimal:2',
        'total' => 'decimal:2',
        'is_published' => 'boolean',
    ];

    public const SEMESTERS = [
        'first' => 'الفصل الأول',
        'second' => 'الفصل الثاني',
    ];

    public const LETTER_GRADES = [
        'A+' => ['min' => 95, 'label' => 'ممتاز مرتفع'],
        'A' => ['min' => 90, 'label' => 'ممتاز'],
        'B+' => ['min' => 85, 'label' => 'جيد جداً مرتفع'],
        'B' => ['min' => 80, 'label' => 'جيد جداً'],
        'C+' => ['min' => 75, 'label' => 'جيد مرتفع'],
        'C' => ['min' => 70, 'label' => 'جيد'],
        'D+' => ['min' => 65, 'label' => 'مقبول مرتفع'],
        'D' => ['min' => 60, 'label' => 'مقبول'],
        'F' => ['min' => 0, 'label' => 'راسب'],
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
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

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function getSemesterLabelAttribute(): string
    {
        return self::SEMESTERS[$this->semester] ?? $this->semester;
    }

    public function getLetterGradeLabelAttribute(): string
    {
        return self::LETTER_GRADES[$this->letter_grade]['label'] ?? '-';
    }

    public function calculateTotal(): void
    {
        $total = 0;
        $count = 0;

        if ($this->quiz_avg !== null) { $total += $this->quiz_avg * 0.15; $count++; }
        if ($this->homework_avg !== null) { $total += $this->homework_avg * 0.10; $count++; }
        if ($this->midterm !== null) { $total += $this->midterm * 0.25; $count++; }
        if ($this->final !== null) { $total += $this->final * 0.40; $count++; }
        if ($this->participation !== null) { $total += $this->participation * 0.10; $count++; }

        if ($count > 0) {
            $this->total = $total;
            $this->letter_grade = $this->calculateLetterGrade($total);
            $this->save();
        }
    }

    public function calculateLetterGrade(float $percentage): string
    {
        foreach (self::LETTER_GRADES as $grade => $info) {
            if ($percentage >= $info['min']) {
                return $grade;
            }
        }
        return 'F';
    }

    public function isPassing(): bool
    {
        return $this->total !== null && $this->total >= 60;
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeForStudent($query, $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    public function scopeForSubject($query, $subjectId)
    {
        return $query->where('subject_id', $subjectId);
    }

    public function scopeForClass($query, $classId)
    {
        return $query->where('class_id', $classId);
    }

    public function scopeForSemester($query, $semester)
    {
        return $query->where('semester', $semester);
    }

    public function scopeForAcademicYear($query, $yearId)
    {
        return $query->where('academic_year_id', $yearId);
    }
}
