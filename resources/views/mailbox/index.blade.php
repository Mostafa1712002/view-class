@extends('layouts.app')

@section('title', __('mailbox.title'))
@section('body_class', 'theme-light')

@php
    $isRtl = app()->getLocale() === 'ar';

    $folders = [
        'inbox'     => ['label' => __('mailbox.inbox'),   'icon' => 'la-inbox',       'count' => $counts['unreadInbox'] ?? 0, 'badge' => 'badge-primary'],
        'sent'      => ['label' => __('mailbox.sent'),    'icon' => 'la-paper-plane',  'count' => $counts['sent'] ?? 0,       'badge' => 'badge-info'],
        'drafts'    => ['label' => __('mailbox.drafts'),  'icon' => 'la-file',         'count' => $counts['drafts'] ?? 0,     'badge' => 'badge-secondary'],
        'starred'   => ['label' => __('mailbox.starred'), 'icon' => 'la-star',         'count' => $counts['starred'] ?? 0,    'badge' => 'badge-warning'],
        'important' => ['label' => __('mailbox.important'),'icon' => 'la-exclamation-circle','count' => $counts['important'] ?? 0,'badge' => 'badge-danger'],
        'task'      => ['label' => __('mailbox.task'),    'icon' => 'la-tasks',        'count' => $counts['task'] ?? 0,       'badge' => 'badge-success'],
        'archive'   => ['label' => __('mailbox.archive'), 'icon' => 'la-archive',      'count' => $counts['archive'] ?? 0,    'badge' => 'badge-light'],
        'trash'     => ['label' => __('mailbox.trash'),   'icon' => 'la-trash',        'count' => $counts['trash'] ?? 0,      'badge' => 'badge-danger'],
    ];

    $isSentFolder   = $folder === 'sent';
    $isDraftFolder  = $folder === 'drafts';
    $isTrashFolder  = $folder === 'trash';
@endphp

@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-9 col-12 mb-2">
        <h2 class="content-header-title float-{{ $isRtl ? 'right' : 'left' }} mb-0">
            @lang('mailbox.title')
        </h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('mailbox.breadcrumb_home')</a></li>
                <li class="breadcrumb-item active">@lang('mailbox.breadcrumb_mailbox')</li>
            </ol>
        </div>
    </div>
    <div class="content-header-right text-md-{{ $isRtl ? 'left' : 'right' }} col-md-3 col-12 d-flex justify-content-{{ $isRtl ? 'start' : 'end' }}">
        <a href="{{ route('my.mailbox.create') }}" class="btn btn-primary">
            <i class="la la-plus"></i> @lang('mailbox.compose')
        </a>
    </div>
</div>

