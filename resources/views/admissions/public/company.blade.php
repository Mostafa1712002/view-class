<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>اختر المدرسة للتسجيل</title>
    <style>
        body { font-family: 'Cairo', Tahoma, sans-serif; margin: 0; background: #f1f5f9; color: #0f172a; }
        .wrap { max-width: 680px; margin: 0 auto; padding: 40px 16px; }
        h1 { text-align: center; color: #1e293b; }
        .school { display: block; background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px 18px; margin-bottom: 12px; text-decoration: none; color: #0f172a; font-weight: 600; box-shadow: 0 6px 18px rgba(15,23,42,.04); }
        .school:hover { border-color: #cfa046; }
        .empty { text-align: center; color: #64748b; padding: 40px; }
    </style>
</head>
<body>
<div class="wrap">
    <h1>اختر المدرسة للتسجيل</h1>
    @forelse($schools as $school)
        <a class="school" href="{{ route('admissions.public.school', $school->id) }}">{{ $school->name }}</a>
    @empty
        <div class="empty">لا توجد مدارس متاحة للتسجيل حاليًا.</div>
    @endforelse
</div>
</body>
</html>
