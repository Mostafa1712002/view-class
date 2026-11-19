<?php

namespace App\Modules\SpecialPrograms\Actions\Bank;

use App\Modules\QuestionBankCore\Models\Passage;
use App\Modules\SpecialPrograms\Models\SpQuestion;
use App\Modules\SpecialPrograms\Models\SpSubject;
use Illuminate\Support\Facades\DB;

/**
 * Saves a passage and its sub-questions (each one a normal program question with passage_id + sub_index).
 *
 * $data: passage_text, term, week?, difficulty, grade_levels[], sp_skill_id? (passage-wide default),
 * subs[] = {id?, body_ar, options_ar, correct_index, sp_skill_id?}. Existing sub-questions missing from `subs` are removed.
 */
final class SaveProgramPassage
{
    private const DIFFICULTY = ['easy' => 1, 'medium' => 2, 'hard' => 3];

    public function __construct(private SaveProgramQuestion $saveQuestion, private DeleteProgramQuestion $delete) {}

    public function execute(SpSubject $subject, array $data, ?Passage $existing = null): Passage
    {
        $bank = $this->saveQuestion->bank($subject);

        return DB::transaction(function () use ($subject, $bank, $data, $existing) {
            $fields = [
                'passage_text' => trim($data['passage_text']),
                'difficulty_level' => self::DIFFICULTY[$data['difficulty']],
                'status' => 'approved',
            ];
            if ($existing) {
                $existing->update($fields);
                $passage = $existing;
            } else {
                $passage = Passage::create($fields + ['question_bank_id' => $bank->id, 'created_by' => auth()->id()]);
                $passage->update(['passage_code' => 'SP-'.$passage->id]);
            }

            $current = SpQuestion::where('passage_id', $passage->id)->get()->keyBy('id');
            $keep = [];
            foreach (array_values($data['subs']) as $i => $sub) {
                $row = isset($sub['id']) ? $current->get((int) $sub['id']) : null;
                $saved = $this->saveQuestion->execute($subject, [
                    'body_ar' => $sub['body_ar'],
                    'options_ar' => $sub['options_ar'],
                    'correct_index' => $sub['correct_index'],
                    'difficulty' => $data['difficulty'],
                    'term' => $data['term'],
                    'week' => $data['week'] ?? null,
                    'sp_skill_id' => $sub['sp_skill_id'] ?? $data['sp_skill_id'] ?? null,
                    'grade_levels' => $data['grade_levels'],
                    'is_active' => $data['is_active'] ?? ($row?->is_active ?? true),
                    'passage_id' => $passage->id,
                    'sub_index' => $i,
                    'passage_text' => $passage->passage_text,
                ], $row);
                $keep[] = $saved->id;
            }
            foreach ($current->except($keep) as $gone) {
                $this->delete->question($gone);
            }

            return $passage;
        });
    }
}
