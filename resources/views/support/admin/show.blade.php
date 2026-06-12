@extends('layouts.app')

@section('title', __('support.admin_ticket_detail_title'))
@section('body_class', 'theme-light')

@php
    $isRtl = app()->getLocale() === 'ar';
    $user  = auth()->user();
@endphp

@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-9 col-12 mb-2">
        <h2 class="content-header-title float-{{ $isRtl ? 'right' : 'left' }} mb-0">
            #{{ $ticket->id }} — {{ $ticket->subject }}
        </h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('support.breadcrumb_home')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.support.index') }}">@lang('support.breadcrumb_admin_tickets')</a></li>
                <li class="breadcrumb-item active">#{{ $ticket->id }}</li>
            </ol>
        </div>
    </div>
    <div class="content-header-right text-md-{{ $isRtl ? 'left' : 'right' }} col-md-3 col-12 d-flex justify-content-{{ $isRtl ? 'start' : 'end' }} gap-1 flex-wrap">
        <span class="badge badge-{{ $ticket->statusColor() }} align-self-center" style="font-size:.85rem">{{ $ticket->statusLabel() }}</span>
        <span class="badge badge-{{ $ticket->priorityColor() }} align-self-center" style="font-size:.85rem">{{ $ticket->priorityLabel() }}</span>
    </div>
</div>

@include('components.alerts')

<div class="row">
    {{-- Main column: details + replies + reply form --}}
    <div class="col-md-8">
        {{-- Ticket details --}}
        <div class="card mb-2">
            <div class="card-header">
                <h4 class="card-title">@lang('support.section_ticket_details')</h4>
            </div>
            <div class="card-content">
                <div class="card-body">
                    <div class="row mb-1">
                        <div class="col-md-4 font-weight-bold">@lang('support.field_creator')</div>
                        <div class="col-md-8">{{ optional($ticket->creator)->name }} ({{ $ticket->creator_role }})</div>
                    </div>
                    <div class="row mb-1">
                        <div class="col-md-4 font-weight-bold">@lang('support.field_category')</div>
                        <div class="col-md-8">{{ __('support.category_' . $ticket->category) }}</div>
                    </div>
                    <div class="row mb-1">
                        <div class="col-md-4 font-weight-bold">@lang('support.field_created_at')</div>
                        <div class="col-md-8">{{ $ticket->created_at->format('Y-m-d H:i') }}</div>
                    </div>
                    @if($ticket->last_reply_at)
                    <div class="row mb-1">
                        <div class="col-md-4 font-weight-bold">@lang('support.field_last_reply_at')</div>
                        <div class="col-md-8">{{ $ticket->last_reply_at->format('Y-m-d H:i') }}</div>
                    </div>
                    @endif
                    <hr>
                    <div class="mt-1">{!! nl2br(e($ticket->body)) !!}</div>
                </div>
            </div>
        </div>

        {{-- Replies --}}
        @if($ticket->replies->count())
        <div class="card mb-2">
            <div class="card-header">
                <h4 class="card-title">@lang('support.section_replies')</h4>
            </div>
            <div class="card-content">
                <div class="card-body p-0">
                    @foreach($ticket->replies as $reply)
                    <div class="p-2 border-bottom {{ $reply->is_staff ? 'bg-light-primary' : '' }}">
                        <div class="d-flex align-items-center mb-1">
                            <strong>{{ optional($reply->user)->name }}</strong>
                            @if($reply->is_staff)
                                <span class="badge badge-info ml-1">@lang('support.badge_staff')</span>
                            @endif
                            <small class="text-muted ml-auto">{{ $reply->created_at->format('Y-m-d H:i') }}</small>
                        </div>
                        <div>{!! nl2br(e($reply->body)) !!}</div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif

        {{-- Staff reply form --}}
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">@lang('support.section_staff_reply')</h4>
            </div>
            <div class="card-content">
                <div class="card-body">
                    <form action="{{ route('admin.support.reply', $ticket->id) }}" method="POST">
                        @csrf
                        <div class="form-group">
                            <label for="body">@lang('support.field_reply_body') <span class="text-danger">*</span></label>
                            <textarea name="body" id="body" rows="4"
                                class="form-control @error('body') is-invalid @enderror"
                                required>{{ old('body') }}</textarea>
                            @error('body')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="la la-paper-plane"></i> @lang('support.btn_send_reply')
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Side column: assign + status change --}}
    <div class="col-md-4">
        {{-- Assign --}}
        <div class="card mb-2">
            <div class="card-header">
                <h4 class="card-title">@lang('support.section_assign')</h4>
            </div>
            <div class="card-content">
                <div class="card-body">
                    @if($ticket->assignee)
                        <p class="mb-1"><strong>@lang('support.field_assigned_to'):</strong> {{ $ticket->assignee->name }}</p>
                    @endif
                    <form action="{{ route('admin.support.assign', $ticket->id) }}" method="POST" id="assignForm">
                        @csrf
                        <div class="form-group">
                            <label for="assigned_to">@lang('support.field_assign_to_user')</label>
                            <input type="number" name="assigned_to" id="assigned_to"
                                class="form-control @error('assigned_to') is-invalid @enderror"
                                value="{{ old('assigned_to', $ticket->assigned_to) }}"
                                placeholder="{{ __('support.placeholder_user_id') }}">
                            @error('assigned_to')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <button type="submit" class="btn btn-sm btn-warning" id="assignBtn"
                            data-confirm="{{ __('support.confirm_assign') }}">
                            <i class="la la-user-check"></i> @lang('support.btn_assign')
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Status change --}}
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">@lang('support.section_change_status')</h4>
            </div>
            <div class="card-content">
                <div class="card-body">
                    <form action="{{ route('admin.support.updateStatus', $ticket->id) }}" method="POST" id="statusForm">
                        @csrf
                        <div class="form-group">
                            <label for="status">@lang('support.field_status')</label>
                            <select name="status" id="status" class="form-control">
                                <option value="open"        @selected($ticket->status === 'open')>@lang('support.status_open')</option>
                                <option value="in_progress" @selected($ticket->status === 'in_progress')>@lang('support.status_in_progress')</option>
                                <option value="resolved"    @selected($ticket->status === 'resolved')>@lang('support.status_resolved')</option>
                                <option value="closed"      @selected($ticket->status === 'closed')>@lang('support.status_closed')</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-sm btn-success" id="statusBtn"
                            data-confirm="{{ __('support.confirm_status_change') }}">
                            <i class="la la-sync"></i> @lang('support.btn_update_status')
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    ['assignForm', 'statusForm'].forEach(function (formId) {
        var form = document.getElementById(formId);
        if (!form) return;
        var btn = form.querySelector('[data-confirm]');
        if (!btn) return;
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            var msg = btn.getAttribute('data-confirm');
            if (window.vcConfirm) {
                window.vcConfirm({ title: msg }).then(function (r) { if (r.isConfirmed) { form.submit(); } });
            } else if (confirm(msg)) {
                form.submit();
            }
        });
    });
});
</script>
@endpush
