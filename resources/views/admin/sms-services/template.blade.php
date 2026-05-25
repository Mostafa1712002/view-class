<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 13px; line-height: 1.8; color: #222; }
        h1 { font-size: 18px; text-align: center; margin-bottom: 4px; }
        h2 { font-size: 14px; text-align: center; color: #cfa046; margin-top: 0; }
        .box { border: 1px solid #ccc; padding: 14px; margin-top: 18px; }
        .row { margin-bottom: 14px; }
        .line { border-bottom: 1px dotted #888; display: inline-block; width: 60%; }
        .sign { margin-top: 50px; }
        .muted { color: #777; font-size: 11px; }
    </style>
</head>
<body>
    <h1>نموذج تفويض اعتماد اسم مرسل SMS</h1>
    <h2>شركة الاتصالات: {{ $providerLabel }}</h2>

    <div class="box">
        <div class="row">اسم الجهة / المدرسة: <span class="line"></span></div>
        <div class="row">السجل التجاري / الرقم الموحد: <span class="line"></span></div>
        <div class="row">اسم المرسل المطلوب (English): <span class="line"></span></div>
        <div class="row">اسم المرسل المطلوب (عربي): <span class="line"></span></div>
        <div class="row">الغرض من الرسائل: <span class="line"></span></div>
        <div class="row">اسم المفوّض بالتوقيع: <span class="line"></span></div>
        <div class="row">الصفة: <span class="line"></span></div>
    </div>

    <p class="muted">
        نفوّض شركة {{ $providerLabel }} باعتماد اسم المرسل أعلاه لإرسال الرسائل النصية باسم الجهة،
        ونقرّ بأن جميع البيانات المذكورة صحيحة.
    </p>

    <div class="sign">
        <div class="row">التوقيع: <span class="line"></span></div>
        <div class="row">الختم الرسمي:</div>
        <div class="row">التاريخ: <span class="line"></span></div>
    </div>

    <p class="muted" style="margin-top:30px; text-align:center;">
        يُطبع هذا النموذج، ويُعبأ، ويُختم، ثم يُعاد رفعه ضمن مرفقات طلب اسم المرسل في منصة الأول.
    </p>
</body>
</html>
