@extends('layouts.app')

@section('title', 'نتيجة الاستيراد')
@section('body_class', 'theme-light')

@push('styles')
<style>
    .rs-card{border:1px solid #e2e8f0;border-radius:12px;padding:22px;background:#fff;text-align:center}
    .rs-stat{border:1px solid #e2e8f0;border-radius:10px;padding:14px;text-align:center}
    .rs-stat .n{font-size:26px;font-weight:700}
</style>
@endpush

@section('content')
<div class="content-header row">
    <div class="content-header-left col-12 mb-2">
        <h2 class="content-header-title mb-0">نتيجة الاستيراد — {{ $bank->name_ar }}</h2>
    </div>
</div>

<div class="rs-card mb-3">
    <x-svg-icon name="check-circle-fill" :size="48" class="text-success" />
    <h4 class="mt-2 mb-1">اكتمل الاستيراد</h4>
    <p class="text-muted">تمت معالجة {{ $result->total }} صف.</p>

    <div class="row mt-3">
        <div class="col"><div class="rs-stat"><div class="n text-success">{{ $result->imported }}</div>تم استيرادها</div></div>
        <div class="col"><div class="rs-stat"><div class="n text-danger">{{ $result->failed }}</div>فشلت / متخطاة</div></div>
        <div class="col"><div class="rs-stat"><div class="n">{{ $result->total }}</div>الإجمالي</div></div>
    </div>

    <div class="mt-4 d-flex justify-content-center gap-2">
        <a href="{{ route('admin.qb.questions.index', ['bank_id' => $bank->id]) }}" class="btn btn-warning">عرض أسئلة البنك</a>
        @if ($result->failed > 0)
            <a href="{{ route('admin.qb.import.errors', $batch->id) }}" class="btn btn-outline-danger">
                <x-svg-icon name="download" :size="15" /> تحميل تقرير الأخطاء
            </a>
        @endif
        <a href="{{ route('admin.qb.import.index') }}" class="btn btn-outline-secondary">استيراد ملف آخر</a>
    </div>
</div>
@endsection
