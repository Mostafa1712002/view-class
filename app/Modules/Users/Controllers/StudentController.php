<?php

namespace App\Modules\Users\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ClassRoom;
use App\Models\Role;
use App\Models\Section;
use App\Models\User;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use App\Modules\Users\Repositories\Contracts\StudentRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class StudentController extends Controller
{
    use HasSchoolScope;

    public function __construct(private readonly StudentRepository $students)
    {
    }

    public function index(Request $request): View
    {
        $schoolId = $this->activeSchoolId();
        $students = $this->students->paginate($schoolId, $request->string('q')->toString() ?: null);
        $sections = Section::query()->where('school_id', $schoolId)->orderBy('name')->get();

        return view('admin.users.students.index', [
            'students' => $students,
            'sections' => $sections,
            'q' => $request->string('q')->toString(),
        ]);
    }

    public function create(): View
    {
        $schoolId = $this->activeSchoolId();
        $sections = Section::query()->where('school_id', $schoolId)->orderBy('name')->get();
        $classes = ClassRoom::query()->whereIn('section_id', $sections->pluck('id'))->orderBy('name')->get();

        return view('admin.users.students.create', compact('sections', 'classes'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateStudent($request);
        $schoolId = $this->activeSchoolId();

        $user = DB::transaction(function () use ($data, $schoolId) {
            $plain = $data['password'] ?: ($data['national_id'] ?? str()->random(8));
            $user = User::create([
                'school_id' => $schoolId,
                'section_id' => $data['section_id'] ?? null,
                'class_room_id' => $data['class_room_id'] ?? null,
                'name' => $data['name'],
                'name_ar' => $data['name'],
                'username' => $data['username'],
                'email' => $data['email'] ?: null,
                'national_id' => $data['national_id'] ?? null,
                'gender' => $data['gender'] ?? null,
                'date_of_birth' => $data['date_of_birth'] ?? null,
                'phone' => $data['phone'] ?? null,
                'password' => Hash::make($plain),
                'plain_password_for_card' => encrypt($plain),
                'is_active' => true,
                'status' => 'active',
            ]);

            $role = Role::where('slug', 'student')->first();
            if ($role) {
                $user->roles()->syncWithoutDetaching($role);
            }

            if (!empty($data['class_room_id'])) {
                DB::table('class_student')->insertOrIgnore([
                    'class_id' => $data['class_room_id'],
                    'student_id' => $user->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            return $user;
        });

        return redirect()->route('admin.users.students.index')
            ->with('status', __('users.student_created', ['name' => $user->name]));
    }

    public function edit(int $id): View|RedirectResponse
    {
        $student = $this->students->findScoped($id, $this->activeSchoolId());
        if (!$student) {
            return redirect()->route('admin.users.students.index')->with('error', __('users.not_found'));
        }

        $schoolId = $this->activeSchoolId();
        $sections = Section::query()->where('school_id', $schoolId)->orderBy('name')->get();
        $classes = ClassRoom::query()->whereIn('section_id', $sections->pluck('id'))->orderBy('name')->get();

        return view('admin.users.students.edit', compact('student', 'sections', 'classes'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $student = $this->students->findScoped($id, $this->activeSchoolId());
        if (!$student) {
            return redirect()->route('admin.users.students.index')->with('error', __('users.not_found'));
        }
        $data = $this->validateStudent($request, $id);

        $student->fill([
            'name' => $data['name'],
            'name_ar' => $data['name'],
            'username' => $data['username'],
            'email' => $data['email'] ?: null,
            'national_id' => $data['national_id'] ?? null,
            'gender' => $data['gender'] ?? null,
            'date_of_birth' => $data['date_of_birth'] ?? null,
            'phone' => $data['phone'] ?? null,
            'section_id' => $data['section_id'] ?? null,
            'class_room_id' => $data['class_room_id'] ?? null,
        ]);
        if (!empty($data['password'])) {
            $student->password = Hash::make($data['password']);
            $student->plain_password_for_card = encrypt($data['password']);
        }
        $student->save();

        return redirect()->route('admin.users.students.index')
            ->with('status', __('users.student_updated', ['name' => $student->name]));
    }

    public function destroy(int $id): RedirectResponse
    {
        $student = $this->students->findScoped($id, $this->activeSchoolId());
        if ($student) {
            $student->delete();
        }
        return redirect()->route('admin.users.students.index')
            ->with('status', __('users.student_deleted'));
    }

    public function bulk(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'action' => 'required|in:hide_grades,show_grades,hide_report,show_report,license,unlicense,waiting',
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer',
        ]);
        $schoolId = $this->activeSchoolId();
        $q = User::query()->whereIn('id', $data['ids']);
        if ($schoolId !== null) {
            $q->where('school_id', $schoolId);
        }
        $rows = $q->get();
        foreach ($rows as $u) {
            switch ($data['action']) {
                case 'license':
                    $u->status = 'active';
                    $u->is_active = true;
                    break;
                case 'unlicense':
                    $u->status = 'unlicensed';
                    $u->is_active = false;
                    break;
                case 'waiting':
                    $u->status = 'waiting';
                    $u->is_active = false;
                    break;
                case 'hide_grades':
                case 'show_grades':
                case 'hide_report':
                case 'show_report':
                    $prefs = json_decode($u->notification_preferences ?? '{}', true) ?: [];
                    $key = match ($data['action']) {
                        'hide_grades' => 'grades_hidden',
                        'show_grades' => 'grades_hidden',
                        'hide_report' => 'report_hidden',
                        'show_report' => 'report_hidden',
                    };
                    $prefs[$key] = str_starts_with($data['action'], 'hide_');
                    $u->notification_preferences = json_encode($prefs);
                    break;
            }
            $u->save();
        }

        return redirect()->route('admin.users.students.index')
            ->with('status', __('users.bulk_done', ['count' => $rows->count()]));
    }

    private function validateStudent(Request $request, ?int $id = null): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:64|unique:users,username'.($id ? ','.$id : ''),
            'email' => 'nullable|email|max:255|unique:users,email'.($id ? ','.$id : ''),
            'national_id' => 'nullable|string|max:32',
            'gender' => 'nullable|in:male,female',
            'date_of_birth' => 'nullable|date',
            'phone' => 'nullable|string|max:32',
            'section_id' => 'nullable|integer|exists:sections,id',
            'class_room_id' => 'nullable|integer|exists:classes,id',
            'password' => 'nullable|string|min:6|max:64',
        ]);
    }
}
