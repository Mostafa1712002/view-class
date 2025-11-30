<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\ClassRoom;
use App\Models\Section;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClassController extends Controller
{
    public function index()
    {
        $query = ClassRoom::with(['section.school', 'academicYear']);

        if (!Auth::user()->isSuperAdmin()) {
            $query->whereHas('section', function ($q) {
                $q->where('school_id', Auth::user()->school_id);
            });
        }

        $classes = $query->paginate(15);
        return view('admin.classes.index', compact('classes'));
    }

    public function create()
    {
        $sections = $this->getSections();
        $academicYears = $this->getAcademicYears();

        return view('admin.classes.create', compact('sections', 'academicYears'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'section_id' => 'required|exists:sections,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'grade_level' => 'required|integer|min:1|max:12',
            'division' => 'required|string|max:10',
            'capacity' => 'required|integer|min:1|max:100',
            'room' => 'nullable|string|max:50',
        ], [
            'name.required' => 'اسم الفصل مطلوب',
            'section_id.required' => 'القسم مطلوب',
            'section_id.exists' => 'القسم غير موجود',
            'academic_year_id.required' => 'السنة الدراسية مطلوبة',
            'grade_level.required' => 'الصف مطلوب',
            'division.required' => 'الشعبة مطلوبة',
            'capacity.required' => 'السعة مطلوبة',
        ]);

        $this->authorizeSection($validated['section_id']);

        ClassRoom::create($validated);

        return redirect()->route('manage.classes.index')
            ->with('success', 'تم إضافة الفصل بنجاح');
    }

    public function show(ClassRoom $class)
    {
        $this->authorizeAccess($class);
        $class->load(['section.school', 'academicYear', 'students']);
        return view('admin.classes.show', compact('class'));
    }

    public function edit(ClassRoom $class)
    {
        $this->authorizeAccess($class);

        $sections = $this->getSections();
        $academicYears = $this->getAcademicYears();

        return view('admin.classes.edit', compact('class', 'sections', 'academicYears'));
    }

    public function update(Request $request, ClassRoom $class)
    {
        $this->authorizeAccess($class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'section_id' => 'required|exists:sections,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'grade_level' => 'required|integer|min:1|max:12',
            'division' => 'required|string|max:10',
            'capacity' => 'required|integer|min:1|max:100',
            'room' => 'nullable|string|max:50',
            'is_active' => 'boolean',
        ], [
            'name.required' => 'اسم الفصل مطلوب',
            'section_id.required' => 'القسم مطلوب',
            'academic_year_id.required' => 'السنة الدراسية مطلوبة',
        ]);

        $this->authorizeSection($validated['section_id']);

        $class->update($validated);

        return redirect()->route('manage.classes.index')
            ->with('success', 'تم تحديث الفصل بنجاح');
    }

    public function destroy(ClassRoom $class)
    {
        $this->authorizeAccess($class);

        if ($class->students()->count() > 0) {
            return back()->with('error', 'لا يمكن حذف الفصل لوجود طلاب مسجلين فيه');
        }

        $class->delete();

        return redirect()->route('manage.classes.index')
            ->with('success', 'تم حذف الفصل بنجاح');
    }

    private function getSections()
    {
        if (Auth::user()->isSuperAdmin()) {
            return Section::with('school')->where('is_active', true)->get();
        }

        return Section::where('school_id', Auth::user()->school_id)
            ->where('is_active', true)->get();
    }

    private function getAcademicYears()
    {
        if (Auth::user()->isSuperAdmin()) {
            return AcademicYear::with('school')->get();
        }

        return AcademicYear::where('school_id', Auth::user()->school_id)->get();
    }

    private function authorizeAccess(ClassRoom $class): void
    {
        if (!Auth::user()->isSuperAdmin() && $class->section->school_id !== Auth::user()->school_id) {
            abort(403, 'غير مصرح لك بالوصول إلى هذا الفصل');
        }
    }

    private function authorizeSection(int $sectionId): void
    {
        if (!Auth::user()->isSuperAdmin()) {
            $section = Section::findOrFail($sectionId);
            if ($section->school_id !== Auth::user()->school_id) {
                abort(403, 'غير مصرح لك بالوصول إلى هذا القسم');
            }
        }
    }
}
