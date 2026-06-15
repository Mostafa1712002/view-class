@extends('layouts.app')

@section('title', 'نتائج الاختبار')
@section('body_class', 'theme-light')

@section('content')
<div class="content-header row">
    <div class="content-header-left col-12 mb-2">
        <h2 class="content-header-title mb-0">نتائج: {{ $exam->title }}</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.qb.exams.index') }}">قائمة الاختبارات</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.qb.exams.show', $exam->id) }}">{{ $exam->title }}</a></li>
                <li class="breadcrumb-item active">النتائج</li>
            </ol>
        </div>
    </div>
</div>

<div class="card"><div class="card-body text-center py-5">
    <x-svg-icon name="bar-chart" :size="44" class="text-muted" />
    <h5 class="mt-2">لا توجد محاولات بعد</h5>
    <p class="text-muted mb-0">
        @if ($exam->delivery_type === 'paper')
            هذا اختبار ورقي — يتم إدخال النتائج يدويًا أو تصحيحها بشكل منفصل بعد التوزيع.
        @else
            محرك محاولات الطلاب الإلكترونية لاختبارات بنك الأسئلة لم يُفعّل بعد (خارج نطاق هذه البطاقة).
        @endif
    </p>
    <p class="text-muted">عدد أسئلة الاختبار: {{ $exam->questions->count() }}</p>
</div></div>
@endsection
