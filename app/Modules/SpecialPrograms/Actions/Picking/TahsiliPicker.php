<?php

namespace App\Modules\SpecialPrograms\Actions\Picking;

use App\Models\User;
use App\Modules\SpecialPrograms\Models\SpExamSubject;
use App\Modules\SpecialPrograms\Repositories\Contracts\QuestionPoolRepository;

/** D§5.3: difficulty buckets inside a question_order range; a short range yields fewer items. */
class TahsiliPicker implements QuestionPicker
{
    public function __construct(private QuestionPoolRepository $pool) {}

    public function pick(SpExamSubject $subject, User $student): array
    {
        $n = (int) $subject->total_questions;
        $sid = (int) $subject->sp_subject_id;
        $range = $subject->range_start !== null && $subject->range_end !== null
            ? [(int) $subject->range_start, (int) $subject->range_end] : null;
        $base = ['order_range' => $range, 'random' => true];

        $easy = (int) round($subject->easy_pct * $n / 100);
        $medium = (int) round($subject->medium_pct * $n / 100);
        $hard = max(0, $n - $easy - $medium);

        $ids = [];
        foreach (['easy' => $easy, 'medium' => $medium, 'hard' => $hard] as $difficulty => $count) {
            if ($count <= 0) {
                continue;
            }
            $ids = array_merge($ids, $this->pool->candidates($sid, $base + [
                'difficulty' => $difficulty, 'limit' => $count, 'exclude_ids' => $ids,
            ])->pluck('id')->all());
        }
        if (count($ids) < $n) {
            $ids = array_merge($ids, $this->pool->candidates($sid, $base + [
                'limit' => $n - count($ids), 'exclude_ids' => $ids,
            ])->pluck('id')->all());
        }
        shuffle($ids);

        return $ids;
    }
}
