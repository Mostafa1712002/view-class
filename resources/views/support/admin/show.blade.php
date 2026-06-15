@extends('layouts.app')

@section('title', __('support.admin_ticket_detail_title'))
@section('body_class', 'theme-light')

@php
    $isRtl = app()->getLocale() === 'ar';
    $user  = auth()->user();
    $canReply      = $user->canDo('support.reply');
    $canAssign     = $user->canDo('support.assign');
    $canStatus     = $user->canDo('support.change_status');
    $canClose      = $user->canDo('support.close');
    $canDelete     = $user->canDo('support.delete');
    $canSeeAttach  = $user->canDo('support.view_attachments');
    $isClosed      = in_array($ticket->status, ['closed'], true);
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

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="row">
    {{-- Main column: details + replies + reply form --}}
    <div class="col-md-8">
        {{-- Ticket details --}}
        <div class="card mb-2">
            <div class="card-header"><h4 class="card-title">@lang('support.section_ticket_details')</h4></div>
            <div class="card-content">
                <div class="card-body">
                    <div class="row mb-1">
                        <div class="col-md-4 font-weight-bold">@lang('support.field_creator')</div>
                        <div class="col-md-8">{{ optional($ticket->creator)->name }} ({{ $ticket->creator_role }})</div>
                    </div>
                    <div class="row mb-1">
                        <div class="col-md-4 font-weight-bold">@lang('support.field_school')</div>
                        <div class="col-md-8">{{ optional($ticket->school)->name ?? '—' }}</div>
                    </div>
                    <div class="row mb-1">
                        <div class="col-md-4 font-weight-bold">@lang('support.field_type')</div>
                        <div class="col-md-8">{{ $ticket->typeLabel() }}</div>
                    </div>
                    <div class="row mb-1">
                        <div class="col-md-4 font-weight-bold">@lang('support.field_category')</div>
                        <div class="col-md-8">{{ __('support.category_' . $ticket->category) }}</div>
                    </div>
                    <div class="row mb-1">
                        <div class="col-md-4 font-weight-bold">@lang('support.field_department')</div>
                        <div class="col-md-8">{{ $ticket->departmentLabel() }}</div>
                    </div>
                    <div class="row mb-1">
                        <div class="col-md-4 font-weight-bold">@lang('support.field_created_at')</div>
                        <div class="col-md-8">{{ $ticket->created_at->format('Y-m-d H:i') }}</div>
                    </div>
                    @if($ticket->problem_url)
                    <div class="row mb-1">
                        <div class="col-md-4 font-weight-bold">@lang('support.field_problem_url')</div>
                        <div class="col-md-8"><a href="{{ $ticket->problem_url }}" target="_blank" rel="noopener">{{ $ticket->problem_url }}</a></div>
                    </div>
                    @endif
                    @if($ticket->attachment_path)
                    <div class="row mb-1">
                        <div class="col-md-4 font-weight-bold">@lang('support.field_attachments')</div>
                        <div class="col-md-8">
                            @if($canSeeAttach)
                                <a href="{{ route('admin.support.attachment', $ticket->id) }}" class="btn btn-sm btn-outline-primary">
                                    <x-svg-icon name="paperclip" size="14" /> @lang('support.btn_download_attachment')
                                </a>
                            @else
                                <span class="text-muted">{{ basename($ticket->attachment_path) }}</span>
                            @endif
                        </div>
                    </div>
                    @endif
                    <hr>
                    <div class="mt-1">{!! nl2br(e($ticket->body)) !!}</div>
                </div>
            </div>
        </div>

        {{-- Replies --}}
        <div class="card mb-2">
            <div class="card-header"><h4 class="card-title">@lang('support.section_replies')</h4></div>
            <div class="card-content">
                <div class="card-body p-0">
                    @forelse($ticket->replies as $reply)
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
                    @empty
                    <div class="p-3 text-center text-muted">@lang('support.empty_replies')</div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Staff reply form --}}
        @if($canReply && !$isClosed)
        <div class="card">
            <div class="card-header"><h4 class="card-title">@lang('support.section_staff_reply')</h4></div>
            <div class="card-content">
                <div class="card-body">
                    <form action="{{ route('admin.support.reply', $ticket->id) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="form-group">
                            <label for="body">@lang('support.field_reply_body') <span class="text-danger">*</span></label>
                            <textarea name="body" id="body" rows="4" class="form-control @error('body') is-invalid @enderror" required>{{ old('body') }}</textarea>
                            @error('body')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-group">
                            <label for="attachment">@lang('support.field_attachment')</label>
                            <input type="file" name="attachment" id="attachment" class="form-control-file @error('attachment') is-invalid @enderror">
                            <small class="text-muted">@lang('support.attachment_hint')</small>
                            @error('attachment')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <x-svg-icon name="send" size="15" /> @lang('support.btn_send_reply')
                        </button>
                    </form>
                </div>
            </div>
        </div>
        @elseif($isClosed)
        <div class="alert alert-secondary">@lang('support.ticket_closed_notice')</div>
        @endif
    </div>

    {{-- Side column: actions + assign + status + status log --}}
    <div class="col-md-4">
        {{-- Quick actions --}}
        @if($canClose || $canStatus || $canDelete)
        <div class="card mb-2">
            <div class="card-header"><h4 class="card-title">@lang('support.section_actions')</h4></div>
            <div class="card-content"><div class="card-body d-flex flex-wrap gap-1">
                @if($canClose && !$isClosed)
                    <form action="{{ route('admin.support.close', $ticket->id) }}" method="POST" class="js-confirm" data-confirm="{{ __('support.confirm_close') }}">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-secondary"><x-svg-icon name="lock" size="14" /> @lang('support.btn_close')</button>
                    </form>
                @endif
                @if($canStatus && $isClosed)
                    <form action="{{ route('admin.support.reopen', $ticket->id) }}" method="POST" class="js-confirm" data-confirm="{{ __('support.confirm_reopen') }}">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-success"><x-svg-icon name="unlock" size="14" /> @lang('support.btn_reopen')</button>
                    </form>
                @endif
                @if($canDelete)
                    <form action="{{ route('admin.support.destroy', $ticket->id) }}" method="POST" class="js-confirm" data-confirm="{{ __('support.confirm_delete') }}">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-danger"><x-svg-icon name="trash" size="14" /> @lang('support.btn_delete')</button>
                    </form>
                @endif
            </div></div>
        </div>
        @endif

        {{-- Assign --}}
        @if($canAssign)
        <div class="card mb-2">
            <div class="card-header"><h4 class="card-title">@lang('support.section_assign')</h4></div>
            <div class="card-content"><div class="card-body">
                @if($ticket->assignee)
                    <p class="mb-1"><strong>@lang('support.field_assigned_to'):</strong> {{ $ticket->assignee->name }}</p>
                @endif
                <form action="{{ route('admin.support.assign', $ticket->id) }}" method="POST" class="js-confirm" data-confirm="{{ __('support.confirm_assign') }}">
                    @csrf
                    <div class="form-group">
                        <label for="assigned_to">@lang('support.field_assign_to_user')</label>
                        <input type="number" name="assigned_to" id="assigned_to"
                            class="form-control @error('assigned_to') is-invalid @enderror"
                            value="{{ old('assigned_to', $ticket->assigned_to) }}"
                            placeholder="{{ __('support.placeholder_user_id') }}">
                        @error('assigned_to')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <button type="submit" class="btn btn-sm btn-warning"><x-svg-icon name="person-check" size="14" /> @lang('support.btn_assign')</button>
                </form>
            </div></div>
        </div>
        @endif

        {{-- Status change --}}
        @if($canStatus)
        <div class="card mb-2">
            <div class="card-header"><h4 class="card-title">@lang('support.section_change_status')</h4></div>
            <div class="card-content"><div class="card-body">
                <form action="{{ route('admin.support.updateStatus', $ticket->id) }}" method="POST" class="js-confirm" data-confirm="{{ __('support.confirm_status_change') }}">
                    @csrf
                    <div class="form-group">
                        <label for="status">@lang('support.field_status')</label>
                        <select name="status" id="status" class="form-control">
                            @foreach(['open','in_progress','resolved','closed'] as $s)
                                <option value="{{ $s }}" @selected($ticket->status === $s)>@lang('support.status_'.$s)</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="btn btn-sm btn-success"><x-svg-icon name="arrow-repeat" size="14" /> @lang('support.btn_update_status')</button>
                </form>
            </div></div>
        </div>
        @endif

        {{-- Status change log --}}
        <div class="card">
            <div class="card-header"><h4 class="card-title">@lang('support.section_status_log')</h4></div>
            <div class="card-content"><div class="card-body p-0">
                @php
                    $statusColors = ['open' => 'primary', 'in_progress' => 'warning', 'resolved' => 'success', 'closed' => 'secondary'];
                @endphp
                @forelse($ticket->statusLogs as $log)
                    <div class="p-2 border-bottom small">
                        <div class="d-flex align-items-center">
                            <span class="badge badge-light">{{ $log->fromStatusLabel() }}</span>
                            <x-svg-icon name="{{ $isRtl ? 'arrow-left' : 'arrow-right' }}" size="12" class="mx-1" />
                            <span class="badge badge-{{ $statusColors[$log->to_status] ?? 'light' }}">{{ $log->toStatusLabel() }}</span>
                            <span class="text-muted ml-auto">{{ $log->created_at->format('Y-m-d H:i') }}</span>
                        </div>
                        @if($log->user)<div class="text-muted mt-1">{{ $log->user->name }}</div>@endif
                    </div>
                @empty
                    <div class="p-3 text-center text-muted">@lang('support.empty_status_log')</div>
                @endforelse
            </div></div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('form.js-confirm').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            if (form.dataset.confirmed === '1') return;
            e.preventDefault();
            var msg = form.getAttribute('data-confirm');
            if (window.vcConfirm) {
                window.vcConfirm({ title: msg }).then(function (r) {
                    if (r.isConfirmed) { form.dataset.confirmed = '1'; form.submit(); }
                });
            } else if (confirm(msg)) {
                form.dataset.confirmed = '1'; form.submit();
            }
        });
    });
});
</script>
@endpush
