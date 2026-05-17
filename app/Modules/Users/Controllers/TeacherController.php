<?php

namespace App\Modules\Users\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ClassRoom;
use App\Models\Role;
use App\Models\TeacherProfile;
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
        DB::transaction(function () use ($data, $request, $schoolId) {
            $plain = ($data['password'] ?? null) ?: ($data['national_id'] ?? str()->random(8));
            $name = $this->composeArabicName($data);
            $nameEn = $this->composeEnglishName($data);
            $user = User::create($this->withoutNulls([
                'school_id' => $schoolId,
                'name' => $name,
                'name_ar' => $name,
                'name_en' => $nameEn,
                'username' => $data['username'],
                'email' => ($data['email'] ?? null) ?: ($data['username'].'@viewclass.local'),
                'national_id' => $data['national_id'] ?? null,
                'employee_id' => $data['employee_id'] ?? null,
                'specialization' => $data['specialization'] ?? null,
                'qualification' => $data['qualification'] ?? null,
                'gender' => $data['gender'] ?? null,
                'phone' => $data['phone'] ?? null,
                'address' => $data['address'] ?? null,
                'date_of_birth' => $data['date_of_birth'] ?? null,
                'hire_date' => $data['hire_date'] ?? null,
                'password' => Hash::make($plain),
                'plain_password_for_card' => encrypt($plain),
                'is_active' => true,
                'status' => 'active',
            ]));
            $role = Role::where('slug', 'teacher')->first();
            if ($role) {
                $user->roles()->syncWithoutDetaching($role);
            }
            $this->syncProfile($user, $data, $request);
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
        $teacher->load('teacherProfile');
        return view('admin.users.teachers.edit', compact('teacher'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $teacher = $this->teachers->findScoped($id, $this->activeSchoolId());
        if (!$teacher) {
            return redirect()->route('admin.users.teachers.index')->with('error', __('users.not_found'));
        }
        $data = $this->validateTeacher($request, $id);
        DB::transaction(function () use ($teacher, $data, $request) {
            $name = $this->composeArabicName($data);
            $nameEn = $this->composeEnglishName($data);
            $teacher->fill([
                'name' => $name,
                'name_ar' => $name,
                'name_en' => $nameEn,
                'username' => $data['username'],
                'email' => ($data['email'] ?? null) ?: ($data['username'].'@viewclass.local'),
                'national_id' => $data['national_id'] ?? null,
                'employee_id' => $data['employee_id'] ?? null,
                'specialization' => $data['specialization'] ?? null,
                'qualification' => $data['qualification'] ?? null,
                'gender' => $data['gender'] ?? null,
                'phone' => $data['phone'] ?? null,
                'address' => $data['address'] ?? null,
                'date_of_birth' => $data['date_of_birth'] ?? null,
                'hire_date' => $data['hire_date'] ?? null,
            ]);
            if (!empty($data['password'])) {
                $teacher->password = Hash::make($data['password']);
                $teacher->plain_password_for_card = encrypt($data['password']);
            }
            $teacher->save();
            $this->syncProfile($teacher, $data, $request);
        });
        return redirect()->route('admin.users.teachers.index')
            ->with('status', __('users.teacher_updated'));
    }

    public function show(int $id): View|RedirectResponse
    {
        $teacher = $this->teachers->findScoped($id, $this->activeSchoolId());
        if (!$teacher) {
            return redirect()->route('admin.users.teachers.index')->with('error', __('users.not_found'));
        }
        $teacher->load(['teacherProfile', 'subjects', 'jobTitle']);
        return view('admin.users.teachers.show', compact('teacher'));
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

        // النصاب = number of weekly scheduled periods this teacher is assigned to.
        $periodCounts = DB::table('schedule_periods')
            ->select('teacher_id', DB::raw('COUNT(*) as total'))
            ->whereNotNull('teacher_id')
            ->groupBy('teacher_id')
            ->pluck('total', 'teacher_id');

        // Backup signal: number of subjects assigned via subject_teacher pivot.
        $subjectCounts = DB::table('subject_teacher')
            ->select('user_id', DB::raw('COUNT(DISTINCT subject_id) as total'))
            ->groupBy('user_id')
            ->pluck('total', 'user_id');

        // Backup signal: classes lead.
        $classCounts = ClassRoom::query()
            ->select('lead_teacher_id', DB::raw('COUNT(*) as total'))
            ->whereNotNull('lead_teacher_id')
            ->groupBy('lead_teacher_id')
            ->pluck('total', 'lead_teacher_id');

        foreach ($teachers as $t) {
            $t->workload_periods = (int) ($periodCounts[$t->id] ?? 0);
            $t->subjects_count = (int) ($subjectCounts[$t->id] ?? 0);
            $t->classes_count = (int) ($classCounts[$t->id] ?? 0);
        }

        return view('admin.users.teachers.workloads', compact('teachers'));
    }

    public function importForm(): View
    {
        return view('admin.users.teachers.import');
    }

    private function validateTeacher(Request $request, ?int $id = null): array
    {
        return $request->validate([
            // legacy single-field name kept optional for back-compat
            'name' => 'nullable|string|max:255',
            // Arabic name parts
            'first_name_ar' => 'required|string|max:80',
            'father_name_ar' => 'nullable|string|max:80',
            'grandfather_name_ar' => 'nullable|string|max:80',
            'family_name_ar' => 'required|string|max:80',
            // English name parts
            'first_name_en' => 'nullable|string|max:80',
            'father_name_en' => 'nullable|string|max:80',
            'grandfather_name_en' => 'nullable|string|max:80',
            'family_name_en' => 'nullable|string|max:80',
            // identity & work
            'passport_number' => 'nullable|string|max:32',
            'employee_id' => 'nullable|string|max:32',
            'national_id' => 'required|string|max:32',
            'username' => 'required|string|max:64|unique:users,username'.($id ? ','.$id : ''),
            'password' => ($id ? 'nullable' : 'required').'|string|min:6|max:64',
            // professional/personal
            'specialization' => 'nullable|string|max:120',
            'qualification' => 'nullable|string|max:120',
            'date_of_birth' => 'nullable|date',
            'birth_place' => 'nullable|string|max:120',
            // contact
            'address' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:32',
            'phone_secondary' => 'nullable|string|max:32',
            'email' => 'nullable|email|max:255|unique:users,email'.($id ? ','.$id : ''),
            // misc
            'gender' => 'nullable|in:male,female',
            'hire_date' => 'nullable|date',
            'nationality' => 'nullable|string|max:80',
            'profile_photo' => 'nullable|image|max:2048',
        ]);
    }

    private function composeArabicName(array $data): string
    {
        $parts = array_filter([
            $data['first_name_ar'] ?? null,
            $data['father_name_ar'] ?? null,
            $data['grandfather_name_ar'] ?? null,
            $data['family_name_ar'] ?? null,
        ], fn ($v) => filled($v));
        $joined = trim(implode(' ', $parts));
        return $joined !== '' ? $joined : (string) ($data['name'] ?? '');
    }

    private function composeEnglishName(array $data): ?string
    {
        $parts = array_filter([
            $data['first_name_en'] ?? null,
            $data['father_name_en'] ?? null,
            $data['grandfather_name_en'] ?? null,
            $data['family_name_en'] ?? null,
        ], fn ($v) => filled($v));
        $joined = trim(implode(' ', $parts));
        return $joined !== '' ? $joined : null;
    }

    private function syncProfile(User $user, array $data, Request $request): void
    {
        $payload = [
            'first_name_ar' => $data['first_name_ar'] ?? null,
            'father_name_ar' => $data['father_name_ar'] ?? null,
            'grandfather_name_ar' => $data['grandfather_name_ar'] ?? null,
            'family_name_ar' => $data['family_name_ar'] ?? null,
            'first_name_en' => $data['first_name_en'] ?? null,
            'father_name_en' => $data['father_name_en'] ?? null,
            'grandfather_name_en' => $data['grandfather_name_en'] ?? null,
            'family_name_en' => $data['family_name_en'] ?? null,
            'passport_number' => $data['passport_number'] ?? null,
            'birth_place' => $data['birth_place'] ?? null,
            'nationality' => $data['nationality'] ?? null,
            'phone_secondary' => $data['phone_secondary'] ?? null,
        ];

        if ($request->hasFile('profile_photo')) {
            $payload['profile_photo'] = $request->file('profile_photo')
                ->store('teachers/photos', 'public');
        }

        TeacherProfile::updateOrCreate(
            ['user_id' => $user->id],
            $payload,
        );
    }
}
