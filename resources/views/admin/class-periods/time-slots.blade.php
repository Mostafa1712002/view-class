@extends('layouts.app')

@section('title', __('sprint4.class_periods.time_slots.title'))

@section('content')
<div class="content-header row">
    <div class="content-header-left col-12 mb-2">
        <h2 class="content-header-title mb-0">@lang('sprint4.class_periods.time_slots.title')</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.class-periods.index') }}">@lang('sprint4.class_periods.page_title')</a></li>
                <li class="breadcrumb-item active">@lang('sprint4.class_periods.time_slots.title')</li>
            </ol>
        </div>
    </div>
</div>

<div class="content-body">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

    <div class="card mb-3">
        <div class="card-header"><h5 class="mb-0">@lang('sprint4.class_periods.time_slots.add')</h5></div>
        <div class="card-body">
            <form action="{{ route('admin.class-periods.time-slots.store') }}" method="POST" class="row g-2 align-items-end">
                @csrf
                <div class="col-md-2">
                    <label class="form-label">@lang('sprint4.class_periods.form.period_no')</label>
                    <input type="number" name="period_no" min="1" max="20" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">@lang('sprint4.class_periods.form.starts_at')</label>
                    <input type="time" name="starts_at" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">@lang('sprint4.class_periods.form.ends_at')</label>
                    <input type="time" name="ends_at" class="form-control" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <div class="form-check">
                        <input type="checkbox" name="is_break" value="1" class="form-check-input" id="is_break">
                        <label for="is_break" class="form-check-label">@lang('sprint4.class_periods.form.is_break')</label>
                    </div>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">@lang('sprint4.class_periods.form.save')</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>@lang('sprint4.class_periods.form.period_no')</th>
                        <th>@lang('sprint4.class_periods.form.starts_at')</th>
                        <th>@lang('sprint4.class_periods.form.ends_at')</th>
                        <th>@lang('sprint4.class_periods.form.is_break')</th>
                        <th>@lang('sprint4.class_periods.columns.actions')</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($slots as $s)
                        <tr>
                            <td><strong>{{ $s->period_no }}</strong></td>
                            <td>{{ $s->starts_at }}</td>
                            <td>{{ $s->ends_at }}</td>
                            <td>{!! $s->is_break ? '<span class="badge bg-warning">'.__('sprint4.class_periods.form.is_break').'</span>' : '—' !!}</td>
                            <td>
                                <form action="{{ route('admin.class-periods.time-slots.destroy', $s->id) }}" method="POST" onsubmit="return confirm('?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="la la-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">@lang('sprint4.class_periods.time_slots.no_slots')</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
