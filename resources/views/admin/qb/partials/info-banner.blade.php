{{--
  Closable info banner (#257 — "معلومات توضيحية قابلة للإغلاق").
  Usage: @include('admin.qb.partials.info-banner', ['key' => 'qb-questions', 'slot' => '...html...'])
  - $key       : unique storage key; the dismissed state is remembered in localStorage.
  - $slot      : the inner HTML/markup of the banner.
  - $variant   : 'info' (default) | 'gold'
--}}
@php
    $key = $key ?? 'qb-info';
    $variant = $variant ?? 'info';
@endphp
<div class="qb-info-banner qb-info-{{ $variant }}" data-info-key="{{ $key }}" role="note">
    <div class="qb-info-icon"><x-svg-icon name="info-circle-fill" :size="18" /></div>
    <div class="qb-info-content">{!! $slot !!}</div>
    <button type="button" class="qb-info-close" aria-label="إغلاق" title="إغلاق"
            onclick="(function(b){var w=b.closest('.qb-info-banner');w.style.display='none';try{localStorage.setItem('qbInfo:'+w.dataset.infoKey,'1');}catch(e){}})(this)">
        <x-svg-icon name="x-lg" :size="14" />
    </button>
</div>

@once
@push('styles')
<style>
    .qb-info-banner{display:flex;align-items:flex-start;gap:12px;border-radius:12px;padding:14px 16px;margin-bottom:16px;font-size:13px;line-height:1.7;position:relative}
    .qb-info-info{background:#eff6ff;border:1px solid #bfdbfe;color:#1e3a8a}
    .qb-info-info .qb-info-icon{color:#2563eb}
    .qb-info-gold{background:var(--gold-50,#fffbeb);border:1px solid #e3c97a;color:#7a5d12}
    .qb-info-gold .qb-info-icon{color:#b8860b}
    .qb-info-banner b{font-weight:700}
    .qb-info-icon{flex-shrink:0;margin-top:1px}
    .qb-info-content{flex:1;min-width:0}
    .qb-info-close{flex-shrink:0;background:transparent;border:none;color:inherit;opacity:.55;cursor:pointer;padding:2px 4px;border-radius:6px;line-height:1}
    .qb-info-close:hover{opacity:1;background:rgba(0,0,0,.06)}
</style>
@endpush
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.qb-info-banner[data-info-key]').forEach(function (w) {
        try { if (localStorage.getItem('qbInfo:' + w.dataset.infoKey) === '1') w.style.display = 'none'; } catch (e) {}
    });
});
</script>
@endpush
@endonce
