<?php

namespace App\Modules\Evaluation\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Evaluation;
use App\Models\EvaluationComment;
use App\Models\EvaluationForm;
use App\Models\School;
use App\Models\Subject;
use App\Models\User;
use App\Modules\Evaluation\Actions\SaveEvaluationDraft;
use App\Modules\Evaluation\Actions\StartEvaluation;
use App\Modules\Evaluation\Actions\SubmitEvaluation;
use App\Modules\Evaluation\Repositories\Contracts\EvaluationFormRepository;
use App\Modules\Evaluation\Repositories\Contracts\EvaluationRepository;
use App\Modules\Evaluation\Services\AuditTrail;
use App\Modules\Evaluation\Services\EvaluationNotifier;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Task 10 — subject picker (subjects assigned to THIS evaluator on a form).
 * Task 11 — the type-aware execution screen (start/resume, draft, submit).
 */
class EvaluationExecutionController extends Controller
{
    use HasSchoolScope;

    public function __construct(
        private readonly EvaluationFormRepository $forms,
        private readonly EvaluationRepository $evaluations,
        private readonly StartEvaluation $starter,
        private readonly SaveEvaluationDraft $drafter,
        private readonly SubmitEvaluation $submitter,
        private readonly EvaluationNotifier $notifier,
        private readonly AuditTrail $audit,
    ) {
    }

    /* ----------------------------------------------------------------- Task 10 */

    /** Subjects this evaluator is assigned to evaluate on the given form. */
    public function subjects(Request $request, int $form): View|RedirectResponse
    {
        $userId = (int) auth()->id();
        $evForm = EvaluationForm::query()->whereKey($form)->first();
        if (!$evForm) {
            return redirect()->route('admin.my-evaluations.index')->with('error', __('evaluation.form.not_found'));
        }

        $assignment = $evForm->assignments()
            ->where('evaluator_id', $userId)
            ->with(['targets.subject:id,name,school_id,is_active'])
            ->first();

        if (!$assignment) {
            return redirect()->route('admin.my-evaluations.index')
                ->with('error', __('evaluation.execute.errors.not_assigned'));
        }

        // Existing evaluations by me for these subjects, keyed by subject id.
        $subjectIds = $assignment->targets->pluck('target_id')->map(fn ($id) => (int) $id)->all();
        $existing   = Evaluation::query()
            ->where('form_id', $evForm->id)
            ->where('evaluator_id', $userId)
            ->where('subject_type', 'user')
            ->whereIn('subject_id', $subjectIds)
            ->get()
            ->keyBy('subject_id');

        $filterSchool  = $request->integer('school') ?: null;
        $filterSubject = $request->integer('subject') ?: null;
        $filterStatus  = $request->string('status')->toString() ?: null;

        $rows = [];
        foreach ($assignment->targets as $target) {
            $subject = $target->subject;
            if (!$subject) {
                continue;
            }
            $meta        = $target->meta ?? [];
            $metaSubject = (array) ($meta['subject'] ?? []);
            $eval        = $existing->get($subject->id);
            $status      = $eval?->status?->value ?? 'not_started';

            if ($filterSchool && (int) ($subject->school_id) !== $filterSchool) {
                continue;
            }
            if ($filterSubject && !in_array($filterSubject, array_map('intval', $metaSubject), true)) {
                continue;
            }
            if ($filterStatus && $status !== $filterStatus) {
                continue;
            }

            $rows[] = [
                'subject_id'   => $subject->id,
                'name'         => $subject->name,
                'school_id'    => $subject->school_id,
                'active'       => (bool) $subject->is_active,
                'status'       => $status,
                'evaluation_id'=> $eval?->id,
                'percentage'   => $eval?->percentage,
            ];
        }

        return view('admin.evaluation.execute.subjects', [
            'form'     => $evForm,
            'rows'     => $rows,
            'schools'  => School::query()->orderBy('name')->get(['id', 'name']),
            'subjects' => Subject::query()->orderBy('name')->get(['id', 'name']),
            'filters'  => ['school' => $filterSchool, 'subject' => $filterSubject, 'status' => $filterStatus],
        ]);
    }

    /* ----------------------------------------------------------------- Task 11 */

    /** Start (or resume) and render the execution screen for a subject. */
    public function start(int $form, int $subject): RedirectResponse
    {
        $evForm = EvaluationForm::query()->whereKey($form)->first();
        if (!$evForm) {
            return redirect()->route('admin.my-evaluations.index')->with('error', __('evaluation.form.not_found'));
        }

        try {
            $evaluation = $this->starter->start($evForm, (int) auth()->id(), $subject);
        } catch (ValidationException $e) {
            return redirect()->route('admin.evaluations.subjects', $evForm->id)->withErrors($e->errors());
        }

        return redirect()->route('admin.evaluations.execute.show', $evaluation->id);
    }

