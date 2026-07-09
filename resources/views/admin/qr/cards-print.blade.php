<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="utf-8">
<title>طباعة بطاقات QR</title>
<style>
    body { font-family: 'Cairo', Tahoma, sans-serif; background:#fff; }
    .cards { display:flex; flex-wrap:wrap; gap:12px; }
    .qr-card { width:300px; max-width:100%; box-sizing:border-box; border:2px solid #c9a227; border-radius:10px; padding:14px; text-align:center; page-break-inside:avoid; }
    .qr-card h4 { margin:4px 0; color:#1a2942; }
    .qr-card .meta { font-size:13px; color:#555; }
    .qr-box { margin:10px auto; width:160px; height:160px; }
    .brand { font-weight:bold; color:#c9a227; }
    @media print { .no-print { display:none; } }
</style>
</head>
<body>
<div class="no-print" style="margin-bottom:12px">
    <button onclick="window.print()">طباعة</button>
</div>
<div class="cards">
    @forelse($cards as $card)
    <div class="qr-card">
        <div class="brand">{{ config('app.name', 'المنصة الذهبية') }}</div>
        <h4>{{ optional($card->student)->name }}</h4>
        <div class="meta">الفصل: {{ optional(optional($card->student)->classRoom)->name ?? '—' }}</div>
        <div class="meta">رقم الهوية: {{ optional($card->student)->national_id ?? '—' }}</div>
        <div class="qr-box" data-token="{{ $card->token }}"></div>
        <div class="meta">كود البطاقة: {{ $card->card_code }}</div>
        <div class="meta">تاريخ الإصدار: {{ $card->created_at?->format('Y-m-d') }}</div>
    </div>
    @empty
        <p>لا توجد بطاقات للطباعة.</p>
    @endforelse
</div>
<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
document.querySelectorAll('.qr-box').forEach(function (el) {
    new QRCode(el, { text: el.dataset.token, width: 160, height: 160 });
});
</script>
</body>
</html>
