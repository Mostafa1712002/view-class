@php
    $answer = $question->answer_data ?? [];
@endphp
<div class="preview-card">
    <div class="row-meta">
        <div><b>@lang('questions.preview.type'):</b> @lang('questions.types.'.$question->type)</div>
        <div><b>@lang('questions.preview.points'):</b> {{ rtrim(rtrim(number_format((float)$question->points, 2), '0'), '.') }}</div>
        @if($question->difficulty)
            <div><b>@lang('questions.preview.difficulty'):</b> @lang('questions.difficulty.'.$question->difficulty)</div>
        @endif
        @if($question->lesson)
            <div><b>@lang('questions.preview.lesson'):</b> {{ $question->lesson->name_ar }}</div>
        @endif
    </div>

    <div class="mb-3">
        <b>@lang('questions.preview.body'):</b>
        <div class="p-2 mt-1" style="background:#f8fafc; border-radius:8px;">
            {!! nl2br(e($question->body_ar)) !!}
        </div>
    </div>

    @if($question->attachment_path)
        <div class="mb-3">
            <b>@lang('questions.preview.attachment'):</b>
            <a href="{{ asset('storage/'.$question->attachment_path) }}" target="_blank" class="btn btn-sm btn-outline-info ms-2">
                <i class="la la-paperclip"></i> @lang('questions.preview.open_attachment')
            </a>
        </div>
    @endif

    @switch($question->type)
        @case('mcq')
            @php
                $opts = $answer['options'] ?? [];
                $correct = $answer['correct'] ?? null;
            @endphp
            <div class="mb-2"><b>@lang('questions.preview.answers'):</b></div>
            <ul class="ans-list">
                @foreach($opts as $i => $opt)
                    <li class="{{ (string)$correct === (string)$i ? 'correct' : '' }}">
                        <strong>{{ chr(65 + $i) }}.</strong> {{ $opt }}
                        @if((string)$correct === (string)$i)
                            <i class="la la-check ms-2"></i>
                        @endif
                    </li>
                @endforeach
            </ul>
            @break

        @case('true_false')
            <div class="mb-2"><b>@lang('questions.preview.correct'):</b></div>
            <ul class="ans-list">
                <li class="{{ ($answer['correct'] ?? null) === 'true' ? 'correct' : '' }}">
                    @lang('questions.form.true_false.true')
                    @if(($answer['correct'] ?? null) === 'true') <i class="la la-check ms-2"></i> @endif
                </li>
                <li class="{{ ($answer['correct'] ?? null) === 'false' ? 'correct' : '' }}">
                    @lang('questions.form.true_false.false')
                    @if(($answer['correct'] ?? null) === 'false') <i class="la la-check ms-2"></i> @endif
                </li>
            </ul>
            @break

        @case('essay')
        @case('short')
            <div class="mb-2"><b>@lang('questions.preview.model_answer'):</b></div>
            <div class="p-2" style="background:#dcfce7; border-radius:8px; color:#166534;">
                {{ $answer['model_answer'] ?? '—' }}
            </div>
            @break

        @case('matching')
            <div class="mb-2"><b>@lang('questions.preview.answers'):</b></div>
            @foreach(($answer['pairs'] ?? []) as $pair)
                <div class="pair-row">
                    <div class="col-cell">{{ $pair['left'] ?? '' }}</div>
                    <span class="arrow">↔</span>
                    <div class="col-cell">{{ $pair['right'] ?? '' }}</div>
                </div>
            @endforeach
            @break

        @case('fill_blank')
            <div class="mb-2"><b>@lang('questions.preview.answers'):</b></div>
            <ul class="ans-list">
                @foreach(($answer['blanks'] ?? []) as $i => $b)
                    <li class="correct"><strong>{{ $i + 1 }}.</strong> {{ $b }}</li>
                @endforeach
            </ul>
            @break
    @endswitch
</div>
