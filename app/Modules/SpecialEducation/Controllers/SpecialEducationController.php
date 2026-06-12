<?php

namespace App\Modules\SpecialEducation\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\SpecialEducation\Http\Requests\StoreSpecialEducationNoteRequest;
use App\Modules\SpecialEducation\Http\Requests\StoreSpecialEducationPlanRequest;
use App\Modules\SpecialEducation\Http\Requests\StoreSpecialEducationStudentRequest;
use App\Modules\SpecialEducation\Http\Requests\UpdateSpecialEducationStudentRequest;
use App\Modules\SpecialEducation\Repositories\Contracts\SpecialEducationRepository;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SpecialEducationController extends Controller
{
    use HasSchoolScope;

    public function __construct(private SpecialEducationRepository $repo) {}

    // ── Index ─────────────────────────────────────────────────────────────────

    public function index(Request $request): View
    {
        $schoolId = $this->activeSchoolId();
        $filters  = [
            'category' => $request->get('category'),
            'status'   => $request->get('status'),
            'search'   => $request->get('search'),
        ];

        $students = $this->repo->studentsForSchool($schoolId, $filters);

        return view('special-education.index', compact('students', 'filters'));
    }

    // ── Create ────────────────────────────────────────────────────────────────

    public function create(): View
    {
        $schoolId  = $this->activeSchoolId();
        $schoolUsers = $this->schoolUsers($schoolId);

        return view('special-education.create', compact('schoolUsers'));
    }

    public function store(StoreSpecialEducationStudentRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $this->repo->createStudent(array_merge($data, [
            'school_id'  => $this->activeSchoolId(),
            'created_by' => auth()->id(),
        ]));

        return redirect()
            ->route('manage.special-education.index')
            ->with('success', __('special_education.flash_created'));
    }

    // ── Edit ──────────────────────────────────────────────────────────────────

    public function edit(int $id): View
    {
        $seStudent = $this->resolveOwned($id);
        $schoolId  = $this->activeSchoolId();
        $schoolUsers = $this->schoolUsers($schoolId);

        return view('special-education.edit', compact('seStudent', 'schoolUsers'));
    }

    public function update(UpdateSpecialEducationStudentRequest $request, int $id): RedirectResponse
    {
        $this->resolveOwned($id);
        $data = $request->validated();

        $this->repo->updateStudent($id, $data);

        return redirect()
            ->route('manage.special-education.index')
            ->with('success', __('special_education.flash_updated'));
    }

    // ── Show (detail: plans + notes) ──────────────────────────────────────────

    public function show(int $id): View
    {
        $seStudent = $this->resolveOwned($id);
        $plans     = $this->repo->plansFor($seStudent->id);
        $notes     = $this->repo->notesFor($seStudent->id);

        return view('special-education.show', compact('seStudent', 'plans', 'notes'));
    }

    // ── Delete ────────────────────────────────────────────────────────────────

    public function destroy(int $id): RedirectResponse
    {
        $this->resolveOwned($id);
        $this->repo->deleteStudent($id);

        return redirect()
            ->route('manage.special-education.index')
            ->with('success', __('special_education.flash_deleted'));
    }

    // ── Plans ─────────────────────────────────────────────────────────────────

    public function plansStore(StoreSpecialEducationPlanRequest $request, int $id): RedirectResponse
    {
        $seStudent = $this->resolveOwned($id);
        $data      = $request->validated();

        $this->repo->addPlan(array_merge($data, [
            'se_student_id' => $seStudent->id,
            'school_id'     => $this->activeSchoolId(),
            'created_by'    => auth()->id(),
        ]));

        return redirect()
            ->route('manage.special-education.show', $seStudent->id)
            ->with('success', __('special_education.flash_plan_added'));
    }

    public function plansDestroy(int $id, int $planId): RedirectResponse
    {
        $se = $this->resolveOwned($id);
        // Scope the delete to the resolved SE student (prevents cross-record/tenant IDOR).
        $this->repo->deletePlan($se->id, $planId);

        return redirect()
            ->route('manage.special-education.show', $id)
            ->with('success', __('special_education.flash_plan_deleted'));
    }

    // ── Notes ─────────────────────────────────────────────────────────────────

    public function notesStore(StoreSpecialEducationNoteRequest $request, int $id): RedirectResponse
    {
        $seStudent = $this->resolveOwned($id);
        $data      = $request->validated();

        $this->repo->addNote(array_merge($data, [
            'se_student_id' => $seStudent->id,
            'school_id'     => $this->activeSchoolId(),
            'created_by'    => auth()->id(),
        ]));

        return redirect()
            ->route('manage.special-education.show', $seStudent->id)
            ->with('success', __('special_education.flash_note_added'));
    }

    public function notesDestroy(int $id, int $noteId): RedirectResponse
    {
        $se = $this->resolveOwned($id);
        $this->repo->deleteNote($se->id, $noteId);

        return redirect()
            ->route('manage.special-education.show', $id)
            ->with('success', __('special_education.flash_note_deleted'));
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function resolveOwned(int $id)
    {
        $seStudent = $this->repo->findStudent($id);
        $schoolId  = $this->activeSchoolId();

        if (! $seStudent || $seStudent->school_id !== $schoolId) {
            abort(403, __('special_education.access_denied'));
        }

        return $seStudent;
    }

    private function schoolUsers(int $schoolId): \Illuminate\Support\Collection
    {
        return User::where('school_id', $schoolId)
            ->orderBy('name')
            ->get(['id', 'name', 'name_ar']);
    }
}
