<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClassRoom;
use App\Models\EducationalCompany;
use App\Models\School;
use App\Models\Section;
use App\Modules\QuestionBankCore\Models\Compound;
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
        $companyId = $request->integer('company_id') ?: null;
        $complexId = $request->integer('complex_id') ?: null;
        $grade = trim((string) $request->input('grade')) ?: null;

        $schoolFilter = function ($q) use ($schoolId, $companyId, $complexId) {
            $q->when($schoolId, fn ($q) => $q->where('schools.id', $schoolId))
                ->when($companyId, fn ($q) => $q->where('schools.educational_company_id', $companyId))
                ->when($complexId, fn ($q) => $q->whereHas('compounds', fn ($c) => $c->where('compounds.id', $complexId)));
        };

        $classes = ClassRoom::query()
            ->with(['section.school.educationalCompany', 'section.school.compounds'])
            ->withCount('students')
            ->whereHas('section', function ($s) use ($schoolFilter, $grade, $schoolId, $companyId, $complexId) {
                $s->when($grade, fn ($q) => $q->where('name', $grade))
                    ->when($schoolId || $companyId || $complexId, fn ($q) => $q->whereHas('school', $schoolFilter));
            })
            ->orderBy('id')
            ->paginate(20)
            ->withQueryString();

        $schools = School::when($companyId, fn ($q) => $q->where('educational_company_id', $companyId))
            ->when($complexId, fn ($q) => $q->whereHas('compounds', fn ($c) => $c->where('compounds.id', $complexId)))
            ->orderBy('name_ar')->get(['id', 'name_ar', 'name_en']);
        $companies = EducationalCompany::orderBy('name_ar')->get(['id', 'name_ar', 'name_en']);
        $complexes = Compound::when($companyId, fn ($q) => $q->whereHas('schools', fn ($s) => $s->where('educational_company_id', $companyId)))
            ->orderBy('name_ar')->get(['id', 'name_ar', 'name_en']);
        $grades = Section::when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->whereHas('school', $schoolFilter)
            ->distinct()->orderBy('name')->pluck('name');

        return view('admin.classes-overview.index', compact('classes', 'schools', 'companies', 'complexes', 'grades', 'schoolId', 'companyId', 'complexId', 'grade'));
    }
}
