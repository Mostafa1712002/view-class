@extends('layouts.app')
@section('title', __('special_programs.programs.'.$program))
@section('page-title', __('special_programs.programs.'.$program))
@section('content')
@include('special-programs.partials.program-tabs', ['program' => $program, 'active' => ''])
@unless($hasSchool)<div class="alert alert-warning" role="alert">{{ __('special_programs/staff.pick_school') }}</div>@endunless
@if($bankEmpty)<div class="alert alert-warning" role="alert">{{ __('special_programs/staff.empty_bank') }}</div>@endif
@include('special-programs.staff._alerts', ['alerts' => $alerts])
<div class="row">
    @if($enrolled !== null)
    <div class="col-md-4 mb-3"><div class="ds-card"><div class="card-body">
        <h6><x-svg-icon name="people" :size="15" /> {{ __('special_programs/staff.enrolled_students') }}</h6>
        <p class="h3 mb-2">{{ $enrolled }}</p>
        @if(auth()->user()->canDo('special_programs.manage'))
            <a class="btn btn-outline-primary btn-sm" href="{{ route('manage.special-programs.enrollments.index', $program) }}">{{ __('special_programs/staff.tabs.enrollments') }}</a>
            <a class="btn btn-primary btn-sm" href="{{ route('manage.special-programs.enrollments.create', $program) }}">{{ __('special_programs/staff.add_student') }}</a>
        @endif
    </div></div></div>
    @endif
    <div class="col-md-8 mb-3"><div class="ds-card"><div class="card-body">
        <h6>{{ __('special_programs/staff.bank_questions') }}</h6>
        <table class="table table-sm mb-0"><tbody>
        @foreach($subjects as $s)
            <tr><td>{{ __('special_programs.subjects.'.$s->code) }}</td><td>{{ $s->total }}</td></tr>
        @endforeach
        </tbody></table>
    </div></div></div>
</div>
@endsection
