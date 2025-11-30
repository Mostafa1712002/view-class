<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\School;
use App\Models\Section;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with(['school', 'section', 'roles']);

        if (!Auth::user()->isSuperAdmin()) {
            $query->where('school_id', Auth::user()->school_id);
        }

        if ($request->filled('role')) {
            $query->whereHas('roles', fn($q) => $q->where('slug', $request->role));
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('employee_id', 'like', "%{$search}%");
            });
        }

        $users = $query->paginate(15);
        $roles = Role::where('is_active', true)->get();

        return view('admin.users.index', compact('users', 'roles'));
    }

    public function create()
    {
        $schools = $this->getSchools();
        $sections = $this->getSections();
        $roles = $this->getRoles();

        return view('admin.users.create', compact('schools', 'sections', 'roles'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users',
            'password' => ['required', 'confirmed', Password::defaults()],
            'school_id' => 'nullable|exists:schools,id',
            'section_id' => 'nullable|exists:sections,id',
            'employee_id' => 'nullable|string|max:50',
            'national_id' => 'nullable|string|max:20',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'gender' => 'nullable|in:male,female',
            'birth_date' => 'nullable|date',
            'specialization' => 'nullable|string|max:255',
            'qualification' => 'nullable|string|max:255',
            'hire_date' => 'nullable|date',
            'roles' => 'required|array|min:1',
            'roles.*' => 'exists:roles,id',
        ], [
            'name.required' => 'الاسم مطلوب',
            'email.required' => 'البريد الإلكتروني مطلوب',
            'email.unique' => 'البريد الإلكتروني مستخدم مسبقاً',
            'password.required' => 'كلمة المرور مطلوبة',
            'password.confirmed' => 'كلمة المرور غير متطابقة',
            'roles.required' => 'يجب اختيار دور واحد على الأقل',
        ]);

        if (!Auth::user()->isSuperAdmin()) {
            $validated['school_id'] = Auth::user()->school_id;
        }

        $validated['password'] = Hash::make($validated['password']);

        $user = User::create(collect($validated)->except('roles')->toArray());
        $user->roles()->sync($validated['roles']);

        return redirect()->route('manage.users.index')
            ->with('success', 'تم إضافة المستخدم بنجاح');
    }

    public function show(User $user)
    {
        $this->authorizeAccess($user);
        $user->load(['school', 'section', 'roles', 'subjects']);
        return view('admin.users.show', compact('user'));
    }

    public function edit(User $user)
    {
        $this->authorizeAccess($user);

        $schools = $this->getSchools();
        $sections = $this->getSections();
        $roles = $this->getRoles();

        return view('admin.users.edit', compact('user', 'schools', 'sections', 'roles'));
    }

    public function update(Request $request, User $user)
    {
        $this->authorizeAccess($user);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'school_id' => 'nullable|exists:schools,id',
            'section_id' => 'nullable|exists:sections,id',
            'employee_id' => 'nullable|string|max:50',
            'national_id' => 'nullable|string|max:20',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'gender' => 'nullable|in:male,female',
            'birth_date' => 'nullable|date',
            'specialization' => 'nullable|string|max:255',
            'qualification' => 'nullable|string|max:255',
            'hire_date' => 'nullable|date',
            'is_active' => 'boolean',
            'roles' => 'required|array|min:1',
            'roles.*' => 'exists:roles,id',
        ], [
            'name.required' => 'الاسم مطلوب',
            'email.required' => 'البريد الإلكتروني مطلوب',
            'email.unique' => 'البريد الإلكتروني مستخدم مسبقاً',
            'roles.required' => 'يجب اختيار دور واحد على الأقل',
        ]);

        if (!Auth::user()->isSuperAdmin()) {
            $validated['school_id'] = Auth::user()->school_id;
        }

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update(collect($validated)->except('roles')->toArray());
        $user->roles()->sync($validated['roles']);

        return redirect()->route('manage.users.index')
            ->with('success', 'تم تحديث المستخدم بنجاح');
    }

    public function destroy(User $user)
    {
        $this->authorizeAccess($user);

        if ($user->id === Auth::id()) {
            return back()->with('error', 'لا يمكنك حذف حسابك الشخصي');
        }

        $user->delete();

        return redirect()->route('manage.users.index')
            ->with('success', 'تم حذف المستخدم بنجاح');
    }

    private function getSchools()
    {
        if (Auth::user()->isSuperAdmin()) {
            return School::where('is_active', true)->get();
        }
        return collect([Auth::user()->school]);
    }

    private function getSections()
    {
        if (Auth::user()->isSuperAdmin()) {
            return Section::with('school')->where('is_active', true)->get();
        }
        return Section::where('school_id', Auth::user()->school_id)
            ->where('is_active', true)->get();
    }

    private function getRoles()
    {
        $query = Role::where('is_active', true);

        if (!Auth::user()->isSuperAdmin()) {
            $query->where('slug', '!=', 'super-admin');
        }

        return $query->get();
    }

    private function authorizeAccess(User $user): void
    {
        if (!Auth::user()->isSuperAdmin() && $user->school_id !== Auth::user()->school_id) {
            abort(403, 'غير مصرح لك بالوصول إلى هذا المستخدم');
        }
    }
}
