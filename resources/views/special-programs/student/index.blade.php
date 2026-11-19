@extends('layouts.app')
@section('title', __('special_programs/student.title'))
@section('page-title', __('special_programs/student.title'))
@section('content')
@if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
@if(! $enrolled)
    <div class="alert alert-info">{{ __('special_programs/student.not_enrolled') }}</div>
@elseif($cards->isEmpty())
    <div class="alert alert-info">{{ __('special_programs/student.no_exams') }}</div>
@else
<div class="row">
    @foreach($cards as $c)
        @php($e = $c['exam'])
        <div class="col-md-6 col-xl-4 mb-2">
            <div class="card h-100"><div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-1">
                    <h5 class="mb-0">{{ $e->title }}</h5>
                    <span class="badge badge-{{ ['completed' => 'success', 'in_progress' => 'warning', 'expired' => 'secondary', 'available' => 'primary'][$c['state']] }}">{{ __('special_programs/student.state.'.$c['state']) }}</span>
                </div>
                <div class="text-muted small mb-1">{{ \App\Modules\SpecialPrograms\Support\Programs::label($e->program) }} · {{ __('special_programs/student.subjects_done', ['done' => $c['done'], 'total' => $c['total']]) }}</div>
                @if($e->expires_at)<div class="text-muted small mb-2"><x-svg-icon name="clock" :size="13" /> {{ __('special_programs/student.expires') }} {{ $e->expires_at->format('Y-m-d H:i') }}</div>@endif
                <a class="btn btn-primary btn-sm" href="{{ route('student.programs.exam', $e->id) }}">{{ __('special_programs/student.open') }}</a>
            </div></div>
        </div>
    @endforeach
</div>
@endif
@endsection
