<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>التسجيل مغلق</title>
    <style>
        body { font-family: 'Cairo', Tahoma, sans-serif; margin: 0; background: #f1f5f9; color: #0f172a; display: flex; min-height: 100vh; align-items: center; justify-content: center; }
        .card { background: #fff; border-radius: 16px; padding: 40px; text-align: center; max-width: 480px; box-shadow: 0 12px 32px rgba(15,23,42,.08); }
        h1 { color: #1e293b; }
    </style>
</head>
<body>
<div class="card">
    <h1>التسجيل مغلق حاليًا</h1>
    <p>{{ $school->name }}</p>
    <p style="color:#64748b">التسجيل في هذه المدرسة غير متاح في الوقت الحالي.</p>
</div>
</body>
</html>
