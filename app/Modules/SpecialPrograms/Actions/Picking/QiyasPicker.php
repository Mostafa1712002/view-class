<?php

namespace App\Modules\SpecialPrograms\Actions\Picking;

use App\Models\User;
use App\Modules\SpecialPrograms\Models\SpExamSubject;
use App\Modules\SpecialPrograms\Repositories\Contracts\QuestionPoolRepository;

/** D§5.1. NafisPicker reuses it with the term/week filters switched off. */
class QiyasPicker implements QuestionPicker
{
    /** Passage groups taken so far: list of sp_question id lists. */
    protected array $groups = [];

    protected array $usedIds = [];

    public function __construct(protected QuestionPoolRepository $pool) {}

    protected function usesTermAndWeeks(): bool
    {
        return true;
    }

    public function pick(SpExamSubject $subject, User $student): array
    {
        $this->groups = [];
        $this->usedIds = [];
        $n = (int) $subject->total_questions;
        $sid = (int) $subject->sp_subject_id;
        $levels = $subject->question_grade_levels ?: null;
        $terms = $this->usesTermAndWeeks() && $subject->term ? [$subject->term, 'both'] : null;
        $weeks = $this->usesTermAndWeeks() ? ($subject->weeks ?: null) : null;
        $base = ['terms' => $terms, 'grade_levels' => $levels];

        $shares = $subject->skillShares()->pluck('percentage', 'sp_skill_id')->all();
        if (! $shares) {
            $shares = $this->pool->candidates($sid, $base)->pluck('sp_skill_id')->filter()->unique()
                ->mapWithKeys(fn ($id) => [$id => 1])->all();
        }
        $counts = Apportion::bySkill($shares, $n);

        foreach ($counts as $skillId => $need) {
            $need -= $this->take($sid, $need, $base + ['weeks' => $weeks, 'skill_id' => $skillId]);
            if ($need > 0) { // shortfall ladder, D§5.1 step 4
                $need -= $this->take($sid, $need, $base + ['skill_id' => $skillId]);
            }
        }
        $short = $n - $this->count();
        foreach ([$base, ['terms' => $terms], []] as $filters) {
            if ($short <= 0) {
                break;
            }
            $short -= $this->take($sid, $short, $filters);
        }

        $groups = $this->groups;
        shuffle($groups);

        return array_merge(...$groups ?: [[]]);
    }

    protected function count(): int
    {
        return array_sum(array_map('count', $this->groups));
    }

    /** Draws random not-yet-used items; a passage member brings its whole passage. Returns items added. */
    protected function take(int $sid, int $need, array $filters): int
    {
        if ($need <= 0) {
            return 0;
        }
        $added = 0;
        $rows = $this->pool->candidates($sid, $filters + ['random' => true, 'exclude_ids' => $this->usedIds]);
        foreach ($rows as $row) {
            if ($added >= $need) {
                break;
            }
            if (in_array($row->id, $this->usedIds, true)) {
                continue;
            }
            $ids = $row->passage_id
                ? $this->pool->passageMembers([$row->passage_id])->pluck('id')->all()
                : [$row->id];
            $this->groups[] = $ids;
            $this->usedIds = array_merge($this->usedIds, $ids);
            $added += count($ids);
        }

        return $added;
    }
}
