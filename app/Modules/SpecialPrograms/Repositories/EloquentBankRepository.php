<?php

namespace App\Modules\SpecialPrograms\Repositories;

use App\Models\BankQuestion;
use App\Models\User;
use App\Modules\QuestionBankCore\Models\Passage;
use App\Modules\SpecialPrograms\Actions\Import\DedupeKey;
use App\Modules\SpecialPrograms\Models\SpQuestion;
use App\Modules\SpecialPrograms\Models\SpSkill;
use App\Modules\SpecialPrograms\Models\SpSubject;
use App\Modules\SpecialPrograms\Repositories\Contracts\BankRepository;
use App\Modules\SpecialPrograms\Repositories\Contracts\GroupRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class EloquentBankRepository implements BankRepository
{
    /** tashkeel + tatweel code points removed on the SQL side so a plain search matches vowelled text. */
    private const STRIP = ["\u{064B}", "\u{064C}", "\u{064D}", "\u{064E}", "\u{064F}", "\u{0650}", "\u{0651}", "\u{0652}", "\u{0670}", "\u{0640}"];

    public function __construct(private GroupRepository $groups) {}

    public function subjectsFor(User $user, string $program): Collection
    {
        $all = SpSubject::where('program', $program)->orderBy('sort_order')->orderBy('id')->get();
        if ($user->isSuperAdmin()) {
            return $all;
        }
        $ids = $this->groups->teacherSubjectIds((int) $user->school_id, (int) $user->id, $program);

        return $ids === null ? $all : $all->whereIn('id', $ids)->values();
    }

    public function skills(int $subjectId): Collection
    {
        return SpSkill::where('sp_subject_id', $subjectId)->orderBy('sort_order')->orderBy('id')->get();
    }

    public function subjectIdOfQuestion(int $spQuestionId): ?int
    {
        $id = SpQuestion::whereKey($spQuestionId)->value('sp_subject_id');

        return $id === null ? null : (int) $id;
    }

    public function subjectIdOfPassage(int $passageId): ?int
    {
        $bankId = Passage::whereKey($passageId)->value('question_bank_id');
        $id = $bankId === null ? null : SpSubject::where('question_bank_id', $bankId)->value('id');

        return $id === null ? null : (int) $id;
    }

    public function questions(int $subjectId, array $f, int $perPage = 25): LengthAwarePaginator
    {
        $q = DB::table('sp_questions as s')
            ->join('bank_questions as b', 'b.id', '=', 's.bank_question_id')
            ->leftJoin('sp_skills as k', 'k.id', '=', 's.sp_skill_id')
            ->whereNull('b.deleted_at')->whereNull('s.passage_id')->where('s.sp_subject_id', $subjectId)
            ->select('s.id', 's.sp_skill_id', 's.difficulty', 's.term', 's.week', 's.question_order', 's.has_image', 's.is_active',
                'b.body_ar', 'b.attachment_path', 'k.name as skill_name');
        $this->filter($q, $f, 'b.body_ar');
        if (! empty($f['order_from'])) {
            $q->where('s.question_order', '>=', (int) $f['order_from']);
        }
        if (! empty($f['order_to'])) {
            $q->where('s.question_order', '<=', (int) $f['order_to']);
        }

        return $q->orderBy('s.question_order')->orderByDesc('s.id')->paginate($perPage)->withQueryString();
    }

    public function passages(int $subjectId, array $f, int $perPage = 25): LengthAwarePaginator
    {
        $q = DB::table('passages as p')
            ->join('sp_questions as s', 's.passage_id', '=', 'p.id')
            ->whereNull('p.deleted_at')->where('s.sp_subject_id', $subjectId)
            ->groupBy('p.id', 'p.passage_code', 'p.passage_text')
            ->select('p.id', 'p.passage_code', 'p.passage_text')
            ->selectRaw('count(s.id) as items, min(s.term) as term, min(s.week) as week, min(s.difficulty) as difficulty, max(s.is_active) as is_active');
        $this->filter($q, $f, 'p.passage_text');

        return $q->orderByDesc('p.id')->paginate($perPage)->withQueryString();
    }

    public function findQuestion(int $id): ?SpQuestion
    {
        return SpQuestion::find($id);
    }

    public function questionDetail(SpQuestion $question): array
    {
        $bank = BankQuestion::findOrFail($question->bank_question_id);

        return [
            'sp' => $question,
            'bank' => $bank,
            'answers' => $bank->answers()->orderBy('sort_order')->orderBy('id')->get(),
            'levels' => $this->levelsOf([$question->id]),
            'skill' => $question->sp_skill_id ? SpSkill::whereKey($question->sp_skill_id)->value('name') : null,
        ];
    }

    public function findPassage(int $id): ?Passage
    {
        return Passage::find($id);
    }

    public function passageMembers(Passage $passage): Collection
    {
        return SpQuestion::where('passage_id', $passage->id)->orderBy('sub_index')->orderBy('id')->get()
            ->filter(fn ($sp) => BankQuestion::whereKey($sp->bank_question_id)->exists())
            ->map(fn ($sp) => $this->questionDetail($sp))->values();
    }

    public function passageLevels(Passage $passage): array
    {
        return $this->levelsOf(SpQuestion::where('passage_id', $passage->id)->pluck('id')->all());
    }

    public function usedBankQuestionIds(array $bankQuestionIds): array
    {
        if (! $bankQuestionIds) {
            return [];
        }

        return DB::table('sp_attempt_items')->whereIn('bank_question_id', $bankQuestionIds)
            ->distinct()->pluck('bank_question_id')->map(fn ($v) => (int) $v)->all();
    }

    public function activeImportedItems(string $program): array
    {
        return DB::table('sp_questions as s')
            ->join('sp_subjects as sub', 'sub.id', '=', 's.sp_subject_id')
            ->join('bank_questions as b', 'b.id', '=', 's.bank_question_id')
            ->where('sub.program', $program)->where('s.is_active', true)
            ->where('b.external_platform', 'al_awwal')->whereNull('b.deleted_at')
            ->pluck('b.external_id', 's.id')->map(fn ($v) => (string) $v)->all();
    }

    public function deactivate(array $spQuestionIds): void
    {
        foreach (array_chunk($spQuestionIds, 500) as $chunk) {
            SpQuestion::whereIn('id', $chunk)->update(['is_active' => false]);
        }
    }

    /** @return int[] */
    private function levelsOf(array $spIds): array
    {
        return DB::table('sp_question_levels')->whereIn('sp_question_id', $spIds)->distinct()->orderBy('grade_level')
            ->pluck('grade_level')->map(fn ($v) => (int) $v)->all();
    }

    private function filter(Builder $q, array $f, string $textColumn): void
    {
        foreach (['skill_id' => 's.sp_skill_id', 'term' => 's.term', 'difficulty' => 's.difficulty', 'week' => 's.week'] as $key => $col) {
            if (($f[$key] ?? '') !== '' && $f[$key] !== null) {
                $q->where($col, $f[$key]);
            }
        }
        if (! empty($f['grade_level'])) {
            $q->whereExists(fn ($s) => $s->from('sp_question_levels as l')->whereColumn('l.sp_question_id', 's.id')
                ->where('l.grade_level', (int) $f['grade_level']));
        }
        if (($f['status'] ?? '') === 'active') {
            $q->where('s.is_active', true);
        } elseif (($f['status'] ?? '') === 'inactive') {
            $q->where('s.is_active', false);
        }
        $term = DedupeKey::text($f['q'] ?? '');
        if ($term !== '') {
            $expr = $textColumn;
            foreach (self::STRIP as $_) {
                $expr = "REPLACE({$expr}, ?, '')";
            }
            $like = '%'.addcslashes($term, '%_\\').'%';
            $q->whereRaw("{$expr} LIKE ?", [...self::STRIP, $like]);
        }
    }
}
