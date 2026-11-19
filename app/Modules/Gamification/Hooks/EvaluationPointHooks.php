<?php

namespace App\Modules\Gamification\Hooks;

use App\Models\AcademicYear;
use App\Models\ClassVisit;
use App\Models\Evaluation;
use App\Models\User;
use App\Modules\Evaluation\Enums\EvaluationStatus;
use App\Modules\Evaluation\Enums\UsageDomain;
use App\Modules\Gamification\Actions\RevokeSourcePoints;
use App\Modules\Gamification\Actions\SyncActivityPoints;
use App\Modules\Gamification\DTOs\ActivityPoints;
use App\Modules\Gamification\Models\PointTransaction;
use App\Modules\Gamification\Support\RuleSettings;
use Illuminate\Support\Carbon;

/** Vice points for job-performance evaluations (on time) and class visits (per-term cap). */
final class EvaluationPointHooks
{
    private const JOB = 'vice.job_performance_on_time';

    private const VISITS = 'vice.class_visits';

    public function __construct(private SyncActivityPoints $sync, private RevokeSourcePoints $revoke, private RuleSettings $settings) {}

    /** After an evaluation was submitted / reopened / rejected: re-sync both keys (a reopen/reject drops them to 0). */
    public function evaluationChanged(Evaluation $evaluation): void
    {
        HostSafe::run(function () use ($evaluation) {
            $evaluation->loadMissing('form', 'classVisit');
            $submitted = $this->isSubmitted($evaluation);

            if ($evaluation->evaluator_id && $evaluation->form?->usage_domain === UsageDomain::JobPerformance) {
                $close = $evaluation->form->close_date;
                // no close_date = no deadline = on time; close_date is a date (00:00), so the whole closing day counts.
                $onTime = $submitted && (! $close || $evaluation->submitted_at->lte($close->copy()->endOfDay()));
                $this->send(self::JOB, (int) $evaluation->evaluator_id, 'evaluation', $evaluation->id, $onTime ? 1.0 : 0.0);
            }

            if ($visit = $evaluation->classVisit) {
                $this->send(self::VISITS, (int) $visit->supervisor_id, 'class_visit', $visit->id,
                    $submitted && $this->visitCounts($visit) ? 1.0 : 0.0);
            }
        });
    }

    /** Call AFTER the delete succeeded. */
    public function classVisitDeleted(int $visitId): void
    {
        HostSafe::run(function () use ($visitId) {
            $this->revoke->execute('class_visit', $visitId, 'حذف الزيارة الصفية');
        });
    }

    private function send(string $rule, int $userId, string $type, int $id, float $units): void
    {
        $this->sync->execute(new ActivityPoints($rule, $userId, $type, $id, $units, auth()->id()));
    }

    private function isSubmitted(Evaluation $e): bool
    {
        return $e->submitted_at !== null
            && ! in_array($e->status, [EvaluationStatus::Draft, EvaluationStatus::Rejected], true);
    }

    /** Visit date inside the window and the supervisor's awarded visits this window below the cap. */
    private function visitCounts(ClassVisit $visit): bool
    {
        [$start, $end] = $this->window($visit);
        $date = $visit->visit_date?->toDateString();
        if (! $date || ! $start || ! $end || $date < $start || $date > $end) {
            return false;
        }

        $cap = $this->settings->effective(self::VISITS, (int) $visit->school_id)['options']['visits_per_term'] ?? null;
        if ($cap === null) {
            return true;
        }

        $mine = PointTransaction::where('rule_key', self::VISITS)->where('user_id', $visit->supervisor_id)
            ->where('source_type', 'class_visit')->where('source_id', $visit->id)->sum('points');
        if ($mine > 0) {
            return true; // already awarded: keeps following its source
        }

        $ids = ClassVisit::where('supervisor_id', $visit->supervisor_id)->where('id', '!=', $visit->id)
            ->whereBetween('visit_date', [$start, $end])->pluck('id');
        $awarded = PointTransaction::where('rule_key', self::VISITS)->where('user_id', $visit->supervisor_id)
            ->where('source_type', 'class_visit')->whereIn('source_id', $ids)
            ->groupBy('source_id')->havingRaw('sum(points) > 0')->selectRaw('source_id')->get()->count();

        return $awarded < (int) $cap;
    }

    /** @return array{0:?string,1:?string} options window, else the term (current academic year) holding the visit date */
    private function window(ClassVisit $visit): array
    {
        $o = $this->settings->effective(self::VISITS, (int) $visit->school_id)['options'] ?? [];
        if (! empty($o['window_start']) && ! empty($o['window_end'])) {
            return [Carbon::parse($o['window_start'])->toDateString(), Carbon::parse($o['window_end'])->toDateString()];
        }

        $date = $visit->visit_date?->toDateString();
        $term = AcademicYear::forSchool((int) $visit->school_id)->current()->first()?->terms()
            ->whereDate('start_date', '<=', $date)->whereDate('end_date', '>=', $date)->first();

        return [$term?->start_date?->toDateString(), $term?->end_date?->toDateString()];
    }
}
