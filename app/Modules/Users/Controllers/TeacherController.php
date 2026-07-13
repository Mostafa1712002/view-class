<?php

namespace App\Modules\Users\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ClassRoom;
use App\Models\Subject;
use App\Models\JobTitle;
use App\Models\Role;
use App\Models\School;
use App\Models\Section;
use App\Models\TeacherAssignment;
use App\Models\TeacherProfile;
use App\Models\User;
use App\Modules\Users\Actions\ParseTeacherSheet;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use App\Modules\Users\Repositories\Contracts\TeacherRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TeacherController extends Controller
{
    use HasSchoolScope;

    public function __construct(private readonly TeacherRepository $teachers)
    {
    }

    public function index(Request $request): View
    {
        $teachers = $this->teachers->paginate(
            $this->listSchoolId(),
            $request->string('q')->toString() ?: null,
        );
        return view('admin.users.teachers.index', [
            'teachers' => $teachers,
            'q' => $request->string('q')->toString(),
        ]);
    }

    public function create(): View
    {
        return view('admin.users.teachers.create', [
            'schools' => $this->assignableSchools(),
            'subjects' => $this->assignableSubjects(),
            'selectedSubjectIds' => [],
        ]);
    }

    /**
     * Subjects an admin may link a teacher to (card #319). Super-admin sees all;
     * a school-admin is scoped to their own school's subjects.
     */
    private function assignableSubjects(?int $schoolId = null): \Illuminate\Support\Collection
    {
        $sid = $schoolId ?? $this->activeSchoolId();
        return Subject::query()
            ->when($sid, fn ($q) => $q->where('school_id', $sid))
            ->orderBy('name')->get();
    }

    /**
     * Sync the teacher's direct subject links (card #319). Only the section-less
     * rows (the plain teacher↔subject link) are managed here; section-scoped rows
     * created by schedule/weekly-plan assignments are left untouched.
     */
    private function syncSubjects(User $teacher, Request $request): void
    {
        $ids = collect($request->input('subject_ids', []))
            ->map(fn ($v) => (int) $v)->filter()->unique();

        $valid = Subject::query()
            ->whereIn('id', $ids)
            ->when($teacher->school_id, fn ($q) => $q->where('school_id', $teacher->school_id))
            ->pluck('id');

        DB::table('subject_teacher')->where('user_id', $teacher->id)->whereNull('section_id')->delete();
        $rows = $valid->map(fn ($sid) => [
            'user_id' => $teacher->id, 'subject_id' => $sid,
            'section_id' => null, 'academic_year_id' => null,
            'created_at' => now(), 'updated_at' => now(),
        ])->all();
        if ($rows) {
            DB::table('subject_teacher')->insert($rows);
        }
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateTeacher($request);
        $schoolId = $this->writeSchoolId($request);
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
            $this->syncSubjects($user, $request);
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
        $schools = $this->assignableSchools();

        // "التخصيص" panel — assign the teacher to specific classes within their
        // school so their "طلابي" page lists those students (card #318).
        $assignSchoolId = $teacher->school_id ?? $this->activeSchoolId();
        $sections = Section::query()->where('school_id', $assignSchoolId)->orderBy('name')->get();
        $classes = ClassRoom::query()->whereIn('section_id', $sections->pluck('id'))->orderBy('name')->get();
        $assignedClassIds = DB::table('class_teacher')->where('teacher_id', $teacher->id)->pluck('class_id')->all();

        // "المواد" — subjects the teacher is linked to (card #319).
        $subjects = $this->assignableSubjects($teacher->school_id);
        $selectedSubjectIds = $teacher->subjects()->pluck('subjects.id')->unique()->values()->all();

        return view('admin.users.teachers.edit', compact('teacher', 'schools', 'sections', 'classes', 'assignedClassIds', 'subjects', 'selectedSubjectIds'));
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
            if (auth()->user()?->isSuperAdmin()) {
                $teacher->school_id = $this->writeSchoolId($request);
            }
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
            $this->syncAssignedClasses($teacher, $request);
            $this->syncSubjects($teacher, $request);
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

    public function destroy(Request $request, int $id): RedirectResponse
    {
        if (! Hash::check((string) $request->input('confirm_password'), (string) auth()->user()->password)) {
            return redirect()->route('admin.users.teachers.index')
                ->with('error', __('users.delete_password_wrong'));
        }

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

    // ===================== Excel + photo tools =====================

    public function importForm(): View
    {
        return view('admin.users.teachers.import');
    }

    /** Download a CSV template (UTF-8 BOM so Excel reads Arabic correctly). */
    public function importTemplate(): StreamedResponse
    {
        $headers = ['رقم الهوية', 'الاسم الأول', 'اسم الأب', 'اسم الجد', 'اسم العائلة', 'الاسم بالإنجليزي', 'الرقم الوظيفي', 'جواز السفر', 'اسم المستخدم', 'التخصص', 'المؤهل', 'البريد الإلكتروني', 'رقم الهاتف', 'رقم الجوال', 'الجنس', 'تاريخ الميلاد', 'مكان الولادة', 'تاريخ التعيين', 'العنوان', 'الجنسية'];
        $sample = ['1020304050', 'محمد', 'أحمد', 'علي', 'الزهراني', 'Mohammed Alzahrani', 'EMP-1001', 'A1234567', 'mohammed.t', 'رياضيات', 'بكالوريوس', 'sample@example.com', '0500000000', '0500000001', 'male', '1990-01-01', 'الرياض', '2020-09-01', 'الرياض', 'سعودي'];

        return $this->streamCsv('teachers-template.csv', [$headers, $sample]);
    }

    /** Export current teachers so the admin can edit and re-upload. */
    public function export(): StreamedResponse
    {
        $rows = [[
            'رقم الهوية', 'الاسم الكامل', 'الاسم بالإنجليزي', 'الرقم الوظيفي', 'جواز السفر', 'اسم المستخدم', 'التخصص', 'المؤهل', 'البريد الإلكتروني', 'رقم الهاتف', 'رقم الجوال', 'الجنس', 'تاريخ الميلاد', 'مكان الولادة', 'تاريخ التعيين', 'العنوان', 'الجنسية',
        ]];

        $this->teachers->query($this->activeSchoolId())->with('teacherProfile')->orderBy('users.id')
            ->chunk(200, function ($chunk) use (&$rows) {
                foreach ($chunk as $t) {
                    $tp = $t->teacherProfile;
                    $rows[] = [
                        $t->national_id, $t->name, $t->name_en, $t->employee_id,
                        $tp->passport_number ?? null, $t->username, $t->specialization, $t->qualification,
                        $t->email, $t->phone, $tp->phone_secondary ?? null, $t->gender,
                        optional($t->date_of_birth)->format('Y-m-d'), $tp->birth_place ?? null,
                        optional($t->hire_date)->format('Y-m-d'), $t->address, $tp->nationality ?? null,
                    ];
                }
            });

        return $this->streamCsv('teachers-export-'.date('Ymd-His').'.csv', $rows);
    }

    /** Create new teacher accounts from an uploaded sheet (mirrors /teacher/editExcel add mode). */
    public function import(Request $request, ParseTeacherSheet $parser): RedirectResponse
    {
        $request->validate(['file' => 'required|file|mimes:csv,txt,xlsx,xls']);
        $schoolId = $this->activeSchoolId();
        $created = 0; $skipped = 0;

        $rows = $parser->execute($request->file('file'));
        DB::transaction(function () use ($rows, $schoolId, &$created, &$skipped) {
            $role = Role::where('slug', 'teacher')->first();
            foreach ($rows as $row) {
                $username = $row['username'] ?: ($row['national_id'] ?: null);
                $name = $row['name'] ?: $this->composeArabicName($row);
                if (!$username || $name === '') {
                    $skipped++;
                    continue;
                }
                if (User::where('username', $username)->exists()) {
                    $skipped++;
                    continue;
                }
                $plain = $row['national_id'] ?: str()->random(8);
                $user = User::create($this->withoutNulls([
                    'school_id' => $schoolId,
                    'name' => $name,
                    'name_ar' => $name,
                    'name_en' => $row['name_en'] ?: $this->composeEnglishName($row),
                    'username' => $username,
                    'email' => $row['email'] ?: ($username.'@viewclass.local'),
                    'national_id' => $row['national_id'] ?? null,
                    'employee_id' => $row['employee_id'] ?? null,
                    'specialization' => $row['specialization'] ?? null,
                    'qualification' => $row['qualification'] ?? null,
                    'gender' => $this->normalizeGender($row['gender'] ?? null),
                    'phone' => $row['phone'] ?? null,
                    'address' => $row['address'] ?? null,
                    'date_of_birth' => $row['date_of_birth'] ?? null,
                    'hire_date' => $row['hire_date'] ?? null,
                    'password' => Hash::make($plain),
                    'plain_password_for_card' => encrypt($plain),
                    'is_active' => true,
                    'status' => 'active',
                ]));
                if ($role) {
                    $user->roles()->syncWithoutDetaching($role);
                }
                $this->syncProfileFromRow($user, $row);
                $created++;
            }
        });

        return redirect()->route('admin.users.teachers.index')
            ->with('status', __('users.imported_count', ['count' => $created])
                .($skipped ? ' — '.__('users.skipped_count', ['count' => $skipped]) : ''));
    }

    /** Update existing teachers (matched by national_id / username) from an uploaded sheet. */
    public function importUpdate(Request $request, ParseTeacherSheet $parser): RedirectResponse
    {
        $request->validate(['file' => 'required|file|mimes:csv,txt,xlsx,xls']);
        $schoolId = $this->activeSchoolId();
        $updated = 0; $skipped = 0;

        $rows = $parser->execute($request->file('file'));
        DB::transaction(function () use ($rows, $schoolId, &$updated, &$skipped) {
            foreach ($rows as $row) {
                $teacher = $row['national_id']
                    ? $this->teachers->query($schoolId)->where('national_id', $row['national_id'])->first()
                    : ($row['username'] ? $this->teachers->query($schoolId)->where('username', $row['username'])->first() : null);
                if (!$teacher) {
                    $skipped++;
                    continue;
                }
                $name = $row['name'] ?: $this->composeArabicName($row);
                $teacher->fill($this->withoutNulls([
                    'name' => $name !== '' ? $name : null,
                    'name_ar' => $name !== '' ? $name : null,
                    'name_en' => $row['name_en'] ?? null,
                    'employee_id' => $row['employee_id'] ?? null,
                    'specialization' => $row['specialization'] ?? null,
                    'qualification' => $row['qualification'] ?? null,
                    'gender' => $this->normalizeGender($row['gender'] ?? null),
                    'phone' => $row['phone'] ?? null,
                    'address' => $row['address'] ?? null,
                    'date_of_birth' => $row['date_of_birth'] ?? null,
                    'hire_date' => $row['hire_date'] ?? null,
                ]));
                $teacher->save();
                $this->syncProfileFromRow($teacher, $row);
                $updated++;
            }
        });

        return redirect()->route('admin.users.teachers.index')
            ->with('status', __('users.updated_count', ['count' => $updated])
                .($skipped ? ' — '.__('users.skipped_count', ['count' => $skipped]) : ''));
    }

    /** Bulk import teacher photos from a ZIP (mirrors /teacher/zipFile). Files matched by national_id / username stem. */
    public function importPhotos(Request $request): RedirectResponse
    {
        $request->validate(['archive' => 'required|file|mimes:zip']);
        $schoolId = $this->activeSchoolId();
        $matched = 0; $skipped = 0;

        $zip = new \ZipArchive();
        if ($zip->open($request->file('archive')->getRealPath()) !== true) {
            return redirect()->route('admin.users.teachers.import')
                ->with('error', __('users.import_no_file'));
        }

        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entry = $zip->getNameIndex($i);
            if ($entry === false || str_ends_with($entry, '/')) {
                continue;
            }
            $base = basename($entry);
            if ($base === '' || str_starts_with($base, '.') || str_starts_with($base, '__MACOSX')) {
                continue;
            }
            $ext = strtolower(pathinfo($base, PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed, true)) {
                continue;
            }
            $stem = pathinfo($base, PATHINFO_FILENAME);
            $teacher = $this->teachers->query($schoolId)->where('national_id', $stem)->first()
                ?? $this->teachers->query($schoolId)->where('username', $stem)->first();
            if (!$teacher) {
                $skipped++;
                continue;
            }
            $contents = $zip->getFromIndex($i);
            if ($contents === false) {
                $skipped++;
                continue;
            }
            $storedPath = 'teachers/photos/'.$stem.'-'.uniqid().'.'.$ext;
            Storage::disk('public')->put($storedPath, $contents);
            TeacherProfile::updateOrCreate(['user_id' => $teacher->id], ['profile_photo' => $storedPath]);
            $matched++;
        }
        $zip->close();

        return redirect()->route('admin.users.teachers.index')
            ->with('status', __('users.photos_matched_count', ['count' => $matched])
                .($skipped ? ' — '.__('users.skipped_count', ['count' => $skipped]) : ''));
    }

    // ===================== Permissions / school assignments =====================

    /** صلاحيات وأدوار المعلم: school + role + job-title assignments. */
    public function permissions(int $id): View|RedirectResponse
    {
        $teacher = $this->teachers->findScoped($id, $this->activeSchoolId());
        if (!$teacher) {
            return redirect()->route('admin.users.teachers.index')->with('error', __('users.not_found'));
        }

        $schools = School::query()->orderBy('name')->get(['id', 'name']);
        $roles = Role::query()->where('is_active', true)
            ->whereIn('slug', ['school-admin', 'supervisor', 'counselor', 'teacher'])
            ->orderBy('id')->get(['id', 'name', 'slug']);
        $jobTitles = JobTitle::query()->forSchool($this->activeSchoolId())->active()
            ->orderBy('sort_order')->get(['id', 'name_ar']);
        $assignments = TeacherAssignment::query()->where('user_id', $teacher->id)
            ->with(['school:id,name', 'role:id,name', 'jobTitle:id,name_ar'])
            ->orderBy('school_id')->get();

        return view('admin.users.teachers.permissions', compact('teacher', 'schools', 'roles', 'jobTitles', 'assignments'));
    }

    public function storePermission(Request $request, int $id): RedirectResponse
    {
        $teacher = $this->teachers->findScoped($id, $this->activeSchoolId());
        if (!$teacher) {
            return redirect()->route('admin.users.teachers.index')->with('error', __('users.not_found'));
        }
        $data = $request->validate([
            'school_id' => 'required|integer|exists:schools,id',
            'role_id' => 'required|integer|exists:roles,id',
            'job_title_id' => 'nullable|integer|exists:job_titles,id',
        ]);

        TeacherAssignment::firstOrCreate([
            'user_id' => $teacher->id,
            'school_id' => $data['school_id'],
            'role_id' => $data['role_id'],
            'job_title_id' => $data['job_title_id'] ?? null,
        ]);

        return redirect()->route('admin.users.teachers.permissions', $teacher->id)
            ->with('status', __('users.assignment_added'));
    }

    public function destroyPermission(int $id, int $assignmentId): RedirectResponse
    {
        $teacher = $this->teachers->findScoped($id, $this->activeSchoolId());
        if (!$teacher) {
            return redirect()->route('admin.users.teachers.index')->with('error', __('users.not_found'));
        }
        TeacherAssignment::query()->where('id', $assignmentId)->where('user_id', $teacher->id)->delete();

        return redirect()->route('admin.users.teachers.permissions', $teacher->id)
            ->with('status', __('users.assignment_removed'));
    }

    // ===================== Helpers =====================

    /** @param array<int, array<int,mixed>> $rows */
    private function streamCsv(string $filename, array $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM for Excel
            foreach ($rows as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function normalizeGender(?string $gender): ?string
    {
        if (!$gender) {
            return null;
        }
        if (in_array($gender, ['male', 'female'], true)) {
            return $gender;
        }

        return in_array($gender, ['ذكر', 'm', 'M'], true) ? 'male'
            : (in_array($gender, ['أنثى', 'انثى', 'f', 'F'], true) ? 'female' : null);
    }

    /** Persist parsed-sheet profile fields onto the teacher's TeacherProfile. */
    private function syncProfileFromRow(User $user, array $row): void
    {
        TeacherProfile::updateOrCreate(
            ['user_id' => $user->id],
            $this->withoutNulls([
                'first_name_ar' => $row['first_name_ar'] ?? null,
                'father_name_ar' => $row['father_name_ar'] ?? null,
                'grandfather_name_ar' => $row['grandfather_name_ar'] ?? null,
                'family_name_ar' => $row['family_name_ar'] ?? null,
                'passport_number' => $row['passport_number'] ?? null,
                'birth_place' => $row['birth_place'] ?? null,
                'nationality' => $row['nationality'] ?? null,
                'phone_secondary' => $row['phone_secondary'] ?? null,
            ]),
        );
    }

    private function validateTeacher(Request $request, ?int $id = null): array
    {
        return $request->validate([
            'school_id' => (auth()->user()?->isSuperAdmin() ? 'required' : 'nullable').'|integer|exists:schools,id',
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
            // subject links (card #319)
            'subject_ids' => 'nullable|array',
            'subject_ids.*' => 'integer|exists:subjects,id',
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

    /**
     * Sync the teacher's "التخصيص" class assignments (card #318). Only classes
     * inside the teacher's own school are accepted, so an admin can never link
     * a teacher to another tenant's class.
     */
    private function syncAssignedClasses(User $teacher, Request $request): void
    {
        $ids = collect($request->input('assigned_class_ids', []))
            ->map(fn ($v) => (int) $v)->filter();

        $valid = ClassRoom::query()
            ->whereIn('id', $ids)
            ->when($teacher->school_id, fn ($q) => $q->whereHas('section', fn ($s) => $s->where('school_id', $teacher->school_id)))
            ->pluck('id');

        DB::table('class_teacher')->where('teacher_id', $teacher->id)->delete();
        $rows = $valid->map(fn ($cid) => [
            'class_id' => $cid, 'teacher_id' => $teacher->id, 'created_at' => now(), 'updated_at' => now(),
        ])->all();
        if ($rows) {
            DB::table('class_teacher')->insert($rows);
        }
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
