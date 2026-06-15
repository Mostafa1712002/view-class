@extends('layouts.app')

@section('title', 'اختيار المدارس والصفوف')
@section('body_class', 'theme-light')

@push('styles')
<style>
    .qbf .scope-school { border:1px solid #e2e8f0; border-radius:10px; padding:10px 14px; margin-bottom:8px; }
    .qbf .scope-school.selected { border-color:#d4af37; background:#fffbeb; }
    .qbf .compound-title { font-weight:700; color:#7a5d12; margin:10px 0 6px; display:flex; align-items:center; gap:6px; }
    .qb-scope .form-label { font-weight:600; font-size:13px; color:#0f172a; }
    .qb-summary { background:#f8fafc; border:1px dashed #cbd5e1; border-radius:10px; padding:14px; font-size:13px; }
    .qb-summary code { background:#fff; border:1px solid #e2e8f0; padding:2px 8px; border-radius:6px; }
</style>
@endpush

@section('content')
<div class="content-header row">
    <div class="content-header-left col-12 mb-2">
        <h2 class="content-header-title mb-0">اختيار المدارس والصفوف</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">الرئيسية</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.qb.questions.index') }}">قائمة الأسئلة</a></li>
                <li class="breadcrumb-item active">اختيار المدارس والصفوف</li>
            </ol>
        </div>
    </div>
</div>

<div class="content-body qbf">
    <div class="card">
        <div class="card-body">
            <p class="text-muted" style="font-size:13px;">
                اختر المجمع والمدرسة والصف والفصل والفصل الدراسي. يُستخدم هذا المكوّن نفسه في إضافة سؤال، والمهارة، والقطعة، والاختبار.
            </p>

            @include('admin.qb.partials.scope-selector', [
                'tree' => $tree,
                'scope' => $scope,
                'selected' => [],
                'standalone' => true,
            ])

            <div class="qb-summary mt-3">
                <strong>القيم المحددة:</strong>
                <div class="d-flex flex-wrap gap-2 mt-2">
                    <span>compound_id: <code data-summary-compound>—</code></span>
                    <span>school_id: <code data-summary-school>—</code></span>
                    <span>school_type: <code data-summary-type>—</code></span>
                    <span>grade_id: <code data-summary-grade>—</code></span>
                    <span>class_id: <code data-summary-class>—</code></span>
                    <span>semester_id: <code data-summary-semester>—</code></span>
                    <span>week_id: <code data-summary-week>—</code></span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    // Live-mirror the selector's hidden values into the summary card.
    const map = {
        '[data-scope-compound]': '[data-summary-compound]',
        '[data-scope-school]': '[data-summary-school]',
        '[data-scope-school-type]': '[data-summary-type]',
        '[data-scope-grade]': '[data-summary-grade]',
        '[data-scope-class]': '[data-summary-class]',
        '[data-scope-semester]': '[data-summary-semester]',
        '[data-scope-week]': '[data-summary-week]',
    };
    function sync() {
        Object.entries(map).forEach(([src, dst]) => {
            const s = document.querySelector(src);
            const d = document.querySelector(dst);
            if (s && d) d.textContent = s.value || '—';
        });
    }
    document.addEventListener('click', sync);
    document.addEventListener('change', sync);
    setInterval(sync, 600);
})();
</script>
@endpush
