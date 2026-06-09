<?php

namespace App\Modules\Evaluation\Controllers;

use App\Http\Controllers\Controller;
use App\Models\EvaluationForm;
use App\Models\School;
use App\Models\Subject;
use App\Models\User;
use App\Modules\Evaluation\Enums\EvaluationStatus;
use App\Modules\Evaluation\Enums\VisitStatus;
use App\Modules\Evaluation\Services\ReportAggregator;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Sprint 8 — Task 19 (supervisor summary) + Task 20a (detailed supervisor report).
 *
 * Thin controller: resolves the active school scope, normalises the filter
 * array, delegates aggregation to ReportAggregator, and renders the view.
 */
class SupervisorReportController extends Controller
{
    use HasSchoolScope;

    public function __construct(private readonly ReportAggregator $aggregator)
    {
    }

    /** Task 19 — per-supervisor summary with KPIs + table. */
    public function index(Request $request): View|StreamedResponse
    {
        $schoolId = $this->activeSchoolId();
        $filters  = $this->filters($request);

        $rows = $this->aggregator->supervisorSummary($schoolId, $filters);
        $kpis = $this->aggregator->supervisorSummaryKpis($schoolId, $filters, $rows);

        if ($request->query('export') === 'csv') {
            return $this->exportSummaryCsv($rows);
        }

        return view('admin.evaluation.reports.supervisors', [
            'rows'    => $rows,
            'kpis'    => $kpis,
            'filters' => $filters,
        ] + $this->filterOptions($schoolId));
    }

    /** Task 20a — one row per evaluation. */
    public function detailed(Request $request): View|StreamedResponse
    {
        $schoolId = $this->activeSchoolId();
        $filters  = $this->filters($request);

        $query = $this->aggregator->detailedQuery($schoolId, $filters);

        if ($request->query('export') === 'csv') {
            return $this->exportDetailedCsv($this->aggregator->detailedRows($query->get()));
        }

        $paginator = $query->paginate(30)->withQueryString();
        $rows      = $this->aggregator->detailedRows(collect($paginator->items()));

        return view('admin.evaluation.reports.supervisors_detailed', [
            'rows'      => $rows,
            'paginator' => $paginator,
            'filters'   => $filters,
        ] + $this->filterOptions($schoolId));
    }

    /** Normalise request input into the aggregator filter contract. */
    private function filters(Request $request): array
    {
        return [
            'form'         => $request->integer('form') ?: null,
            'school'       => $request->integer('school') ?: null,
            'stage'        => $request->integer('stage') ?: null,
            'subject'      => $request->integer('subject') ?: null,
            'supervisor'   => $request->integer('supervisor') ?: null,
            'eval_status'  => $request->string('eval_status')->toString() ?: null,
            'visit_status' => $request->string('visit_status')->toString() ?: null,
            'date_from'    => $request->date('date_from')?->toDateString(),
            'date_to'      => $request->date('date_to')?->toDateString(),
        ];
    }

    /** Dropdown options for filters, scoped where a school is active. */
    private function filterOptions(?int $schoolId): array
    {
        return [
            'formOptions'       => EvaluationForm::query()
                ->when($schoolId !== null, fn ($q) => $q->where(fn ($w) => $w->where('school_id', $schoolId)->orWhereNull('school_id')))
                ->orderBy('title')->get(['id', 'title']),
            'schoolOptions'     => $schoolId === null
                ? School::query()->orderBy('name')->get(['id', 'name', 'name_ar'])
                : collect(),
            'subjectOptions'    => Subject::query()
                ->when($schoolId !== null, fn ($q) => $q->where('school_id', $schoolId))
                ->orderBy('name')->get(['id', 'name']),
            'supervisorOptions' => User::query()
                ->when($schoolId !== null, fn ($q) => $q->where('school_id', $schoolId))
                ->whereIn('id', function ($q) {
                    $q->select('evaluator_id')->from('evaluations')
                        ->union(\DB::table('class_visits')->select('supervisor_id'));
                })
                ->orderBy('name')->get(['id', 'name', 'name_ar']),
            'evalStatuses'      => EvaluationStatus::options(),
            'visitStatuses'     => VisitStatus::options(),
        ];
    }

    private function exportSummaryCsv(\Illuminate\Support\Collection $rows): StreamedResponse
    {
        $headers = [
            __('eval_reports.cols.supervisor'),
            __('eval_reports.cols.scheduled'),
            __('eval_reports.cols.executed'),
            __('eval_reports.cols.not_executed'),
            __('eval_reports.cols.evaluations'),
            __('eval_reports.cols.completed'),
            __('eval_reports.cols.incomplete'),
            __('eval_reports.cols.avg_pct'),
            __('eval_reports.cols.completion_pct'),
            __('eval_reports.cols.last_visit'),
        ];

        return $this->streamCsv('supervisor-summary', $headers, $rows->map(fn (array $r) => [
            $r['supervisor_name'], $r['scheduled'], $r['executed'], $r['not_executed'],
            $r['evaluations'], $r['completed'], $r['incomplete'],
            $r['avg_pct'] ?? '', $r['completion_pct'] ?? '',
            $r['last_visit'] ? \Illuminate\Support\Carbon::parse($r['last_visit'])->format('Y-m-d') : '',
        ]));
    }

    private function exportDetailedCsv(\Illuminate\Support\Collection $rows): StreamedResponse
    {
        $headers = [
            __('eval_reports.cols.form'),
            __('eval_reports.cols.supervisor'),
            __('eval_reports.cols.teacher'),
            __('eval_reports.cols.school'),
            __('eval_reports.cols.form_type'),
            __('eval_reports.cols.eval_date'),
            __('eval_reports.cols.total_score'),
            __('eval_reports.cols.percentage'),
            __('eval_reports.cols.status'),
            __('eval_reports.cols.evidence'),
            __('eval_reports.cols.notes'),
        ];

        return $this->streamCsv('supervisor-detailed', $headers, $rows->map(fn (array $r) => [
            $r['form'], $r['supervisor'], $r['teacher'], $r['school'], $r['form_type'],
            $r['eval_date'] ? \Illuminate\Support\Carbon::parse($r['eval_date'])->format('Y-m-d') : '',
            $r['total_score'] ?? '', $r['percentage'] ?? '',
            $r['status']?->label() ?? '', $r['evidence_count'], $r['notes_count'],
        ]));
    }

    /** Stream a UTF-8 (BOM) CSV so Excel renders Arabic correctly. */
    private function streamCsv(string $name, array $headers, \Illuminate\Support\Collection $lines): StreamedResponse
    {
        $filename = $name.'-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($headers, $lines) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM
            fputcsv($out, $headers, ',', '"', '\\');
            foreach ($lines as $line) {
                fputcsv($out, $line, ',', '"', '\\');
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
