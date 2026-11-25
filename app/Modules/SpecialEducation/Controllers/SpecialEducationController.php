<?php

namespace App\Modules\SpecialEducation\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ClassRoom;
use App\Models\Section;
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

        return view('special-education.create', $this->pickerData($schoolId));
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

        return view('special-education.edit', ['seStudent' => $seStudent] + $this->pickerData($schoolId));
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

    /** Students (role student only) with their grade/class ids, plus teachers, grades and classes of the school. */
    private function pickerData(?int $schoolId): array
    {
        $inRole = fn (string $slug) => User::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->whereHas('roles', fn ($q) => $q->where('slug', $slug))
            ->orderBy('name');
        $sections = Section::query()->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->orderBy('name')->get(['id', 'name']);

        return [
            'teachers' => $inRole('teacher')->get(['id', 'name', 'name_ar']),
            'sections' => $sections,
            'classes' => ClassRoom::whereIn('section_id', $sections->pluck('id'))->orderBy('name')->get(['id', 'name', 'division', 'section_id']),
            // Same grade/class rules as the counseling picker: users.class_room_id or the class_student pivot.
            'studentOptions' => $inRole('student')->with(['classRoom:id,section_id', 'enrolledClasses:classes.id,section_id'])->get(['id', 'name', 'name_ar', 'class_room_id', 'section_id'])
                ->map(fn (User $s) => [
                    'id' => $s->id,
                    'name' => (app()->getLocale() === 'ar' && $s->name_ar) ? $s->name_ar : $s->name,
                    'classes' => $s->enrolledClasses->pluck('id')->push($s->class_room_id)->filter()->unique()->values(),
                    'sections' => $s->enrolledClasses->pluck('section_id')->push($s->classRoom?->section_id, $s->section_id)->filter()->unique()->values(),
                ])->values(),
        ];
    }
}
