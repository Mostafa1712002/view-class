@extends('layouts.app')
@section('title', $exam->title)
@section('page-title', $exam->title)
@section('content')
<div class="card mb-3"><div class="card-body">
@if(session('success'))<div class="alert alert-success" role="alert">{{ session('success') }}</div>@endif
<h2 class="h5">{{ __('special_programs/exams.config') }}</h2>
<dl class="row">
<dt class="col-sm-3">{{ __('special_programs/exams.type') }}</dt><dd class="col-sm-9">{{ __('special_programs.exam_types.'.$exam->type) }} — {{ __('special_programs.creator_roles.'.$exam->creator_role) }} {{ $creator?->name }}</dd>
<dt class="col-sm-3">{{ __('special_programs/exams.col_expires') }}</dt><dd class="col-sm-9">{{ $exam->expires_at?->format('Y-m-d H:i') }} ({{ $exam->expiry_hours }} {{ __('special_programs/exams.expiry_hours') }})</dd>
<dt class="col-sm-3">{{ __('special_programs/exams.exit_limit') }}</dt><dd class="col-sm-9">{{ $exam->max_exit_attempts ?? __('special_programs/exams.off') }}</dd>
<dt class="col-sm-3">{{ __('special_programs/exams.audience') }}</dt><dd class="col-sm-9">
{{ $exam->targets->map(fn ($t) => $t->class_id ? ($classNames[$t->class_id] ?? '') : ($groupNames[$t->group_id] ?? ''))->filter()->implode('، ') }}</dd>
</dl>
<div class="table-responsive"><table class="table"><thead><tr>
<th scope="col">{{ __('special_programs/exams.order') }}</th><th scope="col">{{ __('special_programs/exams.subject') }}</th>
<th scope="col">{{ __('special_programs/exams.duration') }}</th><th scope="col">{{ __('special_programs/exams.questions') }}</th><th scope="col"></th>
</tr></thead><tbody>
@foreach($exam->subjects as $es)
<tr><td>{{ $es->order_index }}</td><td>{{ __('special_programs.subjects.'.$es->subject?->code) }}</td>
<td>{{ $es->duration_minutes }} {{ __('special_programs/exams.minutes') }}</td><td>{{ $es->total_questions }}</td>
<td>
@if($es->term){{ __('special_programs.terms.'.$es->term) }} · {{ __('special_programs/exams.weeks') }}: {{ implode('، ', $es->weeks ?? []) }}<br>@endif
@if($es->easy_pct !== null){{ __('special_programs/exams.easy') }} {{ $es->easy_pct }} · {{ __('special_programs/exams.medium') }} {{ $es->medium_pct }} · {{ __('special_programs/exams.hard') }} {{ $es->hard_pct }}@if($es->range_start !== null) · {{ __('special_programs/exams.range') }} {{ $es->range_start }}–{{ $es->range_end }}@endif<br>@endif
@if($es->question_grade_levels){{ __('special_programs/exams.levels') }}: {{ implode('، ', $es->question_grade_levels) }}<br>@endif
@foreach($es->skillShares as $share){{ $share->skill?->name }} {{ $share->percentage }}% @endforeach
</td></tr>
@endforeach
</tbody></table></div>
<a class="btn btn-light" href="{{ route('manage.special-programs.exams.export', [$program, $exam->id]) }}"><x-svg-icon name="file-earmark-excel" /> {{ __('special_programs/exams.export') }}</a>
@if($canDelete)
<form method="POST" action="{{ route('manage.special-programs.exams.destroy', [$program, $exam->id]) }}" class="d-inline" onsubmit="return confirm(@js(__('special_programs/exams.delete_confirm')))">
@csrf @method('DELETE')
<button class="btn btn-danger"><x-svg-icon name="trash" :size="16" /> {{ __('special_programs/exams.delete') }}</button>
</form>
@endif
</div></div>
<div class="card"><div class="card-body">
<h2 class="h5">{{ __('special_programs/exams.monitor') }}</h2>
@if($monitor->isEmpty())<div class="alert alert-warning" role="alert">{{ __('special_programs/exams.no_students') }}</div>@else
<div class="table-responsive"><table class="table"><thead><tr>
@foreach(['student', 'subject', 'status', 'score', 'answered'] as $c)<th scope="col">{{ __('special_programs/exams.col_'.$c) }}</th>@endforeach
</tr></thead><tbody>
@foreach($monitor as $row)
<tr><td>{{ $row['student_name'] }}</td><td>{{ $row['subject_name'] }}</td>
<td>{{ $row['status'] === 'not_started' ? __('special_programs/exams.not_started') : __('special_programs.attempt_status.'.$row['status']) }}</td>
<td>{{ $row['score_pct'] !== null ? number_format((float) $row['score_pct'], 2) : '—' }}</td>
<td>{{ $row['attempt_id'] ? $row['answered_count'].' / '.$row['items_count'] : '—' }}</td></tr>
@endforeach
</tbody></table></div>@endif
</div></div>
@endsection
