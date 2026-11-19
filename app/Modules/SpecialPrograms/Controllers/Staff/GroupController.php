<?php

namespace App\Modules\SpecialPrograms\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\SpecialPrograms\Actions\Enrollment\AssignToGroup;
use App\Modules\SpecialPrograms\Actions\Enrollment\SaveGroup;
use App\Modules\SpecialPrograms\Http\Requests\GroupRequest;
use App\Modules\SpecialPrograms\Models\SpEnrollment;
use App\Modules\SpecialPrograms\Models\SpGroup;
use App\Modules\SpecialPrograms\Repositories\Contracts\GroupRepository;
use App\Modules\SpecialPrograms\Repositories\Contracts\SettingsRepository;
use App\Modules\SpecialPrograms\Support\PerformanceLevels;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GroupController extends Controller
{
    use HasSchoolScope;

    public function __construct(
        private GroupRepository $groups,
        private SettingsRepository $settings,
        private SaveGroup $save,
        private AssignToGroup $assign,
    ) {}

    /** School for reads; null only for a super-admin without a school scope (pages render a notice). */
    private function school(): ?int
    {
        return $this->scopedSchoolId();
    }

    private function writeSchool(): int
    {
        return $this->school() ?? abort(422, __('special_programs/groups.pick_school'));
    }

    private function find(string $program, int $id): SpGroup
    {
        $schoolId = $this->school();

        return SpGroup::when($schoolId !== null, fn ($q) => $q->where('school_id', $schoolId))->where('program', $program)->findOrFail($id);
    }

    private function schoolTeachers(?int $schoolId)
    {
        return $schoolId === null ? collect() : User::where('school_id', $schoolId)
            ->whereHas('roles', fn ($q) => $q->where('slug', 'teacher'))->orderBy('name')->get(['id', 'name']);
    }

    public function index(Request $request, string $program)
    {
        $schoolId = $this->school();
        $groups = $schoolId === null ? collect() : $this->groups->forSchool($schoolId, $program);
        $teacherNames = User::whereIn('id', $groups->pluck('teacher_id')->filter())->pluck('name', 'id');

        return view('special-programs.staff.groups.index', compact('program', 'groups', 'teacherNames') + ['needsSchool' => $schoolId === null]);
    }

    public function create(string $program)
    {
        $schoolId = $this->school();

        return view('special-programs.staff.groups.form', ['program' => $program, 'group' => new SpGroup,
            'teachers' => $this->schoolTeachers($schoolId), 'needsSchool' => $schoolId === null]);
    }

    public function store(GroupRequest $request, string $program)
    {
        $group = $this->save->execute($this->writeSchool(), $program, $request->validated(), $request->user()->id);

        return redirect()->route('manage.special-programs.groups.show', [$program, $group->id])->with('success', __('special_programs/groups.saved'));
    }

    public function show(Request $request, string $program, int $id)
    {
        $group = $this->find($program, $id);
        $level = $request->query('level');
        $bands = $this->settings->bandsFor($group->school_id, $program);
        $enrolled = SpEnrollment::where('sp_enrollments.school_id', $group->school_id)->where('sp_enrollments.program', $program)
            ->join('users', 'users.id', '=', 'sp_enrollments.student_id')->whereNull('users.deleted_at')
            ->orderBy('users.name')->get(['sp_enrollments.student_id', 'sp_enrollments.group_id', 'users.name']);
        $standing = $this->standing($enrolled->pluck('student_id')->all(), $program, $bands);

        $members = $enrolled->where('group_id', $group->id)->values();
        $candidates = $enrolled->where('group_id', '!=', $group->id)
            ->when($level, fn ($c) => $c->filter(fn ($s) => ($standing[$s->student_id]['key'] ?? 'none') === $level))
            ->take(300)->values();
        $groupNames = SpGroup::whereIn('id', $candidates->pluck('group_id')->filter())->pluck('name', 'id');
        $teacher = $group->teacher_id ? User::find($group->teacher_id, ['id', 'name']) : null;

        return view('special-programs.staff.groups.show', compact('program', 'group', 'members', 'candidates', 'standing', 'bands', 'groupNames', 'teacher', 'level'));
    }

    public function edit(string $program, int $id)
    {
        return view('special-programs.staff.groups.form', ['program' => $program, 'group' => $this->find($program, $id),
            'teachers' => $this->schoolTeachers($this->school()), 'needsSchool' => false]);
    }

    public function update(GroupRequest $request, string $program, int $id)
    {
        $group = $this->find($program, $id);
        $this->save->execute($group->school_id, $program, $request->validated(), $request->user()->id, $group);

        return redirect()->route('manage.special-programs.groups.show', [$program, $group->id])->with('success', __('special_programs/groups.saved'));
    }

    public function destroy(string $program, int $id)
    {
        $this->save->delete($this->find($program, $id));

        return redirect()->route('manage.special-programs.groups.index', $program)->with('success', __('special_programs/groups.deleted'));
    }

    public function storeMembers(Request $request, string $program, int $id)
    {
        $group = $this->find($program, $id);
        $data = $request->validate(['student_ids' => ['required', 'array', 'min:1'], 'student_ids.*' => ['integer']]);
        $placed = $this->assign->execute($group, array_map('intval', $data['student_ids']));
        $skipped = count($data['student_ids']) - $placed;

        $redirect = back()->with('success', __('special_programs/groups.members_added', ['count' => $placed]));

        return $skipped > 0 ? $redirect->with('warning', __('special_programs/groups.members_skipped', ['count' => $skipped])) : $redirect;
    }

    public function destroyMember(string $program, int $id, int $studentId)
    {
        $this->assign->remove($this->find($program, $id), $studentId);

        return back()->with('success', __('special_programs/groups.member_removed'));
    }

    /** Current level = band of the student's latest closed attempt in the program. @return array<int,array{pct:float,key:string,name:string,color:string}> */
    private function standing(array $studentIds, string $program, array $bands): array
    {
        $latest = DB::table('sp_attempts')->whereIn('student_id', $studentIds)->where('program', $program)
            ->where('status', '!=', 'in_progress')->whereNotNull('ended_at')->whereNotNull('score_pct')
            ->orderByDesc('ended_at')->orderByDesc('id')->get(['student_id', 'score_pct'])->unique('student_id');
        $out = [];
        foreach ($latest as $row) {
            $band = PerformanceLevels::for((float) $row->score_pct, $bands);
            $out[$row->student_id] = ['pct' => (float) $row->score_pct, 'key' => $band['key'], 'name' => $band['name'], 'color' => $band['color']];
        }

        return $out;
    }
}
