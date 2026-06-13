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

        // Whitelist: only persist answers for questions that belong to THIS survey,
        // and coerce each value to what its question type allows (no arbitrary
        // question_id injection, no out-of-range ratings or off-list choices).
        $questions = $survey->questions->keyBy('id');

        foreach ($answers as $questionId => $value) {
            $question = $questions->get((int) $questionId);
            if (! $question) {
                continue; // not a question of this survey — skip
            }

            $clean = $this->sanitizeAnswer($question, $value);
            if ($clean === null) {
                continue;
            }

            SurveyAnswer::create([
                'response_id' => $response->id,
                'question_id' => $question->id,
                'value'       => $clean,
            ]);
        }

        return $response;
    }

    /**
     * Coerce a submitted value to what the question type allows. Returns the
     * storable string, or null to skip an invalid/empty answer.
     */
    private function sanitizeAnswer(SurveyQuestion $question, mixed $value): ?string
    {
        $options = is_array($question->options) ? $question->options : [];

        switch ($question->type) {
            case 'rating':
                $n = (int) $value;
                return ($n >= 1 && $n <= 5) ? (string) $n : null;

            case 'single_choice':
                $v = is_array($value) ? reset($value) : $value;
                return in_array($v, $options, true) ? (string) $v : null;

            case 'multiple_choice':
                $vals = array_values(array_filter(
                    (array) $value,
                    fn ($v) => in_array($v, $options, true)
                ));
                return $vals === [] ? null : json_encode(array_values($vals), JSON_UNESCAPED_UNICODE);

            case 'text':
            default:
                $v = is_array($value) ? '' : trim((string) $value);
                return $v === '' ? null : mb_substr($v, 0, 5000);
        }
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
