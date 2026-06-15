<?php

namespace App\Modules\QuestionBankCore\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Subject;
use App\Modules\QuestionBankCore\Models\Standard;
use App\Modules\QuestionBankCore\Repositories\Contracts\StandardRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * #248 — Standards (المعايير) admin CRUD. Standards are GLOBAL reference data with
 * NO school_id column, so school scoping does not apply; the route group restricts
 * management to super-admin (a school-admin editing shared cross-tenant standards
 * would mutate every school's data). Reads still gated by canDo('standards.view'),
 * writes by standards.create/edit/delete.
 */
class StandardController extends Controller
{
    public function __construct(private StandardRepository $standards) {}

    public function index(Request $request): View
    {
        abort_unless(auth()->user()->canDo('standards.view'), 403);

        $filters = [
            'q'          => trim((string) $request->get('q', '')),
            'subject_id' => $request->get('subject_id'),
            'status'     => $request->get('status'),
        ];

        return view('admin.qb.taxonomy.standards.index', [
            'standards' => $this->standards->paginateForAdmin($filters),
            'filters'   => $filters,
            'subjects'  => $this->subjects(),
        ]);
    }

    public function create(): View
    {
        abort_unless(auth()->user()->canDo('standards.create'), 403);

        return view('admin.qb.taxonomy.standards.create', [
            'standard' => new Standard(['status' => 'active', 'sort_order' => 0]),
            'subjects' => $this->subjects(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()->canDo('standards.create'), 403);

        $standard = $this->standards->create($this->validateStandard($request));
        ActivityLog::log('standards.create', "إضافة معيار: {$standard->name} (#{$standard->id})", $standard);

        return redirect()->route('admin.qb.standards.index')->with('success', 'تمت إضافة المعيار.');
    }

    public function edit(int $standardId): View
    {
        abort_unless(auth()->user()->canDo('standards.edit'), 403);

        $standard = $this->standards->find($standardId);
        abort_if(! $standard, 404);

        return view('admin.qb.taxonomy.standards.edit', [
            'standard' => $standard,
            'subjects' => $this->subjects(),
        ]);
    }

    public function update(Request $request, int $standardId): RedirectResponse
    {
        abort_unless(auth()->user()->canDo('standards.edit'), 403);

        $standard = $this->standards->find($standardId);
        abort_if(! $standard, 404);

        $this->standards->update($standard, $this->validateStandard($request));
        ActivityLog::log('standards.edit', "تعديل معيار (#{$standard->id})", $standard);

        return redirect()->route('admin.qb.standards.index')->with('success', 'تم تحديث المعيار.');
    }

    public function destroy(int $standardId): RedirectResponse
    {
        abort_unless(auth()->user()->canDo('standards.delete'), 403);

        $standard = $this->standards->find($standardId);
        abort_if(! $standard, 404);

        $this->standards->delete($standard);
        ActivityLog::log('standards.delete', "حذف معيار (#{$standard->id})", $standard);

        return redirect()->route('admin.qb.standards.index')->with('success', 'تم حذف المعيار.');
    }

    private function validateStandard(Request $request): array
    {
        return $request->validate([
            'subject_id' => ['nullable', 'integer', 'exists:subjects,id'],
            'domain_id'  => ['nullable', 'integer', 'exists:domains,id'],
            'code'       => ['nullable', 'string', 'max:60'],
            'name'       => ['required', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'status'     => ['required', 'in:active,inactive'],
        ]);
    }

    private function subjects()
    {
        return Subject::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }
}
