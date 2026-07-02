<?php

namespace App\Modules\Subjects\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\DiscussionRoom;
use App\Models\Exam;
use App\Models\Subject;
use App\Models\SubjectContent;
use App\Models\VirtualClass;
use App\Modules\Subjects\Repositories\Contracts\SubjectRepository;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Teacher-facing "مواد المعلم" cards page — card #284.
 *
 * The subject list is never hardcoded: it is derived from the teacher's real
 * teaching assignments (SubjectRepository::teacherSubjects()), and every count
 * on each card is computed live from the teacher's own records.
 */
class TeacherSubjectController extends Controller
{
    use HasSchoolScope;

    /** Grade level (1-12) → [ordinal label, stage label]. */
    private const GRADE_LABELS = [
        1 => ['الأول', 'الابتدائي'], 2 => ['الثاني', 'الابتدائي'], 3 => ['الثالث', 'الابتدائي'],
        4 => ['الرابع', 'الابتدائي'], 5 => ['الخامس', 'الابتدائي'], 6 => ['السادس', 'الابتدائي'],
        7 => ['الأول', 'المتوسط'], 8 => ['الثاني', 'المتوسط'], 9 => ['الثالث', 'المتوسط'],
        10 => ['الأول', 'الثانوي'], 11 => ['الثاني', 'الثانوي'], 12 => ['الثالث', 'الثانوي'],
    ];

    public function __construct(private SubjectRepository $subjects) {}

    public function index(): View
    {
        $teacher = auth()->user();
        $schoolId = $this->scopedSchoolId();

        $subjects = collect($this->subjects->teacherSubjects($teacher->id, $schoolId));

        $cards = $subjects->map(fn (Subject $subject) => $this->buildCard($subject, $teacher->id, $schoolId));

        return view('teacher.subjects.index', compact('cards'));
    }

    private function buildCard(Subject $subject, int $teacherId, ?int $schoolId): array
    {
        $classIds = $this->linkedClassIds($teacherId, $subject->id);
        $levels = $this->gradeLevelsFor($classIds, $subject);

        return [
            'subject' => $subject,
            'grade_label' => $this->gradeLabel($levels),
            'stage_label' => $this->stageLabel($levels),
            'source_label' => $this->sourceLabel($subject),
            'counts' => [
                'classes' => $classIds->count(),
                'students' => $this->studentCount($classIds, $schoolId),
                'assignments' => Assignment::where('teacher_id', $teacherId)->where('subject_id', $subject->id)->count(),
                'exams' => Exam::where('teacher_id', $teacherId)->where('subject_id', $subject->id)->count(),
                'attachments' => SubjectContent::where('teacher_id', $teacherId)->where('subject_id', $subject->id)
                    ->where('type', SubjectContent::TYPE_ATTACHMENT)->count(),
                'videos' => SubjectContent::where('teacher_id', $teacherId)->where('subject_id', $subject->id)
                    ->where('type', SubjectContent::TYPE_VIDEO)->count(),
                // DiscussionRoom has no teacher_id column; scope by subject_id
                // directly (its scope_type is always hardcoded to 'school' by
                // ManageDiscussionController::store(), so it can't be used here).
                'discussion_rooms' => DiscussionRoom::where('subject_id', $subject->id)->where('school_id', $schoolId)->count(),
                'virtual_classes' => VirtualClass::where('teacher_id', $teacherId)->where('subject_id', $subject->id)->count(),
            ],
        ];
    }

    /**
     * Classes this teacher is linked to for this specific subject — union of
     * the timetable (schedule_periods → schedules.class_id) and direct
     * section assignment (subject_teacher.section_id → classes.section_id).
     * Mirrors ResolvesTeacherStudents::teachingStudentIds(), scoped to one subject.
     */
    private function linkedClassIds(int $teacherId, int $subjectId): Collection
    {
        $classIds = collect();

        $classIds = $classIds->merge(
            DB::table('schedule_periods')
                ->join('schedules', 'schedules.id', '=', 'schedule_periods.schedule_id')
                ->where('schedule_periods.teacher_id', $teacherId)
                ->where('schedule_periods.subject_id', $subjectId)
                ->pluck('schedules.class_id')
        );

        $sectionIds = DB::table('subject_teacher')
            ->where('user_id', $teacherId)
            ->where('subject_id', $subjectId)
            ->whereNotNull('section_id')
            ->pluck('section_id');

        if ($sectionIds->isNotEmpty()) {
            $classIds = $classIds->merge(
                DB::table('classes')->whereIn('section_id', $sectionIds)->pluck('id')
            );
        }

        return $classIds->filter()->unique()->values();
    }

    private function studentCount(Collection $classIds, ?int $schoolId): int
    {
        if ($classIds->isEmpty()) {
            return 0;
        }

        return DB::table('users')
            ->where(function ($w) use ($classIds) {
                $w->whereIn('class_room_id', $classIds)
                    ->orWhereIn('id', DB::table('class_student')->whereIn('class_id', $classIds)->select('student_id'));
            })
            ->whereNull('deleted_at')
            ->when($schoolId, fn ($w) => $w->where('school_id', $schoolId))
            ->count();
    }

    /**
     * @return array<int> grade levels actually taught, preferring the linked
     *   classes (real assignment) and falling back to the subject's own
     *   grade_levels definition when no class is linked yet.
     */
    private function gradeLevelsFor(Collection $classIds, Subject $subject): array
    {
        if ($classIds->isNotEmpty()) {
            $levels = DB::table('classes')->whereIn('id', $classIds)->pluck('grade_level')
                ->filter()->map(fn ($l) => (int) $l)->unique()->sort()->values()->all();

            if (! empty($levels)) {
                return $levels;
            }
        }

        return array_map('intval', (array) ($subject->grade_levels ?? []));
    }

    /** @param array<int> $levels */
    private function gradeLabel(array $levels): string
    {
        if (empty($levels)) {
            return '—';
        }

        return collect($levels)
            ->map(fn ($l) => isset(self::GRADE_LABELS[$l]) ? 'الصف ' . self::GRADE_LABELS[$l][0] . ' ' . self::GRADE_LABELS[$l][1] : "الصف {$l}")
            ->implode('، ');
    }

    /** @param array<int> $levels */
    private function stageLabel(array $levels): string
    {
        if (empty($levels)) {
            return '—';
        }

        return collect($levels)
            ->map(fn ($l) => self::GRADE_LABELS[$l][1] ?? null)
            ->filter()
            ->unique()
            ->implode('، ') ?: '—';
    }

    /**
     * ponytail: the schema has no creator/actor tracking for subjects or
     * teaching assignments, so the 3 card-required labels are approximated
     * from the subject's own `source` column (authoritative — matches the
     * "فيوكلاس" wording exactly) with `is_core` as a tiebreak for
     * platform-mandated core subjects ("مادة أساسية من المنصة" per its
     * migration comment). Upgrade to real actor tracking if that's ever needed.
     */
    private function sourceLabel(Subject $subject): string
    {
        return match (true) {
            $subject->source === 'viewclass' => 'فيوكلاس',
            $subject->is_core => 'مدير النظام',
            default => 'مسؤول المدرسة',
        };
    }
}
