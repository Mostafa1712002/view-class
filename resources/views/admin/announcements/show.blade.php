@extends('layouts.app')

@section('title', 'عرض إعلان')
@section('page-title', 'عرض إعلان')
@section('body_class', 'theme-light')

@php
    $statusMeta = [
        'draft'=>['مسودة','ds-badge-warning'],'scheduled'=>['مجدول','ds-badge-info'],'active'=>['نشط','ds-badge-success'],
        'expired'=>['منتهي','ds-badge-danger'],'stopped'=>['متوقف','ds-badge-danger'],'deleted'=>['محذوف','ds-badge-danger'],
    ];
    $targetLabels = ['all'=>'كل المستخدمين','students'=>'الطلاب','teachers'=>'المعلمون','parents'=>'أولياء الأمور','admins'=>'الإداريون','specific_users'=>'مستخدمون محددون','specific_roles'=>'أدوار محددة'];
    $targetBadgeClass = [
        'all'=>'ds-badge-navy','students'=>'ds-badge-info','teachers'=>'ds-badge-gold',
        'parents'=>'ds-badge-gold','admins'=>'ds-badge-navy','specific_users'=>'ds-badge-info','specific_roles'=>'ds-badge-warning',
    ];
    $typeLabels = ['normal'=>'عادي','important'=>'مهم','popup'=>'منبثق'];
    $eff = $announcement->effectiveStatus();
    $sm = $statusMeta[$eff] ?? ['—','ds-badge-warning'];
    $tBadge = $targetBadgeClass[$announcement->target_type] ?? 'ds-badge-navy';
@endphp

@section('content')
<section class="vc-ann-show">

    {{-- Page header + breadcrumb --}}
    <div style="display:flex;justify-content:space-between;align-items:flex-end;flex-wrap:wrap;gap:.75rem;margin-bottom:1rem">
        <div>
            <h2 style="margin:0;font-size:1.45rem;font-weight:800;color:var(--gray-900)">عرض إعلان</h2>
            <nav><ol class="breadcrumb" style="margin:0;padding:0;background:transparent">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">الرئيسية</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.announcements.index') }}">الإعلانات</a></li>
                <li class="breadcrumb-item active" aria-current="page">{{ \Illuminate\Support\Str::limit($announcement->title, 40) }}</li>
            </ol></nav>
        </div>
        <a href="{{ route('admin.announcements.index') }}" class="btn btn-outline-secondary btn-sm">
            <x-svg-icon name="arrow-right" :size="15" /> عودة
        </a>
    </div>

    <div class="ds-card card">
        <div class="ds-card-header card-header" style="display:flex;align-items:center;gap:.5rem;flex-wrap:wrap">
            <x-svg-icon name="megaphone-fill" :size="16" class="text-gold" />
            <h5 class="ds-card-title" style="margin:0;flex:1">{{ $announcement->title }}</h5>
            <span class="badge {{ $sm[1] }}">{{ $sm[0] }}</span>
        </div>

        <div class="card-body">
            {{-- Badges row --}}
            <div style="display:flex;flex-wrap:wrap;gap:.4rem;margin-bottom:1rem">
                <span class="badge ds-badge-warning">{{ $typeLabels[$announcement->type] ?? $announcement->type }}</span>
                <span class="badge {{ $tBadge }}">{{ $targetLabels[$announcement->target_type] ?? $announcement->target_type }}</span>
            </div>

            <div class="ann-body" style="line-height:1.8">
                {!! \App\Support\HtmlSanitizer::clean($announcement->body) !!}
            </div>

            <hr>

            {{-- Meta table --}}
            <dl style="display:grid;grid-template-columns:max-content 1fr;gap:.45rem 1rem;font-size:.9rem">
                <dt class="text-muted">المدرسة</dt>
                <dd style="margin:0">{{ optional($announcement->school)->name ?? '—' }}</dd>

                <dt class="text-muted">تاريخ البدء</dt>
                <dd style="margin:0">{{ $announcement->starts_at?->format('Y-m-d H:i') ?? '—' }}</dd>

                <dt class="text-muted">تاريخ الانتهاء</dt>
                <dd style="margin:0">{{ $announcement->ends_at?->format('Y-m-d H:i') ?? '—' }}</dd>

                <dt class="text-muted">منشئ الإعلان</dt>
                <dd style="margin:0">{{ optional($announcement->creator)->name ?? '—' }}</dd>

                <dt class="text-muted">تأكيد القراءة</dt>
                <dd style="margin:0">{{ $announcement->require_read_ack ? 'مطلوب' : 'غير مطلوب' }}</dd>

                <dt class="text-muted">إشعار داخلي</dt>
                <dd style="margin:0">{{ $announcement->notify_internal ? 'نعم' : 'لا' }}</dd>
            </dl>
        </div>

        <div class="card-footer" style="display:flex;gap:.5rem;flex-wrap:wrap">
            @if(auth()->user()->canDo('announcements.read_log'))
                <a href="{{ route('admin.announcements.read-log', $announcement->id) }}" class="btn btn-outline-secondary btn-sm">
                    <x-svg-icon name="list-check" :size="16" /> سجل القراءة
                </a>
            @endif
            @if(auth()->user()->canDo('announcements.edit'))
                <a href="{{ route('admin.announcements.edit', $announcement->id) }}" class="btn btn-primary btn-sm">
                    <x-svg-icon name="pencil-square" :size="15" /> تعديل
                </a>
            @endif
        </div>
    </div>

</section>
@endsection
