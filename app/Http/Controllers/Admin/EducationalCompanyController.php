<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EducationalCompany;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * الشركات التعليمية — admin CRUD (super-admin only; card «الشركات والمجمعات
 * التعليمية»). Operates on the existing EducationalCompany model/table.
 */
class EducationalCompanyController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->get('q', ''));

        $companies = EducationalCompany::query()
            ->withCount(['compounds', 'schools'])
            ->when($q !== '', fn ($query) => $query->where(fn ($w) => $w
                ->where('name_ar', 'like', "%{$q}%")
                ->orWhere('name_en', 'like', "%{$q}%")))
            ->orderBy('name_ar')
            ->paginate(20)
            ->withQueryString();

        return view('admin.educational-companies.index', [
            'companies' => $companies,
            'q' => $q,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateCompany($request);

        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo')->store('educational-companies', 'public');
        }

        EducationalCompany::create($data);

        return redirect()->route('admin.educational-companies.index')->with('success', __('common.created_successfully'));
    }

    public function update(Request $request, EducationalCompany $company): RedirectResponse
    {
        $data = $this->validateCompany($request);

        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo')->store('educational-companies', 'public');
        }

        $company->update($data);

        return redirect()->route('admin.educational-companies.index')->with('success', __('common.updated_successfully'));
    }

    public function destroy(EducationalCompany $company): RedirectResponse
    {
        if ($company->schools()->count() > 0 || $company->compounds()->count() > 0) {
            return back()->with('error', __('educational_companies.cannot_delete_in_use'));
        }

        $company->delete();

        return redirect()->route('admin.educational-companies.index')->with('success', __('common.deleted_successfully'));
    }

    /** زر «عرض» — ينقل لصفحة المجمعات مفلترة على هذه الشركة. */
    public function show(EducationalCompany $company): RedirectResponse
    {
        return redirect()->route('admin.educational-complexes.index', ['company' => $company->id]);
    }

    private function validateCompany(Request $request): array
    {
        $data = $request->validate([
            'name_ar' => 'required|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'logo' => 'nullable|image|max:2048',
        ]);

        // educational_companies.name_en has no NOT NULL default at the DB level
        // (unlike compounds.name_en, which is nullable) — the card only asks for
        // one name field, so fall back to the Arabic name rather than migrating
        // the column.
        $data['name_en'] = $data['name_en'] ?: $data['name_ar'];
        $data['status'] = 'active';

        return $data;
    }
}
