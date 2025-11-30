<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\School;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AcademicYearController extends Controller
{
    public function index()
    {
        $query = AcademicYear::with('school')->withCount('classes');

        if (!Auth::user()->isSuperAdmin()) {
            $query->where('school_id', Auth::user()->school_id);
        }

        $academicYears = $query->orderBy('start_date', 'desc')->paginate(15);
        return view('admin.academic-years.index', compact('academicYears'));
    }

    public function create()
    {
        $schools = $this->getSchools();
        return view('admin.academic-years.create', compact('schools'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'school_id' => 'required|exists:schools,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'is_current' => 'boolean',
        ], [
            'name.required' => 'اسم السنة الدراسية مطلوب',
            'school_id.required' => 'المدرسة مطلوبة',
            'start_date.required' => 'تاريخ البداية مطلوب',
            'end_date.required' => 'تاريخ النهاية مطلوب',
            'end_date.after' => 'تاريخ النهاية يجب أن يكون بعد تاريخ البداية',
        ]);

        if (!Auth::user()->isSuperAdmin()) {
            $validated['school_id'] = Auth::user()->school_id;
        }

        $academicYear = AcademicYear::create($validated);

        if ($request->boolean('is_current')) {
            $academicYear->setAsCurrent();
        }

        return redirect()->route('manage.academic-years.index')
            ->with('success', 'تم إضافة السنة الدراسية بنجاح');
    }

    public function show(AcademicYear $academicYear)
    {
        $this->authorizeAccess($academicYear);
        $academicYear->load(['school', 'classes']);
        return view('admin.academic-years.show', compact('academicYear'));
    }

    public function edit(AcademicYear $academicYear)
    {
        $this->authorizeAccess($academicYear);
        $schools = $this->getSchools();
        return view('admin.academic-years.edit', compact('academicYear', 'schools'));
    }

    public function update(Request $request, AcademicYear $academicYear)
    {
        $this->authorizeAccess($academicYear);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'school_id' => 'required|exists:schools,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'is_current' => 'boolean',
        ], [
            'name.required' => 'اسم السنة الدراسية مطلوب',
            'start_date.required' => 'تاريخ البداية مطلوب',
            'end_date.required' => 'تاريخ النهاية مطلوب',
            'end_date.after' => 'تاريخ النهاية يجب أن يكون بعد تاريخ البداية',
        ]);

        if (!Auth::user()->isSuperAdmin()) {
            $validated['school_id'] = Auth::user()->school_id;
        }

        $academicYear->update($validated);

        if ($request->boolean('is_current')) {
            $academicYear->setAsCurrent();
        }

        return redirect()->route('manage.academic-years.index')
            ->with('success', 'تم تحديث السنة الدراسية بنجاح');
    }

    public function destroy(AcademicYear $academicYear)
    {
        $this->authorizeAccess($academicYear);

        if ($academicYear->classes()->count() > 0) {
            return back()->with('error', 'لا يمكن حذف السنة الدراسية لوجود فصول مرتبطة بها');
        }

        $academicYear->delete();

        return redirect()->route('manage.academic-years.index')
            ->with('success', 'تم حذف السنة الدراسية بنجاح');
    }

    private function getSchools()
    {
        if (Auth::user()->isSuperAdmin()) {
            return School::where('is_active', true)->get();
        }
        return collect([Auth::user()->school]);
    }

    private function authorizeAccess(AcademicYear $academicYear): void
    {
        if (!Auth::user()->isSuperAdmin() && $academicYear->school_id !== Auth::user()->school_id) {
            abort(403, 'غير مصرح لك بالوصول إلى هذه السنة الدراسية');
        }
    }
}
