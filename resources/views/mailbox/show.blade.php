@extends('layouts.app')

@section('title', $message->subject)
@section('body_class', 'theme-light')

@php
    $isRtl = app()->getLocale() === 'ar';

    $importanceColor = match($message->importance) {
        'urgent'    => 'ds-badge-danger',
        'important' => 'ds-badge-warning',
        default     => 'ds-badge-info',
    };
    $importanceLabel = match($message->importance) {
        'urgent'    => __('mailbox.urgent'),
        'important' => __('mailbox.important_label'),
        default     => __('mailbox.normal'),
    };
@endphp

@section('content')

{{-- Page header + breadcrumb --}}
<div style="display:flex;justify-content:space-between;align-items:flex-end;flex-wrap:wrap;gap:.75rem;margin-bottom:1rem">
    <div>
        <h2 style="margin:0;font-size:1.35rem;font-weight:800;color:var(--gray-900)">
            {{ \Illuminate\Support\Str::limit($message->subject, 60) }}
        </h2>
        <nav><ol class="breadcrumb" style="margin:.1rem 0 0;padding:0;background:transparent">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('mailbox.breadcrumb_home')</a></li>
            <li class="breadcrumb-item"><a href="{{ route('my.mailbox.index') }}">@lang('mailbox.breadcrumb_mailbox')</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{ \Illuminate\Support\Str::limit($message->subject, 40) }}</li>
        </ol></nav>
    </div>
    <a href="{{ url()->previous() }}" class="btn btn-outline-secondary btn-sm">
        <x-svg-icon name="arrow-left" :size="15" /> @lang('mailbox.back')
    </a>
</div>

<div class="ds-card card">
    {{-- Card header: importance badge + subject + action buttons --}}
    <div class="ds-card-header card-header d-flex flex-wrap justify-content-between align-items-center" style="gap:8px;">
        <div style="display:flex;align-items:center;gap:.5rem">
            <span class="badge {{ $importanceColor }}">{{ $importanceLabel }}</span>
            <strong style="color:var(--gray-900)">{{ $message->subject }}</strong>
        </div>
        <div class="d-flex flex-wrap" style="gap:6px;">
            {{-- Reply / Forward --}}
            @if(Route::has('my.mailbox.reply'))
                <a href="{{ route('my.mailbox.reply', $message->id) }}" class="btn btn-sm btn-primary">
                    <x-svg-icon name="reply" :size="15" /> @lang('mailbox.reply')
                </a>
            @endif
            @if(Route::has('my.mailbox.forward'))
                <a href="{{ route('my.mailbox.forward', $message->id) }}" class="btn btn-sm btn-outline-secondary">
                    <x-svg-icon name="forward" :size="15" /> @lang('mailbox.forward')
                </a>
            @endif

            @if($recipientRow)
                {{-- Star / Unstar --}}
                @if($recipientRow->starred)
                    <form action="{{ route('my.mailbox.unstar', $message->id) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-warning">
                            <x-svg-icon name="star" :size="15" /> @lang('mailbox.unstarred_action')
                        </button>
                    </form>
                @else
                    <form action="{{ route('my.mailbox.star', $message->id) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-secondary">
                            <x-svg-icon name="star" :size="15" /> @lang('mailbox.starred_action')
                        </button>
                    </form>
                @endif

                {{-- Task toggle --}}
                <form action="{{ route('my.mailbox.task', $message->id) }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-sm {{ $recipientRow->is_task ? 'btn-success' : 'btn-outline-secondary' }}">
                        <x-svg-icon name="list-check" :size="15" />
                        {{ $recipientRow->is_task ? __('mailbox.unmark_task') : __('mailbox.mark_task') }}
                    </button>
                </form>

                {{-- Archive / Unarchive --}}
                @if($recipientRow->archived)
                    <form action="{{ route('my.mailbox.unarchive', $message->id) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-secondary">
                            <x-svg-icon name="box" :size="15" /> @lang('mailbox.unarchived_action')
                        </button>
                    </form>
                @else
                    <form action="{{ route('my.mailbox.archive', $message->id) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-secondary">
                            <x-svg-icon name="archive" :size="15" /> @lang('mailbox.archived_action')
                        </button>
                    </form>
                @endif

                {{-- Trash --}}
                @if(! $recipientRow->trashed)
                    <form action="{{ route('my.mailbox.trash', $message->id) }}" method="POST" class="d-inline" id="trash-show-form">
                        @csrf
                        <button type="button" class="btn btn-sm btn-outline-danger"
                                onclick="
                                    if(window.vcConfirm){
                                        window.vcConfirm({title:'@lang('mailbox.move_to_trash')?'}).then(r=>{if(r.isConfirmed)document.getElementById('trash-show-form').submit();});
                                    } else {
                                        document.getElementById('trash-show-form').submit();
                                    }">
                            <x-svg-icon name="trash" :size="15" /> @lang('mailbox.move_to_trash')
                        </button>
                    </form>
                @else
                    <form action="{{ route('my.mailbox.restore', $message->id) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-secondary">
                            <x-svg-icon name="arrow-counterclockwise" :size="15" /> @lang('mailbox.restore')
                        </button>
                    </form>
                @endif
            @endif
        </div>
    </div>

    <div class="card-body">
        {{-- Meta table --}}
        <table class="table table-borderless table-sm mb-3" style="max-width:600px;">
            <tr>
                <th class="text-muted" style="width:80px;font-weight:600;font-size:.82rem">@lang('mailbox.from')</th>
                <td>{{ $message->sender->name ?? '—' }}</td>
            </tr>
            <tr>
                <th class="text-muted" style="font-weight:600;font-size:.82rem">@lang('mailbox.to')</th>
                <td>
                    @foreach($message->recipients as $r)
                        <span class="badge badge-light">{{ $r->recipient->name ?? '—' }}</span>
                    @endforeach
                </td>
            </tr>
            @if($message->relatedStudent)
                <tr>
                    <th class="text-muted" style="font-weight:600;font-size:.82rem">@lang('mailbox.related_student')</th>
                    <td>{{ $message->relatedStudent->name }}</td>
                </tr>
            @endif
            <tr>
                <th class="text-muted" style="font-weight:600;font-size:.82rem">@lang('mailbox.date')</th>
                <td class="text-muted" style="font-size:.85rem">{{ $message->created_at->format('Y-m-d H:i') }}</td>
            </tr>
        </table>

        <hr>

        {{-- Body --}}
        <div class="message-body" style="white-space: pre-wrap; line-height:1.8;color:var(--text-base)">{{ $message->body }}</div>

        {{-- Attachment --}}
        @if($message->attachment_path)
            <hr>
            <div class="mt-2" style="display:flex;align-items:center;gap:.5rem">
                <x-svg-icon name="paperclip" :size="15" class="text-muted" />
                <a href="{{ route('my.mailbox.attachment', $message->id) }}" class="btn btn-sm btn-outline-secondary">
                    <x-svg-icon name="download" :size="14" /> @lang('mailbox.download')
                </a>
            </div>
        @endif
    </div>
</div>

@endsection
