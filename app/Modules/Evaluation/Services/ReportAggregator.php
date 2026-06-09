<?php

namespace App\Modules\Evaluation\Services;

use App\Models\ClassVisit;
use App\Models\Evaluation;
use App\Modules\Evaluation\Enums\EvaluationStatus;
use App\Modules\Evaluation\Enums\VisitStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Read-time aggregation for Sprint 8 evaluation reports (Tasks 19-20).
 *
 * Pure query/aggregation service — no HTTP concerns. Controllers gather the
 * filter array, pass the resolved school scope, and receive plain arrays /
 * collections ready for the views.
 *
 * Scope rules: a non-null $schoolId restricts every query to that school
 * (plus global rows where school_id IS NULL). A null $schoolId means
 * super-admin "all schools" — no school filter is applied.
 *
 * Statuses excluded from performance averages: draft + rejected (an
 * unfinished or rejected evaluation must never drag a teacher's average).
 */
class ReportAggregator
{
    /** Statuses that count toward a real performance figure. */
    private const PERFORMANCE_STATUSES = [
        EvaluationStatus::Completed->value,
        EvaluationStatus::Approved->value,
        EvaluationStatus::PendingApproval->value,
        EvaluationStatus::NeedsReview->value,
        EvaluationStatus::Locked->value,
    ];

    /** Statuses never counted in averages / KPIs that imply a finished score. */
    private const EXCLUDED_FROM_AVG = [
        EvaluationStatus::Draft->value,
        EvaluationStatus::Rejected->value,
    ];

    // ---------------------------------------------------------------------
    // Task 19 — Supervisor summary
    // ---------------------------------------------------------------------

    /**
     * Per-supervisor aggregate rows.
     *
     * A "supervisor" is the union of:
     *  - class_visits.supervisor_id (scheduling side), and
     *  - evaluations.evaluator_id   (execution side).
     *
     * Each row carries scheduled/executed/not-executed visit counts, the
     * evaluation counts (total / completed / incomplete), the average score %
     * (over scored evaluations only), a completion %, and the last visit date.
     *
     * @param  array<string,mixed>  $filters
     * @return Collection<int,array<string,mixed>>
     */
    public function supervisorSummary(?int $schoolId, array $filters = []): Collection
    {
        $visits      = $this->visitsBySupervisor($schoolId, $filters);
        $evaluations = $this->evaluationsByEvaluator($schoolId, $filters);

        $names = $this->resolveUserNames(
            array_merge($visits->keys()->all(), $evaluations->keys()->all())
        );

        $ids = collect($visits->keys()->all())
            ->merge($evaluations->keys()->all())
            ->unique()
            ->filter()
            ->values();

        return $ids->map(function (int $id) use ($visits, $evaluations, $names): array {
            $v = $visits->get($id);
            $e = $evaluations->get($id);

            $scheduled   = (int) ($v->scheduled ?? 0);
            $executed    = (int) ($v->executed ?? 0);
            $notExecuted = max(0, $scheduled - $executed);

            $evalTotal      = (int) ($e->total ?? 0);
            $evalCompleted  = (int) ($e->completed ?? 0);
            $evalIncomplete = max(0, $evalTotal - $evalCompleted);

            $avgPct      = $e && $e->scored_count > 0 ? round((float) $e->avg_pct, 2) : null;
            $completion  = $evalTotal > 0 ? round($evalCompleted / $evalTotal * 100, 1) : null;
            $lastVisit   = $v->last_visit ?? null;

            return [
                'supervisor_id'   => $id,
                'supervisor_name' => $names[$id] ?? ('#'.$id),
                'scheduled'       => $scheduled,
                'executed'        => $executed,
                'not_executed'    => $notExecuted,
                'evaluations'     => $evalTotal,
                'completed'       => $evalCompleted,
                'incomplete'      => $evalIncomplete,
                'avg_pct'         => $avgPct,
                'completion_pct'  => $completion,
                'last_visit'      => $lastVisit,
            ];
        })
        ->sortByDesc(fn (array $r) => $r['avg_pct'] ?? -1)
        ->values();
    }

    /**
     * KPI tiles for the supervisor summary.
     *
     * @param  Collection<int,array<string,mixed>>  $rows  output of supervisorSummary()
     * @return array<string,mixed>
     */
    public function supervisorSummaryKpis(?int $schoolId, array $filters, Collection $rows): array
    {
        $visitStats = $this->visitStatusCounts($schoolId, $filters);
        $evalStats  = $this->evaluationStatusCounts($schoolId, $filters);

        $scored = $rows->filter(fn ($r) => $r['avg_pct'] !== null);
        $top    = $scored->sortByDesc('avg_pct')->first();
        $low    = $scored->sortBy('avg_pct')->first();

        $totalVisits     = (int) $visitStats['total'];
        $executedVisits  = (int) $visitStats['executed'];

        return [
            'supervisors'      => $rows->count(),
            'total_visits'     => $totalVisits,
            'total_evals'      => (int) $evalStats['total'],
            'completed'        => (int) $evalStats['completed'],
            'incomplete'       => (int) $evalStats['incomplete'],
            'postponed_visits' => (int) $visitStats['postponed'],
            'cancelled_visits' => (int) $visitStats['cancelled'],
            'avg_pct'          => $evalStats['avg_pct'] !== null ? round((float) $evalStats['avg_pct'], 2) : null,
            'top_supervisor'   => $top['supervisor_name'] ?? null,
            'top_pct'          => $top['avg_pct'] ?? null,
            'low_supervisor'   => $low['supervisor_name'] ?? null,
            'low_pct'          => $low['avg_pct'] ?? null,
            'completion_pct'   => $totalVisits > 0 ? round($executedVisits / $totalVisits * 100, 1) : null,
        ];
    }

    // ---------------------------------------------------------------------
    // Task 20a — Detailed supervisor report (one row per evaluation)
    // ---------------------------------------------------------------------

    /**
     * Paginatable builder of evaluations with eager relations, scoped + filtered.
     */
    public function detailedQuery(?int $schoolId, array $filters): Builder
    {
        return Evaluation::query()
            ->with([
                'form:id,title,type,is_class_visit_only',
                'evaluator:id,name,name_ar',
                'subject:id,name,name_ar,specialization,school_id',
                'subject.school:id,name,name_ar',
                'classVisit:id,visit_type,visit_date,status',
            ])
            ->withCount(['evidences', 'comments'])
            ->when($schoolId !== null, $this->schoolScope($schoolId))
            ->tap(fn (Builder $q) => $this->applyEvaluationFilters($q, $filters))
            ->orderByDesc('updated_at');
    }

    /**
     * Decorate an evaluation collection into flat detailed rows.
     *
     * @param  Collection<int,Evaluation>  $evaluations
     * @return Collection<int,array<string,mixed>>
     */
    public function detailedRows(Collection $evaluations): Collection
    {
        // Teacher-commented: any comment whose author is the evaluated teacher.
        $commenterMap = $this->teacherCommentMap($evaluations->pluck('id')->all());

        return $evaluations->map(function (Evaluation $e) use ($commenterMap): array {
            $teacherCommented = isset($commenterMap[$e->id]) && in_array($e->subject_id, $commenterMap[$e->id], true);

            return [
                'id'                => $e->id,
                'form'              => $e->form?->title,
                'supervisor'        => $this->displayName($e->evaluator),
                'teacher'           => $this->displayName($e->subject),
                'school'            => $this->displayName($e->subject?->school),
                'specialization'    => $e->subject?->specialization,
                'form_type'         => $e->form?->type?->label(),
                'visit_type'        => $e->classVisit?->visit_type,
                'visit_date'        => $e->classVisit?->visit_date,
                'eval_date'         => $e->submitted_at ?? $e->created_at,
                'total_score'       => $e->total_score,
                'percentage'        => $e->percentage,
                'status'            => $e->status,
                // No dedicated viewed_at column yet; commenting implies a view.
                'teacher_commented' => $teacherCommented,
                'teacher_viewed'    => $teacherCommented,
                'evidence_count'    => (int) $e->evidences_count,
                'notes_count'       => (int) $e->comments_count,
                'last_update'       => $e->updated_at,
            ];
        });
    }

    // ---------------------------------------------------------------------
    // Task 20b — General-manager (per-teacher, cross-org)
    // ---------------------------------------------------------------------

    /**
     * Per-teacher aggregate rows with multi-evaluator averaging applied.
     *
     * Draft + rejected evaluations are excluded from the performance average.
     * When a teacher has multiple scored evaluations, the % is averaged (an
     * unweighted mean of the per-evaluation percentages).
     *
     * @return Collection<int,array<string,mixed>>
     */
    public function teacherSummary(?int $schoolId, array $filters): Collection
    {
        $rows = Evaluation::query()
            ->with([
                'subject:id,name,name_ar,specialization,school_id',
                'subject.school:id,name,name_ar',
                'evaluator:id,name,name_ar',
                'form:id,title',
            ])
            ->withCount(['evidences', 'comments'])
            ->when($schoolId !== null, $this->schoolScope($schoolId))
            ->tap(fn (Builder $q) => $this->applyEvaluationFilters($q, $filters))
            ->orderByDesc('updated_at')
            ->get();

        return $rows
            ->groupBy('subject_id')
            ->map(function (Collection $group): array {
                /** @var Evaluation $first */
                $first  = $group->first();
                $latest = $group->sortByDesc('updated_at')->first();

                $scored = $group->reject(
                    fn (Evaluation $e) => in_array($e->status?->value, self::EXCLUDED_FROM_AVG, true)
                        || $e->percentage === null
                );

                $avgPct = $scored->count() > 0
                    ? round($scored->avg(fn (Evaluation $e) => (float) $e->percentage), 2)
                    : null;

                // Multiple evaluators present for this teacher?
                $evaluators = $group->pluck('evaluator_id')->unique()->filter()->values();

                return [
                    'teacher_id'      => $first->subject_id,
                    'teacher'         => $this->displayName($first->subject),
                    'school'          => $this->displayName($first->subject?->school),
                    'specialization'  => $first->subject?->specialization,
                    'evaluations'     => $group->count(),
                    'scored'          => $scored->count(),
                    'evaluator'       => $evaluators->count() > 1
                        ? null // multiple — view will render a "multiple" badge
                        : $this->displayName($first->evaluator),
                    'evaluator_count' => $evaluators->count(),
                    'final_score'     => $scored->count() > 0
                        ? round($scored->avg(fn (Evaluation $e) => (float) $e->total_score), 2)
                        : null,
                    'avg_pct'         => $avgPct,
                    'status'          => $latest?->status,
                    'evidence_count'  => (int) $group->sum('evidences_count'),
                    'eval_date'       => $latest?->submitted_at ?? $latest?->created_at,
                    'last_update'     => $latest?->updated_at,
                ];
            })
            ->sortByDesc(fn (array $r) => $r['avg_pct'] ?? -1)
            ->values();
    }

    /**
     * KPI tiles for the general-manager screen.
     *
     * @param  Collection<int,array<string,mixed>>  $rows  output of teacherSummary()
     * @return array<string,mixed>
     */
    public function teacherSummaryKpis(?int $schoolId, array $filters, Collection $rows): array
    {
        $stats = $this->evaluationStatusCounts($schoolId, $filters);

        $scored  = $rows->filter(fn ($r) => $r['avg_pct'] !== null);
        $top     = $scored->sortByDesc('avg_pct')->first();
        $low     = $scored->sortBy('avg_pct')->first();

        return [
            'teachers'          => $rows->count(),
            'completed'         => (int) $stats['completed'],
            'incomplete'        => (int) $stats['incomplete'],
            'approved'          => (int) $stats['approved'],
            'pending_approval'  => (int) $stats['pending_approval'],
            'avg_pct'           => $stats['avg_pct'] !== null ? round((float) $stats['avg_pct'], 2) : null,
            'highest'           => $top['avg_pct'] ?? null,
            'lowest'            => $low['avg_pct'] ?? null,
            'without_evidence'  => (int) $stats['without_evidence'],
            'needs_review'      => (int) $stats['needs_review'],
        ];
    }

    // ---------------------------------------------------------------------
    // Internal query helpers
    // ---------------------------------------------------------------------

    /** Class-visit aggregates keyed by supervisor_id. */
    private function visitsBySupervisor(?int $schoolId, array $filters): Collection
    {
        return ClassVisit::query()
            ->when($schoolId !== null, fn (Builder $q) => $q->where('school_id', $schoolId))
            ->tap(fn (Builder $q) => $this->applyVisitFilters($q, $filters))
            ->selectRaw('supervisor_id')
            ->selectRaw('COUNT(*) as scheduled')
            ->selectRaw('SUM(status = ?) as executed', [VisitStatus::Completed->value])
            ->selectRaw('MAX(visit_date) as last_visit')
            ->groupBy('supervisor_id')
            ->get()
            ->keyBy('supervisor_id');
    }

    /** Evaluation aggregates keyed by evaluator_id. */
    private function evaluationsByEvaluator(?int $schoolId, array $filters): Collection
    {
        return Evaluation::query()
            ->when($schoolId !== null, $this->schoolScope($schoolId))
            ->tap(fn (Builder $q) => $this->applyEvaluationFilters($q, $filters))
            ->selectRaw('evaluator_id')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(status IN (?, ?)) as completed', [
                EvaluationStatus::Completed->value, EvaluationStatus::Approved->value,
            ])
            ->selectRaw('SUM(percentage IS NOT NULL AND status NOT IN (?, ?)) as scored_count', self::EXCLUDED_FROM_AVG)
            ->selectRaw('AVG(CASE WHEN status NOT IN (?, ?) THEN percentage END) as avg_pct', self::EXCLUDED_FROM_AVG)
            ->groupBy('evaluator_id')
            ->get()
            ->keyBy('evaluator_id');
    }

    /** Aggregate visit-status counts within scope+filters. */
    private function visitStatusCounts(?int $schoolId, array $filters): array
    {
        $row = ClassVisit::query()
            ->when($schoolId !== null, fn (Builder $q) => $q->where('school_id', $schoolId))
            ->tap(fn (Builder $q) => $this->applyVisitFilters($q, $filters))
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(status = ?) as executed', [VisitStatus::Completed->value])
            ->selectRaw('SUM(status = ?) as postponed', [VisitStatus::Postponed->value])
            ->selectRaw('SUM(status = ?) as cancelled', [VisitStatus::Cancelled->value])
            ->first();

        return [
            'total'     => (int) ($row->total ?? 0),
            'executed'  => (int) ($row->executed ?? 0),
            'postponed' => (int) ($row->postponed ?? 0),
            'cancelled' => (int) ($row->cancelled ?? 0),
        ];
    }

    /** Aggregate evaluation-status counts within scope+filters. */
    private function evaluationStatusCounts(?int $schoolId, array $filters): array
    {
        $row = Evaluation::query()
            ->when($schoolId !== null, $this->schoolScope($schoolId))
            ->tap(fn (Builder $q) => $this->applyEvaluationFilters($q, $filters))
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(status IN (?, ?)) as completed', [
                EvaluationStatus::Completed->value, EvaluationStatus::Approved->value,
            ])
            ->selectRaw('SUM(status = ?) as approved', [EvaluationStatus::Approved->value])
            ->selectRaw('SUM(status = ?) as pending_approval', [EvaluationStatus::PendingApproval->value])
            ->selectRaw('SUM(status = ?) as needs_review', [EvaluationStatus::NeedsReview->value])
            ->selectRaw('SUM(status = ? OR (status = ? AND percentage IS NULL)) as incomplete', [
                EvaluationStatus::Draft->value, EvaluationStatus::NeedsReview->value,
            ])
            ->selectRaw('SUM(evidence_count = 0 AND status NOT IN (?, ?)) as without_evidence', self::EXCLUDED_FROM_AVG)
            ->selectRaw('AVG(CASE WHEN status NOT IN (?, ?) THEN percentage END) as avg_pct', self::EXCLUDED_FROM_AVG)
            ->first();

        return [
            'total'            => (int) ($row->total ?? 0),
            'completed'        => (int) ($row->completed ?? 0),
            'approved'         => (int) ($row->approved ?? 0),
            'pending_approval' => (int) ($row->pending_approval ?? 0),
            'needs_review'     => (int) ($row->needs_review ?? 0),
            'incomplete'       => (int) ($row->incomplete ?? 0),
            'without_evidence' => (int) ($row->without_evidence ?? 0),
            'avg_pct'          => $row->avg_pct,
        ];
    }

    // ---------------------------------------------------------------------
    // Filter application
    // ---------------------------------------------------------------------

    /**
     * Shared school scope closure (school rows + global rows).
     *
     * Tolerates a null id (super-admin "all schools") so it is safe to build
     * even when guarded by ->when($schoolId !== null, ...) — the closure is a
     * no-op in that case.
     */
    private function schoolScope(?int $schoolId): \Closure
    {
        return function (Builder $q) use ($schoolId) {
            if ($schoolId === null) {
                return $q;
            }

            return $q->where(
                fn (Builder $w) => $w->where('school_id', $schoolId)->orWhereNull('school_id')
            );
        };
    }

    /** @param array<string,mixed> $f */
    private function applyEvaluationFilters(Builder $q, array $f): void
    {
        $q->when($f['form'] ?? null, fn (Builder $q, $v) => $q->where('form_id', $v))
            ->when($f['school'] ?? null, fn (Builder $q, $v) => $q->where('school_id', $v))
            ->when($f['supervisor'] ?? null, fn (Builder $q, $v) => $q->where('evaluator_id', $v))
            ->when($f['evaluator'] ?? null, fn (Builder $q, $v) => $q->where('evaluator_id', $v))
            ->when($f['teacher'] ?? null, fn (Builder $q, $v) => $q->where('subject_id', $v))
            ->when($f['eval_status'] ?? null, fn (Builder $q, $v) => $q->where('status', $v))
            ->when($f['date_from'] ?? null, fn (Builder $q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($f['date_to'] ?? null, fn (Builder $q, $v) => $q->whereDate('created_at', '<=', $v))
            ->when(isset($f['score_from']) && $f['score_from'] !== null, fn (Builder $q) => $q->where('percentage', '>=', $f['score_from']))
            ->when(isset($f['score_to']) && $f['score_to'] !== null, fn (Builder $q) => $q->where('percentage', '<=', $f['score_to']))
            ->when(($f['has_evidence'] ?? null) === true, fn (Builder $q) => $q->where('evidence_count', '>', 0))
            ->when(($f['has_evidence'] ?? null) === false, fn (Builder $q) => $q->where('evidence_count', '=', 0))
            ->when($f['subject'] ?? null, function (Builder $q, $v) {
                // Subject = the academic subject taught by the evaluated teacher.
                $q->whereHas('subject.subjects', fn (Builder $s) => $s->where('subjects.id', $v));
            })
            ->when($f['specialization'] ?? null, function (Builder $q, $v) {
                $q->whereHas('subject', fn (Builder $s) => $s->where('specialization', $v));
            });
    }

    /** @param array<string,mixed> $f */
    private function applyVisitFilters(Builder $q, array $f): void
    {
        $q->when($f['form'] ?? null, fn (Builder $q, $v) => $q->where('form_id', $v))
            ->when($f['school'] ?? null, fn (Builder $q, $v) => $q->where('school_id', $v))
            ->when($f['supervisor'] ?? null, fn (Builder $q, $v) => $q->where('supervisor_id', $v))
            ->when($f['subject'] ?? null, fn (Builder $q, $v) => $q->where('subject_id', $v))
            ->when($f['stage'] ?? null, fn (Builder $q, $v) => $q->where('stage_id', $v))
            ->when($f['visit_status'] ?? null, fn (Builder $q, $v) => $q->where('status', $v))
            ->when($f['date_from'] ?? null, fn (Builder $q, $v) => $q->whereDate('visit_date', '>=', $v))
            ->when($f['date_to'] ?? null, fn (Builder $q, $v) => $q->whereDate('visit_date', '<=', $v));
    }

    // ---------------------------------------------------------------------
    // Small lookups
    // ---------------------------------------------------------------------

    /** @return array<int,string> id => display name */
    private function resolveUserNames(array $ids): array
    {
        $ids = array_values(array_unique(array_filter($ids)));
        if ($ids === []) {
            return [];
        }

        return \App\Models\User::query()
            ->whereIn('id', $ids)
            ->get(['id', 'name', 'name_ar'])
            ->mapWithKeys(fn ($u) => [$u->id => ($u->name_ar ?: $u->name)])
            ->all();
    }

    /**
     * Map evaluation_id => [user_ids who commented].
     *
     * @return array<int,array<int>>
     */
    private function teacherCommentMap(array $evaluationIds): array
    {
        if ($evaluationIds === []) {
            return [];
        }

        return \App\Models\EvaluationComment::query()
            ->whereIn('evaluation_id', $evaluationIds)
            ->get(['evaluation_id', 'user_id'])
            ->groupBy('evaluation_id')
            ->map(fn ($g) => $g->pluck('user_id')->map(fn ($v) => (int) $v)->unique()->values()->all())
            ->all();
    }

    private function displayName(?object $model): ?string
    {
        if (!$model) {
            return null;
        }

        return $model->name_ar ?: ($model->name ?? null);
    }
}
