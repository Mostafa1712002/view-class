<?php

namespace App\Modules\Counseling\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ClassRoom;
use App\Models\Section;
use App\Models\User;
use App\Modules\Counseling\Models\CounselingCase;
use App\Modules\Counseling\Repositories\CounselingCaseRepository;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CounselingController extends Controller
{
    use HasSchoolScope;

    public function __construct(private CounselingCaseRepository $cases) {}

    private function authorizeAccess(): void
    {
        Gate::authorize('viewAny', CounselingCase::class);
        $this->scopedSchoolId();
    }

    private function findCase(int $id, string $ability = 'view'): CounselingCase
    {
        $this->authorizeAccess();
        $case = CounselingCase::findOrFail($id);
        Gate::authorize($ability, $case);

        return $case;
    }

    public function index(Request $request)
    {
        $this->authorizeAccess();
        $user = $request->user();
        $filters = $request->validate([
            'status' => ['nullable', Rule::in(CounselingCase::STATUSES)],
            'category' => ['nullable', Rule::in(CounselingCase::CATEGORIES)],
            'student' => ['nullable', 'string', 'max:255'],
            'school_id' => ['nullable', 'integer'],
            'section_id' => ['nullable', 'integer'],
            'class_id' => ['nullable', 'integer'],
            'counselor_id' => ['nullable', 'integer'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
        ]);
        $query = $this->cases->visibleTo($user);
        $statusCounts = (clone $query)->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
        $categoryCounts = (clone $query)->selectRaw('category, count(*) as total')->groupBy('category')->pluck('total', 'category');
        // Non-super-admins are already held to their school by visibleTo(); their school_id filter is ignored.
        $schoolId = $user->isSuperAdmin() ? ($filters['school_id'] ?? null) : $user->school_id;
        foreach (['status', 'category', 'counselor_id'] as $field) {
            if (! empty($filters[$field])) {
                $query->where($field, $filters[$field]);
            }
        }
        $query->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->when(! empty($filters['section_id']) || ! empty($filters['class_id']), fn ($q) => $q->whereIn('student_id',
                $this->studentsQuery(null, $filters['section_id'] ?? null, $filters['class_id'] ?? null)->select('users.id')))
            ->when($filters['date_from'] ?? null, fn ($q, $date) => $q->whereDate('opened_at', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($q, $date) => $q->whereDate('opened_at', '<=', $date));
        if (! empty($filters['student'])) {
            $query->whereHas('student', fn ($q) => $q->where('name', 'like', '%'.$filters['student'].'%'));
        }
        $cases = $query->with(['school', 'counselor', 'student.section', 'student.classRoom.section', 'student.enrolledClasses.section'])
            ->latest()->paginate(20)->withQueryString();
        $sections = Section::with('school:id,name')->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))->orderBy('name')->get(['id', 'name', 'school_id']);
        $classes = ClassRoom::whereIn('section_id', ! empty($filters['section_id']) ? [$filters['section_id']] : $sections->pluck('id'))
            ->orderBy('name')->get(['id', 'name', 'division', 'section_id']);
        $counselors = $user->isSuperAdmin() || $user->isSchoolAdmin()
            ? User::whereHas('jobTitle', fn ($q) => $q->where('slug', 'counselor'))
                ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))->orderBy('name')->get(['id', 'name'])
            : collect();

        return view('counseling.index', compact('cases', 'statusCounts', 'categoryCounts', 'sections', 'classes', 'counselors') + [
            'schools' => $this->assignableSchools(),
        ]);
    }

    /** Students of a school, optionally narrowed to a grade (section) and class; class links come from users.class_room_id or the class_student pivot. */
    private function studentsQuery(?int $schoolId, ?int $sectionId = null, ?int $classId = null)
    {
        return User::query()
            ->when($schoolId !== null, fn ($q) => $q->where('school_id', $schoolId))
            ->whereHas('roles', fn ($q) => $q->where('slug', 'student'))
            ->when($classId, fn ($q) => $q->where(fn ($w) => $w->where('class_room_id', $classId)
                ->orWhereHas('enrolledClasses', fn ($c) => $c->where('classes.id', $classId))))
            ->when($sectionId, fn ($q) => $q->where(fn ($w) => $w->where('section_id', $sectionId)
                ->orWhereHas('classRoom', fn ($c) => $c->where('section_id', $sectionId))
                ->orWhereHas('enrolledClasses', fn ($c) => $c->where('classes.section_id', $sectionId))));
    }

    private function formData(CounselingCase $case): array
    {
        $schoolId = $case->school_id ?? (auth()->user()->isSuperAdmin() ? null : auth()->user()->school_id);
        $users = User::query()->when($schoolId !== null, fn ($q) => $q->where('school_id', $schoolId));
        $students = $this->studentsQuery($schoolId)->with(['classRoom', 'enrolledClasses'])->orderBy('name')->get();
        $sections = Section::query()->when($schoolId !== null, fn ($q) => $q->where('school_id', $schoolId))
            ->orderBy('name')->get(['id', 'name', 'school_id']);

        return [
            'case' => $case,
            'schools' => $this->assignableSchools(),
            'students' => $students,
            'counselors' => (clone $users)->whereHas('jobTitle', fn ($q) => $q->where('slug', 'counselor'))->orderBy('name')->get(),
            'sections' => $sections,
            'classes' => ClassRoom::whereIn('section_id', $sections->pluck('id'))->orderBy('name')->get(['id', 'name', 'division', 'section_id']),
            // Same grade/class rules as studentsQuery(), used by the form to filter the student picker.
            'studentOptions' => $students->map(fn (User $s) => [
                'id' => $s->id,
                'name' => $s->name,
                'school' => $s->school_id,
                'classes' => $s->enrolledClasses->pluck('id')->push($s->class_room_id)->filter()->unique()->values(),
                'sections' => $s->enrolledClasses->pluck('section_id')
                    ->push($s->classRoom?->section_id, $s->section_id)->filter()->unique()->values(),
            ])->values(),
        ];
    }

    public function create()
    {
        $this->authorizeAccess();
        Gate::authorize('create', CounselingCase::class);

        return view('counseling.form', $this->formData(new CounselingCase(['counselor_id' => auth()->id(), 'status' => 'open'])));
    }

    /** @return array{0: array, 1: array<int>} case attributes (without student) and the student ids */
    private function payload(Request $request, ?CounselingCase $case = null): array
    {
        $user = $request->user();
        if (! $case && ! $user->isSuperAdmin() && ! $user->isSchoolAdmin() && ! $request->filled('counselor_id')) {
            $request->merge(['counselor_id' => $user->id]);
        }
        // Existing records retain their school; regular actors cannot select another tenant.
        $schoolId = $case?->school_id ?? ($user->isSuperAdmin() ? $request->input('school_id') : $user->school_id);
        $data = $request->validate([
            'school_id' => $user->isSuperAdmin() && ! $case ? ['required', 'integer', 'exists:schools,id'] : ['sometimes'],
            'student_id' => [$case ? 'required' : 'nullable', 'integer'],
            'student_ids' => ['nullable', 'array'],
            'student_ids.*' => ['integer'],
            'all_students' => ['nullable', 'boolean'],
            'filter_section_id' => ['nullable', 'integer'],
            'filter_class_id' => ['nullable', 'integer'],
            'counselor_id' => ['required', 'integer'],
            'category' => ['required', Rule::in(CounselingCase::CATEGORIES)],
            'title' => ['required', 'string', 'max:255'],
            'summary' => ['required', 'string'],
            'status' => ['required', Rule::in(CounselingCase::STATUSES)],
        ]);
        if ($case) {
            $ids = [(int) $data['student_id']];
        } elseif ($request->boolean('all_students')) {
            // Resolved here, not posted as ids, so a big school is not cut by max_input_vars.
            $ids = $this->studentsQuery((int) $schoolId, $data['filter_section_id'] ?? null, $data['filter_class_id'] ?? null)
                ->pluck('id')->all();
        } else {
            $ids = $data['student_ids'] ?? array_filter([$data['student_id'] ?? null]);
        }
        $ids = array_values(array_unique(array_map('intval', $ids)));
        if ($ids === []) {
            throw ValidationException::withMessages(['student_ids' => __('counseling.students_required')]);
        }
        abort_unless($this->studentsQuery((int) $schoolId)->whereKey($ids)->count() === count($ids), 422);
        abort_unless(User::where('school_id', $schoolId)->whereKey($data['counselor_id'])
            ->whereHas('jobTitle', fn ($q) => $q->where('slug', 'counselor'))->exists(), 422);
        if (! $user->isSuperAdmin() && ! $user->isSchoolAdmin()) {
            abort_unless((int) $data['counselor_id'] === (int) $user->id, 403);
        }
        $data['school_id'] = $schoolId;
        $data['closed_at'] = $data['status'] === 'closed' ? ($case?->closed_at ?? now()) : null;
        unset($data['student_id'], $data['student_ids'], $data['all_students'], $data['filter_section_id'], $data['filter_class_id']);

        return [$data, $ids];
    }

    public function store(Request $request)
    {
        $this->authorizeAccess();
        Gate::authorize('create', CounselingCase::class);
        [$data, $ids] = $this->payload($request);
        $extra = ['opened_at' => now(), 'created_by' => $request->user()->id];
        $cases = DB::transaction(fn () => collect($ids)->map(fn ($id) => CounselingCase::create($data + $extra + ['student_id' => $id])));

        if ($cases->count() === 1) {
            return redirect()->route('manage.counseling.show', $cases->first()->id);
        }

        return redirect()->route('manage.counseling.index')->with('success', __('counseling.created_many', ['count' => $cases->count()]));
    }

    public function show(int $id)
    {
        $case = $this->findCase($id)->load(['student', 'counselor', 'notes.author']);

        return view('counseling.show', compact('case'));
    }

    public function edit(int $id)
    {
        return view('counseling.form', $this->formData($this->findCase($id, 'update')));
    }

    public function update(Request $request, int $id)
    {
        $case = $this->findCase($id, 'update');
        [$data, $ids] = $this->payload($request, $case);
        $case->update($data + ['student_id' => $ids[0]]);

        return redirect()->route('manage.counseling.show', $case->id);
    }

    public function status(Request $request, int $id)
    {
        $case = $this->findCase($id, 'update');
        $data = $request->validate(['status' => ['required', Rule::in(CounselingCase::STATUSES)]]);
        $case->update($data + ['closed_at' => $data['status'] === 'closed' ? ($case->closed_at ?? now()) : null]);

        return redirect()->route('manage.counseling.show', $id);
    }

    public function note(Request $request, int $id)
    {
        $case = $this->findCase($id, 'update');
        $data = $request->validate(['body' => ['required', 'string'], 'action_taken' => ['nullable', 'string', 'max:255']]);
        $case->notes()->create($data + ['author_id' => $request->user()->id]);

        return redirect()->route('manage.counseling.show', $id);
    }

    public function destroy(int $id)
    {
        $this->findCase($id, 'delete')->delete();

        return redirect()->route('manage.counseling.index');
    }
}
