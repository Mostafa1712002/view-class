<?php

namespace App\Modules\Evaluation\Controllers;

use App\Http\Controllers\Controller;
use App\Models\EvaluationAssignment;
use App\Modules\Evaluation\Repositories\Contracts\EvaluationRepository;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use Illuminate\View\View;

/**
 * Task 9 — "التقييمات" landing for the current user:
 *  (a) "required of me"  — forms they are assigned to evaluate, with progress.
 *  (b) "my results"      — evaluations where they are the SUBJECT (if allowed to view).
 */
class MyEvaluationsController extends Controller
{
    use HasSchoolScope;

    public function __construct(private readonly EvaluationRepository $evaluations)
    {
    }

    public function index(): View
    {
        $userId   = (int) auth()->id();
        $schoolId = $this->activeSchoolId();

        return view('admin.evaluation.my.index', [
            'required'  => $this->requiredOfMe($userId),
            'myResults' => $this->myResults($userId, $schoolId),
        ]);
    }

    /**
     * Forms the current user is assigned to evaluate, with completion stats
     * derived from how many of their targets they have already submitted.
     *
     * @return array<int,array<string,mixed>>
     */
    private function requiredOfMe(int $userId): array
    {
        $assignments = EvaluationAssignment::query()
            ->where('evaluator_id', $userId)
            ->with(['form:id,title,type,status,school_id', 'targets:id,target_id'])
            ->get()
            ->filter(fn ($a) => $a->form !== null);

        $out = [];
        foreach ($assignments as $a) {
            $form        = $a->form;
            $targetCount = $a->targets->count();
            $subjectIds  = $a->targets->pluck('target_id')->map(fn ($id) => (int) $id)->all();

            // Submitted = a non-draft evaluation by me for that subject on that form.
            $done = $subjectIds === [] ? 0 : \App\Models\Evaluation::query()
                ->where('form_id', $form->id)
                ->where('evaluator_id', $userId)
                ->whereIn('subject_id', $subjectIds)
                ->where('subject_type', 'user')
                ->where('status', '!=', 'draft')
                ->distinct('subject_id')
                ->count('subject_id');

            $remaining = max(0, $targetCount - $done);
            $percent   = $targetCount > 0 ? round($done / $targetCount * 100, 1) : 0.0;

            $out[] = [
                'assignment_id' => $a->id,
                'form_id'       => $form->id,
                'title'         => $form->title,
                'type'          => $form->type,
                'status'        => $form->status,
                'target_count'  => $targetCount,
                'done'          => $done,
                'remaining'     => $remaining,
                'percent'       => $percent,
                'can_start'     => $form->status?->value === 'published' && $targetCount > 0,
            ];
        }

        return $out;
    }

    /**
     * Evaluations where the current user is the evaluated subject. Results are only
     * surfaced when the form allows the subject to view them.
     *
     * @return array<int,array<string,mixed>>
     */
    private function myResults(int $userId, ?int $schoolId): array
    {
        return $this->evaluations->forSubject($userId, $schoolId)
            ->map(function ($eval) {
                $allowView = (bool) ($eval->form?->setting('allow_subject_view_results', false));
                $visible   = $allowView && in_array($eval->status?->value, ['completed', 'approved', 'locked'], true);

                return [
                    'id'         => $eval->id,
                    'form_title' => $eval->form?->title ?? '—',
                    'evaluator'  => $eval->evaluator?->name ?? '—',
                    'status'     => $eval->status,
                    'percentage' => $eval->percentage,
                    'grade'      => $eval->grade_label,
                    'can_view'   => $visible,
                    'submitted'  => $eval->submitted_at,
                ];
            })
            ->all();
    }
}
