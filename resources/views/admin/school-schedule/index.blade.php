@extends('layouts.app')

@section('title', __('sprint4.school_schedule.page_title'))

@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-8 col-12 mb-2">
        <h2 class="content-header-title mb-0">@lang('sprint4.school_schedule.page_title')</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
                <li class="breadcrumb-item active">@lang('sprint4.school_schedule.page_title')</li>
            </ol>
        </div>
    </div>
    <div class="content-header-right col-md-4 col-12 text-end">
        <a href="{{ route('admin.school-schedule.pdf', request()->query()) }}" class="btn btn-outline-primary btn-sm" target="_blank">
            <i class="la la-print"></i> @lang('sprint4.school_schedule.print')
        </a>
    </div>
</div>

<div class="content-body">
    <p class="text-muted small">@lang('sprint4.school_schedule.help')</p>

    <form action="{{ route('admin.school-schedule.index') }}" method="GET" class="card p-3 mb-3">
        <div class="row g-2">
            <div class="col-md-2">
                <label class="form-label">@lang('sprint4.school_schedule.filters.grade_level')</label>
                <select name="grade_level" class="form-select form-select-sm">
                    <option value="">@lang('sprint4.school_schedule.filters.all_grades')</option>
                    @for($g = 1; $g <= 12; $g++)
                        <option value="{{ $g }}" {{ (string)$filters['grade_level'] === (string)$g ? 'selected' : '' }}>{{ $g }}</option>
                    @endfor
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">@lang('sprint4.school_schedule.filters.class')</label>
                <select name="class_id" class="form-select form-select-sm">
                    <option value="">@lang('sprint4.school_schedule.filters.all_classes')</option>
                    @foreach($classes as $c)
                        <option value="{{ $c->id }}" {{ (string)$filters['class_id'] === (string)$c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">@lang('sprint4.school_schedule.filters.teacher')</label>
                <select name="teacher_id" class="form-select form-select-sm">
                    <option value="">@lang('sprint4.school_schedule.filters.all_teachers')</option>
                    @foreach($teachers as $t)
                        <option value="{{ $t->id }}" {{ (string)$filters['teacher_id'] === (string)$t->id ? 'selected' : '' }}>{{ $t->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">@lang('sprint4.school_schedule.filters.subject')</label>
                <select name="subject_id" class="form-select form-select-sm">
                    <option value="">@lang('sprint4.school_schedule.filters.all_subjects')</option>
                    @foreach($subjects as $s)
                        <option value="{{ $s->id }}" {{ (string)$filters['subject_id'] === (string)$s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end gap-1">
                <button type="submit" class="btn btn-primary btn-sm">@lang('sprint4.school_schedule.filters.apply')</button>
                <a href="{{ route('admin.school-schedule.index') }}" class="btn btn-outline-secondary btn-sm">@lang('sprint4.school_schedule.filters.reset')</a>
            </div>
        </div>
    </form>

    @php $days = \App\Models\ScheduleEntry::DAYS_AR; @endphp

    @if($slots->isEmpty())
        <div class="alert alert-warning">@lang('sprint4.school_schedule.no_entries')</div>
    @else
        <div class="card">
            <div class="table-responsive">
                <table class="table table-bordered align-middle text-center mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th style="width: 90px">#</th>
                            @foreach($days as $idx => $name)<th>{{ $name }}</th>@endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($slots as $slot)
                            <tr>
                                <td>
                                    <strong>{{ $slot->period_no }}</strong>
                                    <small class="d-block text-muted">{{ \Illuminate\Support\Str::limit($slot->starts_at, 5, '') }}–{{ \Illuminate\Support\Str::limit($slot->ends_at, 5, '') }}</small>
                                </td>
                                @foreach($days as $dayIdx => $dayName)
                                    @php $cellEntries = $entries->get($dayIdx . '-' . $slot->id, collect()); @endphp
                                    <td>
                                        @forelse($cellEntries as $e)
                                            <div class="bg-light p-1 rounded mb-1 small">
                                                <strong>{{ $e->classPeriod->subject->name ?? '—' }}</strong>
                                                <div class="text-muted">{{ $e->classPeriod->teacher->name ?? '—' }}</div>
                                                <div class="text-muted small">{{ $e->classPeriod->classRoom->name ?? '—' }}</div>
                                            </div>
                                        @empty
                                            <span class="text-muted small">—</span>
                                        @endforelse
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
@endsection
