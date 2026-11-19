@extends('layouts.app')
@section('title', __('special_programs/exams.list_title'))
@section('page-title', __('special_programs/exams.list_title').' — '.\App\Modules\SpecialPrograms\Support\Programs::label($program))
@section('content')
<div class="card"><div class="card-body">
@if(session('success'))<div class="alert alert-success" role="alert">{{ session('success') }}</div>@endif
<a class="btn btn-primary mb-3" href="{{ route('manage.special-programs.exams.create', $program) }}"><x-svg-icon name="plus-lg" /> {{ __('special_programs/exams.new_exam') }}</a>
<form method="GET" class="form-inline mb-3">
<label class="mr-1" for="role">{{ __('special_programs/exams.filter_role') }}</label>
<select name="role" id="role" class="form-control mr-2">
<option value="">{{ __('special_programs/exams.all') }}</option>
@foreach(['admin', 'teacher'] as $r)<option value="{{ $r }}" @selected($role === $r)>{{ __('special_programs.creator_roles.'.$r) }}</option>@endforeach
</select>
<input type="search" name="q" class="form-control mr-2" value="{{ $q }}" placeholder="{{ __('special_programs/exams.search') }}" aria-label="{{ __('special_programs/exams.search') }}">
<button class="btn btn-light">{{ __('special_programs.buttons.search') }}</button>
</form>
<div class="table-responsive"><table class="table"><thead><tr>
<th scope="col">{{ __('special_programs/exams.col_title') }}</th><th scope="col">{{ __('special_programs/exams.col_subjects') }}</th>
<th scope="col">{{ __('special_programs/exams.col_role') }}</th><th scope="col">{{ __('special_programs/exams.col_creator') }}</th>
<th scope="col">{{ __('special_programs/exams.col_expires') }}</th><th scope="col">{{ __('special_programs/exams.col_attempts') }}</th>
</tr></thead><tbody>
@forelse($exams as $exam)
<tr>
<td><a href="{{ route('manage.special-programs.exams.show', [$program, $exam->id]) }}">{{ $exam->title }}</a></td>
<td>{{ $exam->subjects->map(fn ($s) => __('special_programs.subjects.'.$s->subject?->code))->implode('، ') }}</td>
<td>{{ __('special_programs.creator_roles.'.$exam->creator_role) }}</td>
<td>{{ $creators[$exam->created_by] ?? '' }}</td>
<td>{{ $exam->expires_at?->format('Y-m-d H:i') }} @if($exam->expires_at?->isPast())<span class="badge badge-secondary">{{ __('special_programs/exams.expired') }}</span>@endif</td>
<td>{{ $exam->attempts_count }}</td>
</tr>@empty<tr><td colspan="6">{{ __('special_programs/exams.empty') }}</td></tr>@endforelse
</tbody></table></div>
{{ $exams->links() }}
</div></div>
@endsection
