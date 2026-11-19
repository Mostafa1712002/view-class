@extends('layouts.app')
@section('title', __('special_programs/staff.add_student'))
@section('page-title', __('special_programs.programs.'.$program))
@section('content')
@include('special-programs.partials.program-tabs', ['program' => $program, 'active' => 'enrollments'])
@if($errors->any())<div class="alert alert-danger" role="alert">@foreach($errors->all() as $e)<p class="mb-0">{{ $e }}</p>@endforeach</div>@endif
@unless($hasSchool)<div class="alert alert-warning" role="alert">{{ __('special_programs/staff.pick_school') }}</div>@endunless
@if($hasSchool)
@include('special-programs.staff._alerts', ['alerts' => $alerts])
<div class="ds-card"><div class="card-body">
    <h5 class="mb-3">{{ __('special_programs/staff.add_student') }}</h5>
    <form method="GET" class="form-row align-items-end mb-3">
        <div class="col-md-4 mb-2"><label for="class_id">{{ __('special_programs/staff.class') }}</label>
            <select class="form-control" id="class_id" name="class_id" onchange="this.form.submit()">
                <option value="">{{ __('special_programs/staff.all_classes') }}</option>
                @foreach($classes as $c)
                    <option value="{{ $c->id }}" @selected($classId == $c->id)>{{ $c->section?->name }} - {{ $c->name }}{{ $c->division ? ' - '.$c->division : '' }}@unless($c->eligible) ({{ __('special_programs/staff.outside_target') }})@endunless</option>
                @endforeach
            </select></div>
        <div class="col-md-4 mb-2"><label for="q">{{ __('special_programs/staff.search') }}</label>
            <input class="form-control" id="q" name="q" value="{{ $q }}" placeholder="{{ __('special_programs/staff.search_placeholder') }}"></div>
        <div class="col-md-4 mb-2">
            <div class="custom-control custom-checkbox d-inline-block ml-2 mr-2">
                <input type="checkbox" class="custom-control-input" id="all" name="all" value="1" @checked($showAll) onchange="this.form.submit()">
                <label class="custom-control-label" for="all">{{ __('special_programs/staff.show_all_classes') }}</label>
            </div>
            <button class="btn btn-outline-secondary btn-sm">{{ __('special_programs/staff.search') }}</button>
        </div>
    </form>

    @if($students === null)
        <p class="text-muted mb-0">{{ __('special_programs/staff.pick_class_or_search') }}</p>
    @elseif($students->isEmpty())
        <p class="text-muted mb-0">{{ __('special_programs/staff.no_students_found') }}</p>
    @else
    <form method="POST" action="{{ route('manage.special-programs.enrollments.store', $program) }}">
        @csrf
        <input type="hidden" name="class_id" value="{{ $classId }}">
        <table class="table table-sm">
            <tbody>
            @foreach($students as $s)
                @php($done = in_array($s->id, $enrolledIds, true))
                <tr><td style="width:2rem"><input type="checkbox" name="student_ids[]" value="{{ $s->id }}" id="s{{ $s->id }}" @checked($done) @disabled($done) aria-label="{{ $s->name }}"></td>
                    <td><label for="s{{ $s->id }}" class="mb-0">{{ $s->name }}</label>@if($done) <span class="badge badge-light">{{ __('special_programs/staff.already_enrolled') }}</span>@endif</td>
                    <td>{{ $s->national_id }}</td></tr>
            @endforeach
            </tbody>
        </table>
        @if($classId)
        <div class="custom-control custom-checkbox mb-2">
            <input type="checkbox" class="custom-control-input" name="all_students" id="all_students" value="1">
            <label class="custom-control-label" for="all_students">{{ __('special_programs/staff.enroll_all_class') }}</label>
        </div>
        @endif
        @if($groups->isNotEmpty())
        <div class="form-group"><label for="group_id">{{ __('special_programs/staff.group') }}</label>
            <select class="form-control" id="group_id" name="group_id"><option value="">{{ __('special_programs/staff.no_group') }}</option>
                @foreach($groups as $id => $name)<option value="{{ $id }}">{{ $name }}</option>@endforeach
            </select></div>
        @endif
        <button class="btn btn-primary">{{ __('special_programs/staff.save_enroll') }}</button>
        <a href="{{ route('manage.special-programs.enrollments.index', $program) }}">{{ __('special_programs/staff.back') }}</a>
    </form>
    @endif
</div></div>
@endif
@endsection
