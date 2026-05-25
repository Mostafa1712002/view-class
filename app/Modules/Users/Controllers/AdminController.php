<?php

namespace App\Modules\Users\Controllers;

use App\Http\Controllers\Controller;
use App\Models\JobTitle;
use App\Models\Role;
use App\Models\User;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use App\Modules\Users\Repositories\Contracts\AdminRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AdminController extends Controller
{
    use HasSchoolScope;

    public function __construct(private readonly AdminRepository $admins)
    {
    }

    public function index(Request $request): View
    {
        $schoolId = $this->activeSchoolId();
        $q = $request->string('q')->toString();
        $jobTitleId = (int) $request->input('job_title_id') ?: null;

        $admins = $this->buildAdminListQuery($schoolId, $q ?: null, $jobTitleId)
            ->with(['jobTitle', 'roles'])
            ->orderBy('users.name')
            ->paginate(25)
            ->withQueryString();

        $jobTitles = JobTitle::query()->forSchool($schoolId)->active()->orderBy('sort_order')->get();

        // === Admins card 55 — page KPIs ===
        $stats = $this->computeAdminStats($schoolId);

        return view('admin.users.admins.index', [
            'admins' => $admins,
            'jobTitles' => $jobTitles,
            'q' => $q,
            'stats' => $stats,
        ]);
    }

    /**
     * Shared admin list builder — duplicates the repo's logic so we can
     * inject job_title_id filtering without touching the contract. (card 55)
     */
    private function buildAdminListQuery(?int $schoolId, ?string $search, ?int $jobTitleId)
    {
        $endUserRoles = ['student', 'parent', 'teacher'];

        $q = User::query()
            ->whereHas('roles', fn ($r) => $r->whereNotIn('slug', $endUserRoles))
            ->whereDoesntHave('roles', fn ($r) => $r->whereIn('slug', $endUserRoles));

        if ($schoolId !== null) {
            $q->where(function ($w) use ($schoolId) {
                $w->where('users.school_id', $schoolId)
                  ->orWhereHas('roles', fn ($r) => $r->where('slug', 'super-admin'));
            });
        }

        if ($search !== null && trim($search) !== '') {
            $needle = '%'.trim($search).'%';
            $q->where(function ($w) use ($needle) {
                $w->where('users.name', 'like', $needle)
                  ->orWhere('users.username', 'like', $needle)
                  ->orWhere('users.email', 'like', $needle)
                  ->orWhere('users.national_id', 'like', $needle);
            });
        }

        if ($jobTitleId) {
            $q->where('users.job_title_id', $jobTitleId);
        }

        return $q;
    }

    /**
     * Lightweight per-school KPI counts for the admins index page.
     * (Admins card 55)
     */
    private function computeAdminStats(?int $schoolId): array
    {
        $endUserRoles = ['student', 'parent', 'teacher'];

        $base = User::query()
            ->whereHas('roles', fn ($r) => $r->whereNotIn('slug', $endUserRoles))
            ->whereDoesntHave('roles', fn ($r) => $r->whereIn('slug', $endUserRoles));

        if ($schoolId !== null) {
            $base->where(function ($w) use ($schoolId) {
                $w->where('users.school_id', $schoolId)
                  ->orWhereHas('roles', fn ($r) => $r->where('slug', 'super-admin'));
            });
        }

        $total = (clone $base)->count();
        $active = (clone $base)->where('users.is_active', true)->count();
        $withJob = (clone $base)->whereNotNull('users.job_title_id')->count();
        $super = (clone $base)->whereHas('roles', fn ($r) => $r->where('slug', 'super-admin'))->count();

        return compact('total', 'active', 'withJob', 'super');
    }

    public function create(Request $request): View
    {
        $jobTitles = JobTitle::query()->forSchool($this->activeSchoolId())->active()->orderBy('sort_order')->get();
        $selectedJobTitleId = (int) $request->query('job_title_id') ?: null;
        return view('admin.users.admins.create', compact('jobTitles', 'selectedJobTitleId'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateAdmin($request);
        $schoolId = $this->activeSchoolId();
        $photoPath = $request->hasFile('profile_picture')
            ? $request->file('profile_picture')->store('admins', 'public')
            : null;
        $name = $this->composeName($data);
        DB::transaction(function () use ($data, $schoolId, $photoPath, $name) {
            $plain = ($data['password'] ?? null) ?: ($data['national_id'] ?? str()->random(8));
            $user = User::create($this->withoutNulls(array_merge([
                'school_id' => $schoolId,
                'name' => $name,
                'name_ar' => $name,
                'username' => $data['username'],
                'email' => ($data['email'] ?? null) ?: ($data['username'].'@viewclass.local'),
                'national_id' => $data['national_id'] ?? null,
                'job_title_id' => $data['job_title_id'] ?? null,
                'gender' => $data['gender'] ?? null,
                'phone' => $data['phone'] ?? null,
                'profile_picture' => $photoPath,
                'password' => Hash::make($plain),
                'plain_password_for_card' => encrypt($plain),
                'is_active' => true,
                'status' => 'active',
            ], $this->profileAttributes($data))));
            $role = Role::where('slug', 'school-admin')->first();
            if ($role) {
                $user->roles()->syncWithoutDetaching($role);
            }
        });
        return redirect()->route('admin.users.admins.index')
            ->with('status', __('users.admin_created'));
    }

    public function edit(int $id): View|RedirectResponse
    {
        $admin = $this->admins->findScoped($id, $this->activeSchoolId());
        if (!$admin) {
            return redirect()->route('admin.users.admins.index')->with('error', __('users.not_found'));
        }
        $jobTitles = JobTitle::query()->forSchool($this->activeSchoolId())->active()->orderBy('sort_order')->get();
        $selectedJobTitleId = $admin->job_title_id;
        return view('admin.users.admins.edit', compact('admin', 'jobTitles', 'selectedJobTitleId'));
    }

    public function show(int $id): View|RedirectResponse
    {
        $admin = $this->admins->findScoped($id, $this->activeSchoolId());
        if (!$admin) {
            return redirect()->route('admin.users.admins.index')->with('error', __('users.not_found'));
        }
        $admin->loadMissing(['jobTitle', 'roles']);
        return view('admin.users.admins.show', compact('admin'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $admin = $this->admins->findScoped($id, $this->activeSchoolId());
        if (!$admin) {
            return redirect()->route('admin.users.admins.index')->with('error', __('users.not_found'));
        }
        $data = $this->validateAdmin($request, $id);
        $name = $this->composeName($data);
        $admin->fill(array_merge([
            'name' => $name,
            'name_ar' => $name,
            'username' => $data['username'],
            'email' => ($data['email'] ?? null) ?: ($data['username'].'@viewclass.local'),
            'national_id' => $data['national_id'] ?? null,
            'job_title_id' => $data['job_title_id'] ?? null,
            'gender' => $data['gender'] ?? null,
            'phone' => $data['phone'] ?? null,
        ], $this->profileAttributes($data)));
        if ($request->hasFile('profile_picture')) {
            $admin->profile_picture = $request->file('profile_picture')->store('admins', 'public');
        }
        if (!empty($data['password'])) {
            $admin->password = Hash::make($data['password']);
            $admin->plain_password_for_card = encrypt($data['password']);
        }
        $admin->save();
        return redirect()->route('admin.users.admins.index')
            ->with('status', __('users.admin_updated'));
    }

    public function destroy(int $id): RedirectResponse
    {
        $admin = $this->admins->findScoped($id, $this->activeSchoolId());
        if ($admin) {
            $admin->delete();
        }
        return redirect()->route('admin.users.admins.index')
            ->with('status', __('users.admin_deleted'));
    }

    public function supervisees(int $id): View|RedirectResponse
    {
        $admin = $this->admins->findScoped($id, $this->activeSchoolId());
        if (!$admin) {
            return redirect()->route('admin.users.admins.index')->with('error', __('users.not_found'));
        }
        $schoolId = $this->activeSchoolId();
        $jobSlug = optional($admin->jobTitle)->slug;
        $type = $jobSlug === 'counselor' ? 'student' : 'teacher';

        $candidates = User::query()
            ->whereHas('roles', fn ($r) => $r->where('slug', $type))
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->orderBy('name')
            ->get();

        $assigned = DB::table('admin_supervisees')
            ->where('admin_id', $admin->id)
            ->where('supervisee_type', $type)
            ->pluck('supervisee_id')
            ->all();

        return view('admin.users.admins.supervisees', compact('admin', 'candidates', 'assigned', 'type'));
    }

    public function syncSupervisees(Request $request, int $id): RedirectResponse
    {
        $admin = $this->admins->findScoped($id, $this->activeSchoolId());
        if (!$admin) {
            return redirect()->route('admin.users.admins.index')->with('error', __('users.not_found'));
        }
        $type = $request->input('type', 'teacher');
        $ids = collect($request->input('ids', []))->map(fn ($v) => (int) $v)->filter()->all();

        DB::transaction(function () use ($admin, $type, $ids) {
            DB::table('admin_supervisees')
                ->where('admin_id', $admin->id)
                ->where('supervisee_type', $type)
                ->delete();
            foreach ($ids as $sid) {
                DB::table('admin_supervisees')->insert([
                    'admin_id' => $admin->id,
                    'supervisee_type' => $type,
                    'supervisee_id' => $sid,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });
        return redirect()->route('admin.users.admins.supervisees', $admin->id)
            ->with('status', __('users.supervisees_synced'));
    }

    private function validateAdmin(Request $request, ?int $id = null): array
    {
        return $request->validate([
            'name' => 'nullable|string|max:255',
            'username' => 'required|string|max:64|unique:users,username'.($id ? ','.$id : ''),
            'email' => 'nullable|email|max:255|unique:users,email'.($id ? ','.$id : ''),
            'national_id' => 'nullable|string|max:32',
            'first_name' => 'nullable|string|max:64',
            'father_name' => 'nullable|string|max:64',
            'grandfather_name' => 'nullable|string|max:64',
            'family_name' => 'nullable|string|max:64',
            'name_en' => 'nullable|string|max:255',
            'date_of_birth' => 'nullable|date',
            'birth_place' => 'nullable|string|max:128',
            'nationality' => 'nullable|string|max:64',
            'address' => 'nullable|string|max:500',
            'job_title_id' => 'nullable|integer|exists:job_titles,id',
            'gender' => 'nullable|in:male,female',
            'phone' => 'nullable|string|max:32',
            'phone_secondary' => 'nullable|string|max:32',
            'whatsapp' => 'nullable|string|max:32',
            'password' => 'nullable|string|min:6|max:64',
            'profile_picture' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);
    }

    /**
     * Compose a full display name from the detailed name parts when the
     * single "name" field is left blank. Falls back to the given name.
     */
    private function composeName(array $data): string
    {
        $name = trim((string) ($data['name'] ?? ''));
        if ($name !== '') {
            return $name;
        }
        $parts = array_filter([
            $data['first_name'] ?? null,
            $data['father_name'] ?? null,
            $data['grandfather_name'] ?? null,
            $data['family_name'] ?? null,
        ], fn ($v) => trim((string) $v) !== '');
        $composed = trim(implode(' ', $parts));
        return $composed !== '' ? $composed : ($data['username'] ?? '');
    }

    /**
     * Build the shared profile attributes written on both store and update.
     */
    private function profileAttributes(array $data): array
    {
        return [
            'first_name' => $data['first_name'] ?? null,
            'father_name' => $data['father_name'] ?? null,
            'grandfather_name' => $data['grandfather_name'] ?? null,
            'family_name' => $data['family_name'] ?? null,
            'name_en' => $data['name_en'] ?? null,
            'date_of_birth' => $data['date_of_birth'] ?? null,
            'birth_place' => $data['birth_place'] ?? null,
            'nationality' => $data['nationality'] ?? null,
            'address' => $data['address'] ?? null,
            'phone_secondary' => $data['phone_secondary'] ?? null,
            'whatsapp' => $data['whatsapp'] ?? null,
        ];
    }
}
