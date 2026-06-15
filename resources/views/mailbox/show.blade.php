@extends('layouts.app')

@section('title', $message->subject)
@section('body_class', 'theme-light')

@php
    $isRtl = app()->getLocale() === 'ar';

    $importanceColor = match($message->importance) {
        'urgent'    => 'badge-danger',
        'important' => 'badge-warning',
        default     => 'badge-secondary',
    };
    $importanceLabel = match($message->importance) {
        'urgent'    => __('mailbox.urgent'),
        'important' => __('mailbox.important_label'),
        default     => __('mailbox.normal'),
    };
@endphp

@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-9 col-12 mb-2">
        <h2 class="content-header-title float-{{ $isRtl ? 'right' : 'left' }} mb-0">
            {{ $message->subject }}
        </h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('mailbox.breadcrumb_home')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('my.mailbox.index') }}">@lang('mailbox.breadcrumb_mailbox')</a></li>
                <li class="breadcrumb-item active">{{ Str::limit($message->subject, 40) }}</li>
            </ol>
        </div>
    </div>
    <div class="content-header-right text-md-{{ $isRtl ? 'left' : 'right' }} col-md-3 col-12 d-flex justify-content-{{ $isRtl ? 'start' : 'end' }}">
        <a href="{{ url()->previous() }}" class="btn btn-secondary">
            <x-svg-icon name="arrow-left" /> @lang('mailbox.back')
        </a>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center" style="gap:8px;">
        <div>
            <span class="badge {{ $importanceColor }} mr-1">{{ $importanceLabel }}</span>
            <strong>{{ $message->subject }}</strong>
        </div>
        <div class="d-flex flex-wrap" style="gap:6px;">
            {{-- Reply / Forward (gated on mailbox.send in the controller) --}}
            @if(Route::has('my.mailbox.reply'))
                <a href="{{ route('my.mailbox.reply', $message->id) }}" class="btn btn-sm btn-primary">
                    <x-svg-icon name="reply" /> @lang('mailbox.reply')
                </a>
            @endif
            @if(Route::has('my.mailbox.forward'))
                <a href="{{ route('my.mailbox.forward', $message->id) }}" class="btn btn-sm btn-outline-primary">
                    <x-svg-icon name="share" /> @lang('mailbox.forward')
                </a>
            @endif

            @if($recipientRow)
                {{-- Star / Unstar --}}
                @if($recipientRow->starred)
                    <form action="{{ route('my.mailbox.unstar', $message->id) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-warning">
                            <x-svg-icon name="star" /> @lang('mailbox.unstarred_action')
                        </button>
                    </form>
                @else
                    <form action="{{ route('my.mailbox.star', $message->id) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-warning">
                            <x-svg-icon name="star" /> @lang('mailbox.starred_action')
                        </button>
                    </form>
                @endif

                {{-- Task toggle --}}
                <form action="{{ route('my.mailbox.task', $message->id) }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-sm {{ $recipientRow->is_task ? 'btn-success' : 'btn-outline-success' }}">
                        <x-svg-icon name="list-check" />
                        {{ $recipientRow->is_task ? __('mailbox.unmark_task') : __('mailbox.mark_task') }}
                    </button>
                </form>

                {{-- Archive / Unarchive --}}
                @if($recipientRow->archived)
                    <form action="{{ route('my.mailbox.unarchive', $message->id) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-secondary">
                            <x-svg-icon name="box" /> @lang('mailbox.unarchived_action')
                        </button>
                    </form>
                @else
                    <form action="{{ route('my.mailbox.archive', $message->id) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-secondary">
                            <x-svg-icon name="archive" /> @lang('mailbox.archived_action')
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
                            <x-svg-icon name="trash" /> @lang('mailbox.move_to_trash')
                        </button>
                    </form>
                @else
                    <form action="{{ route('my.mailbox.restore', $message->id) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-info">
                            <x-svg-icon name="arrow-counterclockwise" /> @lang('mailbox.restore')
                        </button>
                    </form>
                @endif
            @endif
        </div>
    </div>

    <div class="card-content">
        <div class="card-body">
            {{-- Meta --}}
            <table class="table table-borderless table-sm mb-3" style="max-width:600px;">
                <tr>
                    <th class="text-muted pr-3" style="width:80px;">@lang('mailbox.from')</th>
                    <td>{{ $message->sender->name ?? '—' }}</td>
                </tr>
                <tr>
                    <th class="text-muted pr-3">@lang('mailbox.to')</th>
                    <td>
                        @foreach($message->recipients as $r)
                            <span class="badge badge-light mr-1">{{ $r->recipient->name ?? '—' }}</span>
                        @endforeach
                    </td>
                </tr>
                @if($message->relatedStudent)
                    <tr>
                        <th class="text-muted pr-3">@lang('mailbox.related_student')</th>
                        <td>{{ $message->relatedStudent->name }}</td>
                    </tr>
                @endif
                <tr>
                    <th class="text-muted pr-3">@lang('mailbox.date')</th>
                    <td>{{ $message->created_at->format('Y-m-d H:i') }}</td>
                </tr>
            </table>

            <hr>

            {{-- Body --}}
            <div class="message-body" style="white-space: pre-wrap; line-height:1.8;">{{ $message->body }}</div>

            {{-- Attachment --}}
            @if($message->attachment_path)
                <hr>
                <div class="mt-2">
                    <x-svg-icon name="paperclip" class="text-muted" />
                    <a href="{{ route('my.mailbox.attachment', $message->id) }}"
                       class="btn btn-sm btn-outline-primary ml-1">
                        <x-svg-icon name="download" /> @lang('mailbox.download')
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
