<?php

namespace App\Modules\SpecialPrograms\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Modules\QuestionBankCore\Controllers\Concerns\ResolvesAnswerImages;
use App\Modules\SpecialPrograms\Actions\Bank\DeleteProgramQuestion;
use App\Modules\SpecialPrograms\Actions\Bank\SaveProgramPassage;
use App\Modules\SpecialPrograms\Actions\Bank\SaveProgramQuestion;
use App\Modules\SpecialPrograms\Http\Requests\PassageRequest;
use App\Modules\SpecialPrograms\Http\Requests\QuestionRequest;
use App\Modules\SpecialPrograms\Models\SpQuestion;
use App\Modules\SpecialPrograms\Models\SpSubject;
use App\Modules\SpecialPrograms\Repositories\Contracts\BankRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Program question bank (T3.3). Access = route permission special_programs.questions; non-super-admins are further
 * limited to the subjects of their sp_teachers rows (BankRepository::subjectsFor), so a verbal-only editor gets 403 on quant.
 */
class BankController extends Controller
{
    use ResolvesAnswerImages;

    private const FILTERS = ['skill_id', 'term', 'week', 'difficulty', 'grade_level', 'q', 'status', 'order_from', 'order_to'];

    public function __construct(
        private BankRepository $bank,
        private SaveProgramQuestion $saveQuestion,
        private SaveProgramPassage $savePassage,
        private DeleteProgramQuestion $delete,
    ) {}

    // ───────────── lists ─────────────

    public function index(Request $request, string $program)
    {
        [$subjects, $subject] = $this->subjectContext($request, $program);

        return view('special-programs.staff.bank.index', [
            'program' => $program, 'subjects' => $subjects, 'subject' => $subject,
            'skills' => $this->bank->skills($subject->id),
            'rows' => $this->bank->questions($subject->id, $request->only(self::FILTERS)),
            'filters' => $request->only(self::FILTERS),
        ]);
    }

    public function passages(Request $request, string $program)
    {
        abort_if($program === 'tahsili', 404);
        [$subjects, $subject] = $this->subjectContext($request, $program);

        return view('special-programs.staff.bank.passages', [
            'program' => $program, 'subjects' => $subjects, 'subject' => $subject,
            'skills' => $this->bank->skills($subject->id),
            'rows' => $this->bank->passages($subject->id, $request->only(self::FILTERS)),
            'filters' => $request->only(self::FILTERS),
        ]);
    }

    // ───────────── question CRUD ─────────────

    public function create(Request $request, string $program)
    {
        [, $subject] = $this->subjectContext($request, $program);

        return view('special-programs.staff.bank.question-form', $this->formData($program, $subject, null));
    }

    public function store(QuestionRequest $request, string $program)
    {
        $subject = SpSubject::findOrFail($request->subjectId());
        $this->saveQuestion->execute($subject, $this->questionPayload($request, $subject, null), null);

        return $this->backToList($program, $subject, 'saved');
    }

    public function show(string $program, int $id)
    {
        [$sp, $subject] = $this->loadQuestion($program, $id);

        return view('special-programs.staff.bank.show', [
            'program' => $program, 'subject' => $subject, 'detail' => $this->bank->questionDetail($sp),
            'image' => fn (?string $p) => $p ? Storage::disk('public')->url($p) : null,
        ]);
    }

    public function edit(string $program, int $id)
    {
        [$sp, $subject] = $this->loadQuestion($program, $id);

        return view('special-programs.staff.bank.question-form', $this->formData($program, $subject, $this->bank->questionDetail($sp)));
    }

    public function update(QuestionRequest $request, string $program, int $id)
    {
        [$sp, $subject] = $this->loadQuestion($program, $id);
        $this->saveQuestion->execute($subject, $this->questionPayload($request, $subject, $this->bank->questionDetail($sp)), $sp);

        return $this->backToList($program, $subject, 'saved');
    }

    public function destroy(string $program, int $id)
    {
        [$sp, $subject] = $this->loadQuestion($program, $id);

        return $this->backToList($program, $subject, $this->delete->question($sp));
    }

    // ───────────── passage CRUD ─────────────

    public function passagesCreate(Request $request, string $program)
    {
        abort_if($program === 'tahsili', 404);
        [, $subject] = $this->subjectContext($request, $program);

        return view('special-programs.staff.bank.passage-form', $this->passageFormData($program, $subject, null));
    }

    public function passagesStore(PassageRequest $request, string $program)
    {
        abort_if($program === 'tahsili', 404);
        $subject = SpSubject::findOrFail($request->subjectId());
        $this->savePassage->execute($subject, $request->validated());

        return $this->backToList($program, $subject, 'saved', 'manage.special-programs.bank.passages');
    }

    public function passagesEdit(string $program, int $id)
    {
        [$passage, $subject] = $this->loadPassage($program, $id);

        return view('special-programs.staff.bank.passage-form', $this->passageFormData($program, $subject, $passage));
    }

