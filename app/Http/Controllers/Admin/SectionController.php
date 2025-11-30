<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\Section;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SectionController extends Controller
{
    public function index()
    {
        $query = Section::with('school')->withCount('classes');

        if (!Auth::user()->isSuperAdmin()) {
            $query->where('school_id', Auth::user()->school_id);
        }

        $sections = $query->paginate(15);
        return view('admin.sections.index', compact('sections'));
    }

    public function create()
    {
        $schools = Auth::user()->isSuperAdmin()
            ? School::where('is_active', true)->get()
            : collect([Auth::user()->school]);

        return view('admin.sections.create', compact('schools'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'school_id' => 'required|exists:schools,id',
            'gender' => 'required|in:male,female',
            'level' => 'required|in:primary,intermediate,secondary',
            'description' => 'nullable|string|max:500',
        ], [
            'name.required' => 'اسم القسم مطلوب',
            'school_id.required' => 'المدرسة مطلوبة',
            'school_id.exists' => 'المدرسة غير موجودة',
            'gender.required' => 'الجنس مطلوب',
            'gender.in' => 'الجنس غير صحيح',
            'level.required' => 'المرحلة مطلوبة',
            'level.in' => 'المرحلة غير صحيحة',
        ]);

        if (!Auth::user()->isSuperAdmin()) {
            $validated['school_id'] = Auth::user()->school_id;
        }

        Section::create($validated);

        return redirect()->route('manage.sections.index')
            ->with('success', 'تم إضافة القسم بنجاح');
    }

    public function show(Section $section)
    {
        $this->authorizeAccess($section);
        $section->load(['school', 'classes']);
        return view('admin.sections.show', compact('section'));
    }

    public function edit(Section $section)
    {
        $this->authorizeAccess($section);

        $schools = Auth::user()->isSuperAdmin()
            ? School::where('is_active', true)->get()
            : collect([Auth::user()->school]);

        return view('admin.sections.edit', compact('section', 'schools'));
    }

    public function update(Request $request, Section $section)
    {
        $this->authorizeAccess($section);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'school_id' => 'required|exists:schools,id',
            'gender' => 'required|in:male,female',
            'level' => 'required|in:primary,intermediate,secondary',
            'description' => 'nullable|string|max:500',
            'is_active' => 'boolean',
        ], [
            'name.required' => 'اسم القسم مطلوب',
            'school_id.required' => 'المدرسة مطلوبة',
            'gender.required' => 'الجنس مطلوب',
            'level.required' => 'المرحلة مطلوبة',
        ]);

        if (!Auth::user()->isSuperAdmin()) {
            $validated['school_id'] = Auth::user()->school_id;
        }

        $section->update($validated);

        return redirect()->route('manage.sections.index')
            ->with('success', 'تم تحديث القسم بنجاح');
    }

    public function destroy(Section $section)
    {
        $this->authorizeAccess($section);

        if ($section->classes()->count() > 0) {
            return back()->with('error', 'لا يمكن حذف القسم لوجود فصول مرتبطة به');
        }

        $section->delete();

        return redirect()->route('manage.sections.index')
            ->with('success', 'تم حذف القسم بنجاح');
    }

    private function authorizeAccess(Section $section): void
    {
        if (!Auth::user()->isSuperAdmin() && $section->school_id !== Auth::user()->school_id) {
            abort(403, 'غير مصرح لك بالوصول إلى هذا القسم');
        }
    }
}
