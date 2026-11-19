@extends('layouts.app')
@section('title', __('special_programs/staff.tabs.enrollments'))
@section('page-title', __('special_programs.programs.'.$program))
@section('content')
@include('special-programs.partials.program-tabs', ['program' => $program, 'active' => 'enrollments'])
@if(session('success'))<div class="alert alert-success" role="alert">{{ session('success') }}</div>@endif
@unless($hasSchool)<div class="alert alert-warning" role="alert">{{ __('special_programs/staff.pick_school') }}</div>@endunless
@if($hasSchool)
<div class="ds-card"><div class="card-body">
    <div class="d-flex justify-content-between flex-wrap mb-3">
        <form method="GET" class="form-inline">
            <input class="form-control mr-2 ml-2" name="q" value="{{ request('q') }}" placeholder="{{ __('special_programs/staff.search_placeholder') }}" aria-label="{{ __('special_programs/staff.search') }}">
            <button class="btn btn-outline-secondary btn-sm">{{ __('special_programs/staff.search') }}</button>
        </form>
        @if(auth()->user()->canDo('special_programs.manage'))
            <a class="btn btn-primary btn-sm" href="{{ route('manage.special-programs.enrollments.create', $program) }}"><x-svg-icon name="plus-lg" :size="14" /> {{ __('special_programs/staff.add_student') }}</a>
        @endif
    </div>
    @if($students->isEmpty())
        <p class="text-muted mb-0">{{ __('special_programs/staff.no_enrolled') }}</p>
    @else
    <div class="table-responsive"><table class="table table-sm">
        <thead><tr><th scope="col">{{ __('special_programs/staff.student') }}</th><th scope="col">{{ __('special_programs/staff.national_id') }}</th><th scope="col">{{ __('special_programs/staff.group') }}</th><th scope="col">{{ __('special_programs/staff.enrolled_at') }}</th><th scope="col"></th></tr></thead>
        <tbody>
        @foreach($students as $s)
            <tr>
                <td>{{ $s->name }}</td><td>{{ $s->national_id }}</td>
                <td>{{ $groups[$s->group_id] ?? __('special_programs/staff.no_group') }}</td>
                <td>{{ \Illuminate\Support\Carbon::parse($s->enrolled_at)->format('Y-m-d') }}</td>
                <td class="text-nowrap">
                    @if(Route::has('manage.special-programs.student-report'))<a class="btn btn-link btn-sm" href="{{ route('manage.special-programs.student-report', [$program, $s->id]) }}">{{ __('special_programs.buttons.view') }}</a>@endif
                    @if(auth()->user()->canDo('special_programs.manage'))
                    <form method="POST" action="{{ route('manage.special-programs.enrollments.destroy', [$program, $s->enrollment_id]) }}" class="d-inline" onsubmit="return confirm('{{ __('special_programs/staff.remove_confirm') }}')">
                        @csrf @method('DELETE')<button class="btn btn-outline-danger btn-sm">{{ __('special_programs/staff.remove') }}</button>
                    </form>
                    @endif
                </td>
            </tr>
        @endforeach
        </tbody>
    </table></div>
    {{ $students->links() }}
    @endif
</div></div>
@endif
@endsection
