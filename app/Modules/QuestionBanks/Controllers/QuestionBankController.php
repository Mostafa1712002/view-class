<?php

namespace App\Modules\QuestionBanks\Controllers;

use App\Http\Controllers\Controller;
use App\Models\QuestionBank;
use App\Models\Subject;
use App\Models\User;
use App\Modules\QuestionBanks\Repositories\Contracts\QuestionBankRepository;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QuestionBankController extends Controller
{
    use HasSchoolScope;

    public function __construct(private QuestionBankRepository $banks) {}

    public function index(Request $request): View
    {
        $schoolId = $this->activeSchoolId();
        $banks = $this->banks->paginate($schoolId, $request->get('q'));

        return view('admin.question-banks.index', compact('banks'));
    }

    public function library(): View
    {
        $banks = $this->banks->library();
        return view('admin.question-banks.library', compact('banks'));
    }

    public function clone(int $id): RedirectResponse
    {
        $template = QuestionBank::query()->where('is_library', true)->findOrFail($id);
        $schoolId = $this->activeSchoolId();
        $copy = $this->banks->clone($template, $schoolId, auth()->id());

        return redirect()
            ->route('admin.question-banks.edit', $copy->id)
            ->with('success', __('sprint4.question_banks.flash.cloned'));
    }

    public function create(): View
    {
        $schoolId = $this->activeSchoolId();
        $bank = new QuestionBank();
        $subjects = $this->subjectsForSchool($schoolId);
        $teachers = $this->teachersForSchool($schoolId);

        return view('admin.question-banks.create', compact('bank', 'subjects', 'teachers'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateBank($request);
        $subjectIds = $request->input('subject_ids', []);
        $memberRoles = $request->input('member_roles', []);

        $payload = [
            'name_ar' => $data['name_ar'],
            'name_en' => $data['name_en'] ?? null,
            'school_id' => $this->activeSchoolId(),
            'is_library' => false,
            'created_by' => auth()->id(),
        ];

        $this->banks->create($payload, $subjectIds, $memberRoles);

        return redirect()
            ->route('admin.question-banks.index')
            ->with('success', __('sprint4.question_banks.flash.created'));
    }

    public function edit(int $id): View
    {
        $schoolId = $this->activeSchoolId();
        $bank = $this->banks->findScoped($id, $schoolId);
        abort_if(! $bank, 404);

        $subjects = $this->subjectsForSchool($schoolId);
        $teachers = $this->teachersForSchool($schoolId);

        return view('admin.question-banks.edit', compact('bank', 'subjects', 'teachers'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $schoolId = $this->activeSchoolId();
        $bank = $this->banks->findScoped($id, $schoolId);
        abort_if(! $bank, 404);

        $data = $this->validateBank($request);
        $subjectIds = $request->input('subject_ids', []);
        $memberRoles = $request->input('member_roles', []);

        $this->banks->update($bank, [
            'name_ar' => $data['name_ar'],
            'name_en' => $data['name_en'] ?? null,
        ], $subjectIds, $memberRoles);

        return redirect()
            ->route('admin.question-banks.index')
            ->with('success', __('sprint4.question_banks.flash.updated'));
    }

    public function destroy(int $id): RedirectResponse
    {
        $schoolId = $this->activeSchoolId();
        $bank = $this->banks->findScoped($id, $schoolId);
        abort_if(! $bank, 404);

        $this->banks->delete($bank);

        return redirect()
            ->route('admin.question-banks.index')
            ->with('success', __('sprint4.question_banks.flash.deleted'));
    }

    private function validateBank(Request $request): array
    {
        return $request->validate([
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'subject_ids' => ['nullable', 'array'],
            'subject_ids.*' => ['integer', 'exists:subjects,id'],
            'member_roles' => ['nullable', 'array'],
            'member_roles.*' => ['nullable', 'in:viewer,editor'],
        ]);
    }

    private function subjectsForSchool(?int $schoolId): \Illuminate\Support\Collection
    {
        $query = Subject::query()->select('id', 'name', 'name_en')->where('is_active', true);
        if ($schoolId !== null) {
            $query->where('school_id', $schoolId);
        }
        return $query->orderBy('name')->get();
    }

    private function teachersForSchool(?int $schoolId): \Illuminate\Support\Collection
    {
        $query = User::query()->select('id', 'name', 'username');
        if ($schoolId !== null) {
            $query->where('school_id', $schoolId);
        }
        // Anyone with a role; we filter casual users via roles existence
        $query->whereHas('roles', function ($q) {
            $q->whereIn('slug', ['teacher', 'school-admin', 'super-admin']);
        });
        return $query->orderBy('name')->limit(200)->get();
    }
}
