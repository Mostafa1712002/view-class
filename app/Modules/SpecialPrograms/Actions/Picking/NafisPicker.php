<?php

namespace App\Modules\SpecialPrograms\Actions\Picking;

use App\Models\User;
use App\Modules\SpecialPrograms\Models\SpExamSubject;

/** D§5.2: reading = N whole passages; other subjects = qiyas logic without term/week. */
class NafisPicker extends QiyasPicker
{
    protected function usesTermAndWeeks(): bool
    {
        return false;
    }

    public function pick(SpExamSubject $subject, User $student): array
    {
        if (! $subject->subject->picks_passages) {
            return parent::pick($subject, $student);
        }
        $rows = $this->pool->candidates((int) $subject->sp_subject_id, [
            'passage_only' => true, 'random' => true, 'grade_levels' => $subject->question_grade_levels ?: null,
        ]);
        $passageIds = $rows->pluck('passage_id')->unique()->take((int) $subject->total_questions)->values()->all();
        $byPassage = $this->pool->passageMembers($passageIds)->groupBy('passage_id');
        $groups = array_map(fn ($pid) => $byPassage[$pid]->pluck('id')->all(), $passageIds);
        shuffle($groups);

        return array_merge(...$groups ?: [[]]);
    }
}
