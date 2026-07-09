@extends('layouts.admin')

@section('title', 'الإشعارات')

@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-7 col-12 mb-2">
        <h2 class="content-header-title mb-0">@lang('shell.notifications_heading')</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
                <li class="breadcrumb-item active">@lang('shell.notifications_heading')</li>
            </ol>
        </div>
    </div>
    <div class="content-header-right col-md-5 col-12 text-end mb-2 vc-notif-pageactions">
        <button type="button" class="btn btn-sm" id="markAllRead" {{ $unreadCount == 0 ? 'disabled' : '' }}>
            <i class="la la-check-double"></i> @lang('shell.notifications_mark_all')
        </button>
        <button type="button" class="btn btn-sm btn-outline-danger" id="clearRead">
            <i class="la la-trash"></i> @lang('shell.notifications_clear_read')
        </button>
    </div>
</div>

<div class="content-body">
    <div class="card vc-notif-page">
        @if($notifications->count() > 0)
            <div class="vc-notif-list" style="max-height:none;">
                @foreach($notifications as $notification)
                    @php($ic = $notification->getIcon())
                    @php($icClass = \Illuminate\Support\Str::startsWith($ic,'bi-') ? 'bi '.$ic : (\Illuminate\Support\Str::startsWith($ic,'la-') ? 'la '.$ic : $ic))
                    <div class="vc-notif-item {{ $notification->isRead() ? '' : 'unread' }}" data-notification-id="{{ $notification->id }}">
                        <span class="vc-notif-ico c-{{ $notification->color ?? 'info' }}"><i class="{{ $icClass }}"></i></span>
                        <span class="vc-notif-body">
                            <span class="vc-notif-title">{{ $notification->title }}</span>
                            <span class="vc-notif-text">{{ $notification->body }}</span>
                            <span class="vc-notif-time"><i class="la la-clock"></i> {{ $notification->created_at->diffForHumans() }}</span>
                        </span>
                        <span class="vc-notif-actions">
                            @if($notification->action_url)
                                <a href="{{ $notification->action_url }}" class="btn btn-sm btn-outline-secondary"><i class="la la-eye"></i> {{ $notification->action_text ?? __('shell.notifications_open') }}</a>
                            @endif
                            @if(!$notification->isRead())
                                <button type="button" class="btn btn-sm btn-outline-secondary mark-read" data-id="{{ $notification->id }}" title="@lang('shell.notifications_mark_one')"><i class="la la-check"></i></button>
                            @endif
                            <button type="button" class="btn btn-sm btn-outline-danger delete-notification" data-id="{{ $notification->id }}" title="@lang('libraries.actions.delete')"><i class="la la-trash"></i></button>
                        </span>
                    </div>
                @endforeach
            </div>
            <div class="p-3">{{ $notifications->links() }}</div>
        @else
            <div class="vc-notif-empty" style="padding:3.5rem 1rem;">
                <span class="vc-notif-empty-ico"><i class="la la-bell"></i></span>
                <p class="vc-notif-empty-title">@lang('shell.notifications_empty')</p>
                <p class="vc-notif-empty-sub">@lang('shell.notifications_empty_sub')</p>
            </div>
        @endif
    </div>
</div>

@push('styles')
<style>
.vc-notif-page .vc-notif-item { align-items: center; }
.vc-notif-page .vc-notif-actions { display: inline-flex; gap: .35rem; flex: 0 0 auto; }
.vc-notif-page .vc-notif-actions .btn { border-radius: 8px; }
.vc-notif-pageactions .btn#markAllRead { background: linear-gradient(135deg, var(--gold-200), var(--gold-500)); color:#fff; border:none; }
.vc-notif-pageactions .btn#markAllRead[disabled] { opacity:.5; }
@media (max-width: 480px) {
    /* Long notification text + action buttons don't fit one row on narrow phones; stack them. */
    .vc-notif-page .vc-notif-item { flex-wrap: wrap; }
    .vc-notif-page .vc-notif-body { flex-basis: 100%; }
    .vc-notif-page .vc-notif-actions { flex-basis: 100%; justify-content: flex-end; margin-top: .4rem; }
}
</style>
@endpush

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
