<?php

namespace App\Modules\Surveys\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Survey;
use App\Modules\Surveys\Http\Requests\StoreSurveyResponseRequest;
use App\Modules\Surveys\Repositories\Contracts\SurveyRepository;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class MySurveyController extends Controller
{
    use HasSchoolScope;

    public function __construct(private SurveyRepository $surveys) {}

    public function index(): View
    {
        $user     = auth()->user();
        $schoolId = $user->school_id;
        $audience = $this->resolveAudience($user);

        $allSurveys = $this->surveys->publishedForUser($schoolId, $audience);

        // Partition: answered vs pending
        $answeredIds = \App\Models\SurveyResponse::where('user_id', $user->id)
            ->whereIn('survey_id', $allSurveys->pluck('id'))
            ->pluck('survey_id')
            ->toArray();

        $pending  = $allSurveys->whereNotIn('id', $answeredIds)->values();
        $answered = $allSurveys->whereIn('id', $answeredIds)->values();

        return view('surveys.my.index', compact('pending', 'answered'));
    }

    public function show(int $survey): View
    {
        $model = $this->surveys->findWithQuestions($survey);
        abort_if(! $model, 404);

        $user = auth()->user();
        $this->assertCanTake($model, $user);

        return view('surveys.my.show', compact('model'));
    }

    public function submit(StoreSurveyResponseRequest $request, int $survey): RedirectResponse
    {
        $model = $this->surveys->findWithQuestions($survey);
        abort_if(! $model, 404);

        $user = auth()->user();
        $this->assertCanTake($model, $user);

        // Validate required questions are answered
        $answers = $request->input('answers', []);
        foreach ($model->questions as $question) {
            if ($question->is_required) {
                $val = $answers[$question->id] ?? null;
                if ($val === null || $val === '' || (is_array($val) && count(array_filter($val)) === 0)) {
                    return back()
                        ->withInput()
                        ->withErrors(['answers.' . $question->id => __('surveys.validation.required_question')]);
                }
            }
        }

        $this->surveys->submitResponse($model, $user->id, $answers);

        return redirect()
            ->route('my.surveys.index')
            ->with('success', __('surveys.flash.submitted'));
    }

    // ──────────────────────────────────────────
    // Private helpers
    // ──────────────────────────────────────────

    private function assertCanTake(Survey $survey, \App\Models\User $user): void
    {
        // Tenant scope: a survey belongs to its school. Only that school's users
        // (or anyone, for a global/null-school survey) may take it. No cross-tenant.
        abort_unless($survey->school_id === null || $survey->school_id === $user->school_id, 404);

        // Must be published
        abort_unless($survey->status === 'published', 404);

        // Must be within date window (if set)
        $today = now()->toDateString();
        if ($survey->starts_at && $survey->starts_at->toDateString() > $today) {
            abort(404);
        }
        if ($survey->ends_at && $survey->ends_at->toDateString() < $today) {
            abort(404);
        }

        // Audience must match
        abort_unless($survey->isForAudience($user), 403);

        // Must not have already answered
        $existing = $this->surveys->findResponse($survey->id, $user->id);
        abort_if($existing !== null, 403);
    }

    private function resolveAudience(\App\Models\User $user): string
    {
        if ($user->isStudent()) {
            return 'students';
        }
        if ($user->isParent()) {
            return 'parents';
        }
        if ($user->isTeacher()) {
            return 'teachers';
        }
        return 'all';
    }
}
