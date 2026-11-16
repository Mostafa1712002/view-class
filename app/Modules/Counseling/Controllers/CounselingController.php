<?php

namespace App\Modules\Counseling\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Counseling\Models\CounselingCase;
use App\Modules\Counseling\Repositories\CounselingCaseRepository;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

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
        $filters = $request->validate([
            'status' => ['nullable', Rule::in(CounselingCase::STATUSES)],
            'category' => ['nullable', Rule::in(CounselingCase::CATEGORIES)],
            'student' => ['nullable', 'string', 'max:255'],
        ]);
        $query = $this->cases->visibleTo($request->user());
        $statusCounts = (clone $query)->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
        $categoryCounts = (clone $query)->selectRaw('category, count(*) as total')->groupBy('category')->pluck('total', 'category');
        foreach (['status', 'category'] as $field) {
            if (! empty($filters[$field])) {
                $query->where($field, $filters[$field]);
            }
        }
        if (! empty($filters['student'])) {
            $query->whereHas('student', fn ($q) => $q->where('name', 'like', '%'.$filters['student'].'%'));
        }
        $cases = $query->with(['student', 'counselor'])->latest()->paginate(20)->withQueryString();

        return view('counseling.index', compact('cases', 'statusCounts', 'categoryCounts'));
    }

    private function formData(CounselingCase $case): array
    {
        $schoolId = $case->school_id ?? (auth()->user()->isSuperAdmin() ? null : auth()->user()->school_id);
        $users = User::query()->when($schoolId !== null, fn ($q) => $q->where('school_id', $schoolId));

        return [
            'case' => $case,
            'schools' => $this->assignableSchools(),
            'students' => (clone $users)->whereHas('roles', fn ($q) => $q->where('slug', 'student'))->orderBy('name')->get(),
            'counselors' => (clone $users)->whereHas('jobTitle', fn ($q) => $q->where('slug', 'counselor'))->orderBy('name')->get(),
        ];
    }

    public function create()
    {
        $this->authorizeAccess();
        Gate::authorize('create', CounselingCase::class);

        return view('counseling.form', $this->formData(new CounselingCase(['counselor_id' => auth()->id(), 'status' => 'open'])));
    }

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
            'student_id' => ['required', 'integer'],
            'counselor_id' => ['required', 'integer'],
            'category' => ['required', Rule::in(CounselingCase::CATEGORIES)],
            'title' => ['required', 'string', 'max:255'],
            'summary' => ['required', 'string'],
            'status' => ['required', Rule::in(CounselingCase::STATUSES)],
        ]);
        abort_unless(User::where('school_id', $schoolId)->whereKey($data['student_id'])
            ->whereHas('roles', fn ($q) => $q->where('slug', 'student'))->exists(), 422);
        abort_unless(User::where('school_id', $schoolId)->whereKey($data['counselor_id'])
            ->whereHas('jobTitle', fn ($q) => $q->where('slug', 'counselor'))->exists(), 422);
        if (! $user->isSuperAdmin() && ! $user->isSchoolAdmin()) {
            abort_unless((int) $data['counselor_id'] === (int) $user->id, 403);
        }
        $data['school_id'] = $schoolId;
        $data['closed_at'] = $data['status'] === 'closed' ? ($case?->closed_at ?? now()) : null;

        return $data;
    }

    public function store(Request $request)
    {
        $this->authorizeAccess();
        Gate::authorize('create', CounselingCase::class);
        $case = CounselingCase::create($this->payload($request) + ['opened_at' => now(), 'created_by' => $request->user()->id]);

        return redirect()->route('manage.counseling.show', $case->id);
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
        $case->update($this->payload($request, $case));

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
