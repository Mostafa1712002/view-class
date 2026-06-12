@extends('layouts.app')

@section('title', __('appointments.my_bookings_title'))
@section('body_class', 'theme-light')

@push('styles')
<style>
    .bk-filter { background:#fff; border:1px solid #e5e7eb; border-radius:14px; padding:1rem 1.1rem; margin-bottom:1rem; box-shadow:0 1px 2px rgba(15,23,42,.04); }
    .bk-filter .se-title { display:flex; align-items:center; gap:.55rem; font-size:.92rem; font-weight:700; color:#0f172a; margin-bottom:.6rem; }
    .bk-filter .se-title i { color:var(--gold-400); }
    .bk-row { display:flex; gap:.55rem; flex-wrap:wrap; }
    .bk-row .form-control { background:#fff; border:1px solid #e2e8f0; border-radius:10px; padding:.55rem .85rem; font-size:.93rem; flex:1 1 160px; min-width:0; }
    .bk-row select.form-control { flex:0 1 160px; }
    .bk-row .form-control:focus { border-color:var(--gold-300); box-shadow:0 0 0 .2rem rgba(207,160,70,.16); outline:none; }

    .bk-toolbar { background:#fff; border:1px solid #e5e7eb; border-radius:14px 14px 0 0; padding:.9rem 1.1rem; display:flex; flex-wrap:wrap; gap:.55rem; align-items:center; justify-content:space-between; border-bottom:0; }
    .count-pill { background:#f8fafc; border:1px solid #e5e7eb; color:#475569; font-size:.78rem; font-weight:600; padding:.25rem .65rem; border-radius:999px; }
    .btn-gold { background:linear-gradient(135deg,var(--gold-300),var(--gold-500)); border:1px solid var(--gold-400); color:#fff; font-weight:600; padding:.55rem 1.1rem; border-radius:10px; box-shadow:0 1px 2px rgba(207,160,70,.18); transition:transform .15s,box-shadow .2s; display:inline-flex; align-items:center; gap:.45rem; }
    .btn-gold:hover { color:#fff; transform:translateY(-1px); box-shadow:0 6px 16px rgba(207,160,70,.22); }
    .btn-reset { background:#fff; border:1px solid #e2e8f0; color:#475569; font-weight:600; padding:.55rem 1rem; border-radius:10px; display:inline-flex; align-items:center; gap:.35rem; transition:all .15s; }
    .btn-reset:hover { background:#f8fafc; color:#0f172a; }

    .bk-surface { background:#fff; border:1px solid #e5e7eb; border-top:0; border-radius:0 0 14px 14px; overflow:hidden; }
    .bk-table { margin:0; }
    .bk-table thead th { background:#f8fafc !important; color:#475569 !important; font-weight:600; font-size:.78rem; text-transform:uppercase; letter-spacing:.5px; border-bottom:1px solid #e5e7eb; padding:.8rem 1rem; white-space:nowrap; }
    .bk-table tbody td { padding:.85rem 1rem; vertical-align:middle; color:#0f172a; }
    .bk-table tbody tr:hover { background:#fafbfc; }
    .bk-table tbody tr + tr td { border-top:1px solid #f1f5f9; }

    .bk-pill { display:inline-flex; align-items:center; gap:.3rem; padding:.2rem .6rem; border-radius:999px; font-size:.72rem; font-weight:600; border:1px solid transparent; }
    .bk-pill.requested { background:#fffbeb; color:#92400e; border-color:#fde68a; }
    .bk-pill.confirmed { background:#dcfce7; color:#15803d; border-color:#bbf7d0; }
    .bk-pill.rejected  { background:#fee2e2; color:#b91c1c; border-color:#fecaca; }
    .bk-pill.cancelled { background:#f1f5f9; color:#64748b; border-color:#e2e8f0; }
    .bk-pill.completed { background:#eff6ff; color:#1d4ed8; border-color:#bfdbfe; }

    .bk-empty { padding:3rem 1.5rem; text-align:center; }
    .bk-empty i { font-size:2.5rem; color:#cbd5e1; }
    .bk-empty h6 { margin:.75rem 0 .35rem; color:#475569; font-weight:600; }
    .bk-empty p { font-size:.88rem; color:#94a3b8; }
</style>
@endpush

@section('content')
<div class="app-content content">
<div class="content-overlay"></div>
<div class="content-wrapper">

    {{-- Breadcrumb --}}
    <div class="content-header row">
        <div class="content-header-left col-md-6 col-12 mb-2">
            <h3 class="content-header-title">@lang('appointments.my_bookings_title')</h3>
            <div class="row breadcrumbs-top">
                <div class="breadcrumb-wrapper col-12">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') ?? '#' }}">@lang('appointments.breadcrumb_home')</a></li>
                        <li class="breadcrumb-item active">@lang('appointments.my_bookings_title')</li>
                    </ol>
                </div>
            </div>
        </div>
        <div class="content-header-right col-md-6 col-12 d-flex align-items-center justify-content-end mb-2">
            <a href="{{ route('my.appointments.create') }}" class="btn-gold">
                <i class="la la-plus"></i> @lang('appointments.btn_book_new')
            </a>
        </div>
    </div>

    {{-- Filters --}}
    <form method="GET" class="bk-filter mb-1">
        <div class="se-title"><i class="la la-filter"></i> @lang('appointments.filter_title')</div>
        <div class="bk-row">
            <select name="status" class="form-control">
                <option value="">@lang('appointments.filter_all') (@lang('appointments.field_status'))</option>
                @foreach($bookingStatuses as $key => $label)
                    <option value="{{ $key }}" {{ request('status') === $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}" placeholder="@lang('appointments.filter_date_from')">
            <input type="date" name="date_to"   class="form-control" value="{{ request('date_to') }}"   placeholder="@lang('appointments.filter_date_to')">
            <button type="submit" class="btn-gold"><i class="la la-search"></i> @lang('appointments.filter_apply')</button>
            <a href="{{ route('my.appointments.index') }}" class="btn-reset"><i class="la la-times"></i> @lang('appointments.filter_reset')</a>
        </div>
    </form>

    {{-- Table --}}
    <div class="bk-toolbar">
        <div class="left">
            <span class="count-pill">{{ $bookings->total() }} @lang('appointments.label_booking')</span>
        </div>
    </div>
    <div class="bk-surface">
        @if($bookings->isEmpty())
            <div class="bk-empty">
                <i class="la la-calendar-times"></i>
                <h6>@lang('appointments.booking_empty_title')</h6>
                <p>@lang('appointments.booking_empty_hint')</p>
                <a href="{{ route('my.appointments.create') }}" class="btn-gold mt-1">
                    <i class="la la-plus"></i> @lang('appointments.btn_book_new')
                </a>
            </div>
        @else
            <div class="table-responsive">
                <table class="table bk-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>@lang('appointments.table_bookable_role')</th>
                            <th>@lang('appointments.table_target_person')</th>
                            <th>@lang('appointments.table_date_time')</th>
                            <th>@lang('appointments.table_contact_method')</th>
                            <th>@lang('appointments.table_status')</th>
                            <th>@lang('appointments.table_actions')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($bookings as $booking)
                        <tr>
                            <td class="text-muted" style="font-size:.8rem;">{{ $booking->id }}</td>
                            <td>{{ $booking->bookableRole?->label ?? '—' }}</td>
                            <td>{{ $booking->targetUser?->name ?? '—' }}</td>
                            <td>
                                <span style="font-weight:600;">{{ $booking->appointment_date?->format('Y-m-d') }}</span>
                                <span class="text-muted" style="font-size:.85rem;"> {{ $booking->appointment_time }}</span>
                            </td>
                            <td>{{ __('appointments.mode_' . $booking->contact_method) }}</td>
                            <td>
                                <span class="bk-pill {{ $booking->status }}">
                                    {{ $bookingStatuses[$booking->status] ?? $booking->status }}
                                </span>
                            </td>
                            <td>
                                @if(in_array($booking->status, ['requested', 'confirmed']))
                                <form method="POST" action="{{ route('my.appointments.cancel', $booking->id) }}" style="display:inline;">
                                    @csrf
                                    <button type="button" class="btn btn-sm btn-outline-danger"
                                        onclick="window.vcConfirm({ title: '{{ __('appointments.booking_confirm_cancel') }}' }).then(function(r){ if(r.isConfirmed){ this.closest('form').submit(); } }.bind(this))">
                                        <i class="la la-times"></i> @lang('appointments.btn_cancel_booking')
                                    </button>
                                </form>
                                @else
                                <span class="text-muted" style="font-size:.8rem;">—</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="p-3">
                {{ $bookings->withQueryString()->links() }}
            </div>
        @endif
    </div>

</div>
</div>
@endsection
