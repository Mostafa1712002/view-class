<?php

namespace App\Modules\SpecialPrograms\Repositories;

use App\Modules\SpecialPrograms\Models\SpQuestion;
use App\Modules\SpecialPrograms\Repositories\Contracts\QuestionPoolRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EloquentQuestionPoolRepository implements QuestionPoolRepository
{
    public function candidates(int $spSubjectId, array $filters): Collection
    {
        $q = $this->query($spSubjectId, $filters);
        if (! empty($filters['random'])) {
            $q->inRandomOrder();
        } else {
            $q->orderBy('id');
        }
        if (! empty($filters['limit'])) {
            $q->limit((int) $filters['limit']);
        }

        return $q->get();
    }

    public function passageMembers(array $passageIds): Collection
    {
        if (! $passageIds) {
            return collect();
        }

        return SpQuestion::query()->where('is_active', true)->whereIn('passage_id', $passageIds)
            ->orderBy('passage_id')->orderBy('sub_index')->orderBy('id')->get();
    }

    public function stats(int $spSubjectId, array $filters): array
    {
        $base = fn () => $this->query($spSubjectId, array_diff_key($filters, ['order_range' => 1]));
        $inRange = ! empty($filters['order_range'])
            ? $this->query($spSubjectId, $filters)->count()
            : $base()->count();

        return [
            'total' => $base()->count(),
            'by_skill' => $base()->selectRaw('sp_skill_id, count(*) c')->groupBy('sp_skill_id')->pluck('c', 'sp_skill_id')->all(),
            'by_difficulty' => $base()->selectRaw('difficulty, count(*) c')->groupBy('difficulty')->pluck('c', 'difficulty')->all(),
            'by_level' => DB::table('sp_question_levels')
                ->whereIn('sp_question_id', $base()->select('sp_questions.id'))
                ->selectRaw('grade_level, count(*) c')->groupBy('grade_level')->pluck('c', 'grade_level')->all(),
            'max_order' => $this->query($spSubjectId, [])->max('question_order'),
            'in_range' => $inRange,
        ];
    }

    public function snapshot(array $spQuestionIds): array
    {
        if (! $spQuestionIds) {
            return [];
        }
        $rows = DB::table('sp_questions as s')
            ->join('bank_questions as b', 'b.id', '=', 's.bank_question_id')
            ->whereIn('s.id', $spQuestionIds)
            ->get(['s.id', 's.bank_question_id', 's.sp_skill_id', 's.difficulty', 's.passage_id', 's.sub_index',
                'b.body_ar', 'b.body_en', 'b.attachment_path']);
        $answers = DB::table('question_answers')->whereIn('question_id', $rows->pluck('bank_question_id'))
            ->orderBy('sort_order')->orderBy('id')->get()->groupBy('question_id');

        $out = [];
        foreach ($rows as $r) {
            $options = [];
            $correct = null;
            foreach ($answers->get($r->bank_question_id, collect())->values() as $i => $a) {
                $options[] = ['text' => $a->answer_text, 'image' => $a->answer_image];
                if ($a->is_correct && $correct === null) {
                    $correct = $i;
                }
            }
            $out[$r->id] = [
                'bank_question_id' => (int) $r->bank_question_id,
                'stem' => $r->body_ar ?? $r->body_en,
                'image_path' => $r->attachment_path,
                'options' => $options,
                'correct_index' => $correct,
                'sp_skill_id' => $r->sp_skill_id,
                'difficulty' => $r->difficulty,
                'passage_id' => $r->passage_id,
                'sub_index' => (int) $r->sub_index,
            ];
        }

        return $out;
    }

    private function query(int $subjectId, array $f): Builder
    {
        $q = SpQuestion::query()->where('sp_subject_id', $subjectId)->where('is_active', true);
        if (! empty($f['skill_id'])) {
            $q->where('sp_skill_id', $f['skill_id']);
        }
        if (! empty($f['terms'])) {
            $q->whereIn('term', $f['terms']);
        }
        if (! empty($f['weeks'])) {
            $q->whereIn('week', $f['weeks']);
        }
        if (! empty($f['grade_levels'])) {
            $q->whereExists(fn ($s) => $s->from('sp_question_levels')
                ->whereColumn('sp_question_levels.sp_question_id', 'sp_questions.id')
                ->whereIn('grade_level', $f['grade_levels']));
        }
        if (! empty($f['difficulty'])) {
            $q->where('difficulty', $f['difficulty']);
        }
        if (! empty($f['order_range'])) {
            $q->whereBetween('question_order', $f['order_range']);
        }
        if (! empty($f['exclude_ids'])) {
            $q->whereNotIn('id', $f['exclude_ids']);
        }
        if (! empty($f['passage_only'])) {
            $q->whereNotNull('passage_id');
        }
        if (! empty($f['independent_only'])) {
            $q->whereNull('passage_id');
        }

        return $q;
    }
}