    public function passagesUpdate(PassageRequest $request, string $program, int $id)
    {
        [$passage, $subject] = $this->loadPassage($program, $id);
        $this->savePassage->execute($subject, $request->validated(), $passage);

        return $this->backToList($program, $subject, 'saved', 'manage.special-programs.bank.passages');
    }

    public function passagesDestroy(string $program, int $id)
    {
        [$passage, $subject] = $this->loadPassage($program, $id);

        return $this->backToList($program, $subject, $this->delete->passage($passage), 'manage.special-programs.bank.passages');
    }

    // ───────────── helpers ─────────────

    /** @return array{0:\Illuminate\Support\Collection,1:SpSubject} subjects the user may use + the selected one (?subject=). */
    private function subjectContext(Request $request, string $program): array
    {
        $subjects = $this->bank->subjectsFor($request->user(), $program);
        abort_if($subjects->isEmpty(), 403, __('special_programs/bank.errors.no_subject'));
        if ($request->filled('subject')) {
            $subject = $subjects->firstWhere('id', (int) $request->query('subject'));
            abort_unless($subject, SpSubject::where('program', $program)->whereKey((int) $request->query('subject'))->exists() ? 403 : 404);
        }

        return [$subjects, $subject ?? $subjects->first()];
    }

    private function authorizedSubject(string $program, ?int $subjectId): SpSubject
    {
        $subject = $subjectId ? SpSubject::where('program', $program)->find($subjectId) : null;
        abort_unless($subject, 404);
        abort_unless($this->bank->subjectsFor(request()->user(), $program)->contains('id', $subject->id), 403, __('special_programs/bank.errors.no_subject'));

        return $subject;
    }

    /** @return array{0:SpQuestion,1:SpSubject} */
    private function loadQuestion(string $program, int $id): array
    {
        $sp = $this->bank->findQuestion($id);
        abort_unless($sp && $sp->passage_id === null, 404);

        return [$sp, $this->authorizedSubject($program, $sp->sp_subject_id)];
    }

    private function loadPassage(string $program, int $id): array
    {
        abort_if($program === 'tahsili', 404);
        $passage = $this->bank->findPassage($id);
        abort_unless($passage, 404);

        return [$passage, $this->authorizedSubject($program, $this->bank->subjectIdOfPassage($id))];
    }

    private function backToList(string $program, SpSubject $subject, string $result, string $route = 'manage.special-programs.bank.index')
    {
        return redirect()->route($route, [$program, 'subject' => $subject->id])
            ->with('success', __('special_programs/bank.flash.'.$result));
    }

    private function formData(string $program, SpSubject $subject, ?array $detail): array
    {
        return [
            'program' => $program, 'subject' => $subject, 'detail' => $detail,
            'skills' => $this->bank->skills($subject->id),
            'imageUrl' => fn (?string $p) => $p ? Storage::disk('public')->url($p) : null,
        ];
    }

    private function passageFormData(string $program, SpSubject $subject, $passage): array
    {
        $members = $passage ? $this->bank->passageMembers($passage) : collect();

        return [
            'program' => $program, 'subject' => $subject, 'passage' => $passage, 'members' => $members,
            'levels' => $passage ? $this->bank->passageLevels($passage) : [],
            'skills' => $this->bank->skills($subject->id),
        ];
    }

    /** Validated fields + stored images, ready for SaveProgramQuestion. */
    private function questionPayload(QuestionRequest $request, SpSubject $subject, ?array $detail): array
    {
        $data = $request->validated();
        $bank = $this->saveQuestion->bank($subject);

        // Only images the question already owns may be "kept"; anything else posted is dropped.
        $owned = $detail ? $detail['answers']->pluck('answer_image')->filter()->all() : [];
        $request->merge(['option_images_keep' => array_map(
            fn ($p) => in_array($p, $owned, true) ? $p : null, (array) $request->input('option_images_keep', [])
        )]);
        $data['option_image_paths'] = $this->mapAnswerImageGroup($request, 'bank-questions/'.$bank->id.'/answers', 'option_images', 'option_images_keep');

        $current = $detail['bank']->attachment_path ?? null;
        if ($request->hasFile('question_image')) {
            $file = $request->file('question_image');
            $data['image_md5'] = md5_file($file->getRealPath());
            $data['attachment_path'] = $file->storeAs('special-programs/'.$subject->program,
                'question_'.time().'_'.uniqid().'.'.strtolower($file->getClientOriginalExtension() ?: 'jpg'), 'public');
        } elseif ($current && ! $request->boolean('remove_image')) {
            $data['attachment_path'] = $current;
            $disk = Storage::disk('public');
            $data['image_md5'] = $disk->exists($current) ? md5_file($disk->path($current)) : md5($current);
        }
        unset($data['question_image'], $data['remove_image']);

        return $data;
    }
}