<div class="row">
    {{-- Folder Rail --}}
    <div class="col-lg-3 col-12 mb-2">
        <div class="card">
            <div class="card-content">
                <div class="list-group list-group-flush">
                    @foreach($folders as $key => $meta)
                        <a href="{{ route('my.mailbox.folder', $key) }}"
                           class="list-group-item list-group-item-action d-flex justify-content-between align-items-center {{ $folder === $key ? 'active' : '' }}">
                            <span><i class="la {{ $meta['icon'] }} mr-1"></i> {{ $meta['label'] }}</span>
                            @if($meta['count'] > 0)
                                <span class="badge {{ $meta['badge'] }} badge-pill">{{ $meta['count'] }}</span>
                            @endif
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- Messages Panel --}}
    <div class="col-lg-9 col-12">
        {{-- Toolbar --}}
        <div class="card mb-1">
            <div class="card-body py-1">
                <form method="GET" action="{{ route('my.mailbox.folder', $folder) }}" class="d-flex flex-wrap align-items-center" style="gap:8px;">
                    {{-- Importance filter --}}
                    <select name="importance" class="form-control" style="width:auto;">
                        <option value="">— @lang('mailbox.all') —</option>
                        <option value="normal"    @selected(($filters['importance'] ?? '') === 'normal')>@lang('mailbox.normal')</option>
                        <option value="important" @selected(($filters['importance'] ?? '') === 'important')>@lang('mailbox.important_label')</option>
                        <option value="urgent"    @selected(($filters['importance'] ?? '') === 'urgent')>@lang('mailbox.urgent')</option>
                    </select>

                    {{-- Unread toggle --}}
                    @if(! $isSentFolder && ! $isDraftFolder)
                        <div class="custom-control custom-checkbox d-flex align-items-center">
                            <input type="checkbox" class="custom-control-input" id="unreadFilter"
                                   name="unread" value="1" @checked(! empty($filters['unread']))
                                   onchange="this.form.submit()">
                            <label class="custom-control-label mx-1" for="unreadFilter">@lang('mailbox.unread_filter')</label>
                        </div>
                    @endif

                    <button type="submit" class="btn btn-sm btn-secondary">
                        <i class="la la-filter"></i> @lang('mailbox.filter')
                    </button>

                    @if(! empty($filters))
                        <a href="{{ route('my.mailbox.folder', $folder) }}" class="btn btn-sm btn-light">
                            <i class="la la-times"></i> @lang('mailbox.show_all')
                        </a>
                    @endif
                </form>
            </div>
        </div>

        {{-- Messages table --}}
        <div class="card">
            <div class="card-content">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th style="width:40px;"></th>
                                <th>@lang('mailbox.importance_filter')</th>
                                <th>@lang('mailbox.subject')</th>
                                <th>{{ $isSentFolder ? __('mailbox.to') : __('mailbox.from') }}</th>
                                <th>@lang('mailbox.date')</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($messages as $item)
                                @php
                                    if ($isSentFolder || $isDraftFolder) {
                                        $mail       = $item;
                                        $isRead     = true;
                                        $isStarred  = false;
                                        $isArchived = false;
                                        $isTrashed  = false;
                                        $isTask     = false;
                                    } else {
                                        $mail       = $item->mail;
                                        $isRead     = $item->is_read;
                                        $isStarred  = $item->starred;
                                        $isArchived = $item->archived;
                                        $isTrashed  = $item->trashed;
                                        $isTask     = $item->is_task;
                                    }

                                    $importanceColor = match($mail->importance) {
                                        'urgent'    => 'badge-danger',
                                        'important' => 'badge-warning',
                                        default     => 'badge-secondary',
                                    };
                                    $importanceLabel = match($mail->importance) {
                                        'urgent'    => __('mailbox.urgent'),
                                        'important' => __('mailbox.important_label'),
                                        default     => __('mailbox.normal'),
                                    };
                                @endphp
                                <tr class="{{ ! $isRead ? 'font-weight-bold' : '' }}">
                                    {{-- Unread dot --}}
                                    <td class="text-center">
                                        @if(! $isRead)
                                            <span class="badge badge-primary badge-pill">&nbsp;</span>
                                        @endif
                                        @if($isTask)
                                            <i class="la la-tasks text-success" title="@lang('mailbox.task')"></i>
                                        @endif
                                    </td>

                                    {{-- Importance --}}
                                    <td>
                                        <span class="badge {{ $importanceColor }}">{{ $importanceLabel }}</span>
                                    </td>

                                    {{-- Subject --}}
                                    <td>
                                        <a href="{{ route('my.mailbox.show', $mail->id) }}">
                                            {{ $mail->subject }}
                                            @if($mail->attachment_path)
                                                <i class="la la-paperclip text-muted mx-1" title="@lang('mailbox.attachment')"></i>
                                            @endif
                                        </a>
                                    </td>

                                    {{-- From / To --}}
                                    <td>
                                        @if($isSentFolder)
                                            @foreach($mail->recipients->take(3) as $r)
                                                <span class="badge badge-light">{{ $r->recipient->name ?? '—' }}</span>
                                            @endforeach
                                            @if($mail->recipients->count() > 3)
                                                <span class="text-muted small">+{{ $mail->recipients->count() - 3 }}</span>
                                            @endif
                                        @else
                                            {{ $mail->sender->name ?? '—' }}
                                        @endif
                                    </td>

                                    {{-- Date --}}
                                    <td class="text-nowrap">{{ $mail->created_at->format('Y-m-d H:i') }}</td>

                                    {{-- Actions --}}
                                    <td class="text-nowrap">
                                        @if(! $isSentFolder && ! $isDraftFolder && ! $isTrashFolder)
                                            {{-- Star / Unstar --}}
                                            @if($isStarred)
                                                <form action="{{ route('my.mailbox.unstar', $mail->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-warning" title="@lang('mailbox.unstarred_action')">
                                                        <i class="la la-star"></i>
                                                    </button>
                                                </form>
                                            @else
                                                <form action="{{ route('my.mailbox.star', $mail->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-outline-warning" title="@lang('mailbox.starred_action')">
                                                        <i class="la la-star"></i>
                                                    </button>
                                                </form>
                                            @endif

                                            {{-- Archive / Unarchive --}}
                                            @if($isArchived)
                                                <form action="{{ route('my.mailbox.unarchive', $mail->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-secondary" title="@lang('mailbox.unarchived_action')">
                                                        <i class="la la-box-open"></i>
                                                    </button>
                                                </form>
                                            @else
                                                <form action="{{ route('my.mailbox.archive', $mail->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-outline-secondary" title="@lang('mailbox.archived_action')">
                                                        <i class="la la-archive"></i>
                                                    </button>
                                                </form>
                                            @endif

                                            {{-- Trash --}}
                                            <form action="{{ route('my.mailbox.trash', $mail->id) }}" method="POST" class="d-inline" id="trash-form-{{ $mail->id }}">
                                                @csrf
                                                <button type="button" class="btn btn-sm btn-outline-danger"
                                                        title="@lang('mailbox.move_to_trash')"
                                                        onclick="
                                                            if(window.vcConfirm){
                                                                window.vcConfirm({title:'@lang('mailbox.move_to_trash')?'}).then(r=>{if(r.isConfirmed)document.getElementById('trash-form-{{ $mail->id }}').submit();});
                                                            } else {
                                                                document.getElementById('trash-form-{{ $mail->id }}').submit();
                                                            }">
                                                    <i class="la la-trash"></i>
                                                </button>
                                            </form>
                                        @endif

                                        @if($isTrashFolder)
                                            {{-- Restore --}}
                                            <form action="{{ route('my.mailbox.restore', $mail->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-info" title="@lang('mailbox.restore')">
                                                    <i class="la la-undo"></i>
                                                </button>
                                            </form>
                                            {{-- Permanently delete --}}
                                            <form action="{{ route('my.mailbox.destroy', $mail->id) }}" method="POST" class="d-inline" id="del-form-{{ $mail->id }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="button" class="btn btn-sm btn-danger"
                                                        title="@lang('mailbox.delete_permanently')"
                                                        onclick="
                                                            if(window.vcConfirm){
                                                                window.vcConfirm({title:'@lang('mailbox.delete_permanently')?'}).then(r=>{if(r.isConfirmed)document.getElementById('del-form-{{ $mail->id }}').submit();});
                                                            } else {
                                                                document.getElementById('del-form-{{ $mail->id }}').submit();
                                                            }">
                                                    <i class="la la-times-circle"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        <i class="la la-inbox" style="font-size:2rem;"></i>
                                        <p class="mt-1">@lang('mailbox.no_messages')</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($messages->hasPages())
                    <div class="p-2">
                        {{ $messages->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
