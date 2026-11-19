<?php

namespace App\Modules\SpecialPrograms\Repositories\Contracts;

use App\Models\BankQuestion;
use App\Models\User;
use App\Modules\QuestionBankCore\Models\Passage;
use App\Modules\SpecialPrograms\Models\SpQuestion;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/** Program question bank reads (T3.3, only consumer). Writes live in M/Actions/Bank. */
interface BankRepository
{
    /** Subjects of the program the user may maintain: super-admin all; others via GroupRepository::teacherSubjectIds. */
    public function subjectsFor(User $user, string $program): Collection;

    public function skills(int $subjectId): Collection;

    public function subjectIdOfQuestion(int $spQuestionId): ?int;

    public function subjectIdOfPassage(int $passageId): ?int;

    /**
     * Independent questions. Filters: skill_id, term, week, difficulty, grade_level, q (tashkeel-insensitive),
     * status (active|inactive), order_from, order_to.
     */
    public function questions(int $subjectId, array $filters, int $perPage = 25): LengthAwarePaginator;

    /** One row per passage with item count. Same filters as {@see questions()} (q searches the passage text). */
    public function passages(int $subjectId, array $filters, int $perPage = 25): LengthAwarePaginator;

    public function findQuestion(int $id): ?SpQuestion;

    /** @return array{bank:BankQuestion, answers:Collection, levels:int[], skill:?string} */
    public function questionDetail(SpQuestion $question): array;

    public function findPassage(int $id): ?Passage;

    /** Passage members ordered by sub_index, each with detail() keys. @return Collection<int,array> */
    public function passageMembers(Passage $passage): Collection;

    /** @return int[] levels shared by the passage members */
    public function passageLevels(Passage $passage): array;

    /** Which of the given bank_question ids appear in attempt snapshots. @return int[] */
    public function usedBankQuestionIds(array $bankQuestionIds): array;
}
