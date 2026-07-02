<?php

namespace App\Modules\TeacherMaterials\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\TeacherMaterials\Repositories\Contracts\TeacherMaterialRepository;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Teacher "إدارة المواد" hub (Trello #287).
 *
 * Aggregates existing content modules behind a cascading filter:
 *   subject → grade → class → content-type → results.
 *
 * SECURITY: every endpoint validates that the requested subject belongs to the
 * authenticated teacher's own assignments (ownsSubject → 403). No cross-teacher
 * or cross-school data can surface. School scoping is fail-closed via
 * HasSchoolScope::scopedSchoolId() (null allowed only for super-admin).
 */
class TeacherMaterialController extends Controller
{
    use HasSchoolScope;

    /** Content types the hub can surface (key => Arabic label + icon). */
    public const TYPES = [
        'question_bank' => ['label' => 'بنك الأسئلة',        'icon' => 'la la-question-circle'],
        'books'         => ['label' => 'الكتب',             'icon' => 'la la-book'],
        'assignments'   => ['label' => 'الواجبات',          'icon' => 'la la-tasks'],
        'exams'         => ['label' => 'الاختبارات',        'icon' => 'la la-file-alt'],
        'attachments'   => ['label' => 'المرفقات',          'icon' => 'la la-paperclip'],
        'videos'        => ['label' => 'الفيديوهات',        'icon' => 'la la-video'],
        'images'        => ['label' => 'الصور',             'icon' => 'la la-image'],
        'interactive'   => ['label' => 'الأنشطة التفاعلية', 'icon' => 'la la-comments'],
    ];

    public function __construct(private TeacherMaterialRepository $repo) {}

    public function index(): View
    {
        $schoolId = $this->scopedSchoolId();
        $subjects = $this->repo->teacherSubjects((int) auth()->id(), $schoolId);

        return view('teacher.materials.index', [
            'subjects' => $subjects,
            'types'    => self::TYPES,
        ]);
    }

    public function grades(Request $request): JsonResponse
    {
        [$teacherId, $schoolId, $subjectId] = $this->resolveSubject($request);

        return response()->json([
            'grades' => $this->repo->gradesForSubject($teacherId, $schoolId, $subjectId),
        ]);
    }

    public function classes(Request $request): JsonResponse
    {
        [$teacherId, $schoolId, $subjectId] = $this->resolveSubject($request);
        $grade = $request->filled('grade') ? (int) $request->input('grade') : null;

        return response()->json([
            'classes' => $this->repo->classesForSubject($teacherId, $schoolId, $subjectId, $grade),
        ]);
    }

    public function results(Request $request): JsonResponse
    {
        [$teacherId, $schoolId, $subjectId] = $this->resolveSubject($request);

        $type = (string) $request->input('type');
        abort_unless(array_key_exists($type, self::TYPES), 422, 'نوع المحتوى غير معروف');

        $grade   = $request->filled('grade') ? (int) $request->input('grade') : null;
        $classId = $request->filled('class_id') ? (int) $request->input('class_id') : null;

        // A supplied class must be one the teacher teaches this subject in.
        if ($classId !== null) {
            $allowed = $this->repo->teacherClassIdsForSubject($teacherId, $schoolId, $subjectId);
            abort_unless(in_array($classId, $allowed, true), 403);
        }

        return response()->json([
            'items' => $this->repo->content($teacherId, $schoolId, $subjectId, $grade, $classId, $type),
        ]);
    }

    /**
     * Shared guard: resolve teacher + school + a subject the teacher owns.
     *
     * @return array{0:int,1:?int,2:int}
     */
    private function resolveSubject(Request $request): array
    {
        $teacherId = (int) auth()->id();
        $schoolId  = $this->scopedSchoolId();
        $subjectId = (int) $request->input('subject_id');

        abort_unless($subjectId > 0 && $this->repo->ownsSubject($teacherId, $schoolId, $subjectId), 403);

        return [$teacherId, $schoolId, $subjectId];
    }
}
