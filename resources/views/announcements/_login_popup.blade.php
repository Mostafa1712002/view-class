{{-- Popup announcements shown once on login. Self-contained: resolves its own
     data so the layout include stays a single guarded line. --}}
@auth
@php
    $popupAnnouncements = collect();
    try {
        $repo = app(\App\Modules\Announcements\Repositories\Contracts\AnnouncementRepository::class);
        $allPopups = $repo->liveForUser(auth()->user(), true);
        // Only show popups the user has not yet seen (no viewed_at row).
        $seenIds = \App\Models\AnnouncementRead::query()
            ->where('user_id', auth()->id())
            ->whereNotNull('viewed_at')
            ->pluck('announcement_id')->all();
        $popupAnnouncements = $allPopups->reject(fn ($a) => in_array($a->id, $seenIds))->values();
    } catch (\Throwable $e) {
        $popupAnnouncements = collect();
    }
@endphp

@if($popupAnnouncements->isNotEmpty())
    @php $popup = $popupAnnouncements->first(); @endphp
    <div id="annPopupOverlay"
         data-id="{{ $popup->id }}"
         data-ack="{{ $popup->require_read_ack ? '1' : '0' }}"
         style="position:fixed;inset:0;background:rgba(15,23,42,.55);z-index:10800;display:flex;align-items:center;justify-content:center;padding:1rem">
        <div role="dialog" aria-modal="true" aria-labelledby="annPopupTitle"
             style="background:#fff;max-width:540px;width:100%;border-radius:14px;box-shadow:0 20px 48px rgba(15,23,42,.3);overflow:hidden">
            <div style="background:var(--navy,#14233a);color:#fff;padding:1rem 1.25rem;display:flex;align-items:center;gap:.5rem">
                <x-svg-icon name="megaphone-fill" :size="20" />
                <strong id="annPopupTitle" style="flex:1">{{ $popup->title }}</strong>
                @if($popup->require_read_ack === false)
                    <button type="button" id="annPopupClose" aria-label="إغلاق"
                            style="background:none;border:none;color:#fff;font-size:1.4rem;cursor:pointer;line-height:1">&times;</button>
                @endif
            </div>
            <div style="padding:1.25rem;max-height:50vh;overflow:auto">{!! \App\Support\HtmlSanitizer::clean($popup->body) !!}</div>
            <div style="padding:.85rem 1.25rem;border-top:1px solid #f1f5f9;display:flex;justify-content:flex-end;gap:.5rem">
                @if($popup->require_read_ack)
                    <button type="button" id="annPopupConfirm" class="btn btn-primary"><x-svg-icon name="check-lg" :size="16" /> فهمت، تأكيد القراءة</button>
                @else
                    <a href="{{ route('announcements.show', $popup->id) }}" class="btn btn-outline-primary">عرض التفاصيل</a>
                    <button type="button" id="annPopupDismiss" class="btn btn-primary">إغلاق</button>
                @endif
            </div>
        </div>
    </div>

    <script>
    (function () {
        var overlay = document.getElementById('annPopupOverlay');
        if (!overlay) return;
        var id = overlay.dataset.id;
        var token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        function post(url, cb) {
            fetch(url, { method: 'POST', headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json' } })
                .then(function () { if (cb) cb(); }).catch(function () { if (cb) cb(); });
        }
        function close() { overlay.parentNode && overlay.parentNode.removeChild(overlay); }

        var dismiss = document.getElementById('annPopupDismiss');
        var closeX = document.getElementById('annPopupClose');
        var confirm = document.getElementById('annPopupConfirm');
        if (dismiss) dismiss.addEventListener('click', function () { post('{{ route('announcements.dismiss', $popup->id) }}', close); });
        if (closeX) closeX.addEventListener('click', function () { post('{{ route('announcements.dismiss', $popup->id) }}', close); });
        if (confirm) confirm.addEventListener('click', function () { post('{{ route('announcements.confirm', $popup->id) }}', close); });
    })();
    </script>
@endif
@endauth
