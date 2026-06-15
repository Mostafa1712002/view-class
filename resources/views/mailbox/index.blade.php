@extends('layouts.app')

@section('title', __('mailbox.title'))
@section('body_class', 'theme-light')

@php
    $isRtl = app()->getLocale() === 'ar';

    $folders = [
        'inbox'     => ['label' => __('mailbox.inbox'),   'icon' => 'inbox-fill',            'count' => $counts['unreadInbox'] ?? 0, 'badge' => 'ds-badge-navy'],
        'sent'      => ['label' => __('mailbox.sent'),    'icon' => 'send-fill',             'count' => $counts['sent'] ?? 0,       'badge' => 'ds-badge-info'],
        'drafts'    => ['label' => __('mailbox.drafts'),  'icon' => 'file-earmark',          'count' => $counts['drafts'] ?? 0,     'badge' => 'ds-badge-warning'],
        'starred'   => ['label' => __('mailbox.starred'), 'icon' => 'star-fill',             'count' => $counts['starred'] ?? 0,    'badge' => 'ds-badge-gold'],
        'important' => ['label' => __('mailbox.important'),'icon' => 'exclamation-circle-fill','count' => $counts['important'] ?? 0,'badge' => 'ds-badge-danger'],
        'task'      => ['label' => __('mailbox.task'),    'icon' => 'list-check',            'count' => $counts['task'] ?? 0,       'badge' => 'ds-badge-success'],
        'archive'   => ['label' => __('mailbox.archive'), 'icon' => 'archive-fill',          'count' => $counts['archive'] ?? 0,    'badge' => 'ds-badge-warning'],
        'trash'     => ['label' => __('mailbox.trash'),   'icon' => 'trash3-fill',           'count' => $counts['trash'] ?? 0,      'badge' => 'ds-badge-danger'],
    ];

    $isSentFolder   = $folder === 'sent';
    $isDraftFolder  = $folder === 'drafts';
    $isTrashFolder  = $folder === 'trash';
@endphp

@section('content')

{{-- Page header + breadcrumb --}}
<div style="display:flex;justify-content:space-between;align-items:flex-end;flex-wrap:wrap;gap:.75rem;margin-bottom:1rem">
    <div>
        <h2 style="margin:0;font-size:1.45rem;font-weight:800;color:var(--gray-900)">@lang('mailbox.title')</h2>
        <nav><ol class="breadcrumb" style="margin:0;padding:0;background:transparent">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('mailbox.breadcrumb_home')</a></li>
            <li class="breadcrumb-item active" aria-current="page">@lang('mailbox.breadcrumb_mailbox')</li>
        </ol></nav>
    </div>
    <a href="{{ route('my.mailbox.create') }}" class="btn btn-primary">
        <x-svg-icon name="plus-lg" :size="16" /> @lang('mailbox.compose')
    </a>
</div>

