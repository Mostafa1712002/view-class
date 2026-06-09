<?php

namespace App\Modules\Evaluation\Controllers;

use App\Http\Controllers\Controller;
use App\Models\EvaluationForm;
use App\Models\School;
use App\Models\Subject;
use App\Models\User;
use App\Modules\Evaluation\Enums\EvaluationStatus;
use App\Modules\Evaluation\Services\ReportAggregator;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Sprint 8 — Task 20b: general-manager cross-org screen.
 *
 * Per-teacher rows with multi-evaluator averaging applied (delegated to
 * ReportAggregator::teacherSummary). Draft/rejected excluded from averages.
 */
class GeneralManagerController extends Controller
{
    use HasSchoolScope;

    public function __construct(private readonly ReportAggregator $aggregator)
    {
    }

    public function index(Request $request): View|StreamedResponse
    {
        $schoolId = $this->activeSchoolId();
        $filters  = $this->filters($request);

        $rows = $this->aggregator->teacherSummary($schoolId, $filters);
        $kpis = $this->aggregator->teacherSummaryKpis($schoolId, $filters, $rows);

        if ($request->query('export') === 'csv') {
            return $this->exportCsv($rows);
        }

        return view('admin.evaluation.reports.general_manager', [
            'rows'    => $rows,
            'kpis'    => $kpis,
            'filters' => $filters,
        ] + $this->filterOptions($schoolId));
    }

    /** Normalise GM cross-org filters into the aggregator contract. */
    private function filters(Request $request): array
    {
        return [
            'school'         => $request->integer('school') ?: null,
            'stage'          => $request->integer('stage') ?: null,
            'subject'        => $request->integer('subject') ?: null,
            'specialization' => $request->string('specialization')->toString() ?: null,
            'teacher'        => $request->integer('teacher') ?: null,
            'evaluator'      => $request->integer('evaluator') ?: null,
            'form'           => $request->integer('form') ?: null,
            'eval_status'    => $request->string('eval_status')->toString() ?: null,
            'date_from'      => $request->date('date_from')?->toDateString(),
            'date_to'        => $request->date('date_to')?->toDateString(),
            'score_from'     => $request->filled('score_from') ? (float) $request->input('score_from') : null,
            'score_to'       => $request->filled('score_to') ? (float) $request->input('score_to') : null,
            'has_evidence'   => $this->ternary($request->input('has_evidence')),
        ];
    }

    private function filterOptions(?int $schoolId): array
    {
        $teacherQuery = fn () => User::query()
            ->when($schoolId !== null, fn ($q) => $q->where('school_id', $schoolId))
            ->whereIn('id', fn ($q) => $q->select('subject_id')->from('evaluations'))
            ->orderBy('name')->get(['id', 'name', 'name_ar']);

        return [
            'schoolOptions'         => $schoolId === null
                ? School::query()->orderBy('name')->get(['id', 'name', 'name_ar'])
                : collect(),
            'subjectOptions'        => Subject::query()
                ->when($schoolId !== null, fn ($q) => $q->where('school_id', $schoolId))
                ->orderBy('name')->get(['id', 'name']),
            'teacherOptions'        => $teacherQuery(),
            'evaluatorOptions'      => User::query()
                ->when($schoolId !== null, fn ($q) => $q->where('school_id', $schoolId))
                ->whereIn('id', fn ($q) => $q->select('evaluator_id')->from('evaluations'))
                ->orderBy('name')->get(['id', 'name', 'name_ar']),
            'formOptions'           => EvaluationForm::query()
                ->when($schoolId !== null, fn ($q) => $q->where(fn ($w) => $w->where('school_id', $schoolId)->orWhereNull('school_id')))
                ->orderBy('title')->get(['id', 'title']),
            'specializationOptions' => User::query()
                ->when($schoolId !== null, fn ($q) => $q->where('school_id', $schoolId))
                ->whereNotNull('specialization')->distinct()->orderBy('specialization')
                ->pluck('specialization'),
            'evalStatuses'          => EvaluationStatus::options(),
        ];
    }

    private function exportCsv(\Illuminate\Support\Collection $rows): StreamedResponse
    {
        $headers = [
            __('eval_reports.cols.teacher'),
            __('eval_reports.cols.school'),
            __('eval_reports.cols.specialization'),
            __('eval_reports.cols.evaluations'),
            __('eval_reports.cols.evaluator'),
            __('eval_reports.cols.final_score'),
            __('eval_reports.cols.avg_pct'),
            __('eval_reports.cols.status'),
            __('eval_reports.cols.evidence'),
            __('eval_reports.cols.eval_date'),
        ];

        $lines = $rows->map(fn (array $r) => [
            $r['teacher'], $r['school'], $r['specialization'],
            $r['evaluations'],
            $r['evaluator'] ?? __('eval_reports.multiple_evaluators'),
            $r['final_score'] ?? '', $r['avg_pct'] ?? '',
            $r['status']?->label() ?? '', $r['evidence_count'],
            $r['eval_date'] ? \Illuminate\Support\Carbon::parse($r['eval_date'])->format('Y-m-d') : '',
        ]);

        $filename = 'general-manager-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($headers, $lines) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $headers, ',', '"', '\\');
            foreach ($lines as $line) {
                fputcsv($out, $line, ',', '"', '\\');
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function ternary(mixed $v): ?bool
    {
        if ($v === null || $v === '') {
            return null;
        }

        return (bool) $v;
    }
}
