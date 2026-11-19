@extends('layouts.app')
@section('title', __('special_programs/parent.title'))
@section('page-title', __('special_programs/parent.title'))
@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div class="d-flex align-items-center gap-2">
        <a class="btn btn-outline-secondary btn-sm" href="{{ route('parent.programs.index') }}">{{ __('special_programs/parent.back') }}</a>
        @if($children->count() > 1)
            <select class="form-select form-select-sm" style="width:auto" aria-label="{{ __('special_programs/parent.child') }}" onchange="location.href=this.value">
                @foreach($children as $c)
                    <option value="{{ route('parent.programs.report', [$c->id, $program]) }}" @selected($c->id == $childId)>{{ $c->name }}</option>
                @endforeach
            </select>
        @endif
    </div>
    <a class="btn btn-primary btn-sm" href="{{ route('parent.programs.report.pdf', [$childId, $program]) }}"><x-svg-icon name="download" :size="14" /> {{ __('special_programs/parent.download_pdf') }}</a>
</div>
@include('special-programs.partials.student-report', ['report' => $report])
@endsection
