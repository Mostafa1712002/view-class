<?php

namespace App\Modules\Users\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Modules\Users\Actions\ParseParentSheet;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use App\Modules\Users\Repositories\Contracts\ParentRepository;
use App\Modules\Users\Repositories\Contracts\StudentRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ParentController extends Controller
{
    use HasSchoolScope;

    public function __construct(
        private readonly ParentRepository $parents,
        private readonly StudentRepository $students,
    ) {
    }

    public function index(Request $request): View
    {
        $parents = $this->parents->paginate(
            $this->activeSchoolId(),
            $request->string('q')->toString() ?: null,
        );

        return view('admin.users.parents.index', [
            'parents' => $parents,
            'q' => $request->string('q')->toString(),
        ]);
    }

    public function create(): View
    {
        return view('admin.users.parents.create', ['schools' => $this->assignableSchools()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateParent($request);
        $schoolId = $this->writeSchoolId($request);
        DB::transaction(function () use ($data, $request, $schoolId) {
            $plain = ($data['password'] ?? null) ?: ($data['national_id'] ?? str()->random(8));
            $user = User::create($this->withoutNulls(array_merge($this->mapProfile($data), [
                'school_id' => $schoolId,
                'username' => $data['username'],
                'email' => ($data['email'] ?? null) ?: ($data['username'].'@viewclass.local'),
                'password' => Hash::make($plain),
                'plain_password_for_card' => encrypt($plain),
                'is_active' => true,
                'status' => 'active',
            ])));
            if ($request->hasFile('profile_picture')) {
                $user->profile_picture = $request->file('profile_picture')->store('parents/photos', 'public');
                $user->save();
            }
            $this->attachParentRole($user);
        });

        $this->focusScopeOnSchool($schoolId);

        return redirect()->route('admin.users.parents.index')
            ->with('status', __('users.parent_created'));
    }

    public function edit(int $id): View|RedirectResponse
    {
        $parent = $this->parents->findScoped($id, $this->activeSchoolId());
        if (!$parent) {
            return redirect()->route('admin.users.parents.index')->with('error', __('users.not_found'));
        }
        $schools = $this->assignableSchools();
        return view('admin.users.parents.edit', compact('parent', 'schools'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $parent = $this->parents->findScoped($id, $this->activeSchoolId());
        if (!$parent) {
            return redirect()->route('admin.users.parents.index')->with('error', __('users.not_found'));
        }
        $data = $this->validateParent($request, $id);
        $parent->fill(array_merge($this->mapProfile($data), [
            'username' => $data['username'],
            'email' => ($data['email'] ?? null) ?: ($data['username'].'@viewclass.local'),
        ]));
        if (auth()->user()?->isSuperAdmin()) {
            $parent->school_id = $this->writeSchoolId($request);
        }
        if (!empty($data['password'])) {
            $parent->password = Hash::make($data['password']);
            $parent->plain_password_for_card = encrypt($data['password']);
        }
        if ($request->hasFile('profile_picture')) {
            $parent->profile_picture = $request->file('profile_picture')->store('parents/photos', 'public');
        }
        $parent->save();
        $this->focusScopeOnSchool($parent->school_id);
        return redirect()->route('admin.users.parents.index')
            ->with('status', __('users.parent_updated'));
    }

    public function destroy(Request $request, int $id): RedirectResponse
    {
        if (! Hash::check((string) $request->input('confirm_password'), (string) auth()->user()->password)) {
            return redirect()->route('admin.users.parents.index')
                ->with('error', __('users.delete_password_wrong'));
        }

        $parent = $this->parents->findScoped($id, $this->activeSchoolId());
        if ($parent) {
            $parent->delete();
        }
        return redirect()->route('admin.users.parents.index')
            ->with('status', __('users.parent_deleted'));
    }

    public function show(int $id): View|RedirectResponse
    {
        $parent = $this->parents->findScoped($id, $this->activeSchoolId());
        if (!$parent) {
            return redirect()->route('admin.users.parents.index')->with('error', __('users.not_found'));
        }
        $children = $parent->children()->with('classRoom')->get();
        return view('admin.users.parents.show', compact('parent', 'children'));
    }

    public function students(Request $request, int $id): View|RedirectResponse
    {
        $parent = $this->parents->findScoped($id, $this->activeSchoolId());
        if (!$parent) {
            return redirect()->route('admin.users.parents.index')->with('error', __('users.not_found'));
        }
        $linked = $parent->children()->with('classRoom')->get();
        $q = $request->string('q')->toString() ?: null;
        $available = $this->students->paginate($this->activeSchoolId(), $q, 30);
        return view('admin.users.parents.students', compact('parent', 'linked', 'available', 'q'));
    }

    public function syncStudents(Request $request, int $id): RedirectResponse
    {
        $parent = $this->parents->findScoped($id, $this->activeSchoolId());
        if (!$parent) {
            return redirect()->route('admin.users.parents.index')->with('error', __('users.not_found'));
        }
        $ids = collect($request->input('student_ids', []))->map(fn ($v) => (int) $v)->filter()->all();
        $payload = [];
        foreach ($ids as $sid) {
            $payload[$sid] = ['relationship' => 'parent', 'is_primary' => false, 'can_receive_notifications' => true];
        }
        $parent->children()->sync($payload);
        return redirect()->route('admin.users.parents.students', $parent->id)
            ->with('status', __('users.parent_students_synced'));
    }

    // ===================== Excel tools =====================

    public function importForm(): View
    {
        return view('admin.users.parents.import');
    }

    /** Download a CSV template (UTF-8 BOM so Excel reads Arabic correctly). */
    public function importTemplate(): StreamedResponse
    {
        $headers = ['رقم الهوية', 'الاسم الكامل', 'اسم المستخدم', 'البريد الإلكتروني', 'رقم الهاتف', 'رقم الجوال', 'الواتساب', 'الجنس', 'تاريخ الميلاد', 'مكان الولادة', 'العنوان', 'الجنسية', 'الاسم بالإنجليزي', 'هوية الطالب'];
        $sample = ['1010101010', 'ولي أمر تجريبي', 'parent.sample', 'sample@example.com', '0500000000', '0500000001', '0500000001', 'male', '1985-01-01', 'الرياض', 'الرياض', 'سعودي', 'Sample Parent', '9111111111'];

        return $this->streamCsv('parents-template.csv', [$headers, $sample]);
    }

    /** Export current parents so the admin can edit and re-upload. */
    public function export(): StreamedResponse
    {
        $rows = [[
            'رقم الهوية', 'الاسم الكامل', 'اسم المستخدم', 'البريد الإلكتروني', 'رقم الهاتف', 'رقم الجوال', 'الواتساب', 'الجنس', 'تاريخ الميلاد', 'مكان الولادة', 'العنوان', 'الجنسية', 'الاسم بالإنجليزي',
        ]];

        $this->parents->query($this->activeSchoolId())->orderBy('users.id')->chunk(200, function ($chunk) use (&$rows) {
            foreach ($chunk as $p) {
                $rows[] = [
                    $p->national_id, $p->name, $p->username, $p->email,
                    $p->phone, $p->phone_secondary, $p->whatsapp, $p->gender,
                    optional($p->date_of_birth)->format('Y-m-d'), $p->birth_place,
                    $p->address, $p->nationality, $p->name_en,
                ];
            }
        });

        return $this->streamCsv('parents-export-'.date('Ymd-His').'.csv', $rows);
    }

    /** Create new parent accounts from an uploaded sheet. */
    public function import(Request $request, ParseParentSheet $parser): RedirectResponse
    {
        $request->validate(['file' => 'required|file|mimes:csv,txt,xlsx,xls']);
        $schoolId = $this->activeSchoolId();
        $created = 0; $skipped = 0; $errors = [];

        $rows = $parser->execute($request->file('file'));
        DB::transaction(function () use ($rows, $schoolId, &$created, &$skipped, &$errors) {
            foreach ($rows as $row) {
                $username = $row['username'] ?: ($row['national_id'] ?: null);
                $name = $row['name'] ?: trim(($row['first_name'] ?? '').' '.($row['family_name'] ?? ''));
                if (!$username || $name === '') {
                    $skipped++; $errors[] = $row['_row'];
                    continue;
                }
                if (User::where('username', $username)->exists()) {
                    $skipped++; $errors[] = $row['_row'];
                    continue;
                }
                $plain = $row['national_id'] ?: str()->random(8);
                $user = User::create($this->withoutNulls(array_merge($this->mapProfile($row), [
                    'name' => $name,
                    'school_id' => $schoolId,
                    'username' => $username,
                    'email' => $row['email'] ?: ($username.'@viewclass.local'),
                    'password' => Hash::make($plain),
                    'plain_password_for_card' => encrypt($plain),
                    'is_active' => true,
                    'status' => 'active',
                ])));
                $this->attachParentRole($user);
                $created++;
            }
        });

        return redirect()->route('admin.users.parents.index')
            ->with('status', __('users.imported_count', ['count' => $created])
                .($skipped ? ' — '.__('users.skipped_count', ['count' => $skipped]) : ''));
    }

    /** Update existing parents (matched by national_id) from an uploaded sheet. */
    public function importUpdate(Request $request, ParseParentSheet $parser): RedirectResponse
    {
        $request->validate(['file' => 'required|file|mimes:csv,txt,xlsx,xls']);
        $schoolId = $this->activeSchoolId();
        $updated = 0; $skipped = 0;

        $rows = $parser->execute($request->file('file'));
        DB::transaction(function () use ($rows, $schoolId, &$updated, &$skipped) {
            foreach ($rows as $row) {
                $parent = $row['national_id']
                    ? $this->parents->query($schoolId)->where('national_id', $row['national_id'])->first()
                    : ($row['username'] ? $this->parents->query($schoolId)->where('username', $row['username'])->first() : null);
                if (!$parent) {
                    $skipped++;
                    continue;
                }
                $parent->fill($this->withoutNulls($this->mapProfile($row)));
                $parent->save();
                $updated++;
            }
        });

        return redirect()->route('admin.users.parents.index')
            ->with('status', __('users.updated_count', ['count' => $updated])
                .($skipped ? ' — '.__('users.skipped_count', ['count' => $skipped]) : ''));
    }

    /** Link parents to students using national-id columns in an uploaded sheet. */
    public function linkByNumbers(Request $request, ParseParentSheet $parser): RedirectResponse
    {
        $request->validate(['file' => 'required|file|mimes:csv,txt,xlsx,xls']);
        $schoolId = $this->activeSchoolId();
        $linked = 0; $skipped = 0;

        $rows = $parser->execute($request->file('file'));
        DB::transaction(function () use ($rows, $schoolId, &$linked, &$skipped) {
            foreach ($rows as $row) {
                $parent = $row['national_id']
                    ? $this->parents->query($schoolId)->where('national_id', $row['national_id'])->first()
                    : null;
                $student = $row['student_national_id']
                    ? $this->students->query($schoolId)->where('national_id', $row['student_national_id'])->first()
                    : null;
                if (!$parent || !$student) {
                    $skipped++;
                    continue;
                }
                $parent->children()->syncWithoutDetaching([
                    $student->id => ['relationship' => 'parent', 'is_primary' => false, 'can_receive_notifications' => true],
                ]);
                $linked++;
            }
        });

        return redirect()->route('admin.users.parents.index')
            ->with('status', __('users.linked_count', ['count' => $linked])
                .($skipped ? ' — '.__('users.skipped_count', ['count' => $skipped]) : ''));
    }

    // ===================== Helpers =====================

    private function attachParentRole(User $user): void
    {
        $role = Role::where('slug', 'parent')->first();
        if ($role) {
            $user->roles()->syncWithoutDetaching($role);
        }
    }

    /** Build the writable profile payload from validated/parsed data. */
    private function mapProfile(array $data): array
    {
        $name = $data['name'] ?? null;
        if (!$name) {
            $name = trim(implode(' ', array_filter([
                $data['first_name'] ?? null,
                $data['father_name'] ?? null,
                $data['family_name'] ?? null,
            ]))) ?: null;
        }

        $gender = $data['gender'] ?? null;
        if ($gender && !in_array($gender, ['male', 'female'], true)) {
            $gender = in_array($gender, ['ذكر', 'm', 'M'], true) ? 'male'
                : (in_array($gender, ['أنثى', 'انثى', 'f', 'F'], true) ? 'female' : null);
        }

        return [
            'name' => $name,
            'name_ar' => $name,
            'name_en' => $data['name_en'] ?? null,
            'first_name' => $data['first_name'] ?? null,
            'father_name' => $data['father_name'] ?? null,
            'grandfather_name' => $data['grandfather_name'] ?? null,
            'family_name' => $data['family_name'] ?? null,
            'national_id' => $data['national_id'] ?? null,
            'gender' => $gender,
            'phone' => $data['phone'] ?? null,
            'phone_secondary' => $data['phone_secondary'] ?? null,
            'whatsapp' => $data['whatsapp'] ?? null,
            'address' => $data['address'] ?? null,
            'date_of_birth' => $data['date_of_birth'] ?? null,
            'birth_place' => $data['birth_place'] ?? null,
            'nationality' => $data['nationality'] ?? null,
        ];
    }

    private function validateParent(Request $request, ?int $id = null): array
    {
        return $request->validate([
            'school_id' => (auth()->user()?->isSuperAdmin() ? 'required' : 'nullable').'|integer|exists:schools,id',
            'name' => 'required|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'first_name' => 'nullable|string|max:128',
            'father_name' => 'nullable|string|max:128',
            'grandfather_name' => 'nullable|string|max:128',
            'family_name' => 'nullable|string|max:128',
            'username' => 'required|string|max:64|unique:users,username'.($id ? ','.$id : ''),
            'email' => 'nullable|email|max:255|unique:users,email'.($id ? ','.$id : ''),
            'national_id' => 'nullable|string|max:32',
            'gender' => 'nullable|in:male,female',
            'phone' => 'nullable|string|max:32',
            'phone_secondary' => 'nullable|string|max:32',
            'whatsapp' => 'nullable|string|max:32',
            'address' => 'nullable|string|max:500',
            'date_of_birth' => 'nullable|date',
            'birth_place' => 'nullable|string|max:128',
            'nationality' => 'nullable|string|max:64',
            'profile_picture' => 'nullable|image|max:4096',
            'password' => 'nullable|string|min:6|max:64',
        ]);
    }

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
}
