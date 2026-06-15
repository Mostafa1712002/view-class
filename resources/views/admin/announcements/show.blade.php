@extends('layouts.app')

@section('title', 'عرض إعلان')
@section('page-title', 'عرض إعلان')

@php
    $statusMeta = [
        'draft'=>['مسودة','secondary'],'scheduled'=>['مجدول','info'],'active'=>['نشط','success'],
        'expired'=>['منتهي','warning'],'stopped'=>['متوقف','danger'],'deleted'=>['محذوف','dark'],
    ];
    $targetLabels = ['all'=>'كل المستخدمين','students'=>'الطلاب','teachers'=>'المعلمون','parents'=>'أولياء الأمور','admins'=>'الإداريون','specific_users'=>'مستخدمون محددون','specific_roles'=>'أدوار محددة'];
    $typeLabels = ['normal'=>'عادي','important'=>'مهم','popup'=>'منبثق'];
    $eff = $announcement->effectiveStatus();
    $sm = $statusMeta[$eff] ?? ['—','secondary'];
@endphp

@section('content')
<section class="vc-ann-show">
    <div class="ls-header" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem">
        <h2 style="margin:0">{{ $announcement->title }}</h2>
        <a href="{{ route('admin.announcements.index') }}" class="btn btn-light"><x-svg-icon name="arrow-right" :size="16" /> عودة</a>
    </div>

    <div class="card"><div class="card-body">
        <p>
            <span class="badge badge-{{ $sm[1] }}">{{ $sm[0] }}</span>
            <span class="badge badge-light">{{ $typeLabels[$announcement->type] ?? $announcement->type }}</span>
            <span class="badge badge-light">{{ $targetLabels[$announcement->target_type] ?? $announcement->target_type }}</span>
        </p>
        <hr>
        <div class="ann-body">{!! \App\Support\HtmlSanitizer::clean($announcement->body) !!}</div>
        <hr>
        <dl style="display:grid;grid-template-columns:max-content 1fr;gap:.4rem 1rem">
            <dt>المدرسة</dt><dd>{{ optional($announcement->school)->name ?? '—' }}</dd>
            <dt>تاريخ البدء</dt><dd>{{ $announcement->starts_at?->format('Y-m-d H:i') ?? '—' }}</dd>
            <dt>تاريخ الانتهاء</dt><dd>{{ $announcement->ends_at?->format('Y-m-d H:i') ?? '—' }}</dd>
            <dt>منشئ الإعلان</dt><dd>{{ optional($announcement->creator)->name ?? '—' }}</dd>
            <dt>تأكيد القراءة</dt><dd>{{ $announcement->require_read_ack ? 'مطلوب' : 'غير مطلوب' }}</dd>
            <dt>إشعار داخلي</dt><dd>{{ $announcement->notify_internal ? 'نعم' : 'لا' }}</dd>
        </dl>
        @if(auth()->user()->canDo('announcements.read_log'))
            <a href="{{ route('admin.announcements.read-log', $announcement->id) }}" class="btn btn-outline-primary"><x-svg-icon name="list-check" :size="16" /> سجل القراءة</a>
        @endif
    </div></div>
</section>
@endsection
