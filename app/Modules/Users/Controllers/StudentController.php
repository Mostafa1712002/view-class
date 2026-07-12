<?php

namespace App\Modules\Users\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\ClassRoom;
use App\Models\Role;
use App\Models\Section;
use App\Models\StudentProfile;
use App\Models\User;
use App\Modules\Subjects\Repositories\Contracts\SubjectRepository;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use App\Modules\Users\Repositories\Contracts\StudentRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class StudentController extends Controller
{
    use HasSchoolScope;

    public function __construct(
        private readonly StudentRepository $students,
        private readonly SubjectRepository $subjects,
    ) {}

    public function index(Request $request): View
    {
        $schoolId = $this->activeSchoolId();
        $q = $request->string('q')->toString();
        $filter = $request->string('filter')->toString();        // graduates | no_parents
        $view = $request->string('view')->toString();            // counts
        $advanced = (bool) $request->boolean('advanced');
        $sectionId = $request->integer('section_id') ?: null;
        $classId = $request->integer('class_room_id') ?: null;
        $gender = $request->string('gender')->toString();
        $status = $request->string('status')->toString();        // active | inactive

        $builder = $this->applyFilters(
            $this->students->query($schoolId)->with(['classRoom', 'section']),
            compact('q', 'filter', 'sectionId', 'classId', 'gender', 'status')
        );

        $counts = null;
        if ($view === 'counts') {
            $counts = $this->studentCounts($schoolId);
        }

        $students = $builder->orderBy('users.name')->paginate(25)->withQueryString();
        $sections = Section::query()->where('school_id', $schoolId)->orderBy('name')->get();
        $classes = ClassRoom::query()->whereIn('section_id', $sections->pluck('id'))->orderBy('name')->get();

        return view('admin.users.students.index', [
            'students' => $students,
            'sections' => $sections,
            'classes' => $classes,
            'q' => $q,
            'filter' => $filter,
            'view' => $view,
            'advanced' => $advanced || $sectionId || $classId || $gender || $status,
            'counts' => $counts,
            'sectionId' => $sectionId,
            'classId' => $classId,
            'gender' => $gender,
            'status' => $status,
        ]);
    }

    /** Apply the list search + filters shared by the index and bulk-graduate actions. */
    private function applyFilters(\Illuminate\Database\Eloquent\Builder $builder, array $f): \Illuminate\Database\Eloquent\Builder
    {
        if (! empty($f['q'])) {
            $needle = '%'.trim($f['q']).'%';
            $builder->where(function ($w) use ($needle) {
                $w->where('users.name', 'like', $needle)
                    ->orWhere('users.email', 'like', $needle)
                    ->orWhere('users.username', 'like', $needle)
                    ->orWhere('users.national_id', 'like', $needle);
            });
        }
        if (($f['filter'] ?? '') === 'graduates') {
            $builder->whereHas('section', fn ($s) => $s->where('name', 'like', '%خريج%'));
        }
        if (($f['filter'] ?? '') === 'no_parents') {
            $builder->whereDoesntHave('parents');
        }
        if (! empty($f['sectionId'])) {
            $builder->where('users.section_id', $f['sectionId']);
        }
        if (! empty($f['classId'])) {
            $builder->where('users.class_room_id', $f['classId']);
        }
        if (! empty($f['gender'])) {
            $builder->where('users.gender', $f['gender']);
        }
        if (($f['status'] ?? '') === 'active') {
            $builder->where('users.is_active', true);
        } elseif (($f['status'] ?? '') === 'inactive') {
            $builder->where('users.is_active', false);
        }

        return $builder;
    }

    /** Summary counts for the "أعداد الطلاب" view. */
    private function studentCounts(?int $schoolId): array
    {
        $base = fn () => $this->students->query($schoolId);

        $perSection = $base()->with('section')->get()
            ->groupBy(fn ($u) => optional($u->section)->name ?: __('users.no_grade'))
            ->map->count()->sortDesc();

        return [
            'total' => $base()->count(),
            'active' => $base()->where('users.is_active', true)->count(),
            'inactive' => $base()->where('users.is_active', false)->count(),
            'no_parents' => $base()->whereDoesntHave('parents')->count(),
            'graduates' => $base()->whereHas('section', fn ($s) => $s->where('name', 'like', '%خريج%'))->count(),
            'per_section' => $perSection,
        ];
    }

    /** "حذف الخريجين" — soft-delete all graduate students in the active school. */
    public function deleteGraduates(): RedirectResponse
    {
        $schoolId = $this->activeSchoolId();
        $graduates = $this->students->query($schoolId)
            ->whereHas('section', fn ($s) => $s->where('name', 'like', '%خريج%'))
            ->get();

        foreach ($graduates as $g) {
            $g->delete();
        }

        return redirect()->route('admin.users.students.index')
            ->with('status', __('users.graduates_deleted', ['count' => $graduates->count()]));
    }

    public function create(): View
    {
        $schoolId = $this->activeSchoolId();
        $sections = Section::query()->where('school_id', $schoolId)->orderBy('name')->get();
        $classes = ClassRoom::query()->whereIn('section_id', $sections->pluck('id'))->orderBy('name')->get();
        $schools = $this->assignableSchools();

        return view('admin.users.students.create', compact('sections', 'classes', 'schools'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateStudent($request);
        $schoolId = $this->writeSchoolId($request);

        $user = DB::transaction(function () use ($data, $schoolId) {
            $plain = ($data['password'] ?? null) ?: ($data['national_id'] ?? str()->random(8));
            $user = User::create($this->withoutNulls([
                'school_id' => $schoolId,
                'section_id' => $data['section_id'] ?? null,
                'class_room_id' => $data['class_room_id'] ?? null,
                'name' => $data['name'],
                'name_ar' => $data['name'],
                'name_en' => $data['name_en'] ?? null,
                'username' => $data['username'],
                'email' => ($data['email'] ?? null) ?: ($data['username'].'@viewclass.local'),
                'national_id' => $data['national_id'] ?? null,
                'gender' => $data['gender'] ?? null,
                'date_of_birth' => $data['date_of_birth'] ?? null,
                'phone' => $data['phone'] ?? null,
                'address' => $data['address'] ?? null,
                'password' => Hash::make($plain),
                'plain_password_for_card' => encrypt($plain),
                'is_active' => true,
                'status' => 'active',
            ]));

            $role = Role::where('slug', 'student')->first();
            if ($role) {
                $user->roles()->syncWithoutDetaching($role);
            }

            if (! empty($data['class_room_id'])) {
                DB::table('class_student')->insertOrIgnore([
                    'class_id' => $data['class_room_id'],
                    'student_id' => $user->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $this->syncProfile($user->id, $data);

            return $user;
        });

        $this->focusScopeOnSchool($schoolId);

        return redirect()->route('admin.users.students.index')
            ->with('status', __('users.student_created', ['name' => $user->name]));
    }

    public function show(int $id): View|RedirectResponse
    {
        $student = $this->students->findScoped($id, $this->activeSchoolId());
        if (! $student) {
            return redirect()->route('admin.users.students.index')->with('error', __('users.not_found'));
        }
        $profile = StudentProfile::firstOrNew(['user_id' => $student->id]);
        $student->load(['classRoom', 'section', 'parents']);

        return view('admin.users.students.show', compact('student', 'profile'));
    }

    public function edit(int $id): View|RedirectResponse
    {
        $student = $this->students->findScoped($id, $this->activeSchoolId());
        if (! $student) {
            return redirect()->route('admin.users.students.index')->with('error', __('users.not_found'));
        }

        $schoolId = $this->activeSchoolId();
        $sections = Section::query()->where('school_id', $schoolId)->orderBy('name')->get();
        $classes = ClassRoom::query()->whereIn('section_id', $sections->pluck('id'))->orderBy('name')->get();
        $profile = StudentProfile::firstOrNew(['user_id' => $student->id]);
        $schools = $this->assignableSchools();

        return view('admin.users.students.edit', compact('student', 'sections', 'classes', 'profile', 'schools'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $student = $this->students->findScoped($id, $this->activeSchoolId());
        if (! $student) {
            return redirect()->route('admin.users.students.index')->with('error', __('users.not_found'));
        }
        $data = $this->validateStudent($request, $id);

        $student->fill([
            'name' => $data['name'],
            'name_ar' => $data['name'],
            'name_en' => $data['name_en'] ?? $student->name_en,
            'username' => $data['username'],
            'email' => ($data['email'] ?? null) ?: ($data['username'].'@viewclass.local'),
            'national_id' => $data['national_id'] ?? null,
            'gender' => $data['gender'] ?? null,
            'date_of_birth' => $data['date_of_birth'] ?? null,
            'phone' => $data['phone'] ?? null,
            'address' => $data['address'] ?? null,
            'section_id' => $data['section_id'] ?? null,
            'class_room_id' => $data['class_room_id'] ?? null,
        ]);
        // Super-admin may re-file a student under a different school; a school
        // admin never changes it (writeSchoolId returns their own scope).
        if (auth()->user()?->isSuperAdmin()) {
            $student->school_id = $this->writeSchoolId($request);
        }
        if (! empty($data['password'])) {
            $student->password = Hash::make($data['password']);
            $student->plain_password_for_card = encrypt($data['password']);
        }
        $student->save();

        $this->syncProfile($student->id, $data);
        $this->focusScopeOnSchool($student->school_id);

        return redirect()->route('admin.users.students.index')
            ->with('status', __('users.student_updated', ['name' => $student->name]));
    }

    public function destroy(Request $request, int $id): RedirectResponse
    {
        if (! Hash::check((string) $request->input('confirm_password'), (string) auth()->user()->password)) {
            return redirect()->route('admin.users.students.index')
                ->with('error', __('users.delete_password_wrong'));
        }

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
                    $u->status = 'inactive';
                    $u->is_active = false;
                    break;
                case 'waiting':
                    $u->status = 'pending';
                    $u->is_active = false;
                    break;
                case 'hide_grades':
                case 'show_grades':
                case 'hide_report':
                case 'show_report':
                    $prefs = $u->notification_preferences ?? [];
                    if (is_string($prefs)) {
                        $prefs = json_decode($prefs, true) ?: [];
                    }
                    $key = match ($data['action']) {
                        'hide_grades', 'show_grades' => 'grades_hidden',
                        'hide_report', 'show_report' => 'report_hidden',
                    };
                    $prefs[$key] = str_starts_with($data['action'], 'hide_');
                    $u->notification_preferences = $prefs;
                    break;
            }
            $u->save();
        }

        return redirect()->route('admin.users.students.index')
            ->with('status', __('users.bulk_done', ['count' => $rows->count()]));
    }

    /** Bulk student-photo import — upload form (cards #125 + #130). */
    public function photosForm(): View
    {
        return view('admin.users.students.photos');
    }

    /**
     * Import student photos in bulk from a ZIP (card #130, per the analysis).
     * Each image's file name (without extension) is the student's national id;
     * matched students (in the active school) get the image as their avatar.
     * Returns a per-file report (updated / rejected with reason).
     */
    public function importPhotos(Request $request): RedirectResponse
    {
        $request->validate(['archive' => ['required', 'file', 'mimes:zip']]);
        $schoolId = $this->activeSchoolId();

        $zip = new \ZipArchive();
        if ($zip->open($request->file('archive')->getRealPath()) !== true) {
            return redirect()->route('admin.users.students.photos')->with('error', __('users.photos_bad_zip'));
        }

        $allowed = ['jpg', 'jpeg', 'png'];
        $rows = [];
        $updated = 0;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entry = $zip->getNameIndex($i);
            if ($entry === false || str_ends_with($entry, '/')) {
                continue; // skip directories
            }
            $base = basename($entry);
            if ($base === '' || str_starts_with($base, '.') || str_starts_with($entry, '__MACOSX')) {
                continue; // skip hidden / mac metadata
            }

            $ext = strtolower(pathinfo($base, PATHINFO_EXTENSION));
            if (! in_array($ext, $allowed, true)) {
                $rows[] = ['file' => $base, 'ok' => false, 'reason' => __('users.photos_reason_ext')];

                continue;
            }

            $key = trim(pathinfo($base, PATHINFO_FILENAME));
            $student = $key === '' ? null : User::query()
                ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
                ->where(fn ($q) => $q->where('national_id', $key)->orWhere('username', $key))
                ->first();

            if (! $student) {
                $rows[] = ['file' => $base, 'ok' => false, 'reason' => __('users.photos_reason_no_student')];

                continue;
            }

            $contents = $zip->getFromIndex($i);
            if ($contents === false || $contents === '') {
                $rows[] = ['file' => $base, 'ok' => false, 'reason' => __('users.photos_reason_unreadable')];

                continue;
            }

            if ($student->avatar) {
                Storage::disk('public')->delete($student->avatar);
            }
            $path = 'avatars/'.$key.'-'.uniqid().'.'.$ext;
            Storage::disk('public')->put($path, $contents);
            $student->avatar = $path;
            $student->save();
            $rows[] = ['file' => $base, 'ok' => true, 'reason' => $student->name];
            $updated++;
        }
        $total = $zip->numFiles;
        $zip->close();

        return redirect()->route('admin.users.students.photos')
            ->with('photo_result', ['rows' => $rows, 'updated' => $updated, 'rejected' => count(array_filter($rows, fn ($r) => ! $r['ok']))]);
    }

    /** "Update student data from Excel" — upload page (card #130). */
    public function statusForm(Request $request): View
    {
        return view('admin.users.students.status');
    }

    /**
     * Update EXISTING students from an Excel/CSV file (card #130). Rows are matched
     * to existing students by national id (then username) within the active school
     * and updated in place — new students are never created and passwords are never
     * changed. Returns a per-row report.
     */
    public function updateFromExcel(Request $request, \App\Modules\StudentImport\Actions\ParseStudentExcel $parser): RedirectResponse
    {
        $request->validate(['file' => ['required', 'file', 'mimes:csv,txt,xlsx,xls']]);
        $schoolId = $this->activeSchoolId();

        try {
            $dtos = $parser->execute($request->file('file'));
        } catch (\Throwable $e) {
            return redirect()->route('admin.users.students.status')->with('error', $e->getMessage());
        }

        $rows = [];
        $updated = 0;
        foreach ($dtos as $dto) {
            $key = $dto->nationalId ?: $dto->username;
            $student = $key === null ? null : User::query()
                ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
                ->where(fn ($q) => $q->where('national_id', $dto->nationalId)->orWhere('username', $dto->username))
                ->whereHas('roles', fn ($r) => $r->where('slug', 'student'))
                ->first();

            if (! $student) {
                $rows[] = ['row' => $dto->rowNumber, 'key' => (string) $key, 'ok' => false, 'reason' => __('users.update_reason_no_student')];

                continue;
            }

            // Compose the name only if name parts were supplied; never touch the password.
            $name = $dto->fullName();
            $student->fill($this->withoutNulls([
                'name' => $name !== '' ? $name : null,
                'name_ar' => $name !== '' ? $name : null,
                'phone' => $dto->mobile,
                'email' => $dto->email,
                'gender' => $this->normalizeGenderValue($dto->gender),
                'date_of_birth' => $dto->birthDate,
                'nationality' => $dto->nationality,
            ]));
            $student->save();

            // Update light profile fields if present.
            $profileData = $this->withoutNulls([
                'academic_id' => $dto->academicId,
                'birth_place' => $dto->birthPlace,
            ]);
            if ($profileData !== []) {
                StudentProfile::updateOrCreate(['user_id' => $student->id], $profileData);
            }

            // Re-link the class if a matching class name exists in the school.
            if ($dto->classRoom) {
                $classId = ClassRoom::query()
                    ->where('name', $dto->classRoom)
                    ->when($schoolId, fn ($w) => $w->whereHas('section', fn ($s) => $s->where('school_id', $schoolId)))
                    ->value('id');
                if ($classId) {
                    $student->class_room_id = $classId;
                    $student->save();
                    DB::table('class_student')->insertOrIgnore([
                        'class_id' => $classId, 'student_id' => $student->id, 'created_at' => now(), 'updated_at' => now(),
                    ]);
                }
            }

            $rows[] = ['row' => $dto->rowNumber, 'key' => (string) $key, 'ok' => true, 'reason' => $student->name];
            $updated++;
        }

        return redirect()->route('admin.users.students.status')
            ->with('update_result', ['rows' => $rows, 'updated' => $updated, 'skipped' => count(array_filter($rows, fn ($r) => ! $r['ok']))]);
    }

    /** Drop null/blank values so an update only writes the columns actually supplied. */
    private function withoutNulls(array $data): array
    {
        return array_filter($data, fn ($v) => $v !== null && $v !== '');
    }

    /** Normalize a free-text gender value to male/female (or null). */
    private function normalizeGenderValue(?string $g): ?string
    {
        $g = trim((string) $g);
        if ($g === '') {
            return null;
        }
        $g = mb_strtolower($g);

        return in_array($g, ['male', 'm', 'ذكر', 'ولد'], true) ? 'male'
            : (in_array($g, ['female', 'f', 'أنثى', 'انثى', 'بنت'], true) ? 'female' : null);
    }

    /**
     * Parents linked to a student (read-only listing for now — wired from the row dropdown).
     */
    public function parents(int $id): View|RedirectResponse
    {
        $student = $this->students->findScoped($id, $this->activeSchoolId());
        if (! $student) {
            return redirect()->route('admin.users.students.index')->with('error', __('users.not_found'));
        }
        $parents = $student->parents()->get();

        return view('admin.users.students.parents', compact('student', 'parents'));
    }

    /**
     * The student's class schedule (read-only for the admin user-management view).
     */
    public function schedule(int $id): View|RedirectResponse
    {
        $student = $this->students->findScoped($id, $this->activeSchoolId());
        if (! $student) {
            return redirect()->route('admin.users.students.index')->with('error', __('users.not_found'));
        }

        $class = $student->classRoom;
        $periods = collect();
        if ($class) {
            $schedule = $class->schedules()
                ->where('is_active', true)
                ->first();
            if ($schedule) {
                $periods = $schedule->periods()
                    ->with(['subject', 'teacher'])
                    ->orderBy('day_of_week')
                    ->orderBy('period_number')
                    ->get()
                    ->groupBy('day_of_week');
            }
        }
        $days = [
            'sunday' => 'الأحد',
            'monday' => 'الإثنين',
            'tuesday' => 'الثلاثاء',
            'wednesday' => 'الأربعاء',
            'thursday' => 'الخميس',
        ];

        return view('admin.users.students.schedule', compact('student', 'class', 'periods', 'days'));
    }

    /**
     * Lessons / classes the student is enrolled in (read-only listing).
     */
    public function lessons(int $id): View|RedirectResponse
    {
        $student = $this->students->findScoped($id, $this->activeSchoolId());
        if (! $student) {
            return redirect()->route('admin.users.students.index')->with('error', __('users.not_found'));
        }
        $classes = $student->enrolledClasses()->with('section')->get();

        // Subjects are linked to a grade level (not directly to a class), so
        // resolve each class's subjects via its grade level. Fall back to the
        // student's own school when no active scope is set (e.g. super-admin).
        $schoolId = $this->activeSchoolId() ?? $student->school_id ?? optional($student->section)->school_id;
        foreach ($classes as $class) {
            $subjects = collect($this->subjects->subjectsForGradeLevel($schoolId, (int) $class->grade_level));
            $class->setRelation('subjects', $subjects);
        }

        return view('admin.users.students.lessons', compact('student', 'classes'));
    }

    /**
     * Attendance history for a student (latest 60 entries).
     */
    public function attendance(int $id): View|RedirectResponse
    {
        $student = $this->students->findScoped($id, $this->activeSchoolId());
        if (! $student) {
            return redirect()->route('admin.users.students.index')->with('error', __('users.not_found'));
        }
        $attendances = Attendance::query()
            ->where('student_id', $student->id)
            ->with(['subject', 'classRoom'])
            ->orderByDesc('date')
            ->paginate(30);

        return view('admin.users.students.attendance', compact('student', 'attendances'));
    }

    /**
     * Behaviour log — page shell, full CRUD deferred to future card.
     */
    public function behavior(int $id): View|RedirectResponse
    {
        $student = $this->students->findScoped($id, $this->activeSchoolId());
        if (! $student) {
            return redirect()->route('admin.users.students.index')->with('error', __('users.not_found'));
        }

        // Behaviour records logged for this student (card #123) + running points total.
        $records = \App\Models\BehaviorRecord::query()
            ->with(['behavior', 'action', 'recorder'])
            ->where('scope', 'student')
            ->where('subject_user_id', $student->id)
            ->orderByDesc('id')
            ->limit(500)
            ->get();
        $pointsTotal = (int) \App\Models\BehaviorRecord::where('scope', 'student')
            ->where('subject_user_id', $student->id)->sum('points');

        return view('admin.users.students.behavior', compact('student', 'records', 'pointsTotal'));
    }

    /**
     * Medical record — page shell, full CRUD deferred to future card.
     */
    public function medical(int $id): View|RedirectResponse
    {
        $student = $this->students->findScoped($id, $this->activeSchoolId());
        if (! $student) {
            return redirect()->route('admin.users.students.index')->with('error', __('users.not_found'));
        }

        return view('admin.users.students.medical', compact('student'));
    }

    private function syncProfile(int $userId, array $data): void
    {
        StudentProfile::updateOrCreate(
            ['user_id' => $userId],
            collect($data)->only([
                'first_name', 'father_name', 'grandfather_name', 'last_name',
                'first_name_en', 'father_name_en', 'grandfather_name_en', 'last_name_en',
                'fingerprint_id', 'seat_number', 'passport_number', 'nationality',
                'academic_id', 'birth_place', 'admission_year',
                'previous_school', 'enrollment_date',
                'father_national_id', 'mother_national_id', 'mother_full_name',
                'home_phone', 'notes',
            ])->toArray()
        );
    }

    private function validateStudent(Request $request, ?int $id = null): array
    {
        return $request->validate([
            'school_id' => (auth()->user()?->isSuperAdmin() ? 'required' : 'nullable').'|integer|exists:schools,id',
            'name' => 'required|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'username' => 'required|string|max:64|unique:users,username'.($id ? ','.$id : ''),
            'email' => 'nullable|email|max:255|unique:users,email'.($id ? ','.$id : ''),
            'national_id' => 'nullable|string|max:32',
            'gender' => 'nullable|in:male,female',
            'date_of_birth' => 'nullable|date',
            'phone' => 'nullable|string|max:32',
            'address' => 'nullable|string|max:1000',
            'section_id' => 'nullable|integer|exists:sections,id',
            'class_room_id' => 'nullable|integer|exists:classes,id',
            'password' => 'nullable|string|min:6|max:64',

            // Profile name parts
            'first_name' => 'nullable|string|max:255',
            'father_name' => 'nullable|string|max:255',
            'grandfather_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'first_name_en' => 'nullable|string|max:255',
            'father_name_en' => 'nullable|string|max:255',
            'grandfather_name_en' => 'nullable|string|max:255',
            'last_name_en' => 'nullable|string|max:255',

            // Identification
            'fingerprint_id' => 'nullable|string|max:64',
            'seat_number' => 'nullable|string|max:32',
            'passport_number' => 'nullable|string|max:32',
            'nationality' => 'nullable|string|max:64',
            'academic_id' => 'nullable|string|max:64',
            'birth_place' => 'nullable|string|max:128',
            'admission_year' => 'nullable|integer|min:1990|max:2100',

            // Schooling
            'previous_school' => 'nullable|string|max:255',
            'enrollment_date' => 'nullable|date',

            // Family
            'father_national_id' => 'nullable|string|max:32',
            'mother_national_id' => 'nullable|string|max:32',
            'mother_full_name' => 'nullable|string|max:255',

            // Contact
            'home_phone' => 'nullable|string|max:32',

            // Notes
            'notes' => 'nullable|string|max:2000',
        ]);
    }
}
