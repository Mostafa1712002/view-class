@extends('layouts.app')

@section('title', 'عرض الاختبار')
@section('body_class', 'theme-light')

@php
    $user = auth()->user();
    $canEdit = $user->canDo('exams.edit');
    $deliveryLabel = ['electronic' => 'إلكتروني', 'paper' => 'ورقي'];
    $statusLabel = ['draft' => 'مسودة', 'published' => 'منشور', 'stopped' => 'موقوف'];
    $typeLabel = ['mcq'=>'اختيار','true_false'=>'صح/خطأ','essay'=>'مقالي','short'=>'قصيرة','fill_blank'=>'فراغ','matching'=>'توصيل'];
@endphp

@section('content')
<div class="content-header row">
    <div class="content-header-left col-12 mb-2">
        <h2 class="content-header-title mb-0">{{ $exam->title }}</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.qb.exams.index') }}">قائمة الاختبارات</a></li>
                <li class="breadcrumb-item active">عرض</li>
            </ol>
        </div>
    </div>
</div>

@if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if (session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

<div class="card mb-3"><div class="card-body">
    <div class="row">
        <div class="col-md-3"><small class="text-muted">النوع</small><div>{{ $deliveryLabel[$exam->delivery_type] ?? $exam->delivery_type }}</div></div>
        <div class="col-md-3"><small class="text-muted">المادة</small><div>{{ $exam->subject->name ?? '—' }}</div></div>
        <div class="col-md-3"><small class="text-muted">الحالة</small><div>{{ $statusLabel[$exam->status] ?? $exam->status }}</div></div>
        <div class="col-md-3"><small class="text-muted">عدد الأسئلة</small><div>{{ $exam->questions->count() }}</div></div>
        <div class="col-md-3 mt-2"><small class="text-muted">درجة النجاح</small><div>{{ $exam->pass_score ?? '—' }}</div></div>
        <div class="col-md-3 mt-2"><small class="text-muted">المدة</small><div>{{ $exam->duration_minutes ? $exam->duration_minutes.' دقيقة' : '—' }}</div></div>
        <div class="col-md-3 mt-2"><small class="text-muted">البداية</small><div>{{ $exam->starts_at?->format('Y-m-d H:i') ?? '—' }}</div></div>
        <div class="col-md-3 mt-2"><small class="text-muted">النهاية</small><div>{{ $exam->ends_at?->format('Y-m-d H:i') ?? '—' }}</div></div>
    </div>
    @if ($canEdit)
        <div class="mt-3 d-flex gap-2">
            <a href="{{ route('admin.qb.exams.picker', $exam->id) }}" class="btn btn-warning btn-sm"><x-svg-icon name="plus-circle" :size="15" /> إضافة أسئلة من البنك</a>
            <a href="{{ route('admin.qb.exams.results', $exam->id) }}" class="btn btn-outline-secondary btn-sm"><x-svg-icon name="bar-chart" :size="15" /> النتائج</a>
        </div>
    @endif
</div></div>

<div class="card"><div class="card-body">
    <h6 class="mb-3">أسئلة الاختبار ({{ $exam->questions->count() }})</h6>
    @if ($exam->questions->isEmpty())
        <p class="text-muted mb-0">لم تتم إضافة أسئلة بعد.</p>
    @else
        <div class="table-responsive">
            <table class="table" style="font-size:13px">
                <thead><tr><th>#</th><th>السؤال (Snapshot)</th><th>النوع</th><th>الدرجة</th><th>المصدر</th>@if($canEdit)<th></th>@endif</tr></thead>
                <tbody>
                @foreach ($exam->questions as $i => $q)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>{{ \Illuminate\Support\Str::limit(strip_tags($q->body ?? ''), 120) }}</td>
                        <td>{{ $typeLabel[$q->question_type] ?? $q->question_type }}</td>
                        <td>{{ $q->marks }}</td>
                        <td>{{ $q->bank_question_id ? '#'.$q->bank_question_id : '—' }}</td>
                        @if ($canEdit)
                            <td>
                                <form method="POST" action="{{ route('admin.qb.exams.questions.remove', [$exam->id, $q->id]) }}" onsubmit="return confirm('إزالة السؤال؟')">@csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger"><x-svg-icon name="trash" :size="13" /></button></form>
                            </td>
                        @endif
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div></div>
@endsection
