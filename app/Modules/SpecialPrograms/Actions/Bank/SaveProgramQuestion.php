<?php

namespace App\Modules\SpecialPrograms\Actions\Bank;

use App\Models\BankQuestion;
use App\Models\QuestionBank;
use App\Modules\QuestionBankCore\Actions\CreateQuestion;
use App\Modules\QuestionBankCore\Actions\UpdateQuestion;
use App\Modules\SpecialPrograms\Actions\Import\DedupeKey;
use App\Modules\SpecialPrograms\Models\SpQuestion;
use App\Modules\SpecialPrograms\Models\SpSubject;
use Illuminate\Support\Facades\DB;

/**
 * Writes one program question (independent or passage sub-question) into the owner bank of its subject
 * and its sp_questions side row. Answer rows go through CreateQuestion/UpdateQuestion/MapAnswerData.
 *
 * $data: body_ar, options_ar, option_image_paths?, correct_index, attachment_path?, image_md5?, difficulty (easy|medium|hard),
 * term, week?, sp_skill_id?, grade_levels[], insert_after? (tahsili), is_active?, passage_id?, sub_index?, passage_text?
 */
final class SaveProgramQuestion
{
    private const DIFFICULTY = ['easy' => 1, 'medium' => 2, 'hard' => 3];

    public function __construct(private CreateQuestion $create, private UpdateQuestion $update) {}

    /** Owner bank of the subject (same identity the importer uses). */
    public function bank(SpSubject $subject): QuestionBank
    {
        $bank = $subject->question_bank_id ? QuestionBank::find($subject->question_bank_id) : null;
        if ($bank) {
            return $bank;
        }
        $bank = QuestionBank::unguarded(fn () => QuestionBank::firstOrCreate(
            ['category_type' => 'special_program', 'name_ar' => "بنك {$subject->name_ar} — منصة الأول"],
            [
                'school_id' => null, 'bank_type' => 'public', 'is_owner_bank' => 1, 'visibility' => 'public', 'status' => 'active',
                'source' => 'al_awwal', 'external_platform' => 'al_awwal', 'allow_images' => 1, 'allow_passage_questions' => 1,
                'allow_tahsili_questions' => $subject->program === 'tahsili' ? 1 : 0,
            ],
        ));
        $subject->forceFill(['question_bank_id' => $bank->id])->save();

        return $bank;
    }

    public function execute(SpSubject $subject, array $data, ?SpQuestion $existing = null): SpQuestion
    {
        $bank = $this->bank($subject);
        $isPassageItem = ! empty($data['passage_id']);
        $stem = trim((string) ($data['body_ar'] ?? ''));
        $image = $data['attachment_path'] ?? null;

        $payload = [
            'type' => 'mcq',
            'question_category' => $isPassageItem ? 'passage' : ($subject->program === 'tahsili' ? 'tahsili' : 'normal'),
            'question_content_type' => $image ? ($stem === '' ? 'image' : 'mixed') : 'text',
            'is_full_image_question' => $subject->program === 'tahsili' && $image && $stem === '',
            'body_ar' => $stem,
            'difficulty' => self::DIFFICULTY[$data['difficulty']],
            'points' => 1,
            'attachment_path' => $image,
            'options_ar' => $data['options_ar'],
            'option_image_paths' => $data['option_image_paths'] ?? [],
            'correct_index' => $data['correct_index'],
            'passage_id' => $data['passage_id'] ?? null,
            'status' => 'approved',
        ];

        return DB::transaction(function () use ($subject, $bank, $data, $payload, $existing, $stem, $image, $isPassageItem) {
            if ($existing) {
                $bankQuestion = BankQuestion::findOrFail($existing->bank_question_id);
                $this->update->execute($bankQuestion, $payload);
            } else {
                $bankQuestion = $this->create->execute($bank, $payload);
            }

            $options = array_values(array_filter(
                array_map(fn ($o) => trim((string) $o), $data['options_ar']), fn ($o) => $o !== ''
            ));
            $hash = DedupeKey::make($subject->id, [[
                'stem' => $stem, 'options' => $options ?: [''], 'correct_index' => (int) $data['correct_index'],
                'skill_source_id' => $data['sp_skill_id'] ?? null,
            ]], $data['image_md5'] ?? null, $data['passage_text'] ?? null);

            $order = $subject->program === 'tahsili' && ! $isPassageItem
                ? $this->resolveOrder($subject->id, $existing, $data['insert_after'] ?? null)
                : null;

            $sp = $existing ?? new SpQuestion(['bank_question_id' => $bankQuestion->id, 'sp_subject_id' => $subject->id]);
            $sp->fill([
                'sp_skill_id' => $data['sp_skill_id'] ?? null,
                'passage_id' => $data['passage_id'] ?? null,
                'sub_index' => $data['sub_index'] ?? 0,
                'difficulty' => $data['difficulty'],
                'term' => $data['term'],
                'week' => $data['week'] ?? null,
                'question_order' => $order,
                'has_image' => (bool) $image,
                'content_hash' => $hash,
                'is_active' => (bool) ($data['is_active'] ?? true),
            ])->save();

            DB::table('sp_question_levels')->where('sp_question_id', $sp->id)->delete();
            DB::table('sp_question_levels')->insert(array_map(
                fn ($g) => ['sp_question_id' => $sp->id, 'grade_level' => (int) $g],
                array_values(array_unique($data['grade_levels']))
            ));

            if ($isPassageItem) {
                DB::table('passage_questions')->updateOrInsert(
                    ['passage_id' => $data['passage_id'], 'question_id' => $bankQuestion->id],
                    ['sort_order' => $data['sub_index'] ?? 0]
                );
            }

            return $sp;
        });
    }

    /**
     * Tahsili «أضف السؤال بعد رقم»: new → X+1 (shift the rest up) or MAX+1; existing + insert_after → close the old
     * gap, open the new slot; existing without insert_after keeps its number. Runs inside the caller's transaction.
     */
    private function resolveOrder(int $subjectId, ?SpQuestion $existing, mixed $after): int
    {
        $rows = fn () => DB::table('sp_questions')->where('sp_subject_id', $subjectId)->whereNull('passage_id');
        $after = ($after === null || $after === '') ? null : (int) $after;

        if (! $existing) {
            if ($after === null) {
                return (int) $rows()->max('question_order') + 1;
            }
            $rows()->where('question_order', '>', $after)->increment('question_order');

            return $after + 1;
        }

        $old = $existing->question_order;
        if ($after === null || $old === null) {
            return $old ?? ((int) $rows()->max('question_order') + 1);
        }
        if ($after === $old || $after === $old - 1) {
            return $old; // dropped right where it already is
        }
        $rows()->where('question_order', '>', $old)->decrement('question_order');
        $after = $after > $old ? $after - 1 : $after;
        $rows()->where('question_order', '>', $after)->where('id', '!=', $existing->id)->increment('question_order');

        return $after + 1;
    }
}
