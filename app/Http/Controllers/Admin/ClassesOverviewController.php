<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClassRoom;
use App\Models\School;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * صفوف وفصول — classes across ALL schools with company/complex/school columns
 * (card «الشركات والمجمعات التعليمية», Phase 5). Super-admin only.
 */
class ClassesOverviewController extends Controller
{
    public function index(Request $request): View
    {
        $schoolId = $request->integer('school_id') ?: null;

        $classes = ClassRoom::query()
            ->with(['section.school.educationalCompany', 'section.school.compounds'])
            ->withCount('students')
            ->when($schoolId, fn ($q) => $q->whereHas('section', fn ($s) => $s->where('school_id', $schoolId)))
            ->orderBy('id')
            ->paginate(20)
            ->withQueryString();

        $schools = School::orderBy('name_ar')->get(['id', 'name_ar', 'name_en']);

        return view('admin.classes-overview.index', compact('classes', 'schools', 'schoolId'));
    }
}
