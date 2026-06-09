<?php

namespace App\Modules\Evaluation\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use App\Modules\Evaluation\Services\AuditTrail;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Sprint 8 P7 — سجل العمليات (Audit Log) read screen.
 *
 * Lists `activity_logs` rows whose action is namespaced under the evaluation
 * module ("evaluation.*"). Scoped to the active school (super-admin sees all).
 * Filters: user, action type, date range, free-text search over description /
 * affected model. Read-only — the engine writes these via AuditTrail.
 */
class EvaluationAuditController extends Controller
{
    use HasSchoolScope;

    public function index(Request $request): View
    {
        $schoolId = $this->activeSchoolId();

        $filters = [
            'user'      => $request->integer('user') ?: null,
            'action'    => $request->string('action')->toString() ?: null,
            'date_from' => $request->date('date_from')?->toDateString(),
            'date_to'   => $request->date('date_to')?->toDateString(),
            'search'    => trim((string) $request->string('search')->toString()) ?: null,
        ];

        $logs = $this->baseQuery($schoolId, $filters)
            ->with('user.roles:id,name,slug')
            ->latest('id')
            ->paginate(30)
            ->withQueryString();

        return view('admin.evaluation.audit.index', [
            'logs'          => $logs,
            'filters'       => $filters,
            'actionOptions' => $this->actionOptions($schoolId),
            'userOptions'   => $this->userOptions($schoolId),
        ]);
    }

    /** Evaluation-namespaced rows, school-scoped, with the filter set applied. */
    private function baseQuery(?int $schoolId, array $filters): Builder
    {
        return ActivityLog::query()
            ->where('action', 'like', AuditTrail::PREFIX.'%')
            ->when($schoolId !== null, fn ($q) => $q->where('school_id', $schoolId))
            ->when($filters['user'], fn ($q, $v) => $q->where('user_id', $v))
            ->when($filters['action'], fn ($q, $v) => $q->where('action', $v))
            ->when($filters['date_from'], fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($filters['date_to'], fn ($q, $v) => $q->whereDate('created_at', '<=', $v))
            ->when($filters['search'], function ($q, $v) {
                $q->where(function ($w) use ($v) {
                    $w->where('description', 'like', '%'.$v.'%')
                        ->orWhere('model_type', 'like', '%'.$v.'%');
                });
            });
    }

    /**
     * Distinct evaluation action values present in the (school-scoped) log, so the
     * filter dropdown never drifts from what the engine actually records.
     *
     * @return array<string,string> action value => human label
     */
    private function actionOptions(?int $schoolId): array
    {
        return ActivityLog::query()
            ->where('action', 'like', AuditTrail::PREFIX.'%')
            ->when($schoolId !== null, fn ($q) => $q->where('school_id', $schoolId))
            ->distinct()
            ->orderBy('action')
            ->pluck('action')
            ->mapWithKeys(fn (string $a) => [$a => $this->actionLabel($a)])
            ->all();
    }

    /** Strip the module prefix for a readable dropdown label. */
    private function actionLabel(string $action): string
    {
        return ucwords(str_replace(['evaluation.', '.', '_'], ['', ' ', ' '], $action));
    }

    /** Users who appear as actors in the scoped evaluation log. */
    private function userOptions(?int $schoolId): \Illuminate\Support\Collection
    {
        $userIds = ActivityLog::query()
            ->where('action', 'like', AuditTrail::PREFIX.'%')
            ->when($schoolId !== null, fn ($q) => $q->where('school_id', $schoolId))
            ->whereNotNull('user_id')
            ->distinct()
            ->pluck('user_id');

        return User::query()
            ->whereIn('id', $userIds)
            ->orderBy('name')
            ->get(['id', 'name', 'name_ar']);
    }
}
