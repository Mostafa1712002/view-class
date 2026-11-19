<?php

namespace App\Modules\SpecialPrograms\Actions\Bank;

use App\Models\BankQuestion;
use App\Modules\QuestionBankCore\Models\Passage;
use App\Modules\SpecialPrograms\Models\SpQuestion;
use App\Modules\SpecialPrograms\Repositories\Contracts\BankRepository;
use Illuminate\Support\Facades\DB;

/** A question already frozen into an attempt is only deactivated; an unused one is deleted (tahsili numbering closes the gap). */
final class DeleteProgramQuestion
{
    public const DELETED = 'deleted';

    public const DEACTIVATED = 'deactivated';

    public function __construct(private BankRepository $bank) {}

    public function question(SpQuestion $sp): string
    {
        return DB::transaction(function () use ($sp) {
            if ($this->bank->usedBankQuestionIds([$sp->bank_question_id])) {
                $sp->update(['is_active' => false]);

                return self::DEACTIVATED;
            }
            $this->purge($sp);

            return self::DELETED;
        });
    }

    /** Whole passage: deactivated when any member was used, else removed with its members. */
    public function passage(Passage $passage): string
    {
        return DB::transaction(function () use ($passage) {
            $members = SpQuestion::where('passage_id', $passage->id)->get();
            if ($this->bank->usedBankQuestionIds($members->pluck('bank_question_id')->all())) {
                SpQuestion::where('passage_id', $passage->id)->update(['is_active' => false]);

                return self::DEACTIVATED;
            }
            $members->each(fn ($m) => $this->purge($m));
            $passage->delete();

            return self::DELETED;
        });
    }

    private function purge(SpQuestion $sp): void
    {
        $order = $sp->question_order;
        $subjectId = $sp->sp_subject_id;
        DB::table('sp_question_levels')->where('sp_question_id', $sp->id)->delete();
        DB::table('passage_questions')->where('question_id', $sp->bank_question_id)->delete();
        $bq = BankQuestion::find($sp->bank_question_id);
        if ($bq) {
            $bq->answers()->delete();
            $bq->delete();
        }
        $sp->delete();
        if ($order !== null) {
            DB::table('sp_questions')->where('sp_subject_id', $subjectId)->whereNull('passage_id')
                ->where('question_order', '>', $order)->decrement('question_order');
        }
    }
}
