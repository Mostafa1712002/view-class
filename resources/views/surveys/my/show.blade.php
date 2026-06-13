@extends('layouts.app')

@section('title', $model->title)
@section('body_class', 'theme-light')

@php $isRtl = app()->getLocale() === 'ar'; @endphp

@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-9 col-12 mb-2">
        <h2 class="content-header-title float-{{ $isRtl ? 'right' : 'left' }} mb-0">
            {{ $model->title }}
        </h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('surveys.breadcrumb_home')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('my.surveys.index') }}">@lang('surveys.breadcrumb_my')</a></li>
                <li class="breadcrumb-item active">{{ Str::limit($model->title, 40) }}</li>
            </ol>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        @if($model->description)
            <p class="text-muted">{{ $model->description }}</p>
            <hr>
        @endif

        <form method="POST" action="{{ route('my.surveys.submit', $model->id) }}">
            @csrf

            @foreach($model->questions as $question)
                @php
                    $inputName = 'answers[' . $question->id . ']';
                    $hasError  = $errors->has('answers.' . $question->id);
                @endphp
                <div class="form-group mb-3 {{ $hasError ? 'has-error' : '' }}">
                    <label class="d-block font-weight-bold">
                        {{ $loop->iteration }}. {{ $question->text }}
                        @if($question->is_required)
                            <span class="text-danger">*</span>
                        @endif
                    </label>

                    @if($hasError)
                        <div class="text-danger small mb-1">{{ $errors->first('answers.' . $question->id) }}</div>
                    @endif

                    @if($question->type === 'single_choice')
                        @foreach($question->options ?? [] as $opt)
                            <div class="custom-control custom-radio">
                                <input type="radio"
                                       class="custom-control-input"
                                       id="q{{ $question->id }}_opt{{ $loop->index }}"
                                       name="{{ $inputName }}"
                                       value="{{ $opt }}"
                                       {{ old('answers.' . $question->id) === $opt ? 'checked' : '' }}>
                                <label class="custom-control-label" for="q{{ $question->id }}_opt{{ $loop->index }}">
                                    {{ $opt }}
                                </label>
                            </div>
                        @endforeach

                    @elseif($question->type === 'multiple_choice')
                        @foreach($question->options ?? [] as $opt)
                            @php
                                $oldVals = (array) old('answers.' . $question->id, []);
                            @endphp
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox"
                                       class="custom-control-input"
                                       id="q{{ $question->id }}_opt{{ $loop->index }}"
                                       name="{{ $inputName }}[]"
                                       value="{{ $opt }}"
                                       {{ in_array($opt, $oldVals) ? 'checked' : '' }}>
                                <label class="custom-control-label" for="q{{ $question->id }}_opt{{ $loop->index }}">
                                    {{ $opt }}
                                </label>
                            </div>
                        @endforeach

                    @elseif($question->type === 'rating')
                        <div class="d-flex gap-2">
                            @foreach([1,2,3,4,5] as $r)
                                <div class="custom-control custom-radio custom-control-inline">
                                    <input type="radio"
                                           class="custom-control-input"
                                           id="q{{ $question->id }}_r{{ $r }}"
                                           name="{{ $inputName }}"
                                           value="{{ $r }}"
                                           {{ (string) old('answers.' . $question->id) === (string) $r ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="q{{ $question->id }}_r{{ $r }}">
                                        {{ $r }} ★
                                    </label>
                                </div>
                            @endforeach
                        </div>

                    @else
                        {{-- text --}}
                        <textarea class="form-control @error('answers.' . $question->id) is-invalid @enderror"
                                  name="{{ $inputName }}"
                                  rows="3">{{ old('answers.' . $question->id) }}</textarea>
                    @endif
                </div>
            @endforeach

            <hr>
            <div class="d-flex gap-1">
                <button type="submit" class="btn btn-primary">
                    <i class="la la-paper-plane"></i> @lang('surveys.actions.submit')
                </button>
                <a href="{{ route('my.surveys.index') }}" class="btn btn-secondary ml-1">
                    @lang('common.cancel')
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
