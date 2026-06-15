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
            'lesson_id'   => ['nullable', 'integer', 'exists:subject_lessons,id'],

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
                $options = array_values(array_filter(
                    (array) $this->input('options_ar', []),
                    static fn ($o) => $o !== null && trim((string) $o) !== ''
                ));
                if (count($options) < 2) {
                    $v->errors()->add('options_ar', 'يجب إدخال خيارين على الأقل.');
                }
                $correct = $this->input('correct_index', $this->input('correct'));
                if (! is_numeric($correct) || (int) $correct < 0 || (int) $correct >= count($options)) {
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
                    if (trim((string) ($left[$i] ?? '')) !== '' && trim((string) ($right[$i] ?? '')) !== '') {
                        $pairs++;
                    }
                }
                if ($pairs < 2) {
                    $v->errors()->add('matching_left', 'يجب إدخال زوجين صحيحين على الأقل.');
                }
            }
        });
    }
}
