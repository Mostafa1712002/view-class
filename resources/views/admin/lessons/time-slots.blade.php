@extends('layouts.app')

@section('title', __('lessons_admin.timeslots.title'))
@section('body_class', 'theme-light')

@php $isRtl = app()->getLocale() === 'ar'; @endphp

@push('styles')
<style>
    .ls-card { background:#fff; border:1px solid #e5e7eb; border-radius:14px; box-shadow:0 1px 2px rgba(15,23,42,.04), 0 4px 12px rgba(15,23,42,.04); margin-bottom:1.25rem; }
    .ls-card .ls-card-head { padding:1rem 1.1rem; border-bottom:1px solid #f1f5f9; font-weight:700; color:#0f172a; display:flex; align-items:center; gap:.5rem; }
    .ls-card .ls-card-head i { color:var(--gold-400); }
    .ls-card .ls-card-body { padding:1.1rem; }
    .ls-field label { display:block; font-size:.85rem; font-weight:600; color:#334155; margin-bottom:.35rem; }
    .ls-field .form-control { width:100%; background:#fff; border:1px solid #e2e8f0; border-radius:10px; padding:.55rem .85rem; font-size:.93rem; color:#0f172a; }
    .ls-field .form-control:focus { border-color:var(--gold-300); box-shadow:0 0 0 .2rem rgba(207,160,70,.16); outline:none; }
    .btn-gold { background:linear-gradient(135deg, var(--gold-300), var(--gold-500)); border:1px solid var(--gold-400); color:#fff; font-weight:600; padding:.55rem 1.1rem; border-radius:10px; display:inline-flex; align-items:center; gap:.45rem; transition:transform .15s ease; }
    .btn-gold:hover { color:#fff; transform:translateY(-1px); }
    .btn-back { background:#fff; border:1px solid #e2e8f0; color:#475569; font-weight:600; padding:.55rem 1rem; border-radius:10px; display:inline-flex; align-items:center; gap:.35rem; }
    .ls-table { width:100%; margin:0; }
    .ls-table thead th { background:#f8fafc; color:#475569; font-size:.82rem; font-weight:700; padding:.7rem 1rem; border-bottom:1px solid #e5e7eb; }
    .ls-table tbody td { padding:.7rem 1rem; border-bottom:1px solid #f1f5f9; color:#0f172a; font-size:.9rem; vertical-align:middle; }
    .ls-badge { background:#fef3c7; color:#92400e; border-radius:999px; padding:.15rem .6rem; font-size:.78rem; font-weight:700; }
    .ls-del { background:#fef2f2; border:1px solid #fee2e2; color:#dc2626; border-radius:8px; padding:.35rem .55rem; }
</style>
@endpush

@section('content')
<div style="margin-bottom:1.25rem; display:flex; justify-content:space-between; align-items:flex-start; gap:1rem; flex-wrap:wrap">
    <div>
        <h2 style="font-size:1.5rem;font-weight:700;color:#0f172a;margin-bottom:.15rem">@lang('lessons_admin.timeslots.title')</h2>
        <nav><ol class="breadcrumb" style="padding:0;margin:0;background:transparent;font-size:.85rem">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('lessons_admin.breadcrumb_home')</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.lessons.index') }}">@lang('lessons_admin.breadcrumb_index')</a></li>
            <li class="breadcrumb-item active">@lang('lessons_admin.timeslots.title')</li>
        </ol></nav>
    </div>
    <a href="{{ route('admin.lessons.index') }}" class="btn-back"><i class="la la-arrow-{{ $isRtl ? 'right' : 'left' }}"></i>@lang('lessons_admin.actions.back')</a>
</div>

@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if($errors->any())
    <div class="alert alert-danger"><ul style="margin:0;padding-{{ $isRtl ? 'right' : 'left' }}:1.2rem">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
@endif

<div class="ls-card">
    <div class="ls-card-head"><i class="la la-plus-circle"></i>@lang('lessons_admin.timeslots.add')</div>
    <div class="ls-card-body">
        <form action="{{ route('admin.lessons.time-slots.store') }}" method="POST">
            @csrf
            <div class="row" style="gap-y:1rem">
                <div class="col-md-2 ls-field">
                    <label>@lang('lessons_admin.timeslots.period_no')</label>
                    <input type="number" name="period_no" min="1" max="20" class="form-control" value="{{ old('period_no') }}" required>
                </div>
                <div class="col-md-3 ls-field">
                    <label>@lang('lessons_admin.timeslots.starts_at')</label>
                    <input type="time" name="starts_at" class="form-control" value="{{ old('starts_at') }}" required>
                </div>
                <div class="col-md-3 ls-field">
                    <label>@lang('lessons_admin.timeslots.ends_at')</label>
                    <input type="time" name="ends_at" class="form-control" value="{{ old('ends_at') }}" required>
                </div>
                <div class="col-md-2 ls-field">
                    <label>&nbsp;</label>
                    <div class="form-check" style="padding-top:.4rem">
                        <input type="checkbox" name="is_break" value="1" class="form-check-input" id="is_break">
                        <label for="is_break" class="form-check-label">@lang('lessons_admin.timeslots.is_break')</label>
                    </div>
                </div>
                <div class="col-md-2 ls-field">
                    <label>&nbsp;</label>
                    <button type="submit" class="btn-gold" style="width:100%;justify-content:center"><i class="la la-save"></i>@lang('lessons_admin.actions.save')</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="ls-card">
    <div class="table-responsive">
        <table class="ls-table">
            <thead>
                <tr>
                    <th>@lang('lessons_admin.timeslots.period_no')</th>
                    <th>@lang('lessons_admin.timeslots.starts_at')</th>
                    <th>@lang('lessons_admin.timeslots.ends_at')</th>
                    <th>@lang('lessons_admin.timeslots.is_break')</th>
                    <th style="text-align:{{ $isRtl ? 'left' : 'right' }}">@lang('lessons_admin.table.actions')</th>
                </tr>
            </thead>
            <tbody>
                @forelse($slots as $s)
                    <tr>
                        <td><strong>{{ $s->period_no }}</strong></td>
                        <td>{{ \Illuminate\Support\Str::limit($s->starts_at, 5, '') }}</td>
                        <td>{{ \Illuminate\Support\Str::limit($s->ends_at, 5, '') }}</td>
                        <td>{!! $s->is_break ? '<span class="ls-badge">'.__('lessons_admin.timeslots.is_break').'</span>' : '<span style="color:#94a3b8">—</span>' !!}</td>
                        <td style="text-align:{{ $isRtl ? 'left' : 'right' }}">
                            <form action="{{ route('admin.lessons.time-slots.destroy', $s->id) }}" method="POST" style="display:inline" onsubmit="return confirm('{{ __('lessons_admin.timeslots.confirm_delete') }}')">
                                @csrf @method('DELETE')
                                <button type="submit" class="ls-del"><i class="la la-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" style="text-align:center;color:#94a3b8;padding:2rem">@lang('lessons_admin.timeslots.empty')</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
