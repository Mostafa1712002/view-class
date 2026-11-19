<?php

namespace App\Modules\SpecialPrograms\Repositories\Contracts;

use Illuminate\Support\Collection;

interface QuestionPoolRepository
{
    /**
     * sp_questions rows. Filters: skill_id, terms, weeks, grade_levels, difficulty, order_range,
     * exclude_ids, passage_only, independent_only, limit, random.
     */
    public function candidates(int $spSubjectId, array $filters): Collection;

    /** sp_questions ordered by passage_id, sub_index. */
    public function passageMembers(array $passageIds): Collection;

    /** @return array{total:int,by_skill:array,by_difficulty:array,by_level:array,max_order:?int,in_range:int} */
    public function stats(int $spSubjectId, array $filters): array;

    /**
     * id => [bank_question_id, stem, image_path, options[{text,image}], correct_index,
     *        sp_skill_id, difficulty, passage_id, sub_index] (reads bank_questions + question_answers).
     */
    public function snapshot(array $spQuestionIds): array;
}
