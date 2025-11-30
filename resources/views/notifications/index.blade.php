@extends('layouts.admin')

@section('title', 'الإشعارات')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">الإشعارات</h1>
            @if($unreadCount > 0)
                <small class="text-muted">{{ $unreadCount }} إشعار غير مقروء</small>
            @endif
        </div>
        <div class="btn-group">
            <button type="button" class="btn btn-outline-primary" id="markAllRead" {{ $unreadCount == 0 ? 'disabled' : '' }}>
                <i class="bi bi-check-all me-1"></i>
                تحديد الكل كمقروء
            </button>
            <button type="button" class="btn btn-outline-danger" id="clearRead">
                <i class="bi bi-trash me-1"></i>
                حذف المقروءة
            </button>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            @if($notifications->count() > 0)
                <div class="list-group list-group-flush">
                    @foreach($notifications as $notification)
                        <div class="list-group-item list-group-item-action {{ $notification->isRead() ? '' : 'bg-light' }}"
                             data-notification-id="{{ $notification->id }}">
                            <div class="d-flex w-100 align-items-start">
                                <div class="me-3">
                                    <span class="rounded-circle bg-{{ $notification->color }} text-white d-inline-flex align-items-center justify-content-center"
                                          style="width: 45px; height: 45px;">
                                        <i class="{{ $notification->getIcon() }} fs-5"></i>
                                    </span>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="d-flex w-100 justify-content-between align-items-start">
                                        <div>
                                            <h6 class="mb-1 {{ $notification->isRead() ? '' : 'fw-bold' }}">
                                                {{ $notification->title }}
                                            </h6>
                                            <p class="mb-1 text-muted">{{ $notification->body }}</p>
                                            <small class="text-muted">
                                                <i class="bi bi-clock me-1"></i>
                                                {{ $notification->created_at->diffForHumans() }}
                                            </small>
                                        </div>
                                        <div class="btn-group">
                                            @if($notification->action_url)
                                                <a href="{{ $notification->action_url }}" class="btn btn-sm btn-outline-primary">
                                                    {{ $notification->action_text ?? 'عرض' }}
                                                </a>
                                            @endif
                                            @if(!$notification->isRead())
                                                <button type="button" class="btn btn-sm btn-outline-secondary mark-read"
                                                        data-id="{{ $notification->id }}" title="تحديد كمقروء">
                                                    <i class="bi bi-check"></i>
                                                </button>
                                            @endif
                                            <button type="button" class="btn btn-sm btn-outline-danger delete-notification"
                                                    data-id="{{ $notification->id }}" title="حذف">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="p-3">
                    {{ $notifications->links() }}
                </div>
            @else
                <div class="text-center py-5">
                    <i class="bi bi-bell-slash display-1 text-muted"></i>
                    <p class="mt-3 text-muted">لا توجد إشعارات</p>
                </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Mark single notification as read
    document.querySelectorAll('.mark-read').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            fetch(`/notifications/${id}/read`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                }
            }).then(response => response.json())
            .then(data => {
                if (data.success) {
                    const item = document.querySelector(`[data-notification-id="${id}"]`);
                    item.classList.remove('bg-light');
                    item.querySelector('h6').classList.remove('fw-bold');
                    this.remove();
                }
            });
        });
    });

    // Mark all as read
    document.getElementById('markAllRead').addEventListener('click', function() {
        fetch('/notifications/mark-all-read', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            }
        }).then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            }
        });
    });

    // Delete notification
    document.querySelectorAll('.delete-notification').forEach(btn => {
        btn.addEventListener('click', function() {
            if (!confirm('هل أنت متأكد من حذف هذا الإشعار؟')) return;

            const id = this.dataset.id;
            fetch(`/notifications/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                }
            }).then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.querySelector(`[data-notification-id="${id}"]`).remove();
                }
            });
        });
    });

    // Clear read notifications
    document.getElementById('clearRead').addEventListener('click', function() {
        if (!confirm('هل أنت متأكد من حذف جميع الإشعارات المقروءة؟')) return;

        fetch('/notifications/clear-read', {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            }
        }).then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            }
        });
    });
});
</script>
@endpush
@endsection
