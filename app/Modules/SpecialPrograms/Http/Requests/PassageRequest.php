<?php

namespace App\Modules\SpecialPrograms\Http\Requests;

use App\Modules\SpecialPrograms\Repositories\Contracts\BankRepository;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/** Create/update of a passage with its sub-questions (text options only). Only qiyas and nafis have passages. */
class PassageRequest extends FormRequest
{
    public function program(): string
    {
        return (string) $this->route('program');
    }

    public function subjectId(): ?int
    {
        if ($this->route('id')) {
            return app(BankRepository::class)->subjectIdOfPassage((int) $this->route('id'));
        }

        return $this->filled('sp_subject_id') ? (int) $this->input('sp_subject_id') : null;
    }

    public function authorize(): bool
    {
        $id = $this->subjectId();

        return $id === null || app(BankRepository::class)->subjectsFor($this->user(), $this->program())->contains('id', $id);
    }

    protected function prepareForValidation(): void
    {
        if ($this->route('id') && ($id = $this->subjectId())) {
            $this->merge(['sp_subject_id' => $id]);
        }
    }

    public function rules(): array
    {
        $program = $this->program();
        $skill = Rule::exists('sp_skills', 'id')->where('sp_subject_id', (int) $this->input('sp_subject_id'));

        return [
            'sp_subject_id' => ['required', Rule::exists('sp_subjects', 'id')->where('program', $program)],
            'passage_text' => ['required', 'string', 'max:20000'],
            'term' => ['required', Rule::in(['first', 'second', 'both'])],
            'week' => $program === 'qiyas' ? ['nullable', 'integer', 'between:1,19'] : ['prohibited'],
            // qiyas: one skill for the whole passage; nafis: one per sub-question.
            'sp_skill_id' => $program === 'qiyas' ? ['required', $skill] : ['prohibited'],
            'difficulty' => ['required', Rule::in(['easy', 'medium', 'hard'])],
            'grade_levels' => ['required', 'array', 'min:1'],
            'grade_levels.*' => ['integer', 'between:1,12'],
            'is_active' => ['nullable', 'boolean'],
            'subs' => ['required', 'array', 'min:1', 'max:30'],
            'subs.*.id' => ['nullable', 'integer'],
            'subs.*.body_ar' => ['required', 'string', 'max:5000'],
            'subs.*.sp_skill_id' => $program === 'nafis' ? ['required', $skill] : ['prohibited'],
            'subs.*.options_ar' => ['required', 'array', 'max:6'],
            'subs.*.options_ar.*' => ['nullable', 'string', 'max:1000'],
            'subs.*.correct_index' => ['required', 'integer', 'between:0,5'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            foreach ((array) $this->input('subs', []) as $i => $sub) {
                $texts = array_map(fn ($t) => trim((string) $t), (array) ($sub['options_ar'] ?? []));
                if (count(array_filter($texts, fn ($t) => $t !== '')) < 2) {
                    $v->errors()->add("subs.$i.options_ar", __('special_programs/bank.errors.min_options'));
                } elseif (($texts[(int) ($sub['correct_index'] ?? -1)] ?? '') === '') {
                    $v->errors()->add("subs.$i.correct_index", __('special_programs/bank.errors.correct_empty'));
                }
            }
        });
    }
}
