@extends('layouts.app')

@section('title', __('support.admin_tickets_title'))
@section('body_class', 'theme-light')

@php
    $isRtl = app()->getLocale() === 'ar';
@endphp

@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-9 col-12 mb-2">
        <h2 class="content-header-title float-{{ $isRtl ? 'right' : 'left' }} mb-0">
            @lang('support.admin_tickets_title')
        </h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('support.breadcrumb_home')</a></li>
                <li class="breadcrumb-item active">@lang('support.breadcrumb_admin_tickets')</li>
            </ol>
        </div>
    </div>
</div>


{{-- Filter bar --}}
<div class="card mb-2">
    <div class="card-content">
        <div class="card-body py-1">
            <form method="GET" action="{{ route('admin.support.index') }}" class="form-row align-items-end">
                <div class="col-md-3 col-sm-6 mb-1">
                    <label class="mb-0 small">@lang('support.filter_status')</label>
                    <select name="status" class="form-control form-control-sm">
                        <option value="">— @lang('support.filter_all') —</option>
                        <option value="open"        @selected(($filters['status'] ?? '') === 'open')>@lang('support.status_open')</option>
                        <option value="in_progress" @selected(($filters['status'] ?? '') === 'in_progress')>@lang('support.status_in_progress')</option>
                        <option value="resolved"    @selected(($filters['status'] ?? '') === 'resolved')>@lang('support.status_resolved')</option>
                        <option value="closed"      @selected(($filters['status'] ?? '') === 'closed')>@lang('support.status_closed')</option>
                    </select>
                </div>
                <div class="col-md-3 col-sm-6 mb-1">
                    <label class="mb-0 small">@lang('support.filter_priority')</label>
                    <select name="priority" class="form-control form-control-sm">
                        <option value="">— @lang('support.filter_all') —</option>
                        <option value="low"    @selected(($filters['priority'] ?? '') === 'low')>@lang('support.priority_low')</option>
                        <option value="normal" @selected(($filters['priority'] ?? '') === 'normal')>@lang('support.priority_normal')</option>
                        <option value="high"   @selected(($filters['priority'] ?? '') === 'high')>@lang('support.priority_high')</option>
                        <option value="urgent" @selected(($filters['priority'] ?? '') === 'urgent')>@lang('support.priority_urgent')</option>
                    </select>
                </div>
                <div class="col-md-3 col-sm-6 mb-1">
                    <label class="mb-0 small">@lang('support.filter_category')</label>
                    <select name="category" class="form-control form-control-sm">
                        <option value="">— @lang('support.filter_all') —</option>
                        <option value="technical" @selected(($filters['category'] ?? '') === 'technical')>@lang('support.category_technical')</option>
                        <option value="academic"  @selected(($filters['category'] ?? '') === 'academic')>@lang('support.category_academic')</option>
                        <option value="billing"   @selected(($filters['category'] ?? '') === 'billing')>@lang('support.category_billing')</option>
                        <option value="account"   @selected(($filters['category'] ?? '') === 'account')>@lang('support.category_account')</option>
                        <option value="other"     @selected(($filters['category'] ?? '') === 'other')>@lang('support.category_other')</option>
                    </select>
                </div>
                <div class="col-md-3 col-sm-6 mb-1 d-flex gap-1">
                    <button type="submit" class="btn btn-sm btn-primary">
                        <i class="la la-search"></i> @lang('support.filter_apply')
                    </button>
                    <a href="{{ route('admin.support.index') }}" class="btn btn-sm btn-secondary ml-1">
                        <i class="la la-redo"></i> @lang('support.filter_reset')
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-content">
        <div class="table-responsive">
            <table class="table table-bordered table-striped mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>@lang('support.field_subject')</th>
                        <th>@lang('support.field_creator')</th>
                        <th>@lang('support.field_category')</th>
                        <th>@lang('support.field_status')</th>
                        <th>@lang('support.field_priority')</th>
                        <th>@lang('support.field_assigned_to')</th>
                        <th>@lang('support.field_created_at')</th>
                        <th>@lang('support.field_actions')</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tickets as $ticket)
                        <tr>
                            <td>{{ $ticket->id }}</td>
                            <td>{{ $ticket->subject }}</td>
                            <td>{{ optional($ticket->creator)->name }}</td>
                            <td>{{ __('support.category_' . $ticket->category) }}</td>
                            <td>
                                <span class="badge badge-{{ $ticket->statusColor() }}">
                                    {{ $ticket->statusLabel() }}
                                </span>
                            </td>
                            <td>
                                <span class="badge badge-{{ $ticket->priorityColor() }}">
                                    {{ $ticket->priorityLabel() }}
                                </span>
                            </td>
                            <td>{{ optional($ticket->assignee)->name ?? '—' }}</td>
                            <td>{{ $ticket->created_at->format('Y-m-d H:i') }}</td>
                            <td>
                                <a href="{{ route('admin.support.show', $ticket->id) }}" class="btn btn-sm btn-info">
                                    <i class="la la-eye"></i> @lang('support.btn_view')
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-3">
                                @lang('support.empty_tickets')
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($tickets->hasPages())
            <div class="p-2">
                {{ $tickets->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
