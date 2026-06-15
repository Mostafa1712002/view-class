<?php

namespace App\Modules\QuestionBankCore\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\School;
use App\Modules\QuestionBankCore\Models\Compound;
use App\Modules\QuestionBankCore\Repositories\Contracts\CompoundRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * #248 — Compounds (المجمعات) admin CRUD. A compound groups schools; it keys off
 * educational_company_id and has NO school_id, so school scoping does not apply.
 * The route group restricts management to super-admin (compounds span schools).
 * Reads gated by canDo('compounds.view'); writes by compounds.create/edit/delete.
 */
class CompoundController extends Controller
{
    public function __construct(private CompoundRepository $compounds) {}

    public function index(Request $request): View
    {
        abort_unless(auth()->user()->canDo('compounds.view'), 403);

        $filters = [
            'q'      => trim((string) $request->get('q', '')),
            'status' => $request->get('status'),
        ];

        return view('admin.qb.taxonomy.compounds.index', [
            'compounds' => $this->compounds->paginateForAdmin($filters),
            'filters'   => $filters,
        ]);
    }

    public function create(): View
    {
        abort_unless(auth()->user()->canDo('compounds.create'), 403);

        return view('admin.qb.taxonomy.compounds.create', [
            'compound' => new Compound(['status' => 'active', 'sort_order' => 0]),
            'schools'  => $this->schools(),
            'selectedSchools' => [],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()->canDo('compounds.create'), 403);

        $data = $this->validateCompound($request);
        $compound = $this->compounds->create([
            'name_ar'    => $data['name_ar'],
            'name_en'    => $data['name_en'] ?? null,
            'status'     => $data['status'],
            'sort_order' => $data['sort_order'] ?? 0,
        ]);
        $this->compounds->syncSchools($compound, $data['schools'] ?? []);
        ActivityLog::log('compounds.create', "إضافة مجمع: {$compound->name_ar} (#{$compound->id})", $compound);

        return redirect()->route('admin.qb.compounds.index')->with('success', 'تمت إضافة المجمع.');
    }

    public function edit(int $compoundId): View
    {
        abort_unless(auth()->user()->canDo('compounds.edit'), 403);

        $compound = $this->compounds->find($compoundId);
        abort_if(! $compound, 404);

        return view('admin.qb.taxonomy.compounds.edit', [
            'compound'        => $compound,
            'schools'         => $this->schools(),
            'selectedSchools' => $compound->schools->pluck('id')->all(),
        ]);
    }

    public function update(Request $request, int $compoundId): RedirectResponse
    {
        abort_unless(auth()->user()->canDo('compounds.edit'), 403);

        $compound = $this->compounds->find($compoundId);
        abort_if(! $compound, 404);

        $data = $this->validateCompound($request);
        $this->compounds->update($compound, [
            'name_ar'    => $data['name_ar'],
            'name_en'    => $data['name_en'] ?? null,
            'status'     => $data['status'],
            'sort_order' => $data['sort_order'] ?? 0,
        ]);
        $this->compounds->syncSchools($compound, $data['schools'] ?? []);
        ActivityLog::log('compounds.edit', "تعديل مجمع (#{$compound->id})", $compound);

        return redirect()->route('admin.qb.compounds.index')->with('success', 'تم تحديث المجمع.');
    }

    public function destroy(int $compoundId): RedirectResponse
    {
        abort_unless(auth()->user()->canDo('compounds.delete'), 403);

        $compound = $this->compounds->find($compoundId);
        abort_if(! $compound, 404);

        $this->compounds->syncSchools($compound, []);
        $this->compounds->delete($compound);
        ActivityLog::log('compounds.delete', "حذف مجمع (#{$compound->id})", $compound);

        return redirect()->route('admin.qb.compounds.index')->with('success', 'تم حذف المجمع.');
    }

    private function validateCompound(Request $request): array
    {
        return $request->validate([
            'name_ar'    => ['required', 'string', 'max:255'],
            'name_en'    => ['nullable', 'string', 'max:255'],
            'status'     => ['required', 'in:active,inactive'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'schools'    => ['nullable', 'array'],
            'schools.*'  => ['integer', 'exists:schools,id'],
        ]);
    }

    private function schools()
    {
        return School::query()->orderBy('name')->get(['id', 'name', 'name_ar']);
    }
}
