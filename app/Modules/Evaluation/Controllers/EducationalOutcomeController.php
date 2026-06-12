<?php

namespace App\Modules\Evaluation\Controllers;

use App\Http\Controllers\Controller;
use App\Models\EvaluationOutcome;
use App\Modules\Evaluation\Actions\ComputeEducationalOutcome;
use App\Modules\Evaluation\Actions\RecomputeEducationalOutcome;
use App\Modules\Evaluation\Enums\OutcomeApprovalStatus;
use App\Modules\Evaluation\Enums\OutcomeMethod;
use App\Modules\Evaluation\Http\Requests\StoreEvaluationOutcomeRequest;
use App\Modules\Evaluation\Services\EducationalOutcomeResolver;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Phase C (#205) — Educational outcome management.
 *
 * Every query is scoped to the authenticated user's active school_id.
 * Super-admins use the session-selected school via HasSchoolScope::activeSchoolId().
 *
 * TODO Phase D: replace role checks with granular EvaluationOutcomePermissions gates.
 * TODO Phase F: bind outcome records into the live evaluation item scoring pipeline (#206/#207).
 */
class EducationalOutcomeController extends Controller
{
    use HasSchoolScope;

    public function __construct(
        private readonly ComputeEducationalOutcome   $computeAction,
        private readonly RecomputeEducationalOutcome $recomputeAction,
        private readonly EducationalOutcomeResolver  $resolver,
    ) {
    }

    // -----------------------------------------------------------------------
    // Index
    // -----------------------------------------------------------------------

    /** List outcomes for the active school, paginated. */
    public function index(): View
    {
        $schoolId = $this->activeSchoolId();

        $outcomes = EvaluationOutcome::query()
            ->when($schoolId !== null, fn ($q) => $q->where('school_id', $schoolId))
            ->latest()
            ->paginate(20);

        return view('admin.evaluation.outcomes.index', compact('outcomes'));
    }

    // -----------------------------------------------------------------------
    // Settings
    // -----------------------------------------------------------------------

    /** Show the current outcome method setting for this school and the global default. */
    public function settings(): View
    {
        $schoolId      = $this->activeSchoolId();
        $schoolMethod  = $schoolId ? \App\Models\Setting::get('eval.outcome_method', null, $schoolId) : null;
        $globalMethod  = \App\Models\Setting::get('eval.outcome_method', null, null);
        $effectiveMethod = $this->resolver->methodFor($schoolId);

        return view('admin.evaluation.outcomes.settings', compact(
            'schoolId',
            'schoolMethod',
            'globalMethod',
            'effectiveMethod',
        ));
    }

    /** Save the school-level outcome method. Super-admins may also set the global default. */
    public function updateSettings(Request $request): RedirectResponse
    {
        $validMethods = array_column(OutcomeMethod::cases(), 'value');

        $data = $request->validate([
            'school_method'  => ['nullable', Rule::in($validMethods)],
            'global_method'  => ['nullable', Rule::in($validMethods)],
        ]);

        $schoolId = $this->activeSchoolId();

        if ($schoolId && !empty($data['school_method'])) {
            $this->resolver->setMethod($schoolId, $data['school_method']);
        }

        // Only super-admins may change the global default.
        if (auth()->user()?->isSuperAdmin() && !empty($data['global_method'])) {
            $this->resolver->setGlobalMethod($data['global_method']);
        }

        return redirect()->route('admin.evaluations.outcomes.settings')
            ->with('status', __('evaluation_outcomes.flash.settings_saved'));
    }

    // -----------------------------------------------------------------------
    // Create / Store
    // -----------------------------------------------------------------------

    /** Show the create outcome form. */
    public function create(): View
    {
        $methods = OutcomeMethod::options();

        return view('admin.evaluation.outcomes.create', compact('methods'));
    }

    /** Compute and persist a new educational outcome. */
    public function store(StoreEvaluationOutcomeRequest $request): RedirectResponse
    {
        $schoolId = $this->activeSchoolId();

        $data = $request->validated();
        $data['school_id'] = $schoolId;

        try {
            $outcome = $this->computeAction->execute($data, auth()->user());
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        return redirect()->route('admin.evaluations.outcomes.show', $outcome->id)
            ->with('status', __('evaluation_outcomes.flash.created'));
    }

    // -----------------------------------------------------------------------
    // Show
    // -----------------------------------------------------------------------

    /** Detail view for a single outcome (school-scoped). */
    public function show(EvaluationOutcome $outcome): View
    {
        $schoolId = $this->activeSchoolId();
        if ($schoolId !== null && (int) $outcome->school_id !== (int) $schoolId) {
            abort(403);
        }

        $methods = OutcomeMethod::options();

        return view('admin.evaluation.outcomes.show', compact('outcome', 'methods'));
    }

    // -----------------------------------------------------------------------
    // Recompute
    // -----------------------------------------------------------------------

    /** Re-run the calculation, optionally switching the averaging method. */
    public function recompute(Request $request, EvaluationOutcome $outcome): RedirectResponse
    {
        $schoolId = $this->activeSchoolId();
        if ($schoolId !== null && (int) $outcome->school_id !== (int) $schoolId) {
            abort(403);
        }

        $validMethods = array_column(OutcomeMethod::cases(), 'value');

        $data = $request->validate([
            'method' => ['nullable', Rule::in($validMethods)],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $this->recomputeAction->execute(
                $outcome,
                $data['method'] ?? null,
                $data['reason'] ?? null,
                auth()->user(),
            );
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return redirect()->route('admin.evaluations.outcomes.show', $outcome->id)
            ->with('status', __('evaluation_outcomes.flash.recomputed'));
    }
}
