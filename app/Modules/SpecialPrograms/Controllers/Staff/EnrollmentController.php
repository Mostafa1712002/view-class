<?php

namespace App\Modules\SpecialPrograms\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Modules\SpecialPrograms\Actions\Enrollment\EnrollStudents;
use App\Modules\SpecialPrograms\Actions\Enrollment\RemoveEnrollment;
use App\Modules\SpecialPrograms\Http\Requests\EnrollRequest;
use App\Modules\SpecialPrograms\Models\SpGroup;
use App\Modules\SpecialPrograms\Repositories\Contracts\EnrollmentRepository;
use App\Modules\SpecialPrograms\Support\ProgramAudience;
use App\Modules\SpecialPrograms\Support\Programs;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use Illuminate\Http\Request;

class EnrollmentController extends Controller
{
    use HasSchoolScope;

    public function __construct(private EnrollmentRepository $enrollments) {}

    private function check(string $program): ?int
    {
        abort_unless(in_array($program, Programs::keys(), true), 404);

        return $this->scopedSchoolId();
    }

    public function index(Request $request, string $program)
    {
        $schoolId = $this->check($program);
        $students = null;
        if ($schoolId) {
            $q = trim((string) $request->query('q'));
            $students = $this->enrollments->enrolledQuery($schoolId, $program)
                ->when($q !== '', fn ($w) => $w->where(fn ($x) => $x->where('users.name', 'like', "%$q%")->orWhere('users.national_id', 'like', "%$q%")))
                ->orderBy('users.name')->paginate(25)->withQueryString();
        }

        return view('special-programs.staff.enrollments.index', [
            'program' => $program, 'students' => $students, 'hasSchool' => (bool) $schoolId,
            'groups' => $schoolId ? SpGroup::where(['school_id' => $schoolId, 'program' => $program])->pluck('name', 'id') : collect(),
        ]);
    }

    public function create(Request $request, string $program)
    {
        $schoolId = $this->check($program);
        $showAll = $request->boolean('all');
        $classId = $request->integer('class_id') ?: null;
        $q = trim((string) $request->query('q'));
        $classes = $students = null;
        $alerts = null;
        $enrolledIds = [];
        $groups = collect();
        if ($schoolId) {
            $classes = ProgramAudience::classes($schoolId, $program, $showAll);
            $alerts = ProgramAudience::alerts($schoolId, $program);
            $groups = SpGroup::where(['school_id' => $schoolId, 'program' => $program])->pluck('name', 'id');
            // A class outside the school is never listed: the id must come from the school's own class list.
            $classId = ProgramAudience::classes($schoolId, $program, true)->contains('id', $classId) ? $classId : null;
            if ($classId || $q !== '') {
                $students = ProgramAudience::studentsQuery($schoolId, $classId ? [$classId] : [])
                    ->when($q !== '', fn ($w) => $w->where(fn ($x) => $x->where('users.name', 'like', "%$q%")->orWhere('users.national_id', 'like', "%$q%")))
                    ->orderBy('users.name')->limit(300)->get(['users.id', 'users.name', 'users.national_id']);
                $enrolledIds = $this->enrollments->enrolledQuery($schoolId, $program)->pluck('users.id')->all();
            }
        }

        return view('special-programs.staff.enrollments.create', compact('program', 'classes', 'students', 'alerts', 'enrolledIds', 'groups', 'classId', 'q', 'showAll') + ['hasSchool' => (bool) $schoolId]);
    }

    public function store(EnrollRequest $request, string $program, EnrollStudents $action)
    {
        $schoolId = $this->check($program);
        abort_unless($schoolId, 422, __('special_programs/staff.pick_school'));
        $n = $action->execute($schoolId, $program, $request->input('student_ids', []), $request->boolean('all_students'),
            $request->integer('class_id') ?: null, $request->integer('group_id') ?: null, $request->user()->id);

        return redirect()->route('manage.special-programs.enrollments.index', $program)
            ->with('success', __('special_programs/staff.enrolled_n', ['count' => $n]));
    }

    public function destroy(string $program, int $id, RemoveEnrollment $action)
    {
        $schoolId = $this->check($program);
        abort_unless($schoolId, 422, __('special_programs/staff.pick_school'));
        $action->execute($schoolId, $program, $id);

        return redirect()->route('manage.special-programs.enrollments.index', $program)->with('success', __('special_programs/staff.removed'));
    }
}
