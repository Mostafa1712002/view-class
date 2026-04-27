<?php

namespace App\Modules\Users\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use App\Modules\Users\Repositories\Contracts\ParentRepository;
use App\Modules\Users\Repositories\Contracts\StudentRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

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
        return view('admin.users.parents.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateParent($request);
        $schoolId = $this->activeSchoolId();
        DB::transaction(function () use ($data, $schoolId) {
            $plain = ($data['password'] ?? null) ?: ($data['national_id'] ?? str()->random(8));
            $user = User::create($this->withoutNulls([
                'school_id' => $schoolId,
                'name' => $data['name'],
                'name_ar' => $data['name'],
                'username' => $data['username'],
                'email' => ($data['email'] ?? null) ?: ($data['username'].'@viewclass.local'),
                'national_id' => $data['national_id'] ?? null,
                'gender' => $data['gender'] ?? null,
                'phone' => $data['phone'] ?? null,
                'password' => Hash::make($plain),
                'plain_password_for_card' => encrypt($plain),
                'is_active' => true,
                'status' => 'active',
            ]));
            $role = Role::where('slug', 'parent')->first();
            if ($role) {
                $user->roles()->syncWithoutDetaching($role);
            }
        });

        return redirect()->route('admin.users.parents.index')
            ->with('status', __('users.parent_created'));
    }

    public function edit(int $id): View|RedirectResponse
    {
        $parent = $this->parents->findScoped($id, $this->activeSchoolId());
        if (!$parent) {
            return redirect()->route('admin.users.parents.index')->with('error', __('users.not_found'));
        }
        return view('admin.users.parents.edit', compact('parent'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $parent = $this->parents->findScoped($id, $this->activeSchoolId());
        if (!$parent) {
            return redirect()->route('admin.users.parents.index')->with('error', __('users.not_found'));
        }
        $data = $this->validateParent($request, $id);
        $parent->fill([
            'name' => $data['name'],
            'name_ar' => $data['name'],
            'username' => $data['username'],
            'email' => ($data['email'] ?? null) ?: ($data['username'].'@viewclass.local'),
            'national_id' => $data['national_id'] ?? null,
            'gender' => $data['gender'] ?? null,
            'phone' => $data['phone'] ?? null,
        ]);
        if (!empty($data['password'])) {
            $parent->password = Hash::make($data['password']);
            $parent->plain_password_for_card = encrypt($data['password']);
        }
        $parent->save();
        return redirect()->route('admin.users.parents.index')
            ->with('status', __('users.parent_updated'));
    }

    public function destroy(int $id): RedirectResponse
    {
        $parent = $this->parents->findScoped($id, $this->activeSchoolId());
        if ($parent) {
            $parent->delete();
        }
        return redirect()->route('admin.users.parents.index')
            ->with('status', __('users.parent_deleted'));
    }

    public function students(int $id): View|RedirectResponse
    {
        $parent = $this->parents->findScoped($id, $this->activeSchoolId());
        if (!$parent) {
            return redirect()->route('admin.users.parents.index')->with('error', __('users.not_found'));
        }
        $linked = $parent->children()->get();
        $available = $this->students->paginate($this->activeSchoolId(), null, 50);
        return view('admin.users.parents.students', compact('parent', 'linked', 'available'));
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

    private function validateParent(Request $request, ?int $id = null): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:64|unique:users,username'.($id ? ','.$id : ''),
            'email' => 'nullable|email|max:255|unique:users,email'.($id ? ','.$id : ''),
            'national_id' => 'nullable|string|max:32',
            'gender' => 'nullable|in:male,female',
            'phone' => 'nullable|string|max:32',
            'password' => 'nullable|string|min:6|max:64',
        ]);
    }
}
