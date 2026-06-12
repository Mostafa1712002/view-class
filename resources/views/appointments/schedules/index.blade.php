@extends('layouts.app')

@section('title', __('appointments.schedules_title'))
@section('body_class', 'theme-light')

@php
    $isRtl  = app()->getLocale() === 'ar';
    $user   = auth()->user();
    $isAdmin = $user && ($user->isSuperAdmin() || $user->isSchoolAdmin());
    $modes  = __('appointments.modes');
    $statuses = __('appointments.statuses');
@endphp

@push('styles')
<style>
    .ap-kpis { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:.75rem; margin-bottom:1.25rem; }
    .ap-kpi  { background:#fff; border:1px solid #e5e7eb; border-radius:14px; padding:.85rem 1rem; display:flex; align-items:center; gap:.75rem; box-shadow:0 1px 2px rgba(15,23,42,.04); transition:transform .2s,box-shadow .2s; }
    .ap-kpi:hover { transform:translateY(-2px); box-shadow:0 4px 14px rgba(15,23,42,.06); }
    .ap-kpi .ico  { width:38px; height:38px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:1.1rem; flex-shrink:0; background:linear-gradient(135deg,#fef3c7,#fde68a); color:var(--gold-500); }
    .ap-kpi .ico.b { background:linear-gradient(135deg,#dbeafe,#bfdbfe); color:#1d4ed8; }
    .ap-kpi .ico.g { background:linear-gradient(135deg,#dcfce7,#bbf7d0); color:#15803d; }
    .ap-kpi .ico.r { background:linear-gradient(135deg,#fee2e2,#fecaca); color:#b91c1c; }
    .ap-kpi .num  { font-size:1.35rem; font-weight:800; color:var(--gold-400); line-height:1.1; }
    .ap-kpi .lbl  { font-size:.8rem; color:#64748b; }

    .ap-filter { background:#fff; border:1px solid #e5e7eb; border-radius:14px; padding:1rem 1.1rem; margin-bottom:1rem; box-shadow:0 1px 2px rgba(15,23,42,.04); }
    .ap-filter .se-title { display:flex; align-items:center; gap:.55rem; font-size:.92rem; font-weight:700; color:#0f172a; margin-bottom:.6rem; }
    .ap-filter .se-title i { color:var(--gold-400); }
    .ap-row { display:flex; gap:.55rem; flex-wrap:wrap; }
    .ap-row .form-control { background:#fff; border:1px solid #e2e8f0; border-radius:10px; padding:.55rem .85rem; font-size:.93rem; flex:1 1 180px; min-width:0; }
    .ap-row select.form-control { flex:0 1 180px; }
    .ap-row .form-control:focus { border-color:var(--gold-300); box-shadow:0 0 0 .2rem rgba(207,160,70,.16); outline:none; }

    .ap-toolbar { background:#fff; border:1px solid #e5e7eb; border-radius:14px 14px 0 0; padding:.9rem 1.1rem; display:flex; flex-wrap:wrap; gap:.55rem; align-items:center; justify-content:space-between; border-bottom:0; }
    .ap-toolbar .left { display:flex; flex-wrap:wrap; gap:.45rem; align-items:center; }
    .count-pill { background:#f8fafc; border:1px solid #e5e7eb; color:#475569; font-size:.78rem; font-weight:600; padding:.25rem .65rem; border-radius:999px; }

    .btn-gold { background:linear-gradient(135deg,var(--gold-300),var(--gold-500)); border:1px solid var(--gold-400); color:#fff; font-weight:600; padding:.55rem 1.1rem; border-radius:10px; box-shadow:0 1px 2px rgba(207,160,70,.18); transition:transform .15s,box-shadow .2s; display:inline-flex; align-items:center; gap:.45rem; }
    .btn-gold:hover { color:#fff; transform:translateY(-1px); box-shadow:0 6px 16px rgba(207,160,70,.22); }
    .btn-reset { background:#fff; border:1px solid #e2e8f0; color:#475569; font-weight:600; padding:.55rem 1rem; border-radius:10px; display:inline-flex; align-items:center; gap:.35rem; transition:all .15s; }
    .btn-reset:hover { background:#f8fafc; color:#0f172a; }

    .ap-surface { background:#fff; border:1px solid #e5e7eb; border-top:0; border-radius:0 0 14px 14px; overflow:hidden; }
    .ap-table { margin:0; }
    .ap-table thead th { background:#f8fafc !important; color:#475569 !important; font-weight:600; font-size:.78rem; text-transform:uppercase; letter-spacing:.5px; border-bottom:1px solid #e5e7eb; padding:.8rem 1rem; white-space:nowrap; }
    .ap-table tbody td { padding:.85rem 1rem; vertical-align:middle; color:#0f172a; }
    .ap-table tbody tr:hover { background:#fafbfc; }
    .ap-table tbody tr + tr td { border-top:1px solid #f1f5f9; }

    .ap-pill { display:inline-flex; align-items:center; gap:.3rem; padding:.2rem .55rem; border-radius:999px; font-size:.72rem; font-weight:600; line-height:1.3; border:1px solid transparent; }
    .ap-pill.active   { background:#dcfce7; color:#15803d; border-color:#bbf7d0; }
    .ap-pill.inactive { background:#f1f5f9; color:#64748b; border-color:#e2e8f0; }
    .ap-pill.expired  { background:#fee2e2; color:#b91c1c; border-color:#fecaca; }
    .ap-pill.mode     { background:#eff6ff; color:#1d4ed8; border-color:#bfdbfe; }
    .ap-pill.day      { background:#fffbeb; color:#92400e; border-color:#fde68a; }
    .ap-pill.open     { background:#dcfce7; color:#15803d; border-color:#bbf7d0; }
    .ap-pill.closed   { background:#fee2e2; color:#b91c1c; border-color:#fecaca; }

    .ap-actions { display:inline-flex; align-items:center; gap:.35rem; }
    .ap-btn { width:32px; height:32px; padding:0; display:inline-flex; align-items:center; justify-content:center; border-radius:8px; border:1px solid #e2e8f0; background:#fff; color:#475569; transition:all .15s; }
    .ap-btn:hover { transform:translateY(-1px); }
    .ap-btn.edit:hover  { background:#fffbeb; border-color:#fde68a; color:#92400e; }
    .ap-btn.show:hover  { background:#eff6ff; border-color:#bfdbfe; color:#1d4ed8; }
    .ap-btn.copy:hover  { background:#f0fdf4; border-color:#bbf7d0; color:#15803d; }
    .ap-btn.del  { background:#fff5f5; border-color:#fecaca; color:#b91c1c; }
    .ap-btn.del:hover   { background:#fee2e2; border-color:#fca5a5; }

    .ap-empty { padding:3rem 1rem; text-align:center; color:#64748b; }
    .ap-empty .ap-empty-ico { width:64px; height:64px; border-radius:16px; margin:0 auto 1rem; display:flex; align-items:center; justify-content:center; background:linear-gradient(135deg,#fef3c7,#fde68a); color:var(--gold-500); font-size:1.7rem; }
    .ap-empty h4 { color:#0f172a; font-weight:700; margin-bottom:.35rem; }

    @media (max-width:768px) { .ap-kpis { grid-template-columns:repeat(2,minmax(0,1fr)); } }
</style>
@endpush

@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-9 col-12 mb-2">
        <h2 class="content-header-title float-{{ $isRtl ? 'right' : 'left' }} mb-0">
            @lang('appointments.schedules_title')
        </h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('appointments.breadcrumb_home')</a></li>
                <li class="breadcrumb-item active">@lang('appointments.breadcrumb_schedules')</li>
            </ol>
        </div>
    </div>
    <div class="content-header-right text-md-{{ $isRtl ? 'left' : 'right' }} col-md-3 col-12 d-flex justify-content-{{ $isRtl ? 'start' : 'end' }} gap-2 flex-wrap">
        @if($isAdmin)
        <a href="{{ route('admin.appointment-settings.index') }}" class="btn btn-outline-secondary">
            <i class="la la-cog"></i> @lang('appointments.settings_title')
        </a>
        @endif
        <a href="{{ route('manage.appointment-schedules.create') }}" class="btn btn-primary">
            <i class="la la-plus"></i> @lang('appointments.btn_add')
        </a>
    </div>
</div>

@include('components.alerts')

@php
    $total    = $schedules->total();
    $active   = $schedules->getCollection()->filter(fn($s) => $s->effective_status === 'active')->count();
    $inactive = $schedules->getCollection()->filter(fn($s) => $s->effective_status === 'inactive')->count();
    $openCount = $schedules->getCollection()->filter(fn($s) => $s->booking_open)->count();
@endphp

<div class="ap-kpis">
    <div class="ap-kpi">
        <div class="ico"><i class="la la-calendar"></i></div>
        <div><div class="num">{{ number_format($total) }}</div><div class="lbl">@lang('appointments.kpi_total')</div></div>
    </div>
    <div class="ap-kpi">
        <div class="ico g"><i class="la la-check-circle"></i></div>
        <div><div class="num" style="color:#15803d">{{ $active }}</div><div class="lbl">@lang('appointments.kpi_active')</div></div>
    </div>
    <div class="ap-kpi">
        <div class="ico r"><i class="la la-pause-circle"></i></div>
        <div><div class="num" style="color:#b91c1c">{{ $inactive }}</div><div class="lbl">@lang('appointments.kpi_inactive')</div></div>
    </div>
    <div class="ap-kpi">
        <div class="ico b"><i class="la la-lock-open"></i></div>
        <div><div class="num" style="color:#1d4ed8">{{ $openCount }}</div><div class="lbl">@lang('appointments.kpi_open')</div></div>
    </div>
</div>

<div class="ap-filter">
    <div class="se-title"><i class="la la-filter"></i> @lang('appointments.filter_title')</div>
    <form method="GET" action="{{ route('manage.appointment-schedules.index') }}" class="ap-row">
        <input type="text" name="q" class="form-control" placeholder="{{ __('appointments.filter_q') }}" value="{{ $filters['q'] ?? '' }}">
        <select name="status" class="form-control">
            <option value="">@lang('appointments.filter_status') — @lang('appointments.filter_all')</option>
            @foreach($statuses as $key => $label)
                <option value="{{ $key }}" @selected(($filters['status'] ?? '') === $key)>{{ $label }}</option>
            @endforeach
        </select>
        <select name="mode" class="form-control">
            <option value="">@lang('appointments.filter_mode') — @lang('appointments.filter_all')</option>
            @foreach($modes as $key => $label)
                <option value="{{ $key }}" @selected(($filters['mode'] ?? '') === $key)>{{ $label }}</option>
            @endforeach
        </select>
        <input type="date" name="date_from" class="form-control" placeholder="{{ __('appointments.filter_date_from') }}" value="{{ $filters['date_from'] ?? '' }}">
        <input type="date" name="date_to" class="form-control" placeholder="{{ __('appointments.filter_date_to') }}" value="{{ $filters['date_to'] ?? '' }}">
        <button type="submit" class="btn-gold"><i class="la la-search"></i> @lang('appointments.filter_apply')</button>
        <a href="{{ route('manage.appointment-schedules.index') }}" class="btn-reset"><i class="la la-redo"></i> @lang('appointments.filter_reset')</a>
    </form>
</div>

<div class="ap-toolbar">
    <div class="left">
        <span class="count-pill">{{ number_format($total) }} @lang('appointments.kpi_total')</span>
    </div>
    <div>
        <a href="{{ route('manage.appointment-schedules.create') }}" class="btn-gold"><i class="la la-plus"></i> @lang('appointments.btn_add')</a>
    </div>
</div>

<div class="ap-surface">
    @if($schedules->count() === 0)
        <div class="ap-empty">
            <div class="ap-empty-ico"><i class="la la-calendar"></i></div>
            <h4>@lang('appointments.empty_title')</h4>
            <p>@lang('appointments.empty_hint')</p>
            <a href="{{ route('manage.appointment-schedules.create') }}" class="btn-gold" style="margin-top:.5rem">
                <i class="la la-plus"></i> @lang('appointments.btn_add')
            </a>
        </div>
    @else
        <div class="table-responsive">
            <table class="table ap-table">
                <thead>
                    <tr>
                        <th>@lang('appointments.table_title')</th>
                        @if($isAdmin)<th>@lang('appointments.table_owner')</th>@endif
                        <th>@lang('appointments.table_dates')</th>
                        <th>@lang('appointments.table_time')</th>
                        <th>@lang('appointments.table_slot')</th>
                        <th>@lang('appointments.table_mode')</th>
                        <th>@lang('appointments.table_booked')</th>
                        <th>@lang('appointments.table_status')</th>
                        <th>@lang('appointments.table_booking')</th>
                        <th style="text-align:{{ $isRtl ? 'left' : 'right' }}">@lang('appointments.table_actions')</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($schedules as $schedule)
                    <tr>
                        <td><strong>{{ $schedule->title }}</strong></td>
                        @if($isAdmin)
                        <td>{{ optional($schedule->owner)->name ?? '—' }}</td>
                        @endif
                        <td>
                            <span class="ap-pill day">{{ $schedule->date_from?->format('Y-m-d') }}</span>
                            <span style="color:#94a3b8;font-size:.75rem"> — </span>
                            <span class="ap-pill day">{{ $schedule->date_to?->format('Y-m-d') }}</span>
                        </td>
                        <td>
                            <span class="ap-pill mode">
                                {{ \Illuminate\Support\Str::substr($schedule->time_from, 0, 5) }}
                                –
                                {{ \Illuminate\Support\Str::substr($schedule->time_to, 0, 5) }}
                            </span>
                        </td>
                        <td>{{ $schedule->slot_minutes }} @lang('appointments.min_label')</td>
                        <td>
                            <span class="ap-pill mode">{{ $modes[$schedule->mode] ?? $schedule->mode }}</span>
                        </td>
                        <td>
                            @php $booked = $schedule->bookedCount(); $avail = $schedule->availableCount(); @endphp
                            {{ $booked }} /
                            @if($avail === null)
                                <span style="color:#94a3b8">@lang('appointments.slots_unlimited')</span>
                            @else
                                {{ $avail }}
                            @endif
                        </td>
                        <td>
                            <span class="ap-pill {{ $schedule->effective_status }}">
                                {{ $statuses[$schedule->effective_status] ?? $schedule->effective_status }}
                            </span>
                        </td>
                        <td>
                            <span class="ap-pill {{ $schedule->booking_open ? 'open' : 'closed' }}">
                                <i class="la la-{{ $schedule->booking_open ? 'lock-open' : 'lock' }}"></i>
                                {{ $schedule->booking_open ? __('appointments.btn_open_booking') : __('appointments.btn_close_booking') }}
                            </span>
                        </td>
                        <td style="text-align:{{ $isRtl ? 'left' : 'right' }}">
                            <div class="ap-actions">
                                <a href="{{ route('manage.appointment-schedules.show', $schedule->id) }}" class="ap-btn show" title="@lang('appointments.btn_show')"><i class="la la-eye"></i></a>
                                <a href="{{ route('manage.appointment-schedules.edit', $schedule->id) }}" class="ap-btn edit" title="@lang('appointments.btn_edit')"><i class="la la-edit"></i></a>

                                {{-- Toggle booking --}}
                                <form action="{{ route('manage.appointment-schedules.toggle', $schedule->id) }}" method="POST" style="display:inline" class="form-toggle">
                                    @csrf
                                    <button type="submit" class="ap-btn {{ $schedule->booking_open ? 'del' : 'copy' }}"
                                        title="{{ $schedule->booking_open ? __('appointments.btn_close_booking') : __('appointments.btn_open_booking') }}"
                                        data-confirm="{{ $schedule->booking_open ? __('appointments.confirm_toggle_close') : __('appointments.confirm_toggle_open') }}">
                                        <i class="la la-{{ $schedule->booking_open ? 'lock' : 'lock-open' }}"></i>
                                    </button>
                                </form>

                                {{-- Copy --}}
                                <form action="{{ route('manage.appointment-schedules.copy', $schedule->id) }}" method="POST" style="display:inline" class="form-copy">
                                    @csrf
                                    <button type="submit" class="ap-btn copy"
                                        title="@lang('appointments.btn_copy')"
                                        data-confirm="{{ __('appointments.confirm_copy') }}">
                                        <i class="la la-copy"></i>
                                    </button>
                                </form>

                                {{-- Delete --}}
                                <form action="{{ route('manage.appointment-schedules.destroy', $schedule->id) }}" method="POST" style="display:inline" class="form-delete">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="ap-btn del"
                                        title="@lang('appointments.btn_delete')"
                                        data-confirm="{{ __('appointments.confirm_delete') }}">
                                        <i class="la la-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @if($schedules->hasPages())
            <div style="padding:.85rem 1rem; border-top:1px solid #f1f5f9; background:#fff">
                {{ $schedules->links() }}
            </div>
        @endif
    @endif
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // SweetAlert confirms for destructive/toggle actions
    document.querySelectorAll('.form-delete button[data-confirm], .form-toggle button[data-confirm], .form-copy button[data-confirm]').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            var form = btn.closest('form');
            var msg  = btn.getAttribute('data-confirm');
            if (window.vcConfirm) {
                window.vcConfirm(msg, function () { form.submit(); });
            } else if (confirm(msg)) {
                form.submit();
            }
        });
    });
});
</script>
@endpush
