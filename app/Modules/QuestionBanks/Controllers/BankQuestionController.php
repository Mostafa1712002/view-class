<?php

namespace App\Modules\QuestionBanks\Controllers;

use App\Http\Controllers\Controller;
use App\Models\BankQuestion;
use App\Models\QuestionBank;
use App\Modules\QuestionBanks\Repositories\Contracts\QuestionBankRepository;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BankQuestionController extends Controller
{
    use HasSchoolScope;

    public function __construct(private QuestionBankRepository $banks) {}

    public function index(int $bankId): View
    {
        $bank = $this->resolveBank($bankId);
        $questions = $bank->questions()->latest()->paginate(25);

        return view('admin.question-banks.questions.index', compact('bank', 'questions'));
    }

    public function create(int $bankId): View
    {
        $bank = $this->resolveBank($bankId);
        $question = new BankQuestion(['type' => 'mcq']);

        return view('admin.question-banks.questions.create', compact('bank', 'question'));
    }

    public function store(Request $request, int $bankId): RedirectResponse
    {
        $bank = $this->resolveBank($bankId);
        $data = $this->validateQuestion($request);

        $bank->questions()->create([
            'type' => $data['type'],
            'body_ar' => $data['body_ar'],
            'body_en' => $data['body_en'] ?? null,
            'answer_data' => $this->extractAnswerData($data),
            'difficulty' => $data['difficulty'] ?? null,
        ]);

        return redirect()
            ->route('admin.question-banks.questions.index', $bank->id)
            ->with('success', __('sprint4.question_banks.flash.question_added'));
    }

    public function destroy(int $bankId, int $questionId): RedirectResponse
    {
        $bank = $this->resolveBank($bankId);
        $question = $bank->questions()->whereKey($questionId)->firstOrFail();
        $question->delete();

        return redirect()
            ->route('admin.question-banks.questions.index', $bank->id)
            ->with('success', __('sprint4.question_banks.flash.question_deleted'));
    }

    private function resolveBank(int $bankId): QuestionBank
    {
        $schoolId = $this->activeSchoolId();
        $bank = $this->banks->findScoped($bankId, $schoolId);
        abort_if(! $bank, 404);
        return $bank;
    }

    private function validateQuestion(Request $request): array
    {
        return $request->validate([
            'type' => ['required', 'in:mcq,true_false,short,essay'],
            'body_ar' => ['required', 'string'],
            'body_en' => ['nullable', 'string'],
            'difficulty' => ['nullable', 'integer', 'min:1', 'max:5'],
            'options_ar' => ['nullable', 'array'],
            'options_ar.*' => ['nullable', 'string'],
            'correct' => ['nullable'],
        ]);
    }

    private function extractAnswerData(array $data): ?array
    {
        if (($data['type'] ?? null) === 'mcq') {
            return [
                'options' => array_values(array_filter($data['options_ar'] ?? [], static fn ($v) => $v !== null && $v !== '')),
                'correct' => $data['correct'] ?? null,
            ];
        }

        if ($data['type'] === 'true_false') {
            return ['correct' => $data['correct'] ?? null];
        }

        return null;
    }
}
