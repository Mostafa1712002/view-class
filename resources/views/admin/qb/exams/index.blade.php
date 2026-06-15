@extends('layouts.app')

@section('title', 'قائمة الاختبارات')
@section('body_class', 'theme-light')

@php
    $user = auth()->user();
    $canCreate = $user->canDo('exams.create');
    $canEdit = $user->canDo('exams.edit');
    $canDelete = $user->canDo('exams.delete');
    $deliveryLabel = ['electronic' => 'إلكتروني', 'paper' => 'ورقي'];
    $statusLabel = ['draft' => 'مسودة', 'published' => 'منشور', 'stopped' => 'موقوف'];
    $statusClass = ['draft' => 'qx-st-draft', 'published' => 'qx-st-pub', 'stopped' => 'qx-st-stop'];
@endphp

@push('styles')
<style>
    .qx-info{background:#eff6ff;border:1px solid #bfdbfe;border-radius:10px;padding:14px;color:#1e3a8a;font-size:13px;line-height:1.7}
    .qx-info b{color:#1e40af}
    .qx-filters .form-label{font-size:12px;font-weight:600;color:#475569;margin-bottom:3px}
    .qx-table td,.qx-table th{font-size:13px;vertical-align:middle}
    .qx-badge{padding:3px 9px;border-radius:999px;font-size:11px}
    .qx-d-electronic{background:#dbeafe;color:#1e40af}.qx-d-paper{background:#f1f5f9;color:#475569}
    .qx-st-draft{background:#e2e8f0;color:#475569}.qx-st-pub{background:#dcfce7;color:#166534}.qx-st-stop{background:#fee2e2;color:#991b1b}
    .qx-empty{padding:56px 16px;text-align:center;color:#64748b}.qx-empty .ic{font-size:46px;color:#cbd5e1}
</style>
@endpush

@section('content')
<div class="content-header row">
    <div class="content-header-left col-12 mb-2">
        <h2 class="content-header-title mb-0">قائمة الاختبارات</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">الرئيسية</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.qb.questions.index') }}">الأسئلة والاختبارات</a></li>
                <li class="breadcrumb-item active">قائمة الاختبارات</li>
            </ol>
        </div>
    </div>
</div>

@if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if (session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

<div class="qx-info mb-3">
    <b>الاختبارات الورقية:</b> مخصصة للطباعة والتوزيع على الطلاب في الفصل، وتحتاج لإدخال النتائج يدويًا أو تصحيح منفصل.<br>
    <b>الاختبارات الإلكترونية:</b> يمكن للطالب الدخول إليها مباشرة من حسابه، ويتم التصحيح تلقائيًا حسب نوع السؤال، وتظهر النتائج حسب إعدادات الاختبار.
</div>

<div class="card mb-3">
    <div class="card-body py-3">
        @if ($canCreate)
            <div class="d-flex gap-2 mb-3">
                <a href="{{ route('admin.qb.exams.create', ['delivery_type' => 'electronic']) }}" class="btn btn-warning btn-sm">
                    <x-svg-icon name="pc-display" :size="15" /> إضافة اختبار إلكتروني
                </a>
                <a href="{{ route('admin.qb.exams.create', ['delivery_type' => 'paper']) }}" class="btn btn-outline-warning btn-sm">
                    <x-svg-icon name="printer" :size="15" /> إضافة اختبار ورقي
                </a>
            </div>
        @endif

        <form method="GET" action="{{ route('admin.qb.exams.index') }}" class="qx-filters row g-2">
            <div class="col-md-2"><label class="form-label">من تاريخ</label><input type="date" name="date_from" value="{{ $filters['date_from'] }}" class="form-control form-control-sm"></div>
            <div class="col-md-2"><label class="form-label">إلى تاريخ</label><input type="date" name="date_to" value="{{ $filters['date_to'] }}" class="form-control form-control-sm"></div>
            <div class="col-md-2"><label class="form-label">المادة</label>
                <select name="subject_id" class="form-select form-select-sm"><option value="">الكل</option>
                    @foreach ($subjects as $s)<option value="{{ $s->id }}" @selected($filters['subject_id']==$s->id)>{{ $s->name }}</option>@endforeach
                </select></div>
            <div class="col-md-2"><label class="form-label">النوع</label>
                <select name="delivery_type" class="form-select form-select-sm"><option value="">الكل</option>
                    <option value="electronic" @selected($filters['delivery_type']=='electronic')>إلكتروني</option>
                    <option value="paper" @selected($filters['delivery_type']=='paper')>ورقي</option>
                </select></div>
            <div class="col-md-2"><label class="form-label">الحالة</label>
                <select name="status" class="form-select form-select-sm"><option value="">الكل</option>
                    <option value="draft" @selected($filters['status']=='draft')>مسودة</option>
                    <option value="published" @selected($filters['status']=='published')>منشور</option>
                    <option value="stopped" @selected($filters['status']=='stopped')>موقوف</option>
                </select></div>
            <div class="col-md-2 d-flex align-items-end gap-1">
                <button class="btn btn-primary btn-sm">تطبيق</button>
                <a href="{{ route('admin.qb.exams.index') }}" class="btn btn-outline-secondary btn-sm">تفريغ</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
        @if ($exams->isEmpty())
            <div class="qx-empty">
                <div class="ic"><x-svg-icon name="inbox-fill" :size="46" /></div>
                <p class="mt-2">لا توجد اختبارات بعد.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table qx-table">
                    <thead><tr>
                        <th>#</th><th>العنوان</th><th>النوع</th><th>المادة</th><th>الأسئلة</th>
                        <th>تاريخ الإنشاء</th><th>الحالة</th><th>الإجراءات</th>
                    </tr></thead>
                    <tbody>
                    @foreach ($exams as $exam)
                        <tr>
                            <td>{{ $exam->id }}</td>
                            <td>{{ $exam->title }}</td>
                            <td><span class="qx-badge qx-d-{{ $exam->delivery_type }}">{{ $deliveryLabel[$exam->delivery_type] ?? $exam->delivery_type }}</span></td>
                            <td>{{ $exam->subject->name ?? '—' }}</td>
                            <td>{{ $exam->questions_count }}</td>
                            <td>{{ $exam->created_at?->format('Y-m-d') }}</td>
                            <td><span class="qx-badge {{ $statusClass[$exam->status] ?? '' }}">{{ $statusLabel[$exam->status] ?? $exam->status }}</span></td>
                            <td>
                                <a href="{{ route('admin.qb.exams.show', $exam->id) }}" class="btn btn-sm btn-outline-info" title="عرض"><x-svg-icon name="eye" :size="14" /></a>
                                @if ($canEdit)
                                    <a href="{{ route('admin.qb.exams.picker', $exam->id) }}" class="btn btn-sm btn-outline-warning" title="إضافة أسئلة"><x-svg-icon name="plus-circle" :size="14" /></a>
                                    @if ($exam->is_published)
                                        <form method="POST" action="{{ route('admin.qb.exams.unpublish', $exam->id) }}" class="d-inline">@csrf
                                            <button class="btn btn-sm btn-outline-secondary" title="إيقاف"><x-svg-icon name="pause-circle" :size="14" /></button></form>
                                    @else
                                        <form method="POST" action="{{ route('admin.qb.exams.publish', $exam->id) }}" class="d-inline">@csrf
                                            <button class="btn btn-sm btn-outline-success" title="نشر"><x-svg-icon name="send" :size="14" /></button></form>
                                    @endif
                                @endif
                                @if ($canDelete)
                                    <form method="POST" action="{{ route('admin.qb.exams.destroy', $exam->id) }}" class="d-inline" onsubmit="return confirm('حذف الاختبار؟')">@csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" title="حذف"><x-svg-icon name="trash" :size="14" /></button></form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            {{ $exams->links() }}
        @endif
    </div>
</div>
@endsection