<div class="row">
    {{-- Folder Rail --}}
    <div class="col-lg-3 col-12 mb-2">
        <div class="ds-card card">
            <div class="ds-card-header card-header">
                <h6 class="ds-card-title" style="margin:0;font-size:.82rem;text-transform:uppercase;letter-spacing:.4px">
                    <x-svg-icon name="envelope" :size="14" /> المجلدات
                </h6>
            </div>
            <div class="list-group list-group-flush">
                @foreach($folders as $key => $meta)
                    <a href="{{ route('my.mailbox.folder', $key) }}"
                       class="list-group-item list-group-item-action d-flex justify-content-between align-items-center {{ $folder === $key ? 'active' : '' }}">
                        <span><x-svg-icon :name="$meta['icon']" :size="15" class="me-1" /> {{ $meta['label'] }}</span>
                        @if($meta['count'] > 0)
                            <span class="badge {{ $meta['badge'] }} badge-pill">{{ $meta['count'] }}</span>
                        @endif
                    </a>
                @endforeach

                {{-- Notifications — links to system notifications page --}}
                @if(Route::has('notifications.index'))
                    <a href="{{ route('notifications.index') }}"
                       class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                        <span><x-svg-icon name="bell-fill" :size="15" class="me-1" /> @lang('mailbox.notifications')</span>
                    </a>
                @endif
            </div>
        </div>
    </div>

    {{-- Messages Panel --}}
    <div class="col-lg-9 col-12">
        {{-- Search & filter toolbar --}}
        <div class="ds-card card mb-1">
            <div class="card-body py-2">
                <form method="GET" action="{{ route('my.mailbox.folder', $folder) }}" class="d-flex flex-wrap align-items-center" style="gap:8px;">
                    <input type="search" name="search" class="form-control" style="width:auto;min-width:180px;"
                           value="{{ $filters['search'] ?? '' }}" placeholder="@lang('mailbox.search_placeholder')">

                    <select name="importance" class="form-control" style="width:auto;">
                        <option value="">— @lang('mailbox.all') —</option>
                        <option value="normal"    @selected(($filters['importance'] ?? '') === 'normal')>@lang('mailbox.normal')</option>
                        <option value="important" @selected(($filters['importance'] ?? '') === 'important')>@lang('mailbox.important_label')</option>
                        <option value="urgent"    @selected(($filters['importance'] ?? '') === 'urgent')>@lang('mailbox.urgent')</option>
                    </select>

                    @if(! $isSentFolder && ! $isDraftFolder)
                        <div class="custom-control custom-checkbox d-flex align-items-center">
                            <input type="checkbox" class="custom-control-input" id="unreadFilter"
                                   name="unread" value="1" @checked(! empty($filters['unread']))
                                   onchange="this.form.submit()">
                            <label class="custom-control-label mx-1" for="unreadFilter">@lang('mailbox.unread_filter')</label>
                        </div>
                    @endif

                    <button type="submit" class="btn btn-sm btn-secondary">
                        <x-svg-icon name="funnel-fill" :size="15" /> @lang('mailbox.filter')
                    </button>

                    @if(! empty($filters))
                        <a href="{{ route('my.mailbox.folder', $folder) }}" class="btn btn-sm btn-outline-secondary">
                            <x-svg-icon name="x-circle-fill" :size="15" class="ic-muted" /> @lang('mailbox.show_all')
                        </a>
                    @endif
                </form>
            </div>
        </div>

        {{-- Messages table --}}
        <div class="ds-card card">
            <div class="ds-card-header card-header" style="display:flex;align-items:center;gap:.4rem">
                @php $currentFolder = $folders[$folder] ?? null; @endphp
                @if($currentFolder)
                    <x-svg-icon :name="$currentFolder['icon']" :size="16" />
                    <h5 class="ds-card-title" style="margin:0">{{ $currentFolder['label'] }}</h5>
                @endif
            </div>

            @if($messages->isEmpty())
                <div class="ds-empty">
                    <div class="ds-empty-icon"><x-svg-icon name="inbox-fill" :size="30" /></div>
                    <div class="ds-empty-title">@lang('mailbox.no_messages')</div>
                    <div class="ds-empty-desc">لا توجد رسائل في هذا المجلد.</div>
                    <a href="{{ route('my.mailbox.create') }}" class="btn btn-primary btn-sm">
                        <x-svg-icon name="plus-lg" :size="14" /> @lang('mailbox.compose')
                    </a>
                </div>
            @else
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
                            @foreach($messages as $item)
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
                                        'urgent'    => 'ds-badge-danger',
                                        'important' => 'ds-badge-warning',
                                        default     => 'ds-badge-info',
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
                                            <span class="badge ds-badge-navy badge-pill">&nbsp;</span>
                                        @endif
                                        @if($isTask)
                                            <x-svg-icon name="list-check" :size="15" class="ic-success" title="@lang('mailbox.task')" />
                                        @endif
                                    </td>

                                    {{-- Importance --}}
                                    <td>
                                        <span class="badge {{ $importanceColor }}">{{ $importanceLabel }}</span>
                                    </td>

                                    {{-- Subject --}}
                                    <td>
                                        <a href="{{ route('my.mailbox.show', $mail->id) }}" style="color:var(--text-base);font-weight:{{ $isRead ? 500 : 700 }}">
                                            {{ $mail->subject }}
                                            @if($mail->attachment_path)
                                                <x-svg-icon name="paperclip" :size="14" class="ic-muted mx-1" />
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
                                    <td class="text-nowrap text-muted" style="font-size:.82rem">
                                        {{ $mail->created_at->format('Y-m-d H:i') }}
                                    </td>

                                    {{-- Actions --}}
                                    <td class="text-nowrap">
                                        @if($isDraftFolder && Route::has('my.mailbox.edit'))
                                            <a href="{{ route('my.mailbox.edit', $mail->id) }}"
                                               class="ds-action-btn" title="@lang('mailbox.edit_draft')" aria-label="@lang('mailbox.edit_draft')">
                                                <x-svg-icon name="pencil-fill" :size="15" />
                                            </a>
                                            <form action="{{ route('my.mailbox.destroy', $mail->id) }}" method="POST" class="d-inline" id="discard-form-{{ $mail->id }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="button" class="ds-action-btn"
                                                        title="@lang('mailbox.delete_permanently')" aria-label="@lang('mailbox.delete_permanently')"
                                                        style="color:var(--status-danger)"
                                                        onclick="
                                                            if(window.vcConfirm){
                                                                window.vcConfirm({title:'@lang('mailbox.delete_permanently')?'}).then(r=>{if(r.isConfirmed)document.getElementById('discard-form-{{ $mail->id }}').submit();});
                                                            } else {
                                                                document.getElementById('discard-form-{{ $mail->id }}').submit();
                                                            }">
                                                    <x-svg-icon name="trash3-fill" :size="15" />
                                                </button>
                                            </form>
                                        @endif

                                        @if(! $isSentFolder && ! $isDraftFolder && ! $isTrashFolder)
                                            {{-- Star / Unstar --}}
                                            @if($isStarred)
                                                <form action="{{ route('my.mailbox.unstar', $mail->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="ds-action-btn" title="@lang('mailbox.unstarred_action')" aria-label="@lang('mailbox.unstarred_action')" style="color:#d97706">
                                                        <x-svg-icon name="star-fill" :size="15" />
                                                    </button>
                                                </form>
                                            @else
                                                <form action="{{ route('my.mailbox.star', $mail->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="ds-action-btn" title="@lang('mailbox.starred_action')" aria-label="@lang('mailbox.starred_action')">
                                                        <x-svg-icon name="star-fill" :size="15" />
                                                    </button>
                                                </form>
                                            @endif

                                            {{-- Archive / Unarchive --}}
                                            @if($isArchived)
                                                <form action="{{ route('my.mailbox.unarchive', $mail->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="ds-action-btn" title="@lang('mailbox.unarchived_action')" aria-label="@lang('mailbox.unarchived_action')">
                                                        <x-svg-icon name="box-arrow-up" :size="15" />
                                                    </button>
                                                </form>
                                            @else
                                                <form action="{{ route('my.mailbox.archive', $mail->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="ds-action-btn" title="@lang('mailbox.archived_action')" aria-label="@lang('mailbox.archived_action')">
                                                        <x-svg-icon name="archive-fill" :size="15" />
                                                    </button>
                                                </form>
                                            @endif

                                            {{-- Trash --}}
                                            <form action="{{ route('my.mailbox.trash', $mail->id) }}" method="POST" class="d-inline" id="trash-form-{{ $mail->id }}">
                                                @csrf
                                                <button type="button" class="ds-action-btn" title="@lang('mailbox.move_to_trash')" aria-label="@lang('mailbox.move_to_trash')" style="color:var(--status-danger)"
                                                        onclick="
                                                            if(window.vcConfirm){
                                                                window.vcConfirm({title:'@lang('mailbox.move_to_trash')?'}).then(r=>{if(r.isConfirmed)document.getElementById('trash-form-{{ $mail->id }}').submit();});
                                                            } else {
                                                                document.getElementById('trash-form-{{ $mail->id }}').submit();
                                                            }">
                                                    <x-svg-icon name="trash3-fill" :size="15" />
                                                </button>
                                            </form>
                                        @endif

                                        @if($isTrashFolder)
                                            {{-- Restore --}}
                                            <form action="{{ route('my.mailbox.restore', $mail->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="ds-action-btn" title="@lang('mailbox.restore')" aria-label="@lang('mailbox.restore')" style="color:var(--status-info)">
                                                    <x-svg-icon name="arrow-counterclockwise" :size="15" />
                                                </button>
                                            </form>
                                            {{-- Permanently delete --}}
                                            <form action="{{ route('my.mailbox.destroy', $mail->id) }}" method="POST" class="d-inline" id="del-form-{{ $mail->id }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="button" class="ds-action-btn" title="@lang('mailbox.delete_permanently')" aria-label="@lang('mailbox.delete_permanently')" style="color:var(--status-danger)"
                                                        onclick="
                                                            if(window.vcConfirm){
                                                                window.vcConfirm({title:'@lang('mailbox.delete_permanently')?'}).then(r=>{if(r.isConfirmed)document.getElementById('del-form-{{ $mail->id }}').submit();});
                                                            } else {
                                                                document.getElementById('del-form-{{ $mail->id }}').submit();
                                                            }">
                                                    <x-svg-icon name="x-circle-fill" :size="15" />
                                                </button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($messages->hasPages())
                    <div class="card-footer">
                        {{ $messages->links() }}
                    </div>
                @endif
            @endif
        </div>
    </div>
</div>

@endsection
