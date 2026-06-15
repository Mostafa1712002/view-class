<?php

namespace App\Modules\Libraries\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Library;
use App\Models\LibraryItem;
use App\Models\Subject;
use App\Modules\Libraries\Services\StudentFileService;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Student-facing library hub (card #173).
 *
 * Renders one page with three tabs:
 *   - المكتبة العامة (General/Public library) — published items the student may see
 *   - المكتبات الخاصة (Private libraries) — libraries whose audience targets the
 *     student, their class, grade, or school
 *   - ملفاتي (My Files) — files the student owns across the platform
 *
 * Visibility for public items (mapped to the columns that actually exist):
 *   - "published"                 → is_public = true (no separate published flag exists)
 *   - "subject available OR general" → subject_id IN (student's grade subjects) OR subject_id IS NULL
 *   - "not restricted to another school" → school_id IS NULL OR school_id = student's school
 *   - "within allowed time window" → NO backing column exists (no published_at/expires_at),
 *     so this clause is intentionally a no-op.
 */
class StudentLibraryController extends Controller
{
    use HasSchoolScope;

    public function __construct(private StudentFileService $files)
    {
    }

    public function index(Request $request): View
    {
        $student  = auth()->user();
        $schoolId = $this->activeSchoolId();

        $subjectIds = $this->studentSubjectIds($student);

        // ── Filter inputs (card lists: العنوان، المعلم، المادة، الوسم، البحث ضمن، نوع المحتوى، الترتيب) ──
        $filters = [
            'title'        => trim((string) $request->get('title', '')),
            'content_type' => (string) $request->get('content_type', ''),
            'subject_id'   => $request->get('subject_id'),
            'teacher_id'   => $request->get('teacher_id'),
            'tag'          => trim((string) $request->get('tag', '')),
            'sort'         => (string) $request->get('sort', 'newest'),
        ];

        // ── Tab 1: Public library items the student is allowed to see ──
        $publicQuery = LibraryItem::query()
            ->where('is_public', true)
            // school restriction: platform-wide OR the student's own school
            ->where(function ($w) use ($schoolId) {
                $w->whereNull('school_id');
                if ($schoolId) {
                    $w->orWhere('school_id', $schoolId);
                }
            })
            // subject restriction: a subject in the student's grade OR a general item
            ->where(function ($w) use ($subjectIds) {
                $w->whereNull('subject_id');
                if (! empty($subjectIds)) {
                    $w->orWhereIn('subject_id', $subjectIds);
                }
            });

        if ($filters['title'] !== '') {
            $publicQuery->where('title', 'like', '%' . $filters['title'] . '%');
        }
        if ($filters['content_type'] !== '') {
            $publicQuery->where('content_type', $filters['content_type']);
        }
        // "subject" filter — only honoured for a subject the student may access
        if (! empty($filters['subject_id']) && in_array((int) $filters['subject_id'], $subjectIds, true)) {
            $publicQuery->where('subject_id', (int) $filters['subject_id']);
        }
        if (! empty($filters['teacher_id'])) {
            $publicQuery->where('teacher_id', (int) $filters['teacher_id']);
        }
        if ($filters['tag'] !== '') {
            $publicQuery->where('tags', 'like', '%' . $filters['tag'] . '%');
        }

        $publicQuery->with(['subject:id,name', 'teacher:id,name'])
            ->withAvg('ratings as ratings_avg', 'rating')
            ->withCount(['ratings', 'comments']);

        if ($filters['sort'] === 'top_rated') {
            $publicQuery->orderByDesc('ratings_avg')->orderByDesc('id');
        } elseif ($filters['sort'] === 'oldest') {
            $publicQuery->orderBy('id');
        } else {
            $publicQuery->orderByDesc('id');
        }

        $publicItems = $publicQuery->paginate(12, ['*'], 'public_page')->withQueryString();

        // ── Tab 2: Private libraries whose audience targets this student ──
        $privateLibraries = $this->privateLibrariesForStudent($student, $schoolId);

        // ── Tab 3: My files ──
        $myFiles = $this->files->forStudent($student);

        // Filter dropdown data, scoped to what the student may access
        $subjects = empty($subjectIds)
            ? collect()
            : Subject::whereIn('id', $subjectIds)->orderBy('name')->get(['id', 'name']);

        // Teachers that authored items visible to this student (for the المعلم filter)
        $teachers = \App\Models\User::query()
            ->whereIn('id', function ($sub) use ($schoolId, $subjectIds) {
                $sub->select('teacher_id')
                    ->from('library_items')
                    ->whereNotNull('teacher_id')
                    ->where('is_public', true)
                    ->where(function ($w) use ($schoolId) {
                        $w->whereNull('school_id');
                        if ($schoolId) {
                            $w->orWhere('school_id', $schoolId);
                        }
                    })
                    ->where(function ($w) use ($subjectIds) {
                        $w->whereNull('subject_id');
                        if (! empty($subjectIds)) {
                            $w->orWhereIn('subject_id', $subjectIds);
                        }
                    });
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        $types = LibraryItem::TYPES;

        return view('student.libraries.index', compact(
            'publicItems',
            'privateLibraries',
            'myFiles',
            'filters',
            'subjects',
            'teachers',
            'types',
        ));
    }

    /**
     * Stream/download one of the student's own files (ownership re-checked).
     */
    public function downloadFile(Request $request, string $source, int $id)
    {
        return $this->files->download(auth()->user(), $source, $id);
    }

    /**
     * Delete one of the student's own files (ownership enforced in the service).
     */
    public function destroyFile(Request $request, string $source, int $id)
    {
        $ok = $this->files->delete(auth()->user(), $source, $id);

        return redirect()
            ->route('student.libraries.index', ['tab' => 'files'])
            ->with($ok ? 'success' : 'error', $ok
                ? __('student.library.file_deleted')
                : __('student.library.file_delete_failed'));
    }

    /**
     * The subject ids the student may see content for: subjects whose
     * grade_levels JSON contains the student's class grade level. Mirrors
     * StudentSubjectController::studentSubjects() so visibility stays consistent.
     *
     * @return array<int>
     */
    private function studentSubjectIds($student): array
    {
        $gradeLevel = optional($student->classRoom)->grade_level;

        $query = Subject::where('school_id', $student->school_id)
            ->where('is_active', true);

        if ($gradeLevel !== null) {
            $query->where(function ($w) use ($gradeLevel) {
                $w->whereJsonContains('grade_levels', (string) $gradeLevel)
                    ->orWhereJsonContains('grade_levels', (int) $gradeLevel);
            });
        }

        return $query->pluck('id')->map(fn ($v) => (int) $v)->all();
    }

    /**
     * Private libraries accessible to the student. A library is visible when
     * it is active, belongs to the student's school, and has an audience entry
     * matching the student directly (user), their class, their grade, or their
     * school.
     *
     * @return \Illuminate\Support\Collection<int,\App\Models\Library>
     */
    private function privateLibrariesForStudent($student, ?int $schoolId)
    {
        if (! $schoolId) {
            return collect();
        }

        $classIds   = $student->enrolledClassIds();
        $gradeLevel = optional($student->classRoom)->grade_level;
        $studentId  = (int) $student->id;

        return Library::query()
            ->where('type', 'private')
            ->where('is_active', true)
            ->where('school_id', $schoolId)
            ->where(function ($q) use ($schoolId, $classIds, $gradeLevel, $studentId) {
                // direct student audience
                $q->whereHas('audiences', function ($a) use ($studentId) {
                    $a->where('audience_type', 'user')->where('audience_id', $studentId);
                });
                // class audience
                if (! empty($classIds)) {
                    $q->orWhereHas('audiences', function ($a) use ($classIds) {
                        $a->where('audience_type', 'class')->whereIn('audience_id', $classIds);
                    });
                }
                // grade audience
                if ($gradeLevel !== null) {
                    $q->orWhereHas('audiences', function ($a) use ($gradeLevel) {
                        $a->where('audience_type', 'grade')
                            ->where('audience_id', (int) $gradeLevel);
                    });
                }
                // school-wide audience
                $q->orWhereHas('audiences', function ($a) use ($schoolId) {
                    $a->where('audience_type', 'school')->where('audience_id', $schoolId);
                });
            })
            ->withCount('items')
            ->with(['items' => function ($q) {
                $q->with(['subject:id,name', 'teacher:id,name'])
                    ->orderBy('sort_order')
                    ->orderByDesc('id');
            }])
            ->orderByDesc('id')
            ->get();
    }
}
