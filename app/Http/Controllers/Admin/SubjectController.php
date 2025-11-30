<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SubjectController extends Controller
{
    public function index()
    {
        $query = Subject::with('school')->withCount('teachers');

        if (!Auth::user()->isSuperAdmin()) {
            $query->where('school_id', Auth::user()->school_id);
        }

        $subjects = $query->paginate(15);
        return view('admin.subjects.index', compact('subjects'));
    }

    public function create()
    {
        $schools = $this->getSchools();
        $teachers = $this->getTeachers();

        return view('admin.subjects.create', compact('schools', 'teachers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50',
            'school_id' => 'required|exists:schools,id',
            'description' => 'nullable|string|max:500',
            'is_core' => 'boolean',
            'grade_levels' => 'nullable|array',
            'grade_levels.*' => 'integer|min:1|max:12',
            'teachers' => 'nullable|array',
            'teachers.*' => 'exists:users,id',
        ], [
            'name.required' => 'اسم المادة مطلوب',
            'code.required' => 'رمز المادة مطلوب',
            'school_id.required' => 'المدرسة مطلوبة',
        ]);

        if (!Auth::user()->isSuperAdmin()) {
            $validated['school_id'] = Auth::user()->school_id;
        }

        $subject = Subject::create(collect($validated)->except('teachers')->toArray());

        if (!empty($validated['teachers'])) {
            $subject->teachers()->sync($validated['teachers']);
        }

        return redirect()->route('manage.subjects.index')
            ->with('success', 'تم إضافة المادة بنجاح');
    }

    public function show(Subject $subject)
    {
        $this->authorizeAccess($subject);
        $subject->load(['school', 'teachers']);
        return view('admin.subjects.show', compact('subject'));
    }

    public function edit(Subject $subject)
    {
        $this->authorizeAccess($subject);

        $schools = $this->getSchools();
        $teachers = $this->getTeachers();

        return view('admin.subjects.edit', compact('subject', 'schools', 'teachers'));
    }

    public function update(Request $request, Subject $subject)
    {
        $this->authorizeAccess($subject);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50',
            'school_id' => 'required|exists:schools,id',
            'description' => 'nullable|string|max:500',
            'is_core' => 'boolean',
            'is_active' => 'boolean',
            'grade_levels' => 'nullable|array',
            'grade_levels.*' => 'integer|min:1|max:12',
            'teachers' => 'nullable|array',
            'teachers.*' => 'exists:users,id',
        ], [
            'name.required' => 'اسم المادة مطلوب',
            'code.required' => 'رمز المادة مطلوب',
        ]);

        if (!Auth::user()->isSuperAdmin()) {
            $validated['school_id'] = Auth::user()->school_id;
        }

        $subject->update(collect($validated)->except('teachers')->toArray());
        $subject->teachers()->sync($validated['teachers'] ?? []);

        return redirect()->route('manage.subjects.index')
            ->with('success', 'تم تحديث المادة بنجاح');
    }

    public function destroy(Subject $subject)
    {
        $this->authorizeAccess($subject);

        $subject->teachers()->detach();
        $subject->delete();

        return redirect()->route('manage.subjects.index')
            ->with('success', 'تم حذف المادة بنجاح');
    }

    private function getSchools()
    {
        if (Auth::user()->isSuperAdmin()) {
            return School::where('is_active', true)->get();
        }
        return collect([Auth::user()->school]);
    }

    private function getTeachers()
    {
        $query = User::whereHas('roles', fn($q) => $q->where('slug', 'teacher'));

        if (!Auth::user()->isSuperAdmin()) {
            $query->where('school_id', Auth::user()->school_id);
        }

        return $query->get();
    }

    private function authorizeAccess(Subject $subject): void
    {
        if (!Auth::user()->isSuperAdmin() && $subject->school_id !== Auth::user()->school_id) {
            abort(403, 'غير مصرح لك بالوصول إلى هذه المادة');
        }
    }
}
