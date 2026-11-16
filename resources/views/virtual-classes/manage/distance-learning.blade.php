@extends('layouts.admin')
@section('title', __('distance_learning.title'))
@section('content')
<div class="container-fluid">
    <h1 class="h3 mb-4">{{ __('distance_learning.title') }}</h1>
    <div class="d-flex flex-wrap gap-2 mb-4">
        <a class="btn btn-primary" href="{{ route('manage.virtual-classes.create') }}">{{ __('distance_learning.schedule') }}</a>
        <a class="btn btn-outline-primary" href="{{ route('manage.discussion-rooms.index') }}">{{ __('distance_learning.discussion_rooms') }}</a>
    </div>
    <div class="row g-3 mb-4">
        @foreach($counts as $key => $count)
            <div class="col-sm-6 col-lg-3"><div class="card"><div class="card-body">
                <div>{{ __('distance_learning.'.$key) }}</div><strong class="h3">{{ $count }}</strong>
            </div></div></div>
        @endforeach
    </div>
    @foreach(['upcoming' => $upcoming, 'held' => $held] as $kind => $sessions)
        <div class="card mb-4"><div class="card-body">
            <h2 class="h5">{{ __('distance_learning.'.$kind) }}</h2>
            <ul class="list-group list-group-flush">
                @forelse($sessions as $session)
                    <li class="list-group-item d-flex flex-wrap justify-content-between gap-2">
                        <a href="{{ route('manage.virtual-classes.show', $session->id) }}">{{ $session->title }}</a>
                        <span>{{ $session->scheduled_at->format('Y-m-d H:i') }}</span>
                        @if($kind === 'held')
                            <span>{{ __('distance_learning.attendance') }}: {{ $session->attendance_percentage === null ? '—' : $session->attendance_percentage.'%' }}</span>
                        @endif
                    </li>
                @empty
                    <li class="list-group-item text-muted">{{ __('distance_learning.empty') }}</li>
                @endforelse
            </ul>
        </div></div>
    @endforeach
</div>
@endsection
