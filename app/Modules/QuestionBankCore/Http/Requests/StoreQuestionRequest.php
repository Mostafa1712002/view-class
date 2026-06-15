<?php

namespace App\Modules\QuestionBankCore\Http\Requests;

use App\Models\BankQuestion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Validation for adding/editing a normal question (#250). Mirrors the legacy
 * BankQuestionController::validateQuestion rules plus the new classification
 * columns, and enforces the per-type answer rules (min-2 options + a marked
 * correct answer; ≥1 blank; ≥2 matching pairs; full-image ⇒ code required).
 *
 * Authorization (canDo + school scope) is handled in the controller; this
 * request only validates shape, so authorize() returns true.
 */
class StoreQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isFullImage = $this->boolean('is_full_image_question');
        $bankId = (int) $this->route('bankId');
        $ignoreId = $this->route('questionId') ? (int) $this->route('questionId') : null;

        // passage_id must belong to a bank inside the caller's school scope
        // (super-admin = any). Prevents attaching a question to another tenant's passage.
        $user = auth()->user();
        $passageRule = Rule::exists('passages', 'id');
        if (! ($user?->isSuperAdmin() ?? false)) {
            $passageRule->where(fn ($q) => $q->whereIn(
                'question_bank_id',
                \DB::table('question_banks')->where('school_id', $user?->school_id)->select('id')
            ));
        }

        return [
            'type'                   => ['required', 'in:'.implode(',', BankQuestion::TYPES)],
            'question_category'      => ['nullable', 'in:normal,tahsili,passage'],
            'question_code'          => [
                $isFullImage ? 'required' : 'nullable',
                'string', 'max:60',
                Rule::unique('bank_questions', 'question_code')
                    ->where(fn ($q) => $q->where('question_bank_id', $bankId)->whereNull('deleted_at'))
                    ->ignore($ignoreId),
            ],
            'question_content_type'  => ['nullable', 'in:text,image,mixed'],
            'is_full_image_question' => ['nullable', 'boolean'],
            'body_ar'                => [$isFullImage ? 'nullable' : 'required', 'string'],
            'body_en'                => ['nullable', 'string'],
            'explanation'            => ['nullable', 'string'],
            'difficulty'             => ['nullable', 'integer', 'min:1', 'max:3'],
            'points'                 => ['nullable', 'numeric', 'min:0', 'max:9999'],
            'status'                 => ['nullable', 'in:draft,pending_review,approved,rejected,archived'],

            // classification (loose ids — scoped, but no hard FK on grade)
            'subject_id'  => ['nullable', 'integer', 'exists:subjects,id'],
            'grade_id'    => ['nullable', 'integer'],
            'class_id'    => ['nullable', 'integer', 'exists:classes,id'],
            'semester_id' => ['nullable', 'integer', 'exists:academic_terms,id'],
            'week_id'     => ['nullable', 'integer', 'exists:study_weeks,id'],
            'skill_id'    => ['nullable', 'integer', 'exists:skills,id'],
            'standard_id' => ['nullable', 'integer', 'exists:standards,id'],
            'lesson_id'   => ['nullable', 'integer', 'exists:subject_lessons,id'],
            'passage_id'  => ['nullable', 'integer', $passageRule],

            'attachment'        => ['nullable', 'file', 'max:10240'],
            'remove_attachment' => ['nullable', 'boolean'],

            // answer inputs (per type)
            'options_ar'      => ['nullable', 'array'],
            'options_ar.*'    => ['nullable', 'string'],
            'correct'         => ['nullable'],
            'correct_index'   => ['nullable', 'integer'],
            'essay_answer'    => ['nullable', 'string'],
            'short_answer'    => ['nullable', 'string'],
            'matching_left'   => ['nullable', 'array'],
            'matching_left.*' => ['nullable', 'string'],
            'matching_right'  => ['nullable', 'array'],
            'matching_right.*' => ['nullable', 'string'],
            'blanks'          => ['nullable', 'array'],
            'blanks.*'        => ['nullable', 'string'],

            // #247/#250 §10 — per-answer images (MCQ options + matching columns).
            // Files arrive as parallel arrays indexed the same as the text inputs.
            'option_images'        => ['nullable', 'array'],
            'option_images.*'      => ['nullable', 'image', 'max:5120'],
            'option_images_keep'   => ['nullable', 'array'],
            'option_images_keep.*' => ['nullable', 'string'],
            'matching_left_images'        => ['nullable', 'array'],
            'matching_left_images.*'      => ['nullable', 'image', 'max:5120'],
            'matching_left_images_keep'   => ['nullable', 'array'],
            'matching_left_images_keep.*' => ['nullable', 'string'],
            'matching_right_images'        => ['nullable', 'array'],
            'matching_right_images.*'      => ['nullable', 'image', 'max:5120'],
            'matching_right_images_keep'   => ['nullable', 'array'],
            'matching_right_images_keep.*' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'question_code.required' => __('questions.errors.code_required_image'),
            'question_code.unique'   => __('questions.errors.code_duplicate'),
            'body_ar.required'       => __('validation.required', ['attribute' => 'نص السؤال']),
        ];
    }

    /**
     * Per-type answer integrity checks the simple rules can't express.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            $type = $this->input('type');

            if ($type === 'mcq') {
                // An option counts as present if it has text OR an image (new upload
                // or a kept existing path). Keep the indexing identical to MapAnswerData
                // so correct_index points at the right surviving option.
                $present = $this->presentOptionIndexes();
                if (count($present) < 2) {
                    $v->errors()->add('options_ar', 'يجب إدخال خيارين على الأقل (نص أو صورة).');
                }
                $correct = $this->input('correct_index', $this->input('correct'));
                if (! is_numeric($correct) || ! in_array((int) $correct, $present, true)) {
                    $v->errors()->add('correct_index', 'يجب تحديد الإجابة الصحيحة.');
                }
            }

            if ($type === 'true_false' && $this->input('correct') === null) {
                $v->errors()->add('correct', 'يجب تحديد الإجابة الصحيحة (صح / خطأ).');
            }

            if ($type === 'fill_blank') {
                $blanks = array_filter(
                    (array) $this->input('blanks', []),
                    static fn ($b) => $b !== null && trim((string) $b) !== ''
                );
                if (count($blanks) < 1) {
                    $v->errors()->add('blanks', 'يجب إدخال إجابة واحدة على الأقل للفراغ.');
                }
            }

            if ($type === 'matching') {
                $left = (array) $this->input('matching_left', []);
                $right = (array) $this->input('matching_right', []);
                $pairs = 0;
                $count = max(count($left), count($right));
                for ($i = 0; $i < $count; $i++) {
                    $hasLeft  = trim((string) ($left[$i] ?? '')) !== '' || $this->columnHasImage('left', $i);
                    $hasRight = trim((string) ($right[$i] ?? '')) !== '' || $this->columnHasImage('right', $i);
                    if ($hasLeft && $hasRight) {
                        $pairs++;
                    }
                }
                if ($pairs < 2) {
                    $v->errors()->add('matching_left', 'يجب إدخال زوجين صحيحين على الأقل (نص أو صورة في كل عمود).');
                }
            }
        });
    }

    /**
     * MCQ option indexes that are "present" — i.e. carry text OR an image (a new
     * upload or a kept existing path). Used by both the min-2 check and the
     * correct-answer check so they agree with MapAnswerData's indexing.
     *
     * @return array<int,int>
     */
    private function presentOptionIndexes(): array
    {
        $texts = (array) $this->input('options_ar', []);
        $keep  = (array) $this->input('option_images_keep', []);
        $files = $this->file('option_images', []) ?: [];

        $count = max(count($texts), count($keep), count($files));
        $present = [];
        for ($i = 0; $i < $count; $i++) {
            $hasText  = trim((string) ($texts[$i] ?? '')) !== '';
            $hasKeep  = trim((string) ($keep[$i] ?? '')) !== '';
            $hasFile  = isset($files[$i]) && $files[$i] !== null;
            if ($hasText || $hasKeep || $hasFile) {
                $present[] = $i;
            }
        }

        return $present;
    }

    /**
     * Whether a matching column side at index $i has an image (new upload or kept).
     */
    private function columnHasImage(string $side, int $i): bool
    {
        $keepKey  = "matching_{$side}_images_keep";
        $fileKey  = "matching_{$side}_images";
        $keep = (array) $this->input($keepKey, []);
        $files = $this->file($fileKey, []) ?: [];

        return (isset($files[$i]) && $files[$i] !== null)
            || trim((string) ($keep[$i] ?? '')) !== '';
    }
}
