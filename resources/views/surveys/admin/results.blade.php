@extends('layouts.app')

@section('title', __('surveys.results_title') . ' — ' . $model->title)
@section('body_class', 'theme-light')

@php $isRtl = app()->getLocale() === 'ar'; @endphp

@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-9 col-12 mb-2">
        <h2 class="content-header-title float-{{ $isRtl ? 'right' : 'left' }} mb-0">
            @lang('surveys.results_title'): {{ $model->title }}
        </h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('surveys.breadcrumb_home')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.surveys.index') }}">@lang('surveys.breadcrumb_index')</a></li>
                <li class="breadcrumb-item active">@lang('surveys.breadcrumb_results')</li>
            </ol>
        </div>
    </div>
    <div class="content-header-right text-md-{{ $isRtl ? 'left' : 'right' }} col-md-3 col-12 d-flex justify-content-{{ $isRtl ? 'start' : 'end' }}">
        <span class="badge badge-primary" style="font-size:1rem; padding:.5rem 1rem;">
            @lang('surveys.responses_count'): {{ $responsesCount }}
        </span>
    </div>
</div>

@if($responsesCount === 0)
    <div class="alert alert-info">@lang('surveys.results_empty')</div>
@else
    @foreach($aggregated as $qId => $data)
        @php
            /** @var \App\Models\SurveyQuestion $question */
            $question = $data['question'];
            $counts   = $data['counts'];
            $texts    = $data['texts'];
            $total    = array_sum($counts) ?: 1;
        @endphp
        <div class="card mb-2">
            <div class="card-header py-2">
                <strong>{{ $loop->iteration }}. {{ $question->text }}</strong>
                <span class="badge badge-light ml-1">{{ __('surveys.question_types.' . $question->type) }}</span>
            </div>
            <div class="card-body py-2">

                @if($question->type === 'text')
                    {{-- Text answers list --}}
                    @if(count($texts) === 0)
                        <p class="text-muted mb-0">@lang('surveys.no_text_answers')</p>
                    @else
                        <h6>@lang('surveys.section_text_answers')</h6>
                        <ul class="list-group list-group-flush">
                            @foreach($texts as $t)
                                <li class="list-group-item py-1">{{ $t }}</li>
                            @endforeach
                        </ul>
                    @endif

                @elseif($question->type === 'rating')
                    {{-- Rating bar --}}
                    <h6>@lang('surveys.section_choice_counts')</h6>
                    @foreach([1,2,3,4,5] as $r)
                        @php $cnt = $counts[(string)$r] ?? 0; @endphp
                        <div class="d-flex align-items-center mb-1">
                            <span style="width:60px;">{{ $r }} ★</span>
                            <div class="progress flex-grow-1 mx-1" style="height:18px;">
                                <div class="progress-bar bg-warning"
                                     style="width:{{ $total > 0 ? round($cnt / $total * 100) : 0 }}%">
                                    {{ $cnt }}
                                </div>
                            </div>
                            <span style="width:50px;" class="text-muted small">{{ $cnt }}</span>
                        </div>
                    @endforeach

                @else
                    {{-- single_choice / multiple_choice --}}
                    <h6>@lang('surveys.section_choice_counts')</h6>
                    @forelse($question->options ?? [] as $opt)
                        @php $cnt = $counts[$opt] ?? 0; @endphp
                        <div class="d-flex align-items-center mb-1">
                            <span style="min-width:120px;">{{ $opt }}</span>
                            <div class="progress flex-grow-1 mx-1" style="height:18px;">
                                <div class="progress-bar bg-primary"
                                     style="width:{{ $total > 0 ? round($cnt / $total * 100) : 0 }}%">
                                    {{ $cnt }}
                                </div>
                            </div>
                            <span style="width:50px;" class="text-muted small">{{ $cnt }}</span>
                        </div>
                    @empty
                        @foreach($counts as $opt => $cnt)
                            <div class="d-flex align-items-center mb-1">
                                <span style="min-width:120px;">{{ $opt }}</span>
                                <div class="progress flex-grow-1 mx-1" style="height:18px;">
                                    <div class="progress-bar bg-primary"
                                         style="width:{{ round($cnt / $total * 100) }}%">
                                        {{ $cnt }}
                                    </div>
                                </div>
                                <span style="width:50px;" class="text-muted small">{{ $cnt }}</span>
                            </div>
                        @endforeach
                    @endforelse
                @endif

            </div>
        </div>
    @endforeach
@endif
@endsection
