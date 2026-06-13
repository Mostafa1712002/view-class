<?php

namespace App\Modules\Surveys\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Survey;
use App\Modules\Surveys\Http\Requests\StoreSurveyRequest;
use App\Modules\Surveys\Repositories\Contracts\SurveyRepository;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminSurveyController extends Controller
{
    use HasSchoolScope;

    public function __construct(private SurveyRepository $surveys) {}

    public function index(Request $request): View
    {
        $schoolId = $this->activeSchoolId();
        $surveys  = $this->surveys->listForSchool($schoolId);

        return view('surveys.admin.index', compact('surveys'));
    }

    public function create(): View
    {
        return view('surveys.admin.form', [
            'survey'    => null,
            'statuses'  => Survey::STATUSES,
            'audiences' => Survey::AUDIENCES,
        ]);
    }

    public function store(StoreSurveyRequest $request): RedirectResponse
    {
        $schoolId = $this->activeSchoolId();

        abort_if(! auth()->user()->isSuperAdmin() && $schoolId === null, 403);

        $data = $request->safe()->except('questions');
        $data['school_id']  = $schoolId;
        $data['created_by'] = auth()->id();

        $this->surveys->create($data, $request->validated('questions', []));

        return redirect()
            ->route('admin.surveys.index')
            ->with('success', __('surveys.flash.created'));
    }

    public function edit(int $survey): View
    {
        $model = $this->surveys->findWithQuestions($survey);
        abort_if(! $model, 404);
        $this->authorizeSurvey($model);

        return view('surveys.admin.form', [
            'survey'    => $model,
            'statuses'  => Survey::STATUSES,
            'audiences' => Survey::AUDIENCES,
        ]);
    }

    public function update(StoreSurveyRequest $request, int $survey): RedirectResponse
    {
        $model = $this->surveys->find($survey);
        abort_if(! $model, 404);
        $this->authorizeSurvey($model);

        $data = $request->safe()->except('questions');
        $this->surveys->update($model, $data, $request->validated('questions', []));

        return redirect()
            ->route('admin.surveys.index')
            ->with('success', __('surveys.flash.updated'));
    }

    public function publish(int $survey): RedirectResponse
    {
        $model = $this->surveys->find($survey);
        abort_if(! $model, 404);
        $this->authorizeSurvey($model);

        $this->surveys->publish($model);

        return redirect()
            ->route('admin.surveys.index')
            ->with('success', __('surveys.flash.published'));
    }

    public function close(int $survey): RedirectResponse
    {
        $model = $this->surveys->find($survey);
        abort_if(! $model, 404);
        $this->authorizeSurvey($model);

        $this->surveys->close($model);

        return redirect()
            ->route('admin.surveys.index')
            ->with('success', __('surveys.flash.closed'));
    }

    public function results(int $survey): View
    {
        $model = $this->surveys->findWithQuestions($survey);
        abort_if(! $model, 404);
        $this->authorizeSurvey($model);

        $aggregated      = $this->surveys->aggregateResults($model);
        $responsesCount  = $model->responses()->count();

        return view('surveys.admin.results', compact('model', 'aggregated', 'responsesCount'));
    }

    public function destroy(int $survey): RedirectResponse
    {
        $model = $this->surveys->find($survey);
        abort_if(! $model, 404);
        $this->authorizeSurvey($model);

        $this->surveys->delete($model);

        return redirect()
            ->route('admin.surveys.index')
            ->with('success', __('surveys.flash.deleted'));
    }

    // ──────────────────────────────────────────
    // Private helpers
    // ──────────────────────────────────────────

    /**
     * Guard a survey against cross-tenant access. Super-admins may see any
     * survey; school-admins must have a matching concrete school_id.
     */
    private function authorizeSurvey(Survey $survey): void
    {
        $user = auth()->user();
        if ($user->isSuperAdmin()) {
            return;
        }

        $schoolId = $this->activeSchoolId();
        abort_if($schoolId === null, 403);
        abort_unless($survey->school_id === $schoolId, 404);
    }
}
