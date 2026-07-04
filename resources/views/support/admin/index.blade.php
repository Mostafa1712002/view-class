@extends('layouts.app')

@section('title', __('support.admin_tickets_title'))
@section('body_class', 'theme-light')

@php
    $isRtl = app()->getLocale() === 'ar';
    $total = $counts['all'] ?? 0;
    // Stat cards: key => [arabic label, status|reply_state value, css color, svg icon]
    $cards = [
        ['open',          'card_open',          ['status' => 'open'],                 'info',      'folder2-open'],
        ['in_progress',   'card_in_progress',   ['status' => 'in_progress'],          'warning',   'hourglass-split'],
        ['admin_replied', 'card_admin_replied', ['reply_state' => 'admin_replied'],   'primary',   'reply'],
        ['user_replied',  'card_user_replied',  ['reply_state' => 'user_replied'],    'success',   'person'],
        ['closed',        'card_closed',        ['status' => 'closed'],               'secondary', 'lock'],
    ];
    $activeStatus = $filters['status'] ?? null;
    $activeReply  = $filters['reply_state'] ?? null;
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
    <div class="content-header-right col-md-3 col-12 d-flex align-items-center justify-content-{{ $isRtl ? 'start' : 'end' }} mb-2">
        <a href="{{ route('my.support.create') }}" class="btn btn-primary">
            <x-svg-icon name="plus-lg" /> @lang('support.btn_new_ticket')
        </a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

{{-- Stat cards (click to filter) --}}
<div class="row mb-1">
    @foreach($cards as [$key, $labelKey, $param, $color, $icon])
        @php
            $isActive = (isset($param['status']) && $activeStatus === $param['status'])
                     || (isset($param['reply_state']) && $activeReply === $param['reply_state']);
            $pct = $total > 0 ? round(($counts[$key] ?? 0) / $total * 100) : 0;
        @endphp
        <div class="col-6 col-md mb-2">
            <a href="{{ route('admin.support.index', $param) }}"
               class="card mb-0 text-decoration-none {{ $isActive ? 'border-'.$color : '' }}"
               style="display:block; {{ $isActive ? 'box-shadow:0 0 0 2px var(--'.$color.', #c9a04b);' : '' }}">
                <div class="card-body py-2 d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small">@lang('support.'.$labelKey)</div>
                        <div class="h3 mb-0 text-{{ $color }}">{{ $counts[$key] ?? 0 }}</div>
                        <div class="text-muted" style="font-size:.72rem">{{ $pct }}% @lang('support.card_of_total')</div>
                    </div>
                    <span class="badge badge-{{ $color }} d-flex align-items-center justify-content-center" style="width:42px;height:42px;border-radius:50%;">
                        <x-svg-icon name="{{ $icon }}" size="18" />
                    </span>
                </div>
            </a>
        </div>
    @endforeach
</div>

{{-- Filter bar --}}
<div class="card mb-2">
    <div class="card-content">
        <div class="card-body py-1">
            <form method="GET" action="{{ route('admin.support.index') }}" class="form-row align-items-end">
                <div class="col-md-2 col-sm-6 mb-1">
                    <label class="mb-0 small">@lang('support.filter_status')</label>
                    <select name="status" class="form-control form-control-sm">
                        <option value="">— @lang('support.filter_all') —</option>
                        @foreach(['open','in_progress','resolved','closed'] as $s)
                            <option value="{{ $s }}" @selected(($filters['status'] ?? '') === $s)>@lang('support.status_'.$s)</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 col-sm-6 mb-1">
                    <label class="mb-0 small">@lang('support.filter_priority')</label>
                    <select name="priority" class="form-control form-control-sm">
                        <option value="">— @lang('support.filter_all') —</option>
                        @foreach(['low','normal','high','urgent'] as $p)
                            <option value="{{ $p }}" @selected(($filters['priority'] ?? '') === $p)>@lang('support.priority_'.$p)</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 col-sm-6 mb-1">
                    <label class="mb-0 small">@lang('support.filter_type')</label>
                    <select name="type" class="form-control form-control-sm">
                        <option value="">— @lang('support.filter_all') —</option>
                        @foreach(\App\Models\SupportTicket::TYPES as $t)
                            <option value="{{ $t }}" @selected(($filters['type'] ?? '') === $t)>@lang('support.type_'.$t)</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 col-sm-6 mb-1">
                    <label class="mb-0 small">@lang('support.filter_department')</label>
                    <select name="department" class="form-control form-control-sm">
                        <option value="">— @lang('support.filter_all') —</option>
                        @foreach(\App\Models\SupportTicket::DEPARTMENTS as $d)
                            <option value="{{ $d }}" @selected(($filters['department'] ?? '') === $d)>@lang('support.dept_'.$d)</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 col-sm-12 mb-1 d-flex gap-1">
                    <button type="submit" class="btn btn-sm btn-primary">
                        <x-svg-icon name="search" size="14" /> @lang('support.filter_apply')
                    </button>
                    <a href="{{ route('admin.support.index') }}" class="btn btn-sm btn-secondary ml-1">
                        <x-svg-icon name="arrow-clockwise" size="14" /> @lang('support.filter_reset')
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
                        <th>@lang('support.field_ticket_no')</th>
                        <th>@lang('support.field_type')</th>
                        <th>@lang('support.field_department')</th>
                        <th>@lang('support.field_subject')</th>
                        <th>@lang('support.field_status')</th>
                        <th>@lang('support.field_priority')</th>
                        <th>@lang('support.field_created_at')</th>
                        <th>@lang('support.field_school')</th>
                        <th>@lang('support.field_creator')</th>
                        <th>@lang('support.field_assigned_to')</th>
                        <th>@lang('support.field_actions')</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tickets as $ticket)
                        <tr>
                            <td>#{{ $ticket->id }}</td>
                            <td>{{ $ticket->typeLabel() }}</td>
                            <td>{{ $ticket->departmentLabel() }}</td>
                            <td>{{ $ticket->subject }}</td>
                            <td><span class="badge badge-{{ $ticket->statusColor() }}">{{ $ticket->statusLabel() }}</span></td>
                            <td><span class="badge badge-{{ $ticket->priorityColor() }}">{{ $ticket->priorityLabel() }}</span></td>
                            <td>{{ $ticket->created_at->format('Y-m-d H:i') }}</td>
                            <td>{{ optional($ticket->school)->name ?? '—' }}</td>
                            <td>{{ optional($ticket->creator)->name }}</td>
                            <td>{{ optional($ticket->assignee)->name ?? '—' }}</td>
                            <td>
                                <a href="{{ route('admin.support.show', $ticket->id) }}" class="btn btn-sm btn-info">
                                    <x-svg-icon name="eye" size="14" /> @lang('support.btn_view')
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="text-center text-muted py-4">
                                <x-svg-icon name="inbox" size="28" class="d-block mx-auto mb-1 text-muted" />
                                @lang('support.empty_tickets')
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($tickets->hasPages())
            <div class="p-2">{{ $tickets->links() }}</div>
        @endif
    </div>
</div>
@endsection
