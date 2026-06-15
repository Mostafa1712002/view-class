@extends('layouts.app')

@section('title', 'اختيار الأسئلة من البنك')
@section('body_class', 'theme-light')

@php
    $typeLabel = ['mcq'=>'اختيار','true_false'=>'صح/خطأ','essay'=>'مقالي','short'=>'قصيرة','fill_blank'=>'فراغ','matching'=>'توصيل'];
@endphp

@push('styles')
<style>
    .pk-filters .form-label{font-size:12px;font-weight:600;color:#475569;margin-bottom:3px}
    .pk-table td,.pk-table th{font-size:13px;vertical-align:middle}
    .pk-body{max-width:420px;color:#1e293b;line-height:1.5}
    .pk-badge{padding:2px 8px;border-radius:999px;font-size:11px;background:rgba(212,175,55,.13);color:#7a5d12;border:1px solid rgba(212,175,55,.35)}
</style>
@endpush

@section('content')
<div class="content-header row">
    <div class="content-header-left col-12 mb-2">
        <h2 class="content-header-title mb-0">اختيار الأسئلة — {{ $exam->title }}</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.qb.exams.index') }}">قائمة الاختبارات</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.qb.exams.show', $exam->id) }}">{{ $exam->title }}</a></li>
                <li class="breadcrumb-item active">اختيار الأسئلة</li>
            </ol>
        </div>
    </div>
</div>

<div class="alert alert-info py-2" style="font-size:13px">
    تظهر فقط الأسئلة <b>المعتمدة</b> ضمن نطاقك. عند الإضافة يتم حفظ نسخة Snapshot من السؤال داخل الاختبار،
    ولا يؤثر تعديل السؤال الأصلي لاحقًا على هذا الاختبار.
</div>

<div class="card mb-3"><div class="card-body py-2">
    <form method="GET" action="{{ route('admin.qb.exams.picker', $exam->id) }}" class="pk-filters row g-2">
        <div class="col-md-3"><label class="form-label">البنك</label>
            <select name="bank_id" class="form-select form-select-sm"><option value="">الكل</option>
                @foreach ($banks as $b)<option value="{{ $b->id }}" @selected($filters['bank_id']==$b->id)>{{ $b->name_ar }}</option>@endforeach
            </select></div>
        <div class="col-md-2"><label class="form-label">المادة</label>
            <select name="subject_id" class="form-select form-select-sm"><option value="">الكل</option>
                @foreach ($subjects as $s)<option value="{{ $s->id }}" @selected($filters['subject_id']==$s->id)>{{ $s->name }}</option>@endforeach
            </select></div>
        <div class="col-md-2"><label class="form-label">المهارة</label>
            <select name="skill_id" class="form-select form-select-sm"><option value="">الكل</option>
                @foreach ($skills as $sk)<option value="{{ $sk->id }}" @selected($filters['skill_id']==$sk->id)>{{ $sk->name }}</option>@endforeach
            </select></div>
        <div class="col-md-2"><label class="form-label">الصعوبة</label>
            <select name="difficulty" class="form-select form-select-sm"><option value="">الكل</option>
                <option value="1" @selected($filters['difficulty']=='1')>سهل</option>
                <option value="2" @selected($filters['difficulty']=='2')>متوسط</option>
                <option value="3" @selected($filters['difficulty']=='3')>صعب</option>
            </select></div>
        <div class="col-md-2"><label class="form-label">النوع</label>
            <select name="type" class="form-select form-select-sm"><option value="">الكل</option>
                @foreach ($typeLabel as $k => $v)<option value="{{ $k }}" @selected($filters['type']==$k)>{{ $v }}</option>@endforeach
            </select></div>
        <div class="col-md-2"><label class="form-label">كود السؤال</label><input type="text" name="code" value="{{ $filters['code'] }}" class="form-control form-control-sm"></div>
        <div class="col-md-2 d-flex align-items-end gap-1">
            <button class="btn btn-primary btn-sm">تصفية</button>
            <a href="{{ route('admin.qb.exams.picker', $exam->id) }}" class="btn btn-outline-secondary btn-sm">تفريغ</a>
        </div>
    </form>
</div></div>

<form method="POST" action="{{ route('admin.qb.exams.add-from-bank', $exam->id) }}">
    @csrf
    <div class="card"><div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h6 class="mb-0">الأسئلة المعتمدة المتاحة</h6>
            <button type="submit" class="btn btn-warning btn-sm"><x-svg-icon name="plus-circle-fill" :size="15" /> إضافة المحدد إلى الاختبار</button>
        </div>

        @if ($bankQuestions->isEmpty())
            <p class="text-muted py-4 text-center mb-0">لا توجد أسئلة معتمدة مطابقة.</p>
        @else
            <div class="table-responsive">
                <table class="table pk-table">
                    <thead><tr><th style="width:36px"></th><th>الكود</th><th>السؤال</th><th>البنك</th><th>النوع</th><th>الصعوبة</th><th>الدرجة</th></tr></thead>
                    <tbody>
                    @foreach ($bankQuestions as $q)
                        @php $added = in_array($q->id, $alreadyIds, true); @endphp
                        <tr class="{{ $added ? 'table-light text-muted' : '' }}">
                            <td>
                                @if ($added)
                                    <span class="badge bg-secondary" title="مضاف">✓</span>
                                @else
                                    <input type="checkbox" name="bank_question_ids[]" value="{{ $q->id }}" class="form-check-input">
                                @endif
                            </td>
                            <td>{{ $q->question_code ?? '—' }}</td>
                            <td class="pk-body">{{ \Illuminate\Support\Str::limit(strip_tags($q->body_ar ?? $q->body_en ?? ''), 110) }}</td>
                            <td>{{ $q->bank->name_ar ?? '' }}</td>
                            <td><span class="pk-badge">{{ $typeLabel[$q->type] ?? $q->type }}</span></td>
                            <td>{{ [1=>'سهل',2=>'متوسط',3=>'صعب'][$q->difficulty] ?? $q->difficulty }}</td>
                            <td>{{ $q->points }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            {{ $bankQuestions->links() }}
        @endif
    </div></div>
</form>
@endsection
