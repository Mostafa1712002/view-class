<?php

namespace App\Modules\Evaluation\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Evaluation;
use App\Models\User;
use App\Modules\Evaluation\Enums\EvaluationStatus;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Task 15 — read-only job-performance linkage view.
 *
 * Surfaces evaluation results that flow into job performance: teachers (subjects)
 * who have results from forms with links_to_job_performance = true. Aggregation
 * honours each form's job_perf_settings (last vs average) and excludes
 * draft/rejected via EvaluationStatus::countsForJobPerformance() (Completed +
 * Approved). Linkage SETTINGS are NOT edited here — that lives on the form edit
 * screen; this controller only reports.
 */
class JobPerformanceController extends Controller
{
    use HasSchoolScope;

    /** Statuses that count toward job performance (Completed + Approved). */
    private const COUNTED = ['completed', 'approved'];

    /** Per-teacher linkage summary list. */
    public function index(): View
    {
        $schoolId = $this->activeSchoolId();
        $rows     = $this->linkedEvaluations($schoolId)->get();

        $byTeacher = $rows->groupBy('subject_id');

        $teachers   = [];
        $grandTotal = 0.0;
        $grandCount = 0;
        $formIds    = [];

        foreach ($byTeacher as $teacherId => $evals) {
            $summary = $this->summarise($evals);
            $teacher = $evals->first()->subject;

            $teachers[] = [
                'teacher_id' => (int) $teacherId,
                'name'       => $teacher?->name ?? ('#'.$teacherId),
                'school'     => $teacher?->school?->name,
                'count'      => $summary['count'],
                'average'    => $summary['average'],
                'latest'     => $summary['latest'],
                'effective'  => $summary['effective'],
                'mode'       => $summary['mode'],
                'status_mix' => $summary['status_mix'],
            ];

            foreach ($evals as $e) {
                $grandTotal += (float) $e->percentage;
                $grandCount++;
                $formIds[$e->form_id] = true;
            }
        }

        usort($teachers, fn ($a, $b) => $b['average'] <=> $a['average']);

        return view('admin.evaluation.job-performance.index', [
            'teachers' => $teachers,
            'stats'    => [
                'teachers'    => count($teachers),
                'evaluations' => $grandCount,
                'avg'         => $grandCount > 0 ? round($grandTotal / $grandCount, 2) : 0.0,
                'forms'       => count($formIds),
            ],
        ]);
    }

    /** Per-teacher detail: each linked evaluation + linkage settings. */
    public function show($teacher): View|RedirectResponse
    {
        $teacherId = (int) $teacher;
        $schoolId  = $this->activeSchoolId();

        $user = User::query()->find($teacherId);
        if (!$user) {
            return redirect()->route('admin.job-performance.index')
                ->with('error', __('eval_approval.jp.empty_title'));
        }
        // School scope (super-admin may view any teacher).
        $current = auth()->user();
        if ($current && !$current->isSuperAdmin() && $schoolId !== null && (int) $user->school_id !== (int) $schoolId) {
            abort(403);
        }

        $evals = $this->linkedEvaluations($schoolId)
            ->where('subject_id', $teacherId)
            ->with(['form:id,title,links_to_job_performance,job_perf_settings', 'evaluator:id,name', 'evidences:id,evaluation_id'])
            ->get();

        $summary = $this->summarise($evals);

        $detail = $evals->map(function (Evaluation $e) {
            $jp = (array) ($e->form?->job_perf_settings ?? []);

            return [
                'id'          => $e->id,
                'form'        => $e->form?->title,
                'evaluator'   => $e->evaluator?->name,
                'percentage'  => (float) $e->percentage,
                'total'       => $e->total_score,
                'max'         => $e->max_score,
                'date'        => $e->submitted_at ?? $e->created_at,
                'status'      => $e->status,
                'evidence'    => $e->evidences->count(),
                'weight'      => data_get($jp, 'weight'),
                'count_on'    => data_get($jp, 'count_on', 'submit'),
                'aggregation' => data_get($jp, 'aggregation', 'average'),
            ];
        })->values()->all();

        return view('admin.evaluation.job-performance.show', [
            'teacher' => $user,
            'detail'  => $detail,
            'summary' => $summary,
        ]);
    }

    /* --------------------------------------------------------------- helpers */

    /**
     * Base query: counted evaluations of users (teachers) on forms that opt into
     * job-performance linkage, within scope.
     */
    private function linkedEvaluations(?int $schoolId): Builder
    {
        return Evaluation::query()
            ->where('subject_type', 'user')
            ->whereIn('status', self::COUNTED)
            ->whereHas('form', fn (Builder $q) => $q->where('links_to_job_performance', true))
            // Subject must be a teacher (US-7: job performance is per teacher).
            ->whereHas('subject.roles', fn (Builder $q) => $q->where('slug', 'teacher'))
            ->when($schoolId !== null, fn (Builder $q) => $q->where('school_id', $schoolId))
            ->with(['subject:id,name,school_id', 'subject.school:id,name'])
            ->orderByDesc('submitted_at')
            ->orderByDesc('id');
    }

    /**
     * Aggregate a teacher's linked evaluations. Honours the form's
     * job_perf_settings "aggregation" (last vs average); default = average.
     * When forms disagree, "last" wins only if the most-recent form opts into it.
     *
     * @param \Illuminate\Support\Collection<int,Evaluation> $evals
     */
    private function summarise($evals): array
    {
        $count = $evals->count();
        if ($count === 0) {
            return ['count' => 0, 'average' => 0.0, 'latest' => 0.0, 'effective' => 0.0, 'mode' => 'average', 'status_mix' => []];
        }

        $sorted  = $evals->sortByDesc(fn (Evaluation $e) => $e->submitted_at ?? $e->created_at)->values();
        $latestE = $sorted->first();

        $average = round($evals->avg(fn (Evaluation $e) => (float) $e->percentage), 2);
        $latest  = round((float) $latestE->percentage, 2);

        // Aggregation mode from the most-recent linked form's settings.
        $mode = (string) data_get($latestE->form?->job_perf_settings ?? [], 'aggregation', 'average');
        $mode = in_array($mode, ['last', 'average'], true) ? $mode : 'average';

        $effective = $mode === 'last' ? $latest : $average;

        $statusMix = [];
        foreach ($evals as $e) {
            $key = $e->status instanceof EvaluationStatus ? $e->status->value : (string) $e->status;
            $statusMix[$key] = ($statusMix[$key] ?? 0) + 1;
        }

        return [
            'count'      => $count,
            'average'    => $average,
            'latest'     => $latest,
            'effective'  => $effective,
            'mode'       => $mode,
            'status_mix' => $statusMix,
        ];
    }
}
