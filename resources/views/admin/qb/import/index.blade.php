@extends('layouts.app')

@section('title', 'استيراد الأسئلة من Excel')
@section('body_class', 'theme-light')

@push('styles')
<style>
    .imp-card{border:1px solid #e2e8f0;border-radius:12px;padding:18px;background:#fff}
    .imp-help{background:#fff8e6;border:1px solid rgba(184,134,11,.35);border-radius:10px;padding:14px;color:#7a5d12;font-size:13px;line-height:1.7}
    .imp-table td,.imp-table th{font-size:13px;vertical-align:middle}
    .imp-status{padding:3px 9px;border-radius:999px;font-size:11px}
    .imp-st-completed{background:#dcfce7;color:#166534}.imp-st-failed{background:#fee2e2;color:#991b1b}
    .imp-st-previewed{background:#fef3c7;color:#92400e}.imp-st-pending{background:#e2e8f0;color:#475569}
</style>
@endpush

@section('content')
<div class="content-header row">
    <div class="content-header-left col-12 mb-2">
        <h2 class="content-header-title mb-0">استيراد الأسئلة من Excel</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">الرئيسية</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.qb.questions.index') }}">قائمة الأسئلة</a></li>
                <li class="breadcrumb-item active">استيراد من Excel</li>
            </ol>
        </div>
    </div>
</div>

@if ($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

<div class="row">
    <div class="col-lg-7 mb-3">
        <div class="imp-card">
            <h5 class="mb-3"><x-svg-icon name="upload" :size="18" /> رفع ملف الأسئلة</h5>

            <form method="POST" action="{{ route('admin.qb.import.preview') }}" enctype="multipart/form-data">
                @csrf
                <div class="mb-3">
                    <label class="form-label">بنك الأسئلة <span class="text-danger">*</span></label>
                    <select name="question_bank_id" class="form-select" required>
                        <option value="">— اختر البنك —</option>
                        @foreach ($banks as $bank)
                            <option value="{{ $bank->id }}" @selected(old('question_bank_id') == $bank->id)>{{ $bank->name_ar }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">ملف Excel (xlsx / xls) <span class="text-danger">*</span></label>
                    <input type="file" name="file" class="form-control" accept=".xlsx,.xls" required>
                    <small class="text-muted">الحد الأقصى 10 ميجابايت.</small>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">حالة الأسئلة بعد الاستيراد</label>
                        <select name="status_choice" class="form-select">
                            <option value="draft">مسودة</option>
                            <option value="pending_review">بانتظار المراجعة</option>
                            @if (auth()->user()->canDo('question_banks.approve'))
                                <option value="approved">معتمد</option>
                            @endif
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">التعامل مع الأكواد المكررة</label>
                        <select name="duplicate_policy" class="form-select">
                            <option value="skip">تجاهل</option>
                            <option value="new">إنشاء نسخة جديدة</option>
                        </select>
                    </div>
                </div>

                <button type="submit" class="btn btn-warning">
                    <x-svg-icon name="search" :size="15" /> فحص الملف ومعاينته
                </button>
            </form>

            <hr>

            <form method="GET" action="{{ route('admin.qb.import.template') }}" class="d-flex align-items-end gap-2">
                <div class="flex-grow-1">
                    <label class="form-label">تحميل نموذج Excel للبنك</label>
                    <select name="bank_id" class="form-select" required>
                        <option value="">— اختر البنك —</option>
                        @foreach ($banks as $bank)
                            <option value="{{ $bank->id }}">{{ $bank->name_ar }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="btn btn-outline-secondary">
                    <x-svg-icon name="download" :size="15" /> تحميل النموذج
                </button>
            </form>
        </div>
    </div>

    <div class="col-lg-5 mb-3">
        <div class="imp-help">
            <strong>قبل الرفع:</strong>
            <ul class="mb-0 mt-2">
                <li>حمّل النموذج وأضف الأسئلة في ورقة <code>Questions</code>.</li>
                <li>لا يتم حفظ أي سؤال قبل الفحص والمعاينة.</li>
                <li>الصفوف الخاطئة تظهر في تقرير الأخطاء ولا تُستورد.</li>
                <li>روابط الصور تُكتب في أعمدة <code>*_image</code>.</li>
            </ul>
        </div>
    </div>
</div>

<div class="imp-card mt-2">
    <h6 class="mb-3"><x-svg-icon name="clock-history" :size="16" /> سجل عمليات الاستيراد</h6>
    @if ($history->isEmpty())
        <p class="text-muted mb-0">لا توجد عمليات استيراد سابقة.</p>
    @else
        <div class="table-responsive">
            <table class="table imp-table">
                <thead><tr>
                    <th>#</th><th>الملف</th><th>الإجمالي</th><th>صالح</th><th>خطأ</th>
                    <th>مستورد</th><th>الحالة</th><th>التاريخ</th><th></th>
                </tr></thead>
                <tbody>
                @foreach ($history as $b)
                    <tr>
                        <td>{{ $b->id }}</td>
                        <td>{{ $b->original_filename }}</td>
                        <td>{{ $b->total_rows }}</td>
                        <td>{{ $b->valid_rows }}</td>
                        <td>{{ $b->invalid_rows }}</td>
                        <td>{{ $b->imported_rows }}</td>
                        <td><span class="imp-status imp-st-{{ $b->status }}">{{ $b->status }}</span></td>
                        <td>{{ $b->created_at?->format('Y-m-d H:i') }}</td>
                        <td>
                            @if ($b->failed_rows > 0)
                                <a href="{{ route('admin.qb.import.errors', $b->id) }}" class="btn btn-sm btn-outline-danger">تقرير الأخطاء</a>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
