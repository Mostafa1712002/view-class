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
        $admins = $this->admins->paginate(
            $this->activeSchoolId(),
            $request->string('q')->toString() ?: null,
        );
        $jobTitles = JobTitle::query()->forSchool($this->activeSchoolId())->active()->orderBy('sort_order')->get();
        return view('admin.users.admins.index', [
            'admins' => $admins,
            'jobTitles' => $jobTitles,
            'q' => $request->string('q')->toString(),
        ]);
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
        DB::transaction(function () use ($data, $schoolId) {
            $plain = ($data['password'] ?? null) ?: ($data['national_id'] ?? str()->random(8));
            $user = User::create([
                'school_id' => $schoolId,
                'name' => $data['name'],
                'name_ar' => $data['name'],
                'username' => $data['username'],
                'email' => $data['email'] ?: null,
                'national_id' => $data['national_id'] ?? null,
                'job_title_id' => $data['job_title_id'] ?? null,
                'gender' => $data['gender'] ?? null,
                'phone' => $data['phone'] ?? null,
                'password' => Hash::make($plain),
                'plain_password_for_card' => encrypt($plain),
                'is_active' => true,
                'status' => 'active',
            ]);
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
        return view('admin.users.admins.edit', compact('admin', 'jobTitles'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $admin = $this->admins->findScoped($id, $this->activeSchoolId());
        if (!$admin) {
            return redirect()->route('admin.users.admins.index')->with('error', __('users.not_found'));
        }
        $data = $this->validateAdmin($request, $id);
        $admin->fill([
            'name' => $data['name'],
            'name_ar' => $data['name'],
            'username' => $data['username'],
            'email' => $data['email'] ?: null,
            'national_id' => $data['national_id'] ?? null,
            'job_title_id' => $data['job_title_id'] ?? null,
            'gender' => $data['gender'] ?? null,
            'phone' => $data['phone'] ?? null,
        ]);
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
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:64|unique:users,username'.($id ? ','.$id : ''),
            'email' => 'nullable|email|max:255|unique:users,email'.($id ? ','.$id : ''),
            'national_id' => 'nullable|string|max:32',
            'job_title_id' => 'nullable|integer|exists:job_titles,id',
            'gender' => 'nullable|in:male,female',
            'phone' => 'nullable|string|max:32',
            'password' => 'nullable|string|min:6|max:64',
        ]);
    }
}
