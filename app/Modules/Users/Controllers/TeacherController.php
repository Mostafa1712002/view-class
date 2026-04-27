<?php

namespace App\Modules\Users\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ClassRoom;
use App\Models\Role;
use App\Models\User;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use App\Modules\Users\Repositories\Contracts\TeacherRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class TeacherController extends Controller
{
    use HasSchoolScope;

    public function __construct(private readonly TeacherRepository $teachers)
    {
    }

    public function index(Request $request): View
    {
        $teachers = $this->teachers->paginate(
            $this->activeSchoolId(),
            $request->string('q')->toString() ?: null,
        );
        return view('admin.users.teachers.index', [
            'teachers' => $teachers,
            'q' => $request->string('q')->toString(),
        ]);
    }

    public function create(): View
    {
        return view('admin.users.teachers.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateTeacher($request);
        $schoolId = $this->activeSchoolId();
        DB::transaction(function () use ($data, $schoolId) {
            $plain = ($data['password'] ?? null) ?: ($data['national_id'] ?? str()->random(8));
            $user = User::create([
                'school_id' => $schoolId,
                'name' => $data['name'],
                'name_ar' => $data['name'],
                'username' => $data['username'],
                'email' => $data['email'] ?: null,
                'national_id' => $data['national_id'] ?? null,
                'employee_id' => $data['employee_id'] ?? null,
                'specialization' => $data['specialization'] ?? null,
                'qualification' => $data['qualification'] ?? null,
                'gender' => $data['gender'] ?? null,
                'phone' => $data['phone'] ?? null,
                'hire_date' => $data['hire_date'] ?? null,
                'password' => Hash::make($plain),
                'plain_password_for_card' => encrypt($plain),
                'is_active' => true,
                'status' => 'active',
            ]);
            $role = Role::where('slug', 'teacher')->first();
            if ($role) {
                $user->roles()->syncWithoutDetaching($role);
            }
        });
        return redirect()->route('admin.users.teachers.index')
            ->with('status', __('users.teacher_created'));
    }

    public function edit(int $id): View|RedirectResponse
    {
        $teacher = $this->teachers->findScoped($id, $this->activeSchoolId());
        if (!$teacher) {
            return redirect()->route('admin.users.teachers.index')->with('error', __('users.not_found'));
        }
        return view('admin.users.teachers.edit', compact('teacher'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $teacher = $this->teachers->findScoped($id, $this->activeSchoolId());
        if (!$teacher) {
            return redirect()->route('admin.users.teachers.index')->with('error', __('users.not_found'));
        }
        $data = $this->validateTeacher($request, $id);
        $teacher->fill([
            'name' => $data['name'],
            'name_ar' => $data['name'],
            'username' => $data['username'],
            'email' => $data['email'] ?: null,
            'national_id' => $data['national_id'] ?? null,
            'employee_id' => $data['employee_id'] ?? null,
            'specialization' => $data['specialization'] ?? null,
            'qualification' => $data['qualification'] ?? null,
            'gender' => $data['gender'] ?? null,
            'phone' => $data['phone'] ?? null,
            'hire_date' => $data['hire_date'] ?? null,
        ]);
        if (!empty($data['password'])) {
            $teacher->password = Hash::make($data['password']);
            $teacher->plain_password_for_card = encrypt($data['password']);
        }
        $teacher->save();
        return redirect()->route('admin.users.teachers.index')
            ->with('status', __('users.teacher_updated'));
    }

    public function destroy(int $id): RedirectResponse
    {
        $teacher = $this->teachers->findScoped($id, $this->activeSchoolId());
        if ($teacher) {
            $teacher->delete();
        }
        return redirect()->route('admin.users.teachers.index')
            ->with('status', __('users.teacher_deleted'));
    }

    public function workloads(): View
    {
        $schoolId = $this->activeSchoolId();
        $teachers = User::query()
            ->whereHas('roles', fn ($r) => $r->where('slug', 'teacher'))
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->orderBy('name')
            ->get();

        $classCounts = ClassRoom::query()
            ->select('lead_teacher_id', DB::raw('COUNT(*) as total'))
            ->whereNotNull('lead_teacher_id')
            ->groupBy('lead_teacher_id')
            ->pluck('total', 'lead_teacher_id');

        foreach ($teachers as $t) {
            $t->classes_count = $classCounts[$t->id] ?? 0;
        }

        return view('admin.users.teachers.workloads', compact('teachers'));
    }

    private function validateTeacher(Request $request, ?int $id = null): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:64|unique:users,username'.($id ? ','.$id : ''),
            'email' => 'nullable|email|max:255|unique:users,email'.($id ? ','.$id : ''),
            'national_id' => 'nullable|string|max:32',
            'employee_id' => 'nullable|string|max:32',
            'specialization' => 'nullable|string|max:120',
            'qualification' => 'nullable|string|max:120',
            'gender' => 'nullable|in:male,female',
            'phone' => 'nullable|string|max:32',
            'hire_date' => 'nullable|date',
            'password' => 'nullable|string|min:6|max:64',
        ]);
    }
}
