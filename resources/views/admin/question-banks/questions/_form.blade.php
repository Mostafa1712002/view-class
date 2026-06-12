@php
    $isEdit = isset($question) && $question->exists;
    $isRtl = app()->getLocale() === 'ar';
    $typesList = ['true_false','mcq','essay','matching','fill_blank','short'];
    $answer = $question->answer_data ?? [];
    $mcqOptions = $answer['options'] ?? ['', '', '', ''];
    $mcqCorrect = $answer['correct'] ?? null;
    $tfCorrect = $answer['correct'] ?? null;
    $essayAnswer = $answer['model_answer'] ?? null;
    $shortAnswer = $answer['model_answer'] ?? null;
    $matchingPairs = $answer['pairs'] ?? [];
    $blanks = $answer['blanks'] ?? [];
@endphp

@push('styles')
<style>
    .q-form .form-label { font-weight: 600; color:#0f172a; }
    .q-form .section-title {
        font-size:16px; font-weight:700; color:#0f172a; padding:14px 0 8px;
        border-bottom:1px solid #e2e8f0; margin-bottom:14px; display:flex; align-items:center; gap:10px;
    }
    .q-form .section-title .pill {
        background:#fef3c7; color:#92400e; font-size:11px; padding:3px 8px; border-radius:999px; font-weight:700;
    }
    .q-form .helper { font-size:12px; color:#64748b; margin-top:4px; }
    .q-form .answer-row { display:flex; align-items:center; gap:8px; margin-bottom:8px; }
    .q-form .answer-row .opt-letter {
        width:34px; height:34px; border-radius:50%; background:#f1f5f9; color:#0f172a;
        display:flex; align-items:center; justify-content:center; font-weight:700; flex-shrink:0;
    }
    .q-form .answer-row .opt-input { flex:1; }
    .q-form .answer-row .correct-wrap {
        display:flex; align-items:center; gap:4px; font-size:12px; color:#475569;
        padding:6px 10px; border-radius:8px; border:1px solid #e2e8f0; cursor:pointer;
    }
    .q-form .answer-row .correct-wrap input { margin:0; }
    .q-form .answer-row .btn-rm { flex-shrink:0; }
    .q-form .pair-row { display:flex; align-items:center; gap:8px; margin-bottom:8px; }
    .q-form .pair-row input { flex:1; }
    .q-form .pair-row .arrow { color:#94a3b8; font-size:20px; }
    .q-form .actions-bar {
        display:flex; gap:10px; justify-content:flex-end; padding-top:18px;
        border-top:1px solid #e2e8f0; margin-top:18px;
    }
    .q-form details summary { cursor:pointer; padding:8px 10px; background:#f8fafc; border-radius:8px; font-weight:600; }
    .q-form .attach-row { display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
</style>
@endpush

<form action="{{ $isEdit ? route('admin.question-banks.questions.update', [$bank->id, $question->id]) : route('admin.question-banks.questions.store', $bank->id) }}"
      method="POST" enctype="multipart/form-data" class="q-form">
    @csrf
    @if($isEdit) @method('PUT') @endif

    <div class="section-title">
        <i class="la la-info-circle"></i> @lang('questions.form.sections.info')
    </div>

    <div class="row g-3">
        <div class="col-md-3 col-6">
            <label class="form-label">@lang('questions.form.type') <span class="text-danger">*</span></label>
            <select name="type" id="qtype" class="form-select" required>
                @foreach($typesList as $t)
                    <option value="{{ $t }}" {{ old('type', $question->type) === $t ? 'selected' : '' }}>
                        @lang('questions.types.'.$t)
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2 col-6">
            <label class="form-label">@lang('questions.form.difficulty') <span class="text-danger">*</span></label>
            <select name="difficulty" class="form-select">
                @foreach([1,2,3] as $d)
                    <option value="{{ $d }}" {{ (int)old('difficulty', $question->difficulty ?? 1) === $d ? 'selected' : '' }}>
                        @lang('questions.difficulty.'.$d)
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2 col-6">
            <label class="form-label">@lang('questions.form.points') <span class="text-danger">*</span></label>
            <input type="number" step="0.25" min="0" max="9999" name="points" class="form-control"
                   value="{{ old('points', $question->points ?? 1) }}" required>
        </div>
        <div class="col-md-3 col-12">
            <label class="form-label">@lang('questions.form.lesson')</label>
            <select name="lesson_id" class="form-select">
                <option value="">@lang('questions.form.lesson_placeholder')</option>
                @foreach($lessons as $l)
                    <option value="{{ $l->id }}" {{ (string)old('lesson_id', $question->lesson_id) === (string)$l->id ? 'selected' : '' }}>
                        {{ $l->name_ar }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2 col-6">
            <label class="form-label">@lang('questions.form.status')</label>
            <select name="status" class="form-select">
                @foreach(\App\Models\BankQuestion::STATUSES as $s)
                    <option value="{{ $s }}" {{ old('status', $question->status ?? 'approved') === $s ? 'selected' : '' }}>
                        @lang('questions.status.'.$s)
                    </option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- #213: question code + content type + full-image toggle --}}
    <div class="row g-3 mt-1">
        <div class="col-md-3 col-6">
            <label class="form-label">@lang('questions.form.code')</label>
            <input type="text" name="question_code" maxlength="60"
                   class="form-control @error('question_code') is-invalid @enderror"
                   value="{{ old('question_code', $question->question_code ?? '') }}"
                   placeholder="@lang('questions.form.code_placeholder')">
            @error('question_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
            <span class="helper">@lang('questions.form.code_help')</span>
        </div>
        <div class="col-md-3 col-6">
            <label class="form-label">@lang('questions.form.content_type')</label>
            <select name="question_content_type" id="q-content-type" class="form-select">
                @foreach(['text','image','mixed'] as $ct)
                    <option value="{{ $ct }}" {{ old('question_content_type', $question->question_content_type ?? 'text') === $ct ? 'selected' : '' }}>
                        @lang('questions.content_type.'.$ct)
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4 col-12 d-flex align-items-end">
            <label class="d-flex align-items-center gap-2 mb-2">
                <input type="hidden" name="is_full_image_question" value="0">
                <input type="checkbox" name="is_full_image_question" id="q-full-image" value="1"
                       {{ old('is_full_image_question', $question->is_full_image_question ?? false) ? 'checked' : '' }}>
                @lang('questions.form.is_full_image')
            </label>
        </div>
    </div>

    <div class="mt-3">
        <details>
            <summary>{{ __('questions.form.attachment') }}</summary>
            <div class="mt-2 attach-row">
                <input type="file" name="attachment" class="form-control" style="max-width: 360px">
                <span class="helper">@lang('questions.form.attachment_help')</span>
                @if($isEdit && $question->attachment_path)
                    <a href="{{ asset('storage/'.$question->attachment_path) }}" target="_blank" class="btn btn-sm btn-outline-info">
                        <i class="la la-paperclip"></i> {{ basename($question->attachment_path) }}
                    </a>
                    <label class="d-flex align-items-center gap-1 small">
                        <input type="checkbox" name="remove_attachment" value="1"> @lang('questions.form.remove_attachment')
                    </label>
                @endif
            </div>
        </details>
    </div>

    <div class="mt-3">
        <label class="form-label">@lang('questions.form.body_ar') <span class="text-danger" id="body-ar-req">*</span>
            <span class="helper">@lang('questions.form.body_ar_help')</span></label>
        {{-- Not HTML-required: a full-image question has no text head (server validates conditionally). --}}
        <textarea name="body_ar" rows="4" class="form-control @error('body_ar') is-invalid @enderror">{{ old('body_ar', $question->body_ar ?? '') }}</textarea>
        @error('body_ar')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        @error('body_ar')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="mt-3">
        <label class="form-label">@lang('questions.form.body_en')</label>
        <textarea name="body_en" rows="2" class="form-control">{{ old('body_en', $question->body_en ?? '') }}</textarea>
    </div>

    <!-- Answer block — toggled by type -->
    <div id="answer-block">
        <div class="section-title">
            <i class="la la-check-circle"></i> @lang('questions.form.sections.answer')
            <span class="pill" id="qtype-pill">@lang('questions.types.'.$question->type)</span>
        </div>

        <!-- MCQ -->
        <div data-answer-for="mcq" class="answer-pane">
            <div id="mcq-options">
                @forelse($mcqOptions as $i => $opt)
                    <div class="answer-row" data-opt-row>
                        <div class="opt-letter">{{ chr(65 + $i) }}</div>
                        <input type="text" name="options_ar[]" class="form-control opt-input"
                               value="{{ old('options_ar.'.$i, $opt) }}"
                               placeholder="@lang('questions.form.mcq.option_n') {{ $i + 1 }}">
                        <label class="correct-wrap">
                            <input type="radio" name="correct_index" value="{{ $i }}" {{ (string)$mcqCorrect === (string)$i ? 'checked' : '' }}>
                            @lang('questions.form.mcq.correct')
                        </label>
                        <button type="button" class="btn btn-sm btn-outline-danger btn-rm" data-remove>
                            <i class="la la-times"></i>
                        </button>
                    </div>
                @empty
                @endforelse
            </div>
            <button type="button" class="btn btn-sm btn-outline-primary" id="mcq-add">
                <i class="la la-plus"></i> @lang('questions.form.mcq.add_option')
            </button>
        </div>

        <!-- True / false -->
        <div data-answer-for="true_false" class="answer-pane" style="display:none">
            <label class="form-label">@lang('questions.form.true_false.correct') <span class="text-danger">*</span></label>
            <div class="d-flex gap-3">
                <label class="d-flex align-items-center gap-2 p-2 px-3 rounded border" style="cursor:pointer">
                    <input type="radio" name="correct" value="true" {{ $tfCorrect === 'true' ? 'checked' : '' }}>
                    <span>@lang('questions.form.true_false.true')</span>
                </label>
                <label class="d-flex align-items-center gap-2 p-2 px-3 rounded border" style="cursor:pointer">
                    <input type="radio" name="correct" value="false" {{ $tfCorrect === 'false' ? 'checked' : '' }}>
                    <span>@lang('questions.form.true_false.false')</span>
                </label>
            </div>
        </div>

        <!-- Essay -->
        <div data-answer-for="essay" class="answer-pane" style="display:none">
            <label class="form-label">@lang('questions.form.essay.model_answer')</label>
            <textarea name="essay_answer" rows="4" class="form-control">{{ old('essay_answer', $essayAnswer) }}</textarea>
        </div>

        <!-- Short answer -->
        <div data-answer-for="short" class="answer-pane" style="display:none">
            <label class="form-label">@lang('questions.form.short.model_answer')</label>
            <input type="text" name="short_answer" class="form-control" value="{{ old('short_answer', $shortAnswer) }}">
        </div>

        <!-- Matching -->
        <div data-answer-for="matching" class="answer-pane" style="display:none">
            <div id="matching-rows">
                @forelse($matchingPairs as $i => $pair)
                    <div class="pair-row" data-pair-row>
                        <input type="text" name="matching_left[]" class="form-control" placeholder="@lang('questions.form.matching.left')" value="{{ $pair['left'] ?? '' }}">
                        <span class="arrow">↔</span>
                        <input type="text" name="matching_right[]" class="form-control" placeholder="@lang('questions.form.matching.right')" value="{{ $pair['right'] ?? '' }}">
                        <button type="button" class="btn btn-sm btn-outline-danger btn-rm" data-remove><i class="la la-times"></i></button>
                    </div>
                @empty
                    @for($i = 0; $i < 3; $i++)
                        <div class="pair-row" data-pair-row>
                            <input type="text" name="matching_left[]" class="form-control" placeholder="@lang('questions.form.matching.left')">
                            <span class="arrow">↔</span>
                            <input type="text" name="matching_right[]" class="form-control" placeholder="@lang('questions.form.matching.right')">
                            <button type="button" class="btn btn-sm btn-outline-danger btn-rm" data-remove><i class="la la-times"></i></button>
                        </div>
                    @endfor
                @endforelse
            </div>
            <button type="button" class="btn btn-sm btn-outline-primary" id="match-add">
                <i class="la la-plus"></i> @lang('questions.form.matching.add_pair')
            </button>
        </div>

        <!-- Fill blank -->
        <div data-answer-for="fill_blank" class="answer-pane" style="display:none">
            <p class="helper mb-2">@lang('questions.form.fill_blank.hint')</p>
            <div id="blanks-rows">
                @forelse($blanks as $i => $b)
                    <div class="answer-row" data-blank-row>
                        <div class="opt-letter">{{ $i + 1 }}</div>
                        <input type="text" name="blanks[]" class="form-control" placeholder="@lang('questions.form.fill_blank.blank_n')" value="{{ $b }}">
                        <button type="button" class="btn btn-sm btn-outline-danger btn-rm" data-remove><i class="la la-times"></i></button>
                    </div>
                @empty
                    <div class="answer-row" data-blank-row>
                        <div class="opt-letter">1</div>
                        <input type="text" name="blanks[]" class="form-control" placeholder="@lang('questions.form.fill_blank.blank_n')">
                        <button type="button" class="btn btn-sm btn-outline-danger btn-rm" data-remove><i class="la la-times"></i></button>
                    </div>
                @endforelse
            </div>
            <button type="button" class="btn btn-sm btn-outline-primary" id="blank-add">
                <i class="la la-plus"></i> @lang('questions.form.fill_blank.add_blank')
            </button>
        </div>
    </div>

    <div class="actions-bar">
        <a href="{{ route('admin.question-banks.questions.index', $bank->id) }}" class="btn btn-outline-secondary">
            @lang('questions.form.back')
        </a>
        <button type="reset" class="btn btn-outline-warning">@lang('questions.form.reset')</button>
        <button type="submit" class="btn btn-primary">@lang('questions.form.save')</button>
    </div>
</form>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var qtype = document.getElementById('qtype');
    var pill  = document.getElementById('qtype-pill');
    var panes = document.querySelectorAll('.answer-pane');
    var typeLabels = @json(__('questions.types'));
    var lblOption = @json(__('questions.form.mcq.option_n'));
    var lblCorrect = @json(__('questions.form.mcq.correct'));
    var lblLeft = @json(__('questions.form.matching.left'));
    var lblRight = @json(__('questions.form.matching.right'));
    var lblBlank = @json(__('questions.form.fill_blank.blank_n'));

    function refresh() {
        var v = qtype.value;
        panes.forEach(function (p) {
            p.style.display = (p.dataset.answerFor === v) ? '' : 'none';
        });
        if (pill && typeLabels && typeLabels[v]) pill.textContent = typeLabels[v];
    }
    qtype.addEventListener('change', refresh);
    refresh();

    function makeIconBtn(iconClass) {
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'btn btn-sm btn-outline-danger btn-rm';
        btn.setAttribute('data-remove', '');
        var i = document.createElement('i');
        i.className = iconClass;
        btn.appendChild(i);
        return btn;
    }

    // MCQ — add / remove options
    var mcqAdd = document.getElementById('mcq-add');
    var mcqWrap = document.getElementById('mcq-options');
    function relabelMcq() {
        var rows = mcqWrap.querySelectorAll('[data-opt-row]');
        rows.forEach(function (row, idx) {
            row.querySelector('.opt-letter').textContent = String.fromCharCode(65 + idx);
            var ph = row.querySelector('.opt-input');
            ph.placeholder = lblOption + ' ' + (idx + 1);
            var radio = row.querySelector('input[type=radio]');
            if (radio) radio.value = idx;
        });
    }
    if (mcqAdd) {
        mcqAdd.addEventListener('click', function () {
            var rows = mcqWrap.querySelectorAll('[data-opt-row]');
            var idx = rows.length;
            var tpl = document.createElement('div');
            tpl.className = 'answer-row';
            tpl.setAttribute('data-opt-row', '');
            var letter = document.createElement('div');
            letter.className = 'opt-letter';
            letter.textContent = String.fromCharCode(65 + idx);
            var input = document.createElement('input');
            input.type = 'text'; input.name = 'options_ar[]'; input.className = 'form-control opt-input';
            input.placeholder = lblOption + ' ' + (idx + 1);
            var lbl = document.createElement('label'); lbl.className = 'correct-wrap';
            var radio = document.createElement('input'); radio.type='radio'; radio.name='correct_index'; radio.value=idx;
            lbl.appendChild(radio);
            lbl.appendChild(document.createTextNode(' ' + lblCorrect));
            var rm = makeIconBtn('la la-times');
            tpl.appendChild(letter); tpl.appendChild(input); tpl.appendChild(lbl); tpl.appendChild(rm);
            mcqWrap.appendChild(tpl);
        });
    }

    // Matching — add / remove pairs
    var matchAdd = document.getElementById('match-add');
    var matchWrap = document.getElementById('matching-rows');
    if (matchAdd) {
        matchAdd.addEventListener('click', function () {
            var row = document.createElement('div');
            row.className = 'pair-row'; row.setAttribute('data-pair-row','');
            var l = document.createElement('input'); l.type='text'; l.name='matching_left[]'; l.className='form-control';
            l.placeholder = lblLeft;
            var arr = document.createElement('span'); arr.className='arrow'; arr.textContent='↔';
            var r = document.createElement('input'); r.type='text'; r.name='matching_right[]'; r.className='form-control';
            r.placeholder = lblRight;
            var rm = makeIconBtn('la la-times');
            row.appendChild(l); row.appendChild(arr); row.appendChild(r); row.appendChild(rm);
            matchWrap.appendChild(row);
        });
    }

    // Blanks — add / remove
    var blankAdd = document.getElementById('blank-add');
    var blankWrap = document.getElementById('blanks-rows');
    function relabelBlanks() {
        var rows = blankWrap.querySelectorAll('[data-blank-row]');
        rows.forEach(function (row, idx) {
            row.querySelector('.opt-letter').textContent = (idx + 1);
        });
    }
    if (blankAdd) {
        blankAdd.addEventListener('click', function () {
            var rows = blankWrap.querySelectorAll('[data-blank-row]');
            var idx = rows.length;
            var row = document.createElement('div');
            row.className = 'answer-row'; row.setAttribute('data-blank-row','');
            var letter = document.createElement('div'); letter.className='opt-letter'; letter.textContent = idx + 1;
            var input = document.createElement('input'); input.type='text'; input.name='blanks[]'; input.className='form-control';
            input.placeholder = lblBlank;
            var rm = makeIconBtn('la la-times');
            row.appendChild(letter); row.appendChild(input); row.appendChild(rm);
            blankWrap.appendChild(row);
        });
    }

    // Generic remove handler
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-remove]');
        if (!btn) return;
        var row = btn.closest('[data-opt-row], [data-pair-row], [data-blank-row]');
        if (!row) return;
        var parent = row.parentNode;
        row.remove();
        if (parent && parent.id === 'mcq-options') relabelMcq();
        if (parent && parent.id === 'blanks-rows') relabelBlanks();
    });
});
</script>
@endpush
