@extends('layouts.app')
@section('title', __('special_programs/teacher.title'))
@section('page-title', __('special_programs/teacher.title'))
@section('content')
<div class="row mb-3">
    @foreach(['exams' => 'exams', 'students' => 'students', 'completed' => 'completed', 'pending' => 'pending', 'avg' => 'average', 'active' => 'active'] as $key => $label)
        <div class="col-md-2 col-6 mb-2"><div class="ds-card"><div class="card-body text-center">
            <div class="text-muted small">{{ __('special_programs/teacher.'.$label) }}</div>
            <div class="h4 mb-0">{{ $key === 'avg' ? $stats[$key].'%' : $stats[$key] }}</div>
        </div></div></div>
    @endforeach
</div>
<div class="ds-card"><div class="card-body">
    @if(count($programs) > 1)
        <form method="GET" class="form-inline mb-3">
            <label for="program" class="ml-2">{{ __('special_programs/teacher.program') }}</label>
            <select id="program" name="program" class="form-control ml-2" onchange="this.form.submit()">
                <option value="">{{ __('special_programs/teacher.all_programs') }}</option>
                @foreach($programs as $p)<option value="{{ $p }}" @selected($program === $p)>{{ __('special_programs.programs.'.$p) }}</option>@endforeach
            </select>
        </form>
    @endif
    <div class="table-responsive"><table class="table">
        <thead><tr>
            <th scope="col">{{ __('special_programs/teacher.exam') }}</th><th scope="col">{{ __('special_programs/teacher.program') }}</th>
            <th scope="col">{{ __('special_programs/teacher.subjects') }}</th><th scope="col">{{ __('special_programs/teacher.total') }}</th>
            <th scope="col">{{ __('special_programs/teacher.completed') }}</th><th scope="col">{{ __('special_programs/teacher.pending') }}</th>
            <th scope="col">{{ __('special_programs/teacher.avg_pct') }}</th><th scope="col"></th>
        </tr></thead>
        <tbody>
        @forelse($rows as $r)
            <tr>
                <td>{{ $r->title }}</td><td>{{ __('special_programs.programs.'.$r->program) }}</td>
                <td>{{ implode('، ', $r->subjects) }}</td><td>{{ $r->total }}</td><td>{{ $r->completed }}</td><td>{{ $r->pending }}</td>
                <td>{{ $r->avg_pct }}%</td>
                <td><a class="btn btn-sm btn-outline-primary" href="{{ route('manage.special-programs.teacher-reports.exam', $r->exam_id) }}"><x-svg-icon name="bar-chart" :size="14" /> {{ __('special_programs/teacher.view_report') }}</a></td>
            </tr>
        @empty
            <tr><td colspan="8">{{ __('special_programs/teacher.empty') }}</td></tr>
        @endforelse
        </tbody>
    </table></div>
</div></div>
@endsection
