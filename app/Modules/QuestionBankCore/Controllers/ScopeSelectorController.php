<?php

namespace App\Modules\QuestionBankCore\Controllers;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Modules\QuestionBankCore\Services\QbScopeService;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * School/grade selection screen (#249). Renders the reusable compound → school →
 * grade → class / semester → week cascade and exposes AJAX endpoints the same
 * partial uses everywhere (add question / skill / passage / bank / exam ...).
 *
 * School scope is fail-closed via scopedSchoolId(): a school-scoped user only
 * sees their own school; a super-admin (null) sees every active school.
 */
class ScopeSelectorController extends Controller
{
    use HasSchoolScope;

    public function __construct(private QbScopeService $scope) {}

    public function index(): View
    {
        abort_unless(auth()->user()->canDo('question_banks.view'), 403);

        $schoolId = $this->scopedSchoolId();

        return view('admin.qb.scope.index', [
            'tree'      => $this->scope->schoolTree($schoolId),
            'scope'     => $this->scope,
            'schoolId'  => $schoolId,
        ]);
    }

    /**
     * Distinct grade-levels + classes + semesters for one school.
     */
    public function school(Request $request, int $schoolId): JsonResponse
    {
        abort_unless(auth()->user()->canDo('question_banks.view'), 403);
        $this->assertSchoolInScope($schoolId);

        $school = School::findOrFail($schoolId);
        $grades = $this->scope->gradeLevelsForSchool($schoolId)
            ->map(fn ($g) => ['id' => $g, 'name' => $this->gradeLabel($g)])
            ->values();

        return response()->json([
            'school_type' => $this->scope->schoolTypeLabel($school->student_gender),
            'grades'      => $grades,
            'classes'     => $this->scope->classesForSchool($schoolId)
                ->map(fn ($c) => ['id' => $c->id, 'name' => $c->name, 'grade_level' => $c->grade_level])
                ->values(),
            'semesters'   => $this->scope->semestersForSchool($schoolId)
                ->map(fn ($s) => ['id' => $s->id, 'name' => $s->name])
                ->values(),
        ]);
    }

    /**
     * Classes within a school, narrowed to a grade-level.
     */
    public function classes(Request $request, int $schoolId): JsonResponse
    {
        abort_unless(auth()->user()->canDo('question_banks.view'), 403);
        $this->assertSchoolInScope($schoolId);

        $grade = $request->filled('grade') ? (int) $request->get('grade') : null;

        return response()->json([
            'classes' => $this->scope->classesForSchool($schoolId, $grade)
                ->map(fn ($c) => ['id' => $c->id, 'name' => $c->name, 'grade_level' => $c->grade_level])
                ->values(),
        ]);
    }

    /**
     * Study weeks for a semester (academic term).
     */
    public function weeks(Request $request, int $semesterId): JsonResponse
    {
        abort_unless(auth()->user()->canDo('question_banks.view'), 403);

        return response()->json([
            'weeks' => $this->scope->weeksForSemester($semesterId)
                ->map(fn ($w) => ['id' => $w->id, 'name' => $w->name])
                ->values(),
        ]);
    }

    /**
     * Skills for a subject (optionally), used by the classification picker.
     */
    public function skills(Request $request): JsonResponse
    {
        abort_unless(auth()->user()->canDo('question_banks.view'), 403);

        $schoolId = $this->scopedSchoolId();
        $subjectId = $request->filled('subject_id') ? (int) $request->get('subject_id') : null;

        return response()->json([
            'skills' => $this->scope->skillsForSchool($schoolId, $subjectId)
                ->map(fn ($s) => ['id' => $s->id, 'name' => $s->name])
                ->values(),
        ]);
    }

    private function assertSchoolInScope(int $schoolId): void
    {
        $scope = $this->scopedSchoolId();
        abort_if($scope !== null && $scope !== $schoolId, 403, 'هذه المدرسة خارج نطاقك.');
    }

    private function gradeLabel(int $grade): string
    {
        $names = [1 => 'الأول', 2 => 'الثاني', 3 => 'الثالث', 4 => 'الرابع', 5 => 'الخامس', 6 => 'السادس'];

        return $names[$grade] ?? "الصف {$grade}";
    }
}
