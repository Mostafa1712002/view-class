@extends('layouts.app')

@section('title', __('appointments.booking_show_title'))
@section('body_class', 'theme-light')

@push('styles')
<style>
    .bk-card { background:#fff; border:1px solid #e5e7eb; border-radius:14px; padding:1.5rem; box-shadow:0 1px 2px rgba(15,23,42,.04); margin-bottom:1rem; }
    .bk-card .section-title { font-size:.8rem; font-weight:700; color:#94a3b8; text-transform:uppercase; letter-spacing:.6px; margin-bottom:.85rem; padding-bottom:.5rem; border-bottom:1px solid #f1f5f9; }
    .bk-detail-row { display:flex; flex-wrap:wrap; gap:.5rem; padding:.5rem 0; border-bottom:1px solid #f8fafc; }
    .bk-detail-row:last-child { border-bottom:0; }
    .bk-detail-label { min-width:160px; font-size:.85rem; font-weight:600; color:#64748b; }
    .bk-detail-value { font-size:.9rem; color:#0f172a; }
    @media (max-width:420px) {
        .bk-detail-label { min-width:100%; }
    }

    .bk-pill { display:inline-flex; align-items:center; gap:.3rem; padding:.2rem .6rem; border-radius:999px; font-size:.72rem; font-weight:600; border:1px solid transparent; }
    .bk-pill.requested { background:#fffbeb; color:#92400e; border-color:#fde68a; }
    .bk-pill.confirmed { background:#dcfce7; color:#15803d; border-color:#bbf7d0; }
    .bk-pill.rejected  { background:#fee2e2; color:#b91c1c; border-color:#fecaca; }
    .bk-pill.cancelled { background:#f1f5f9; color:#64748b; border-color:#e2e8f0; }
    .bk-pill.completed { background:#eff6ff; color:#1d4ed8; border-color:#bfdbfe; }

    .action-card { background:#fff; border:1px solid #e5e7eb; border-radius:14px; padding:1.25rem; box-shadow:0 1px 2px rgba(15,23,42,.04); }
    .action-card .section-title { font-size:.8rem; font-weight:700; color:#94a3b8; text-transform:uppercase; letter-spacing:.6px; margin-bottom:.85rem; padding-bottom:.5rem; border-bottom:1px solid #f1f5f9; }

    .btn-confirm { background:linear-gradient(135deg,#dcfce7,#bbf7d0); border:1px solid #86efac; color:#15803d; font-weight:600; padding:.5rem 1rem; border-radius:10px; display:inline-flex; align-items:center; gap:.35rem; transition:all .15s; }
    .btn-confirm:hover { background:linear-gradient(135deg,#bbf7d0,#86efac); color:#15803d; transform:translateY(-1px); }
    .btn-reject  { background:linear-gradient(135deg,#fee2e2,#fecaca); border:1px solid #fca5a5; color:#b91c1c; font-weight:600; padding:.5rem 1rem; border-radius:10px; display:inline-flex; align-items:center; gap:.35rem; transition:all .15s; }
    .btn-reject:hover { background:linear-gradient(135deg,#fecaca,#fca5a5); color:#b91c1c; transform:translateY(-1px); }
    .btn-complete { background:linear-gradient(135deg,#eff6ff,#dbeafe); border:1px solid #93c5fd; color:#1d4ed8; font-weight:600; padding:.5rem 1rem; border-radius:10px; display:inline-flex; align-items:center; gap:.35rem; transition:all .15s; }
    .btn-complete:hover { background:linear-gradient(135deg,#dbeafe,#bfdbfe); color:#1d4ed8; transform:translateY(-1px); }

    .form-control { border-radius:10px; border:1px solid #e2e8f0; }
    .form-control:focus { border-color:var(--gold-300); box-shadow:0 0 0 .2rem rgba(207,160,70,.16); }
</style>
@endpush

@section('content')

    <div class="content-header row">
        <div class="content-header-left col-12 mb-2">
            <h3 class="content-header-title">@lang('appointments.booking_show_title')</h3>
            <div class="row breadcrumbs-top">
                <div class="breadcrumb-wrapper col-12">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') ?? '#' }}">@lang('appointments.breadcrumb_home')</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('manage.appointments.index') }}">@lang('appointments.manage_bookings_title')</a></li>
                        <li class="breadcrumb-item active">#{{ $booking->id }}</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        {{-- Booking Details --}}
        <div class="col-lg-8 col-12">
            <div class="bk-card">
                <div class="section-title">@lang('appointments.booking_details_section')</div>
                <div class="bk-detail-row">
                    <div class="bk-detail-label">@lang('appointments.table_status')</div>
                    <div class="bk-detail-value">
                        <span class="bk-pill {{ $booking->status }}">{{ __('appointments.booking_status_' . $booking->status) }}</span>
                    </div>
                </div>
                <div class="bk-detail-row">
                    <div class="bk-detail-label">@lang('appointments.table_student')</div>
                    <div class="bk-detail-value">{{ $booking->student?->name ?? '—' }}</div>
                </div>
                <div class="bk-detail-row">
                    <div class="bk-detail-label">@lang('appointments.table_booked_by')</div>
                    <div class="bk-detail-value">{{ $booking->bookedBy?->name ?? '—' }}</div>
                </div>
                <div class="bk-detail-row">
                    <div class="bk-detail-label">@lang('appointments.table_bookable_role')</div>
                    <div class="bk-detail-value">{{ $booking->bookableRole?->label ?? '—' }}</div>
                </div>
                <div class="bk-detail-row">
                    <div class="bk-detail-label">@lang('appointments.table_target_person')</div>
                    <div class="bk-detail-value">{{ $booking->targetUser?->name ?? '—' }}</div>
                </div>
                @if($booking->subject)
                <div class="bk-detail-row">
                    <div class="bk-detail-label">@lang('appointments.field_subject')</div>
                    <div class="bk-detail-value">{{ $booking->subject->name }}</div>
                </div>
                @endif
                <div class="bk-detail-row">
                    <div class="bk-detail-label">@lang('appointments.table_date_time')</div>
                    <div class="bk-detail-value">{{ $booking->appointment_date?->format('Y-m-d') }} &nbsp; {{ $booking->appointment_time }}</div>
                </div>
                <div class="bk-detail-row">
                    <div class="bk-detail-label">@lang('appointments.field_contact_method')</div>
                    <div class="bk-detail-value">{{ __('appointments.mode_' . $booking->contact_method) }}</div>
                </div>
                <div class="bk-detail-row">
                    <div class="bk-detail-label">@lang('appointments.field_reason')</div>
                    <div class="bk-detail-value" style="white-space:pre-wrap;">{{ $booking->reason }}</div>
                </div>
                @if($booking->notes)
                <div class="bk-detail-row">
                    <div class="bk-detail-label">@lang('appointments.field_notes')</div>
                    <div class="bk-detail-value" style="white-space:pre-wrap;">{{ $booking->notes }}</div>
                </div>
                @endif
                @if($booking->attachment_path)
                <div class="bk-detail-row">
                    <div class="bk-detail-label">@lang('appointments.field_attachment')</div>
                    <div class="bk-detail-value">
                        <a href="{{ Storage::url($booking->attachment_path) }}" target="_blank">
                            <i class="la la-paperclip"></i> @lang('appointments.btn_view_attachment')
                        </a>
                    </div>
                </div>
                @endif

                @if($booking->decision_at)
                <div class="mt-1 pt-1" style="border-top:1px solid #f1f5f9;">
                    <div class="section-title">@lang('appointments.decision_section')</div>
                    <div class="bk-detail-row">
                        <div class="bk-detail-label">@lang('appointments.field_decided_by')</div>
                        <div class="bk-detail-value">{{ $booking->decidedBy?->name ?? '—' }}</div>
                    </div>
                    <div class="bk-detail-row">
                        <div class="bk-detail-label">@lang('appointments.field_decision_at')</div>
                        <div class="bk-detail-value">{{ $booking->decision_at?->format('Y-m-d H:i') }}</div>
                    </div>
                    @if($booking->decision_note)
                    <div class="bk-detail-row">
                        <div class="bk-detail-label">@lang('appointments.field_decision_note')</div>
                        <div class="bk-detail-value" style="white-space:pre-wrap;">{{ $booking->decision_note }}</div>
                    </div>
                    @endif
                </div>
                @endif
            </div>
        </div>

        {{-- Decision Panel --}}
        @if(in_array($booking->status, ['requested', 'confirmed']))
        <div class="col-lg-4 col-12">
            <div class="action-card">
                <div class="section-title">@lang('appointments.decision_action_title')</div>

                {{-- Confirm --}}
                @if($booking->status === 'requested')
                <form method="POST" action="{{ route('manage.appointments.decide', $booking->id) }}" class="mb-1">
                    @csrf
                    <input type="hidden" name="action" value="confirm">
                    <button type="button" class="btn-confirm w-100"
                        onclick="window.vcConfirm({ title: '{{ __('appointments.booking_confirm_action') }}' }).then(function(r){ if(r.isConfirmed){ this.closest('form').submit(); } }.bind(this))">
                        <i class="la la-check-circle"></i> @lang('appointments.btn_confirm_booking')
                    </button>
                </form>
                @endif

                {{-- Complete --}}
                @if($booking->status === 'confirmed')
                <form method="POST" action="{{ route('manage.appointments.decide', $booking->id) }}" class="mb-1">
                    @csrf
                    <input type="hidden" name="action" value="complete">
                    <button type="button" class="btn-complete w-100"
                        onclick="window.vcConfirm({ title: '{{ __('appointments.booking_complete_action') }}' }).then(function(r){ if(r.isConfirmed){ this.closest('form').submit(); } }.bind(this))">
                        <i class="la la-flag-checkered"></i> @lang('appointments.btn_complete_booking')
                    </button>
                </form>
                @endif

                {{-- Reject --}}
                <form method="POST" action="{{ route('manage.appointments.decide', $booking->id) }}" id="reject-form">
                    @csrf
                    <input type="hidden" name="action" value="reject">
                    <div class="form-group">
                        <label class="form-label" style="font-size:.85rem;font-weight:600;color:#64748b;">
                            @lang('appointments.field_decision_note') <span class="text-danger">*</span>
                        </label>
                        <textarea name="decision_note" rows="3" class="form-control @error('decision_note') is-invalid @enderror"
                            placeholder="@lang('appointments.placeholder_reject_note')">{{ old('decision_note') }}</textarea>
                        @error('decision_note')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <button type="button" class="btn-reject w-100"
                        onclick="window.vcConfirm({ title: '{{ __('appointments.booking_reject_action') }}' }).then(function(r){ if(r.isConfirmed){ document.getElementById('reject-form').submit(); } })">
                        <i class="la la-times-circle"></i> @lang('appointments.btn_reject_booking')
                    </button>
                </form>
            </div>
        </div>
        @endif

    </div>

    <div class="mt-1">
        <a href="{{ route('manage.appointments.index') }}" class="btn btn-outline-secondary">
            <i class="la la-arrow-left"></i> @lang('appointments.btn_back_to_list')
        </a>
    </div>

@endsection
