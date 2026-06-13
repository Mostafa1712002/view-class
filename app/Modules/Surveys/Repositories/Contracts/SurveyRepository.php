<?php

namespace App\Modules\Surveys\Repositories\Contracts;

use App\Models\Survey;
use App\Models\SurveyResponse;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface SurveyRepository
{
    /**
     * Paginated list of surveys scoped to a school.
     */
    public function listForSchool(?int $schoolId, int $perPage = 20): LengthAwarePaginator;

    /**
     * Find a survey by ID (no scope — caller applies scope checks).
     */
    public function find(int $id): ?Survey;

    /**
     * Find a survey with its questions.
     */
    public function findWithQuestions(int $id): ?Survey;

    /**
     * Create a survey and its nested questions.
     *
     * @param array<string,mixed> $data      Survey fields
     * @param array<int,array>    $questions Array of question payloads
     */
    public function create(array $data, array $questions): Survey;

    /**
     * Update a survey and sync its questions.
     *
     * @param array<int,array> $questions
     */
    public function update(Survey $survey, array $data, array $questions): Survey;

    /**
     * Publish a survey (set status=published).
     */
    public function publish(Survey $survey): Survey;

    /**
     * Close a survey (set status=closed).
     */
    public function close(Survey $survey): Survey;

    /**
     * Soft-delete a survey (cascades to questions via DB).
     */
    public function delete(Survey $survey): void;

    /**
     * Find a response for a given survey/user pair.
     */
    public function findResponse(int $surveyId, int $userId): ?SurveyResponse;

    /**
     * Record a completed response with answers.
     *
     * @param array<int,string|null> $answers question_id => raw value
     */
    public function submitResponse(Survey $survey, int $userId, array $answers): SurveyResponse;

    /**
     * Published surveys visible to a user based on their role.
     * Returns ALL such surveys (not paginated) for the my/index listing.
     */
    public function publishedForUser(?int $schoolId, string $audience): Collection;

    /**
     * Aggregate answers for each question of a survey.
     * Returns [ question_id => [ 'question' => SurveyQuestion, 'counts' => [], 'texts' => [] ] ]
     */
    public function aggregateResults(Survey $survey): array;
}
