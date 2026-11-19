<?php

namespace App\Modules\SpecialPrograms\Http\Requests;

use App\Modules\SpecialPrograms\Models\SpSubject;
use App\Modules\SpecialPrograms\Repositories\Contracts\BankRepository;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/** Create/update of an independent program question. Subject access is checked in authorize() so a limited editor gets 403, not a validation error. */
class QuestionRequest extends FormRequest
{
    public function program(): string
    {
        return (string) $this->route('program');
    }

    /** Subject being written: the stored one on update (input is ignored), the posted one on create. */
    public function subjectId(): ?int
    {
        if ($this->route('id')) {
            return app(BankRepository::class)->subjectIdOfQuestion((int) $this->route('id'));
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
        $subjectId = (int) $this->input('sp_subject_id');

        return [
            'sp_subject_id' => ['required', Rule::exists('sp_subjects', 'id')->where('program', $program)],
            'body_ar' => ['nullable', 'string', 'max:5000'],
            'term' => ['required', Rule::in(['first', 'second', 'both'])],
            'week' => $program === 'qiyas' ? ['nullable', 'integer', 'between:1,19'] : ['prohibited'],
            'sp_skill_id' => $program === 'tahsili' ? ['prohibited'] : ['required', Rule::exists('sp_skills', 'id')->where('sp_subject_id', $subjectId)],
            'difficulty' => ['required', Rule::in(['easy', 'medium', 'hard'])],
            'grade_levels' => ['required', 'array', 'min:1'],
            'grade_levels.*' => ['integer', 'between:1,12'],
            'question_image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048'],
            'remove_image' => ['nullable', 'boolean'],
            'options_ar' => ['required', 'array', 'max:6'],
            'options_ar.*' => ['nullable', 'string', 'max:1000'],
            'option_images.*' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048'],
            'option_images_keep.*' => ['nullable', 'string', 'max:255'],
            'correct_index' => ['required', 'integer', 'between:0,5'],
            'insert_after' => $program === 'tahsili' ? ['nullable', 'integer', 'min:0'] : ['prohibited'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            $texts = (array) $this->input('options_ar', []);
            $files = (array) $this->file('option_images', []);
            $keep = (array) $this->input('option_images_keep', []);
            $filled = fn ($i) => trim((string) ($texts[$i] ?? '')) !== '' || isset($files[$i]) || trim((string) ($keep[$i] ?? '')) !== '';
            $count = count(array_filter(range(0, 5), $filled));
            if ($count < 2) {
                $v->errors()->add('options_ar', __('special_programs/bank.errors.min_options'));
            } elseif (! $filled((int) $this->input('correct_index'))) {
                $v->errors()->add('correct_index', __('special_programs/bank.errors.correct_empty'));
            }
            $hasImage = $this->hasFile('question_image') || (trim((string) $this->input('current_image')) !== '' && ! $this->boolean('remove_image'));
            if (trim((string) $this->input('body_ar')) === '' && ! $hasImage) {
                $v->errors()->add('body_ar', __('special_programs/bank.errors.stem_or_image'));
            }
        });
    }
}
