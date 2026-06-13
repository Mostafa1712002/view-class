@extends('layouts.admin')

@section('title', __('student.special_ed.title'))
@section('body_class','theme-light')

@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-9 col-12 mb-2">
        <h2 class="content-header-title mb-0">@lang('student.special_ed.title')</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('student.dashboard') }}">@lang('student.special_ed.home')</a></li>
                <li class="breadcrumb-item active">@lang('student.special_ed.title')</li>
            </ol>
        </div>
    </div>
</div>

<div class="content-body">
    @if (! $record)
        <div class="card">
            <div class="card-body text-center text-muted py-5">
                <i class="la la-heart" style="font-size:2.5rem;"></i>
                <p class="mb-0 mt-2">@lang('student.special_ed.none')</p>
            </div>
        </div>
    @else
        {{-- Overview --}}
        <div class="card mb-3">
            <div class="card-header"><h4 class="card-title mb-0">@lang('student.special_ed.overview')</h4></div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 col-6 mb-2"><div class="text-muted small">@lang('student.special_ed.category')</div><div class="fw-bold">{{ $record->category ?: '—' }}</div></div>
                    <div class="col-md-3 col-6 mb-2"><div class="text-muted small">@lang('student.special_ed.severity')</div><div class="fw-bold">{{ $record->severity ?: '—' }}</div></div>
                    <div class="col-md-3 col-6 mb-2"><div class="text-muted small">@lang('student.special_ed.status')</div><div class="fw-bold">{{ $record->status ?: '—' }}</div></div>
                    <div class="col-md-3 col-6 mb-2"><div class="text-muted small">@lang('student.special_ed.specialist')</div><div class="fw-bold">{{ $record->specialist?->name ?: '—' }}</div></div>
                </div>
                @if ($record->diagnosis)
                    <div class="mt-2"><div class="text-muted small">@lang('student.special_ed.diagnosis')</div><div>{{ $record->diagnosis }}</div></div>
                @endif
            </div>
        </div>

        {{-- Plans --}}
        <div class="card mb-3">
            <div class="card-header"><h4 class="card-title mb-0">@lang('student.special_ed.plans')</h4></div>
            <div class="card-body">
                @forelse ($record->plans as $plan)
                    <div class="border rounded p-2 mb-2">
                        <div class="fw-bold">{{ $plan->title ?? $plan->goal ?? __('student.special_ed.plan') }}</div>
                        @if (!empty($plan->description))<div class="text-muted small">{{ $plan->description }}</div>@endif
                    </div>
                @empty
                    <div class="text-muted">@lang('student.special_ed.no_plans')</div>
                @endforelse
            </div>
        </div>

        {{-- Notes the student is allowed to see --}}
        <div class="card">
            <div class="card-header"><h4 class="card-title mb-0">@lang('student.special_ed.notes')</h4></div>
            <div class="card-body">
                @forelse ($record->notes as $note)
                    <div class="border-bottom py-2">
                        <div>{{ $note->body ?? $note->note ?? '' }}</div>
                        <div class="text-muted small">{{ optional($note->created_at)->format('Y-m-d') }}</div>
                    </div>
                @empty
                    <div class="text-muted">@lang('student.special_ed.no_notes')</div>
                @endforelse
            </div>
        </div>
    @endif
</div>
@endsection
