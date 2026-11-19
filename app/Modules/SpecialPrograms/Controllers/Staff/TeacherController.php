<?php

namespace App\Modules\SpecialPrograms\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\SpecialPrograms\Actions\Enrollment\AssignTeacher;
use App\Modules\SpecialPrograms\Models\SpSubject;
use App\Modules\SpecialPrograms\Models\SpTeacher;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use Illuminate\Http\Request;

class TeacherController extends Controller
{
    use HasSchoolScope;

    public function __construct(private AssignTeacher $assign) {}

    public function index(string $program)
    {
        $schoolId = $this->scopedSchoolId();
        $subjects = SpSubject::where('program', $program)->orderBy('sort_order')->get();
        $rows = $schoolId === null ? collect() : SpTeacher::where('school_id', $schoolId)->where('program', $program)->orderBy('id')->get();
        $names = User::whereIn('id', $rows->pluck('teacher_id'))->pluck('name', 'id');
        $teachers = $schoolId === null ? collect() : User::where('school_id', $schoolId)
            ->whereHas('roles', fn ($q) => $q->where('slug', 'teacher'))->orderBy('name')->get(['id', 'name']);

        return view('special-programs.staff.teachers.index', compact('program', 'subjects', 'rows', 'names', 'teachers') + ['needsSchool' => $schoolId === null]);
    }

    public function store(Request $request, string $program)
    {
        $schoolId = $this->scopedSchoolId() ?? abort(422, __('special_programs/groups.pick_school'));
        $data = $request->validate(['teacher_id' => ['required', 'integer'], 'sp_subject_id' => ['nullable', 'integer']]);
        $this->assign->execute($schoolId, $program, (int) $data['teacher_id'], ($data['sp_subject_id'] ?? null) ? (int) $data['sp_subject_id'] : null, $request->user()->id);

        return back()->with('success', __('special_programs/groups.teacher_assigned'));
    }

    public function destroy(string $program, int $id)
    {
        $schoolId = $this->scopedSchoolId();
        SpTeacher::when($schoolId !== null, fn ($q) => $q->where('school_id', $schoolId))->where('program', $program)->findOrFail($id)->delete();

        return back()->with('success', __('special_programs/groups.teacher_removed'));
    }
}
