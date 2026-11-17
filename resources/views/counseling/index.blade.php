@extends('layouts.app')
@section('title', __('counseling.title'))
@section('page-title', __('counseling.title'))
@section('content')
<div class="card"><div class="card-body">
@if($errors->any())<div class="alert alert-danger" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
<a class="btn btn-primary mb-3" href="{{ route('manage.counseling.create') }}">{{ __('counseling.new') }}</a>
@foreach(['status' => $statusCounts, 'category' => $categoryCounts] as $field => $counts)
<h2 class="h5">{{ __('counseling.'.$field.'_report') }}</h2>
<div class="d-flex flex-wrap mb-3">
@foreach($field === 'status' ? \App\Modules\Counseling\Models\CounselingCase::STATUSES : \App\Modules\Counseling\Models\CounselingCase::CATEGORIES as $value)
<span class="badge badge-light m-1">{{ __('counseling.'.($field === 'status' ? 'statuses' : 'categories').'.'.$value) }}: {{ $counts[$value] ?? 0 }}</span>
@endforeach
</div>
@endforeach
@php($autoSubmit = 'this.form.submit()')
<form method="GET" class="mb-3"><div class="form-row align-items-end">
@if($schools->isNotEmpty())
<div class="col-md-3 col-6 mb-2"><label for="school_id">{{ __('counseling.school') }}</label>
<select class="form-control" id="school_id" name="school_id" onchange="{{ $autoSubmit }}"><option value="">{{ __('counseling.all') }}</option>
@foreach($schools as $school)<option value="{{ $school->id }}" @selected(request('school_id') == $school->id)>{{ $school->name }}</option>@endforeach
</select></div>
@endif
<div class="col-md-3 col-6 mb-2"><label for="section_id">{{ __('counseling.grade') }}</label>
<select class="form-control" id="section_id" name="section_id" onchange="{{ $autoSubmit }}"><option value="">{{ __('counseling.all_grades') }}</option>
@foreach($sections as $section)<option value="{{ $section->id }}" @selected(request('section_id') == $section->id)>{{ $section->name }}@if($schools->isNotEmpty() && ! request('school_id')) — {{ $section->school?->name }}@endif</option>@endforeach
</select></div>
<div class="col-md-3 col-6 mb-2"><label for="class_id">{{ __('counseling.class') }}</label>
<select class="form-control" id="class_id" name="class_id"><option value="">{{ __('counseling.all_classes') }}</option>
@foreach($classes as $class)<option value="{{ $class->id }}" @selected(request('class_id') == $class->id)>{{ $class->name }}{{ $class->division ? ' - '.$class->division : '' }}</option>@endforeach
</select></div>
@if($counselors->isNotEmpty())
<div class="col-md-3 col-6 mb-2"><label for="counselor_id">{{ __('counseling.counselor') }}</label>
<select class="form-control" id="counselor_id" name="counselor_id"><option value="">{{ __('counseling.all') }}</option>
@foreach($counselors as $counselor)<option value="{{ $counselor->id }}" @selected(request('counselor_id') == $counselor->id)>{{ $counselor->name }}</option>@endforeach
</select></div>
@endif
@foreach(['status' => 'statuses', 'category' => 'categories'] as $field => $group)
<div class="col-md-3 col-6 mb-2"><label for="{{ $field }}">{{ __('counseling.'.$field) }}</label>
<select class="form-control" id="{{ $field }}" name="{{ $field }}"><option value="">{{ __('counseling.all') }}</option>
@foreach(__('counseling.'.$group) as $value => $label)<option value="{{ $value }}" @selected(request($field) === $value)>{{ $label }}</option>@endforeach
</select></div>
@endforeach
<div class="col-md-3 col-6 mb-2"><label for="date_from">{{ __('counseling.date_from') }}</label><input type="date" class="form-control" id="date_from" name="date_from" value="{{ request('date_from') }}"></div>
<div class="col-md-3 col-6 mb-2"><label for="date_to">{{ __('counseling.date_to') }}</label><input type="date" class="form-control" id="date_to" name="date_to" value="{{ request('date_to') }}"></div>
<div class="col-md-3 col-6 mb-2"><label for="student">{{ __('counseling.student') }}</label><input class="form-control" id="student" name="student" value="{{ request('student') }}" placeholder="{{ __('counseling.search_student') }}"></div>
<div class="col-md-3 col-6 mb-2"><button class="btn btn-primary">{{ __('counseling.filter') }}</button>
<a class="btn btn-light" href="{{ route('manage.counseling.index') }}">{{ __('counseling.reset') }}</a></div>
</div></form>
<div class="table-responsive"><table class="table"><thead><tr>
@foreach(['case_title','student','school','grade','class','counselor','category','status','opened_at'] as $field)<th scope="col">{{ __('counseling.'.$field) }}</th>@endforeach
</tr></thead><tbody>
@forelse($cases as $case)
@php($studentClass = $case->studentClass())
<tr>
<td><a href="{{ route('manage.counseling.show', $case->id) }}">{{ $case->title }}</a></td>
<td>{{ $case->student?->name }}</td>
<td>{{ $case->school?->name ?? '—' }}</td>
<td>{{ ($studentClass?->section ?? $case->student?->section)?->name ?? '—' }}</td>
<td>{{ $studentClass ? $studentClass->name.($studentClass->division ? ' - '.$studentClass->division : '') : '—' }}</td>
<td>{{ $case->counselor?->name }}</td><td>{{ __('counseling.categories.'.$case->category) }}</td><td>{{ __('counseling.statuses.'.$case->status) }}</td>
<td>{{ $case->opened_at?->format('Y-m-d') }}</td>
</tr>@empty<tr><td colspan="9">{{ __('counseling.empty') }}</td></tr>@endforelse
</tbody></table></div>{{ $cases->links() }}
</div></div>
@endsection
