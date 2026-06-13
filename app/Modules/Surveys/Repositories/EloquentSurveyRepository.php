<?php

namespace App\Modules\Surveys\Repositories;

use App\Models\Survey;
use App\Models\SurveyAnswer;
use App\Models\SurveyQuestion;
use App\Models\SurveyResponse;
use App\Modules\Surveys\Repositories\Contracts\SurveyRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class EloquentSurveyRepository implements SurveyRepository
{
    public function listForSchool(?int $schoolId, int $perPage = 20): LengthAwarePaginator
    {
        return Survey::query()
            ->withCount('responses')
            ->when($schoolId, fn ($w) => $w->where('school_id', $schoolId))
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function find(int $id): ?Survey
    {
        return Survey::find($id);
    }

    public function findWithQuestions(int $id): ?Survey
    {
        return Survey::with(['questions' => fn ($q) => $q->orderBy('sort_order')])->find($id);
    }

    public function create(array $data, array $questions): Survey
    {
        $survey = Survey::create($data);
        $this->syncQuestions($survey, $questions);

        return $survey->load('questions');
    }

    public function update(Survey $survey, array $data, array $questions): Survey
    {
        $survey->update($data);
        // Drop all existing questions and re-insert (simplest sync strategy for a small list).
        $survey->questions()->delete();
        $this->syncQuestions($survey, $questions);

        return $survey->fresh(['questions']);
    }

    public function publish(Survey $survey): Survey
    {
        $survey->update(['status' => 'published']);

        return $survey->fresh();
    }

    public function close(Survey $survey): Survey
    {
        $survey->update(['status' => 'closed']);

        return $survey->fresh();
    }

    public function delete(Survey $survey): void
    {
        $survey->delete();
    }

    public function findResponse(int $surveyId, int $userId): ?SurveyResponse
    {
        return SurveyResponse::where('survey_id', $surveyId)
            ->where('user_id', $userId)
            ->first();
    }

    public function submitResponse(Survey $survey, int $userId, array $answers): SurveyResponse
    {
        $response = SurveyResponse::create([
            'survey_id'    => $survey->id,
            'user_id'      => $userId,
            'submitted_at' => now(),
        ]);

        foreach ($answers as $questionId => $value) {
            SurveyAnswer::create([
                'response_id' => $response->id,
                'question_id' => (int) $questionId,
                'value'       => is_array($value) ? json_encode($value) : $value,
            ]);
        }

        return $response;
    }

    public function publishedForUser(?int $schoolId, string $audience): Collection
    {
        return Survey::query()
            ->published()
            ->when($schoolId, fn ($w) => $w->where('school_id', $schoolId))
            ->where(function ($q) use ($audience) {
                $q->where('audience', 'all')
                  ->orWhere('audience', $audience);
            })
            ->orderByDesc('id')
            ->get();
    }

    public function aggregateResults(Survey $survey): array
    {
        $questions = $survey->questions()->with('answers')->get();
        $result    = [];

        foreach ($questions as $question) {
            $counts = [];
            $texts  = [];

            foreach ($question->answers as $answer) {
                if ($question->type === 'text') {
                    if ($answer->value !== null && $answer->value !== '') {
                        $texts[] = $answer->value;
                    }
                } elseif ($question->type === 'multiple_choice') {
                    $chosen = json_decode($answer->value, true) ?? [];
                    foreach ((array) $chosen as $opt) {
                        $counts[$opt] = ($counts[$opt] ?? 0) + 1;
                    }
                } else {
                    // single_choice, rating
                    if ($answer->value !== null && $answer->value !== '') {
                        $counts[$answer->value] = ($counts[$answer->value] ?? 0) + 1;
                    }
                }
            }

            $result[$question->id] = [
                'question' => $question,
                'counts'   => $counts,
                'texts'    => $texts,
            ];
        }

        return $result;
    }

    // ──────────────────────────────────────────
    // Private helpers
    // ──────────────────────────────────────────

    private function syncQuestions(Survey $survey, array $questions): void
    {
        foreach ($questions as $index => $q) {
            SurveyQuestion::create([
                'survey_id'   => $survey->id,
                'text'        => $q['text'],
                'type'        => $q['type'],
                'options'     => isset($q['options']) && is_array($q['options'])
                    ? array_values(array_filter($q['options'], fn ($o) => $o !== null && $o !== ''))
                    : null,
                'is_required' => (bool) ($q['is_required'] ?? true),
                'sort_order'  => $index,
            ]);
        }
    }
}
