<?php

namespace App\Modules\Evaluation\Controllers;

use App\Http\Controllers\Controller;
use App\Models\EvaluationForm;
use App\Modules\Evaluation\Enums\FormStatus;
use App\Modules\Evaluation\Enums\FormType;
use App\Modules\Evaluation\Enums\UsageDomain;
use App\Modules\Evaluation\Repositories\Contracts\EvaluationFormRepository;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EvaluationFormController extends Controller
{
    use HasSchoolScope;

    public function __construct(private readonly EvaluationFormRepository $forms)
    {
    }

    /** Task 1 — evaluation forms management list. */
    public function index(Request $request): View
    {
        $schoolId = $this->activeSchoolId();

        $filters = [
            'type'                     => $request->string('type')->toString() ?: null,
            'usage_domain'             => $request->string('usage_domain')->toString() ?: null,
            'status'                   => $request->string('status')->toString() ?: null,
            'is_class_visit_only'      => $this->ternary($request->input('is_class_visit_only')),
            'links_to_job_performance' => $this->ternary($request->input('links_to_job_performance')),
            'created_from'             => $request->date('created_from')?->toDateString(),
            'created_to'               => $request->date('created_to')?->toDateString(),
            'search'                   => $request->string('q')->toString() ?: null,
        ];

        $forms = $this->forms->paginate($schoolId, array_filter($filters, fn ($v) => $v !== null), 25);

        return view('admin.evaluation.forms.index', [
            'forms'   => $forms,
            'filters' => $filters,
            'stats'   => $this->stats($schoolId),
            'types'   => FormType::options(),
            'domains' => UsageDomain::options(),
            'statuses'=> FormStatus::options(),
        ]);
    }

    /** KPI tiles: counts by status within scope. */
    private function stats(?int $schoolId): array
    {
        $base = fn (): Builder => EvaluationForm::query()->when(
            $schoolId !== null,
            fn (Builder $q) => $q->where(fn (Builder $w) => $w->where('school_id', $schoolId)->orWhereNull('school_id'))
        );

        return [
            'total'     => (clone $base())->count(),
            'published' => (clone $base())->where('status', FormStatus::Published->value)->count(),
            'draft'     => (clone $base())->where('status', FormStatus::Draft->value)->count(),
            'closed'    => (clone $base())->where('status', FormStatus::Closed->value)->count(),
        ];
    }

    /** Tri-state filter: '' / '1' / '0' -> null / true / false. */
    private function ternary(mixed $v): ?bool
    {
        if ($v === null || $v === '') {
            return null;
        }

        return (bool) $v;
    }
}
