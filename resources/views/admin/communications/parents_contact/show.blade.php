@extends('layouts.app')

@section('title', 'سجل التواصل — ' . ($parent->name ?: $parent->username))
@section('page-title', 'سجل التواصل مع ولي الأمر')
@section('body_class', 'theme-light')

@php
    $isRtl = app()->getLocale() === 'ar';
    $mail = $logs['mail'];
    $whatsapp = $logs['whatsapp'];
    $notifications = $logs['notifications'];
    $waStatus = [
        'sent' => ['ds-badge-success', 'مُرسلة'], 'pending' => ['ds-badge-warning', 'قيد الإرسال'],
        'failed' => ['ds-badge-danger', 'فشلت'], 'skipped' => ['ds-badge-info', 'متخطّاة'],
    ];
@endphp

@push('styles')
<style>
    .pc-info-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(190px,1fr)); gap:.75rem; }
    .pc-info-item { background:#f8fafc; border:1px solid #eef2f7; border-radius:10px; padding:.6rem .8rem; }
    .pc-info-item .k { font-size:.74rem; color:#94a3b8; margin-bottom:.15rem; }
    .pc-info-item .v { font-size:.92rem; color:#0f172a; font-weight:600; }
    .pc-section-title { font-size:1rem; font-weight:800; color:#0f172a; margin:1.25rem 0 .6rem; display:flex; align-items:center; gap:.45rem; }
    .pc-log-empty { color:#94a3b8; font-size:.85rem; padding:.6rem .2rem; }
</style>
@endpush

@section('content')
<section class="pc-show" @if($isRtl) dir="rtl" @endif>

    <div style="display:flex;justify-content:space-between;align-items:flex-end;flex-wrap:wrap;gap:.6rem;margin-bottom:1rem">
        <div>
            <h2 style="margin:0;font-weight:800;color:#0f172a">{{ $parent->name ?: $parent->username }}</h2>
            <nav><ol class="breadcrumb" style="margin:0;padding:0;background:transparent;font-size:.85rem">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">الرئيسية</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.parents-contact.index') }}">أولياء الأمور</a></li>
                <li class="breadcrumb-item active" aria-current="page">سجل التواصل</li>
            </ol></nav>
        </div>
        <a href="{{ route('admin.parents-contact.index') }}" class="btn btn-outline-secondary btn-sm">
            <x-svg-icon name="arrow-right" :size="15" /> رجوع
        </a>
    </div>

    {{-- Parent identity --}}
    <div class="ds-card card" style="margin-bottom:1rem">
        <div class="ds-card-header card-header"><h5 class="ds-card-title" style="margin:0">بيانات ولي الأمر</h5></div>
        <div class="card-body">
            <div class="pc-info-grid">
                <div class="pc-info-item"><div class="k">الاسم</div><div class="v">{{ $parent->name ?: '—' }}</div></div>
                <div class="pc-info-item"><div class="k">الجنسية</div><div class="v">{{ $parent->nationality ?: '—' }}</div></div>
                <div class="pc-info-item"><div class="k">رقم الجوال</div><div class="v" dir="ltr" style="text-align:start">{{ $parent->phone ?: '—' }}</div></div>
                <div class="pc-info-item"><div class="k">واتساب</div><div class="v" dir="ltr" style="text-align:start">{{ $parent->whatsapp ?: '—' }}</div></div>
                <div class="pc-info-item"><div class="k">البريد الإلكتروني</div><div class="v">{{ ($parent->email && !\Illuminate\Support\Str::endsWith($parent->email, '@viewclass.local')) ? $parent->email : '—' }}</div></div>
                <div class="pc-info-item"><div class="k">رقم الهوية</div><div class="v">{{ $parent->national_id ?: '—' }}</div></div>
            </div>
        </div>
    </div>

    {{-- Children --}}
    <div class="ds-card card" style="margin-bottom:1rem">
        <div class="ds-card-header card-header"><h5 class="ds-card-title" style="margin:0">الأبناء المرتبطون ({{ $children->count() }})</h5></div>
        @if($children->count() === 0)
            <div class="pc-log-empty" style="padding:1rem 1.1rem">لا يوجد أبناء مرتبطون بهذا الحساب.</div>
        @else
        <div class="table-responsive">
            <table class="table ds-table-tight mb-0">
                <thead><tr><th>اسم الطالب</th><th>الصف / الفصل</th><th>رقم الهوية</th></tr></thead>
                <tbody>
                    @foreach($children as $child)
                        <tr>
                            <td style="font-weight:600">{{ $child->name ?: $child->username }}</td>
                            <td>{{ optional($child->classRoom)->name ?: '—' }}</td>
                            <td>{{ $child->national_id ?: '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

    {{-- Communication logs --}}
    <div class="ds-card card">
        <div class="ds-card-header card-header"><h5 class="ds-card-title" style="margin:0">سجل التواصل</h5></div>
        <div class="card-body">

            <div class="pc-section-title"><x-svg-icon name="envelope" :size="16" /> البريد الداخلي ({{ $mail->count() }})</div>
            @if($mail->isEmpty())
                <div class="pc-log-empty">لا توجد رسائل بريد داخلي.</div>
            @else
            <div class="table-responsive">
                <table class="table ds-table-tight mb-0">
                    <thead><tr><th>الموضوع</th><th>المُرسِل</th><th>الحالة</th><th>التاريخ</th></tr></thead>
                    <tbody>
                        @foreach($mail as $m)
                            <tr>
                                <td>{{ $m->subject ?: '—' }}</td>
                                <td>{{ $m->sender_name ?: '—' }}</td>
                                <td>@if($m->is_read)<span class="ds-badge-success">مقروءة</span>@else<span class="ds-badge-warning">غير مقروءة</span>@endif</td>
                                <td dir="ltr" style="text-align:start">{{ $m->created_at ? \Illuminate\Support\Carbon::parse($m->created_at)->format('Y-m-d H:i') : '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif

            <div class="pc-section-title"><x-svg-icon name="whatsapp" :size="16" /> رسائل واتساب ({{ $whatsapp->count() }})</div>
            @if($whatsapp->isEmpty())
                <div class="pc-log-empty">لا توجد رسائل واتساب.</div>
            @else
            <div class="table-responsive">
                <table class="table ds-table-tight mb-0">
                    <thead><tr><th>النص</th><th>الرقم</th><th>النوع</th><th>الحالة</th><th>التاريخ</th></tr></thead>
                    <tbody>
                        @foreach($whatsapp as $w)
                            @php $st = $waStatus[$w->status] ?? ['ds-badge-info', $w->status]; @endphp
                            <tr>
                                <td style="max-width:320px">{{ \Illuminate\Support\Str::limit($w->message_text, 90) ?: '—' }}</td>
                                <td dir="ltr" style="text-align:start">{{ $w->to_number ?: '—' }}</td>
                                <td>{{ $w->type ?: '—' }}</td>
                                <td><span class="{{ $st[0] }}">{{ $st[1] }}</span></td>
                                <td dir="ltr" style="text-align:start">{{ ($w->sent_at ?: $w->created_at) ? \Illuminate\Support\Carbon::parse($w->sent_at ?: $w->created_at)->format('Y-m-d H:i') : '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif

            <div class="pc-section-title"><x-svg-icon name="bell" :size="16" /> الإشعارات ({{ $notifications->count() }})</div>
            @if($notifications->isEmpty())
                <div class="pc-log-empty">لا توجد إشعارات.</div>
            @else
            <div class="table-responsive">
                <table class="table ds-table-tight mb-0">
                    <thead><tr><th>العنوان</th><th>المحتوى</th><th>الحالة</th><th>التاريخ</th></tr></thead>
                    <tbody>
                        @foreach($notifications as $n)
                            <tr>
                                <td style="font-weight:600">{{ $n->title ?: '—' }}</td>
                                <td style="max-width:320px">{{ \Illuminate\Support\Str::limit($n->body, 90) ?: '—' }}</td>
                                <td>@if($n->read_at)<span class="ds-badge-success">مقروء</span>@else<span class="ds-badge-warning">غير مقروء</span>@endif</td>
                                <td dir="ltr" style="text-align:start">{{ $n->created_at ? \Illuminate\Support\Carbon::parse($n->created_at)->format('Y-m-d H:i') : '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif

        </div>
    </div>

</section>
@endsection