    /** Render the execution screen (the bespoke type-aware view). */
    public function show(int $evaluation): View|RedirectResponse
    {
        $eval = Evaluation::query()->whereKey($evaluation)->with('form')->first();
        if (!$eval) {
            return redirect()->route('admin.my-evaluations.index')->with('error', __('evaluation.form.not_found'));
        }

        $userId    = (int) auth()->id();
        $isEvaluator = (int) $eval->evaluator_id === $userId;
        // The subject may view their OWN result read-only when the form allows it
        // and the evaluation has reached a viewable state.
        $isSubjectViewer = (int) $eval->subject_id === $userId
            && $eval->subject_type === 'user'
            && (bool) ($eval->form?->setting('allow_subject_view_results', false))
            && in_array($eval->status?->value, ['completed', 'approved', 'locked'], true);

        if (!$isEvaluator && !$isSubjectViewer) {
            return redirect()->route('admin.my-evaluations.index')->with('error', __('evaluation.execute.errors.not_yours'));
        }

        $eval->load(['responses', 'evidences', 'form', 'snapshot', 'subject:id,name', 'comments.user']);
        $payload = $eval->snapshot?->payload ?? [];

        $canComment = $isSubjectViewer
            && (bool) ($eval->form?->setting('allow_subject_comment', false))
            && in_array($eval->status?->value, ['completed', 'approved', 'locked'], true);

        return view('admin.evaluation.execute.show', [
            'evaluation'      => $eval,
            'form'            => $eval->form,
            'payload'         => $payload,
            'type'            => $eval->form?->type?->value ?? ($payload['form']['type'] ?? null),
            'responses'       => $this->indexResponses($eval),
            'evidences'       => $this->indexEvidences($eval),
            'locked'          => ($eval->status?->isLocked() ?? false) || !$isEvaluator,
            'editable'        => $isEvaluator && in_array($eval->status?->value, ['draft'], true),
            'isSubjectViewer' => $isSubjectViewer,
            'canComment'      => $canComment,
        ]);
    }

    public function draft(Request $request, int $evaluation): RedirectResponse
    {
        $eval = $this->resolveMine($evaluation);
        if (!$eval instanceof Evaluation) {
            return $eval;
        }

        try {
            $this->drafter->save($eval, $this->answers($request), $request->input('general_notes'));
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        return redirect()->route('admin.evaluations.execute.show', $eval->id)
            ->with('status', __('evaluation.execute.flash.draft_saved'));
    }

    public function submit(Request $request, int $evaluation): RedirectResponse
    {
        $eval = $this->resolveMine($evaluation);
        if (!$eval instanceof Evaluation) {
            return $eval;
        }

        try {
            $this->submitter->submit($eval, $this->answers($request), $request->input('general_notes'));
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        return redirect()->route('admin.evaluations.execute.show', $eval->id)
            ->with('status', __('evaluation.execute.flash.submitted'));
    }

    /* --------------------------------------------------------------- Item 1: subject comment */

    /**
     * Allow the evaluation's subject to post a comment on their result.
     * Guards: must be the subject, form must allow it, evaluation must be completed/approved.
     */
    public function comment(Request $request, int $evaluation): RedirectResponse
    {
        $eval = Evaluation::query()->whereKey($evaluation)->with('form')->first();
        if (!$eval) {
            return redirect()->route('admin.my-evaluations.index')->with('error', __('evaluation.form.not_found'));
        }

        $userId = (int) auth()->id();

        // Only the evaluation's own subject may comment.
        if ($eval->subject_type !== 'user' || (int) $eval->subject_id !== $userId) {
            return redirect()->route('admin.my-evaluations.index')->with('error', __('evaluation.execute.errors.not_yours'));
        }

        // Form must have both allow_subject_view_results AND allow_subject_comment.
        if (!(bool) ($eval->form?->setting('allow_subject_view_results', false))
            || !(bool) ($eval->form?->setting('allow_subject_comment', false))) {
            abort(403);
        }

        // Evaluation must be in a viewable / completed state.
        if (!in_array($eval->status?->value, ['completed', 'approved', 'locked'], true)) {
            abort(403);
        }

        $request->validate(['body' => ['required', 'string', 'max:2000']]);

        EvaluationComment::create([
            'evaluation_id' => $eval->id,
            'user_id'       => $userId,
            'body'          => $request->input('body'),
        ]);

        $this->notifier->subjectCommented($eval);
        $this->audit->record('comment', "تعليق من المُقيَّم على تقييم #{$eval->id}", $eval);

        return redirect()->route('admin.evaluations.execute.show', $eval->id)
            ->with('status', __('evaluation.execute.flash.comment_saved'));
    }

    /* --------------------------------------------------------------- helpers */

    /** Pull the answer arrays out of the request in a shape WritesResponses expects. */
    private function answers(Request $request): array
    {
        return [
            'items'      => (array) $request->input('items', []),
            'indicators' => (array) $request->input('indicators', []),
            'item_notes' => (array) $request->input('item_notes', []),
        ];
    }

    /** Map existing responses for the view: item_id => levelId; indicator_id => levelId|bool. */
    private function indexResponses(Evaluation $eval): array
    {
        $byItem  = [];
        $byInd   = [];
        $notes   = [];
        foreach ($eval->responses as $r) {
            if ($r->indicator_id !== null) {
                $byInd[(int) $r->indicator_id] = $r->checklist_value !== null
                    ? (bool) $r->checklist_value
                    : ($r->level_id !== null ? (int) $r->level_id : null);
            } elseif ($r->item_id !== null) {
                $byItem[(int) $r->item_id] = $r->level_id !== null ? (int) $r->level_id : null;
                if ($r->note !== null) {
                    $notes[(int) $r->item_id] = $r->note;
                }
            }
        }

        return ['items' => $byItem, 'indicators' => $byInd, 'notes' => $notes];
    }

    /** Evidence grouped by node: 'item:ID' / 'ind:ID' => [evidence...]. */
    private function indexEvidences(Evaluation $eval): array
    {
        $out = [];
        foreach ($eval->evidences as $e) {
            $key = $e->indicator_id !== null ? 'ind:'.$e->indicator_id : 'item:'.$e->item_id;
            $out[$key][] = $e;
        }

        return $out;
    }

    /** Resolve an evaluation that belongs to the current evaluator (scope guard). */
    private function resolveMine(int $id): Evaluation|RedirectResponse
    {
        $eval = Evaluation::query()->whereKey($id)->first();
        if (!$eval || (int) $eval->evaluator_id !== (int) auth()->id()) {
            return redirect()->route('admin.my-evaluations.index')->with('error', __('evaluation.execute.errors.not_yours'));
        }

        return $eval;
    }
}
