<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EducationalCompany;
use App\Modules\QuestionBankCore\Models\Compound;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * المجمعات التعليمية — admin CRUD (super-admin only; card «الشركات والمجمعات
 * التعليمية»). Operates on the existing Compound model/table (do not fork the
 * data model — the legacy admin.qb.compounds.* CRUD keeps working unchanged).
 */
class EducationalComplexController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->get('q', ''));
        $companyId = $request->get('company');

        $complexes = Compound::query()
            ->with('company:id,name_ar,name_en')
            ->withCount('schools')
            ->when($q !== '', fn ($query) => $query->where(fn ($w) => $w
                ->where('name_ar', 'like', "%{$q}%")
                ->orWhere('name_en', 'like', "%{$q}%")))
            ->when($companyId, fn ($query) => $query->where('educational_company_id', $companyId))
            ->orderBy('sort_order')
            ->orderBy('name_ar')
            ->paginate(20)
            ->withQueryString();

        $companies = EducationalCompany::orderBy('name_ar')->get(['id', 'name_ar', 'name_en']);

        return view('admin.educational-complexes.index', [
            'complexes' => $complexes,
            'companies' => $companies,
            'q' => $q,
            'companyId' => $companyId,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateComplex($request);

        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo')->store('educational-complexes', 'public');
        }

        Compound::create($data);

        return redirect()->route('admin.educational-complexes.index')->with('success', __('common.created_successfully'));
    }

    public function update(Request $request, Compound $complex): RedirectResponse
    {
        $data = $this->validateComplex($request);

        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo')->store('educational-complexes', 'public');
        }

        $complex->update($data);

        return redirect()->route('admin.educational-complexes.index')->with('success', __('common.updated_successfully'));
    }

    public function destroy(Compound $complex): RedirectResponse
    {
        $complex->schools()->detach();
        $complex->delete();

        return redirect()->route('admin.educational-complexes.index')->with('success', __('common.deleted_successfully'));
    }

    /** AJAX cascade: complexes belonging to a given company (schools form + header scope). */
    public function byCompany(Request $request): JsonResponse
    {
        $companyId = $request->integer('company_id') ?: null;

        if (! $companyId) {
            return response()->json([]);
        }

        $complexes = Compound::query()
            ->where('educational_company_id', $companyId)
            ->orderBy('sort_order')
            ->orderBy('name_ar')
            ->get(['id', 'name_ar', 'name_en'])
            ->map(fn (Compound $c) => ['id' => $c->id, 'name' => $c->display_name])
            ->values();

        return response()->json($complexes);
    }

    private function validateComplex(Request $request): array
    {
        return $request->validate([
            'name_ar' => 'required|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'logo' => 'nullable|image|max:2048',
            'educational_company_id' => 'nullable|exists:educational_companies,id',
        ]);
    }
}
