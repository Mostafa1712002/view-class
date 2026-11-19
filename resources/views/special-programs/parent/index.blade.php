@extends('layouts.app')
@section('title', __('special_programs/parent.title'))
@section('page-title', __('special_programs/parent.title'))
@section('content')
@forelse($children as $child)
    <div class="ds-card mb-3"><div class="card-body">
        <h5 class="ds-card-title">{{ $child->name }}</h5>
        @forelse($child->sp_programs as $program)
            <a class="btn btn-outline-primary btn-sm" href="{{ route('parent.programs.report', [$child->id, $program]) }}">{{ __('special_programs.programs.'.$program) }}</a>
        @empty
            <span class="text-muted">{{ __('special_programs/parent.no_programs') }}</span>
        @endforelse
    </div></div>
@empty
    <div class="alert alert-info" role="status">{{ __('special_programs/parent.no_children') }}</div>
@endforelse
@endsection
