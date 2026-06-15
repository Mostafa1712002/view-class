<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>تم استلام الطلب</title>
    <style>
        body { font-family: 'Cairo', Tahoma, sans-serif; margin: 0; background: #f1f5f9; color: #0f172a; display: flex; min-height: 100vh; align-items: center; justify-content: center; }
        .card { background: #fff; border-radius: 16px; padding: 40px; text-align: center; max-width: 480px; box-shadow: 0 12px 32px rgba(15,23,42,.08); }
        .check { width: 64px; height: 64px; border-radius: 50%; background: #dcfce7; color: #16a34a; font-size: 34px; line-height: 64px; margin: 0 auto 18px; }
        h1 { color: #1e293b; margin: 0 0 8px; }
        .code { font-weight: 800; color: #cfa046; font-size: 18px; }
    </style>
</head>
<body>
<div class="card">
    <div class="check">✓</div>
    <h1>تم استلام طلبك بنجاح</h1>
    <p>{{ $school->name }}</p>
    <p>رقم طلبك: <span class="code">{{ $code }}</span></p>
    <p style="color:#64748b">احتفظ برقم الطلب لمتابعة حالته مع إدارة المدرسة.</p>
</div>
</body>
</html>
