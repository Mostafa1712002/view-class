@extends('layouts.admin')

@section('title', 'الرسائل')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">الرسائل</h1>
            @if($totalUnread > 0)
                <small class="text-muted">{{ $totalUnread }} رسالة غير مقروءة</small>
            @endif
        </div>
        <a href="{{ route('messages.create') }}" class="btn btn-primary">
            <i class="bi bi-pencil-square me-1"></i>
            رسالة جديدة
        </a>
    </div>

    <div class="card">
        <div class="card-body p-0">
            @if($conversations->count() > 0)
                <div class="list-group list-group-flush">
                    @foreach($conversations as $conversation)
                        @php
                            $otherParticipant = $conversation->getOtherParticipant(auth()->user());
                            $displayName = $conversation->getDisplayName(auth()->user());
                            $hasUnread = $conversation->unread_count > 0;
                        @endphp
                        <a href="{{ route('messages.show', $conversation) }}"
                           class="list-group-item list-group-item-action {{ $hasUnread ? 'bg-light' : '' }}">
                            <div class="d-flex w-100 align-items-center">
                                <div class="me-3">
                                    @if($conversation->type === 'private' && $otherParticipant)
                                        <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center"
                                             style="width: 50px; height: 50px;">
                                            <span class="fs-5">{{ mb_substr($otherParticipant->name, 0, 1) }}</span>
                                        </div>
                                    @else
                                        <div class="rounded-circle bg-info text-white d-inline-flex align-items-center justify-content-center"
                                             style="width: 50px; height: 50px;">
                                            <i class="bi bi-people fs-5"></i>
                                        </div>
                                    @endif
                                </div>
                                <div class="flex-grow-1">
                                    <div class="d-flex w-100 justify-content-between align-items-start">
                                        <div>
                                            <h6 class="mb-1 {{ $hasUnread ? 'fw-bold' : '' }}">
                                                {{ $displayName }}
                                                @if($conversation->type === 'group')
                                                    <span class="badge bg-info ms-1">مجموعة</span>
                                                @endif
                                            </h6>
                                            @if($conversation->latestMessage)
                                                <p class="mb-0 text-muted small text-truncate" style="max-width: 400px;">
                                                    @if($conversation->latestMessage->sender_id === auth()->id())
                                                        <span class="text-primary">أنت:</span>
                                                    @else
                                                        <span class="text-primary">{{ $conversation->latestMessage->sender->name }}:</span>
                                                    @endif
                                                    {{ Str::limit($conversation->latestMessage->body, 50) }}
                                                </p>
                                            @endif
                                        </div>
                                        <div class="text-end">
                                            <small class="text-muted d-block">
                                                {{ $conversation->last_message_at ? $conversation->last_message_at->diffForHumans() : $conversation->created_at->diffForHumans() }}
                                            </small>
                                            @if($hasUnread)
                                                <span class="badge bg-primary rounded-pill">{{ $conversation->unread_count }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>

                <div class="p-3">
                    {{ $conversations->links() }}
                </div>
            @else
                <div class="text-center py-5">
                    <i class="bi bi-chat-dots display-1 text-muted"></i>
                    <p class="mt-3 text-muted">لا توجد محادثات</p>
                    <a href="{{ route('messages.create') }}" class="btn btn-primary">
                        <i class="bi bi-pencil-square me-1"></i>
                        ابدأ محادثة جديدة
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
