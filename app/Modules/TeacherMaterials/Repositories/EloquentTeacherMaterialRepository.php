<?php

namespace App\Modules\TeacherMaterials\Repositories;

use App\Models\Assignment;
use App\Models\Book;
use App\Models\DiscussionRoom;
use App\Models\Exam;
use App\Models\LibraryItem;
use App\Models\QuestionBank;
use App\Models\Subject;
use App\Models\SubjectContent;
use App\Modules\TeacherMaterials\Repositories\Contracts\TeacherMaterialRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EloquentTeacherMaterialRepository implements TeacherMaterialRepository
{
    /**
     * Subject ids a teacher is assigned to teach. Three assignment sources are
     * unioned so the hub works regardless of which scheduler a school uses:
     *   1. subject_teacher            (section / subject assignment)
     *   2. schedule_periods.teacher_id (legacy timetable)
     *   3. class_periods.teacher_id    (live scheduler)
     * Lead-teacher is deliberately excluded: leading a class does not mean the
     * teacher teaches a given subject.
     *
     * @return array<int>
     */
    private function subjectIds(int $teacherId): array
    {
        $ids = collect()
            ->merge(DB::table('subject_teacher')->where('user_id', $teacherId)->pluck('subject_id'))
            ->merge(DB::table('schedule_periods')->where('teacher_id', $teacherId)->pluck('subject_id'))
            ->merge(DB::table('class_periods')->where('teacher_id', $teacherId)->whereNull('deleted_at')->pluck('subject_id'));

        return $ids->filter()->map(fn ($id) => (int) $id)->unique()->values()->all();
    }

    public function teacherSubjects(int $teacherId, ?int $schoolId): Collection
    {
        $ids = $this->subjectIds($teacherId);
        if (empty($ids)) {
            return collect();
        }

        return Subject::whereIn('id', $ids)
            ->when($schoolId !== null, fn ($q) => $q->where('school_id', $schoolId))
            ->where('is_active', true)
            ->orderBy('certificate_order')
            ->orderBy('name')
            ->get();
    }

    public function ownsSubject(int $teacherId, ?int $schoolId, int $subjectId): bool
    {
        return $this->teacherSubjects($teacherId, $schoolId)->contains('id', $subjectId);
    }

    public function teacherClassIdsForSubject(int $teacherId, ?int $schoolId, int $subjectId): array
    {
        $classIds = collect()
            // live scheduler carries class_id directly
            ->merge(DB::table('class_periods')
                ->where('teacher_id', $teacherId)->where('subject_id', $subjectId)
                ->whereNull('deleted_at')->pluck('class_id'))
            // legacy timetable: schedule → class
            ->merge(DB::table('schedule_periods')
                ->join('schedules', 'schedules.id', '=', 'schedule_periods.schedule_id')
                ->where('schedule_periods.teacher_id', $teacherId)
                ->where('schedule_periods.subject_id', $subjectId)
                ->pluck('schedules.class_id'));

        // section assignment → all classes in that section
        $sectionIds = DB::table('subject_teacher')
            ->where('user_id', $teacherId)->where('subject_id', $subjectId)
            ->whereNotNull('section_id')->pluck('section_id');
        if ($sectionIds->isNotEmpty()) {
            $classIds = $classIds->merge(
                DB::table('classes')->whereIn('section_id', $sectionIds)->pluck('id')
            );
        }

        $classIds = $classIds->filter()->map(fn ($id) => (int) $id)->unique()->values();
        if ($classIds->isEmpty()) {
            return [];
        }

        // school-scope via sections.school_id (classes has no school_id column)
        return DB::table('classes')
            ->join('sections', 'sections.id', '=', 'classes.section_id')
            ->whereIn('classes.id', $classIds)
            ->when($schoolId !== null, fn ($q) => $q->where('sections.school_id', $schoolId))
            ->where('classes.is_active', true)
            ->pluck('classes.id')
            ->map(fn ($id) => (int) $id)->all();
    }

    public function gradesForSubject(int $teacherId, ?int $schoolId, int $subjectId): array
    {
        $subject = $this->teacherSubjects($teacherId, $schoolId)->firstWhere('id', $subjectId);
        if ($subject === null) {
            return [];
        }

        // grades the subject is offered for (property of the subject he teaches)
        $grades = collect($subject->grade_levels ?? [])->map(fn ($g) => (int) $g);

        // plus grades of the concrete classes he teaches the subject in
        $classIds = $this->teacherClassIdsForSubject($teacherId, $schoolId, $subjectId);
        if (! empty($classIds)) {
            $grades = $grades->merge(
                DB::table('classes')->whereIn('id', $classIds)->pluck('grade_level')->map(fn ($g) => (int) $g)
            );
        }

        return $grades->filter()->unique()->sort()->values()
            ->map(fn ($g) => ['value' => $g, 'label' => 'الصف ' . $g])
            ->all();
    }

    public function classesForSubject(int $teacherId, ?int $schoolId, int $subjectId, ?int $grade): array
    {
        $classIds = $this->teacherClassIdsForSubject($teacherId, $schoolId, $subjectId);
        if (empty($classIds)) {
            return [];
        }

        return DB::table('classes')
            ->whereIn('id', $classIds)
            ->when($grade !== null, fn ($q) => $q->where('grade_level', $grade))
            ->orderBy('grade_level')->orderBy('name')
            ->get(['id', 'name', 'division'])
            ->map(fn ($c) => [
                'id'   => (int) $c->id,
                'name' => trim($c->name . ($c->division ? ' - ' . $c->division : '')),
            ])->all();
    }

    public function content(int $teacherId, ?int $schoolId, int $subjectId, ?int $grade, ?int $classId, string $type): array
    {
        // Narrowing filter for class-scoped content (assignments / exams):
        //   - explicit class  → that class only
        //   - grade only, and the teacher has concrete classes of that grade →
        //     those classes (so grade genuinely narrows)
        //   - otherwise null  → no class narrowing; the teacher_id + subject
        //     scope still bounds it (this keeps teachers whose assignment is a
        //     null-section subject_teacher row, i.e. no concrete class links,
        //     from being filtered down to nothing).
        $classFilter = null;
        if ($classId !== null) {
            $classFilter = [$classId];
        } elseif ($grade !== null) {
            $gradeClassIds = array_column($this->classesForSubject($teacherId, $schoolId, $subjectId, $grade), 'id');
            if (! empty($gradeClassIds)) {
                $classFilter = $gradeClassIds;
            }
        }

        return match ($type) {
            'question_bank' => $this->questionBanks($schoolId, $subjectId, $grade),
            'books'         => $this->books($schoolId, $subjectId, $grade),
            'assignments'   => $this->assignments($teacherId, $schoolId, $subjectId, $classFilter),
            'exams'         => $this->exams($teacherId, $subjectId, $classFilter),
            'attachments'   => $this->files($schoolId, $subjectId, 'attachment'),
            'videos'        => $this->files($schoolId, $subjectId, 'video'),
            'images'        => $this->files($schoolId, $subjectId, 'image'),
            'interactive'   => $this->interactive($schoolId, $subjectId),
            default         => [],
        };
    }

    // ── Content sources ──────────────────────────────────────────────────────

    private function questionBanks(?int $schoolId, int $subjectId, ?int $grade): array
    {
        return QuestionBank::where('subject_id', $subjectId)
            ->when($schoolId !== null, fn ($q) => $q->where('school_id', $schoolId))
            ->when($grade !== null, fn ($q) => $q->where(fn ($w) => $w->where('grade_level', $grade)->orWhereNull('grade_level')))
            ->orderByDesc('created_at')->get()
            ->map(fn ($b) => $this->item($b->name_ar, ['la la-question-circle'], [$b->bank_type === 'public' ? 'عام' : 'خاص', $b->status], $b->created_at, null))
            ->all();
    }

    private function books(?int $schoolId, int $subjectId, ?int $grade): array
    {
        return Book::where('subject_id', $subjectId)
            ->when($schoolId !== null, fn ($q) => $q->where('school_id', $schoolId))
            ->where('is_active', true)
            ->when($grade !== null, fn ($q) => $q->where(fn ($w) => $w->where('grade_level', $grade)->orWhereNull('grade_level')))
            ->orderByDesc('is_ministry')->orderByDesc('created_at')->get()
            ->map(fn ($b) => $this->item($b->title, ['la la-book'], $b->is_ministry ? ['كتاب وزاري'] : [], $b->created_at, $this->route('manage.books.index')))
            ->all();
    }

    private function assignments(int $teacherId, ?int $schoolId, int $subjectId, ?array $classFilter): array
    {
        // Scoped to the teacher's own assignments for the subject (a "my
        // materials" view). Co-taught subjects: a colleague's assignments are
        // not shown here — authorship is the ownership boundary for this table.
        return Assignment::where('subject_id', $subjectId)
            ->where('teacher_id', $teacherId)
            ->when($schoolId !== null, fn ($q) => $q->where('school_id', $schoolId))
            ->when($classFilter !== null, fn ($q) => $q->whereIn('class_id', $classFilter))
            ->orderByDesc('due_date')->get()
            ->map(fn ($a) => $this->item($a->title, ['la la-tasks'], [$this->statusLabel($a->status), $a->due_date?->format('Y-m-d')], $a->created_at, $this->route('admin.assignments.show', $a->id)))
            ->all();
    }

    private function exams(int $teacherId, int $subjectId, ?array $classFilter): array
    {
        // exams has no school_id column — teacher + subject is the scope boundary.
        return Exam::where('subject_id', $subjectId)
            ->where('teacher_id', $teacherId)
            ->when($classFilter !== null, fn ($q) => $q->whereIn('class_id', $classFilter))
            ->orderByDesc('start_time')->get()
            ->map(fn ($e) => $this->item($e->title, ['la la-file-alt'], [$this->statusLabel($e->status), optional($e->start_time)->format('Y-m-d H:i')], $e->created_at, $this->route('teacher.exams.show', $e->id)))
            ->all();
    }

    /** Files/media are aggregated from two stores: subject_contents + library_items. */
    private function files(?int $schoolId, int $subjectId, string $kind): array
    {
        $libTypes = match ($kind) {
            'video'      => ['video'],
            'image'      => ['image', 'photo'],
            'attachment' => ['pdf', 'document', 'doc', 'file', 'attachment', 'other'],
            default      => [],
        };
        $icon = match ($kind) {
            'video' => ['la la-video'],
            'image' => ['la la-image'],
            default => ['la la-paperclip'],
        };

        $items = LibraryItem::where('subject_id', $subjectId)
            ->when($schoolId !== null, fn ($q) => $q->where('school_id', $schoolId))
            ->whereIn('content_type', $libTypes)
            ->orderByDesc('created_at')->get()
            ->map(fn ($i) => $this->item(
                $i->title, $icon, [], $i->created_at,
                $i->is_public ? $this->route('admin.libraries.public.show', $i->id) : null
            ))->all();

        // subject_contents uses its own type vocabulary (video / attachment)
        if ($kind === 'video' || $kind === 'attachment') {
            $sc = SubjectContent::where('subject_id', $subjectId)
                ->when($schoolId !== null, fn ($q) => $q->where('school_id', $schoolId))
                ->where('type', $kind)
                ->orderByDesc('created_at')->get()
                ->map(fn ($c) => $this->item($c->title, $icon, $c->is_published ? ['منشور'] : ['مسودة'], $c->created_at, null))
                ->all();
            $items = array_merge($items, $sc);
        }

        return $items;
    }

    private function interactive(?int $schoolId, int $subjectId): array
    {
        return DiscussionRoom::where('subject_id', $subjectId)
            ->when($schoolId !== null, fn ($q) => $q->where('school_id', $schoolId))
            ->where('status', 'active')
            ->orderByDesc('created_at')->get()
            ->map(fn ($r) => $this->item($r->title, ['la la-comments'], [$r->category], $r->last_activity_at ?? $r->created_at, null))
            ->all();
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function item(string $title, array $icon, array $badges, $date, ?string $url): array
    {
        return [
            'title'  => $title,
            'icon'   => $icon[0] ?? 'la la-file',
            'badges' => array_values(array_filter($badges, fn ($b) => $b !== null && $b !== '')),
            'date'   => $date ? \Illuminate\Support\Carbon::parse($date)->format('Y-m-d') : null,
            'url'    => $url,
        ];
    }

    private function statusLabel(?string $status): ?string
    {
        return match ($status) {
            'draft'     => 'مسودة',
            'published' => 'منشور',
            'closed'    => 'مغلق',
            'scheduled' => 'مجدول',
            'active'    => 'نشط',
            'completed' => 'مكتمل',
            'cancelled' => 'ملغى',
            default     => $status,
        };
    }

    private function route(string $name, $param = null): ?string
    {
        if (! \Illuminate\Support\Facades\Route::has($name)) {
            return null;
        }

        return $param === null ? route($name) : route($name, $param);
    }
}
