@extends('layouts.app')

@section('title', __('sprint4.class_periods.advanced.title'))

@section('content')
<div class="content-header row">
    <div class="content-header-left col-12 mb-2">
        <h2 class="content-header-title mb-0">@lang('sprint4.class_periods.advanced.title')</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.class-periods.index') }}">@lang('sprint4.class_periods.page_title')</a></li>
                <li class="breadcrumb-item active">@lang('sprint4.class_periods.advanced.title')</li>
            </ol>
        </div>
    </div>
</div>

<div class="content-body">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    <p class="text-muted small">@lang('sprint4.class_periods.advanced.help')</p>

    @php $days = \App\Models\ScheduleEntry::DAYS_AR; @endphp

    @if($slots->isEmpty())
        <div class="alert alert-warning">@lang('sprint4.class_periods.advanced.no_slots')</div>
    @elseif($periods->isEmpty())
        <div class="alert alert-warning">@lang('sprint4.class_periods.advanced.no_periods')</div>
    @else
        <div class="row">
            <div class="col-md-3">
                <div class="card">
                    <div class="card-header"><strong>@lang('sprint4.class_periods.page_title')</strong></div>
                    <div class="card-body p-2" style="max-height: 600px; overflow-y: auto">
                        @foreach($periods as $p)
                            <div class="border rounded p-2 mb-1" data-period-id="{{ $p->id }}">
                                <strong>{{ $p->subject->name ?? '—' }}</strong>
                                <small class="d-block text-muted">{{ $p->teacher->name ?? '—' }} · {{ $p->classRoom->name ?? '—' }}</small>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="col-md-9">
                <div class="card">
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle text-center mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th>@lang('sprint4.class_periods.form.period_no')</th>
                                    @foreach($days as $idx => $name)<th>{{ $name }}</th>@endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($slots as $slot)
                                    <tr>
                                        <td>
                                            <strong>#{{ $slot->period_no }}</strong>
                                            <small class="d-block text-muted">{{ \Illuminate\Support\Str::limit($slot->starts_at, 5, '') }}–{{ \Illuminate\Support\Str::limit($slot->ends_at, 5, '') }}</small>
                                        </td>
                                        @foreach($days as $dayIdx => $dayName)
                                            @php $cellEntries = $entries->get($dayIdx . '-' . $slot->id, collect()); @endphp
                                            <td>
                                                @foreach($cellEntries as $entry)
                                                    <div class="bg-light p-1 rounded mb-1 small">
                                                        <strong>{{ $entry->classPeriod->subject->name ?? '—' }}</strong>
                                                        <div class="text-muted">{{ $entry->classPeriod->teacher->name ?? '—' }}</div>
                                                        <form action="{{ route('admin.class-periods.schedule-entries.destroy', $entry->id) }}" method="POST" class="d-inline">
                                                            @csrf @method('DELETE')
                                                            <button type="submit" class="btn btn-sm btn-link text-danger p-0"><i class="la la-times"></i></button>
                                                        </form>
                                                    </div>
                                                @endforeach
                                                <form action="{{ route('admin.class-periods.schedule-entries.store') }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <input type="hidden" name="time_slot_id" value="{{ $slot->id }}">
                                                    <input type="hidden" name="day_of_week" value="{{ $dayIdx }}">
                                                    <select name="class_period_id" class="form-select form-select-sm" onchange="this.form.submit()">
                                                        <option value="">+ @lang('sprint4.class_periods.advanced.place')</option>
                                                        @foreach($periods as $p)
                                                            <option value="{{ $p->id }}">{{ $p->subject->name ?? '?' }} / {{ $p->teacher->name ?? '?' }}</option>
                                                        @endforeach
                                                    </select>
                                                </form>
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
