@extends('layouts.app')

@section('title', __('sprint4.question_banks.questions.create_title'))

@section('content')
<div class="content-header row">
    <div class="content-header-left col-12 mb-2">
        <h2 class="content-header-title mb-0">{{ $bank->name_ar }} — @lang('sprint4.question_banks.questions.create_title')</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.question-banks.index') }}">@lang('sprint4.question_banks.index_title')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.question-banks.questions.index', $bank->id) }}">{{ $bank->name_ar }}</a></li>
                <li class="breadcrumb-item active">@lang('sprint4.question_banks.questions.create_title')</li>
            </ol>
        </div>
    </div>
</div>

<div class="content-body">
    <div class="card">
        <div class="card-body">
            <form action="{{ route('admin.question-banks.questions.store', $bank->id) }}" method="POST">
                @csrf
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">@lang('sprint4.question_banks.questions.form.type') <span class="text-danger">*</span></label>
                        <select name="type" id="qtype" class="form-select" required>
                            @foreach(['mcq', 'true_false', 'short', 'essay'] as $t)
                                <option value="{{ $t }}" {{ old('type', $question->type) === $t ? 'selected' : '' }}>@lang('sprint4.question_banks.questions.types.' . $t)</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">@lang('sprint4.question_banks.questions.form.difficulty')</label>
                        <input type="number" name="difficulty" min="1" max="5" class="form-control" value="{{ old('difficulty') }}">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">@lang('sprint4.question_banks.questions.form.body_ar') <span class="text-danger">*</span></label>
                    <textarea name="body_ar" rows="3" class="form-control @error('body_ar') is-invalid @enderror" required>{{ old('body_ar') }}</textarea>
                    @error('body_ar')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">@lang('sprint4.question_banks.questions.form.body_en')</label>
                    <textarea name="body_en" rows="2" class="form-control">{{ old('body_en') }}</textarea>
                </div>

                <div id="mcq-options" class="mb-3" style="display: none">
                    <label class="form-label">@lang('sprint4.question_banks.questions.form.options')</label>
                    @for($i = 0; $i < 4; $i++)
                        <div class="input-group mb-1">
                            <span class="input-group-text">{{ chr(65 + $i) }}</span>
                            <input type="text" name="options_ar[{{ $i }}]" class="form-control" placeholder="@lang('sprint4.question_banks.questions.form.option') {{ $i + 1 }}">
                            <span class="input-group-text">
                                <input type="radio" name="correct" value="{{ $i }}" title="@lang('sprint4.question_banks.questions.form.correct')">
                            </span>
                        </div>
                    @endfor
                </div>
                <div id="tf-options" class="mb-3" style="display: none">
                    <label class="form-label">@lang('sprint4.question_banks.questions.form.correct')</label>
                    <select name="correct" class="form-select" style="max-width: 200px">
                        <option value="true">@lang('sprint4.question_banks.questions.form.true')</option>
                        <option value="false">@lang('sprint4.question_banks.questions.form.false')</option>
                    </select>
                </div>

                <div class="text-end">
                    <a href="{{ route('admin.question-banks.questions.index', $bank->id) }}" class="btn btn-outline-secondary">@lang('sprint4.question_banks.form.cancel')</a>
                    <button type="submit" class="btn btn-primary">@lang('sprint4.question_banks.form.save')</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var qtype = document.getElementById('qtype');
    var mcq = document.getElementById('mcq-options');
    var tf = document.getElementById('tf-options');
    function refresh() {
        mcq.style.display = qtype.value === 'mcq' ? '' : 'none';
        tf.style.display = qtype.value === 'true_false' ? '' : 'none';
    }
    qtype.addEventListener('change', refresh);
    refresh();
});
</script>
@endsection
