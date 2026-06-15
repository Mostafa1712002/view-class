@extends('layouts.app')

@section('title', 'إرسال عبر Excel')
@section('page-title', 'إرسال عبر Excel')
@section('body_class', 'theme-light')

@section('content')
<div class="content-header row">
    <div class="content-header-left col-12 mb-2">
        <h2 class="content-header-title mb-0">إرسال رسائل قصيرة عبر Excel</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
                <li class="breadcrumb-item">الرسائل القصيرة</li>
                <li class="breadcrumb-item active">إرسال عبر Excel</li>
            </ol>
        </div>
    </div>
</div>

<div class="content-body">
    @include('components.alerts')

    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0"><x-svg-icon name="file-earmark-excel" :size="18" class="me-1" /> رفع ملف الأرقام</h4>
                    <a href="{{ route('admin.sms.excel.template') }}" class="btn btn-sm btn-outline-secondary">
                        <x-svg-icon name="download" :size="14" class="me-1" /> تحميل نموذج Excel
                    </a>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.sms.excel.preview') }}" enctype="multipart/form-data" id="excel-upload-form">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label">اسم المرسل <span class="text-danger">*</span></label>
                            <select name="sender_id" class="form-select @error('sender_id') is-invalid @enderror">
                                <option value="">— اختر اسم المرسل —</option>
                                @foreach($senders as $s)
                                    <option value="{{ $s->id }}"
                                        {{ old('sender_id', $preview['sender_id'] ?? '') == $s->id ? 'selected' : '' }}>
                                        {{ $s->name_ar }} ({{ $s->name_en }})
                                    </option>
                                @endforeach
                            </select>
                            @error('sender_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">القالب (اختياري)</label>
                            <select name="template_id" id="excel-template" class="form-select">
                                <option value="">— اختر قالبًا —</option>
                                @foreach($templates as $tpl)
                                    <option value="{{ $tpl->id }}"
                                            data-body="{{ $tpl->body }}"
                                            {{ old('template_id', $preview['template_id'] ?? '') == $tpl->id ? 'selected' : '' }}>
                                        {{ $tpl->title }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-2">
                            <label class="form-label">نص الرسالة <span class="text-danger">*</span></label>
                            <textarea name="body" id="excelBody" rows="5"
                                      class="form-control @error('body') is-invalid @enderror"
                                      placeholder="اكتب نص الرسالة أو اختر قالبًا…">{{ old('body', $preview['body'] ?? '') }}</textarea>
                            @error('body')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="d-flex flex-wrap gap-3 small text-muted mb-3" id="excel-counter">
                            <span>الأحرف: <b id="cLen">0</b></span>
                            <span>المتبقية: <b id="cRem">0</b></span>
                            <span>عدد الرسائل: <b id="cSeg">0</b></span>
                            <span>النوع: <b id="cLang">—</b></span>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">ملف Excel / CSV <span class="text-danger">*</span></label>
                            <input type="file" name="file" class="form-control @error('file') is-invalid @enderror"
                                   accept=".xlsx,.xls,.csv">
                            @error('file')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text text-muted">يجب أن يحتوي الملف على عمود للهاتف وعمود للاسم. <a href="{{ route('admin.sms.excel.template') }}">تحميل النموذج.</a></div>
                        </div>

                        @if(auth()->user()->canDo('messages.send_excel'))
                        <button type="submit" class="btn btn-primary">
                            <x-svg-icon name="upload" :size="14" class="me-1" /> معاينة
                        </button>
                        @endif
                    </form>
                </div>
            </div>

            @if($preview)
            {{-- Summary cards --}}
            <div class="row g-2 mb-3">
                <div class="col-4">
                    <div class="card text-center border-0" style="background:#f8fafc;">
                        <div class="card-body py-2">
                            <div style="font-size:1.5rem;font-weight:800;color:#0f172a;">{{ $preview['total'] }}</div>
                            <div class="small text-muted">إجمالي</div>
                        </div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="card text-center border-0" style="background:#f0fdf4;">
                        <div class="card-body py-2">
                            <div style="font-size:1.5rem;font-weight:800;color:#15803d;">{{ $preview['valid'] }}</div>
                            <div class="small text-muted">صحيح</div>
                        </div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="card text-center border-0" style="background:#fef2f2;">
                        <div class="card-body py-2">
                            <div style="font-size:1.5rem;font-weight:800;color:#b91c1c;">{{ $preview['invalid'] }}</div>
                            <div class="small text-muted">خاطئ</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Preview table --}}
            <div class="card mb-3">
                <div class="card-header">
                    <h5 class="card-title mb-0">معاينة الصفوف</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>رقم الجوال</th>
                                    <th>الاسم</th>
                                    <th>نص الرسالة النهائي</th>
                                    <th>عدد الرسائل</th>
                                    <th>الحالة</th>
                                    <th>سبب الخطأ</th>
                                </tr>
                            </thead>
                            <tbody>
                            @forelse($preview['rows'] as $row)
                                <tr>
                                    <td dir="ltr" class="text-start">{{ $row['phone'] ?? '—' }}</td>
                                    <td>{{ $row['name'] ?? '—' }}</td>
                                    <td style="max-width:220px;white-space:pre-wrap;font-size:.82rem;">{{ \Illuminate\Support\Str::limit($row['body'] ?? '', 80) }}</td>
                                    <td>{{ $row['segments'] ?? '—' }}</td>
                                    <td>
                                        @php $st = $row['status'] ?? 'unknown'; @endphp
                                        <span class="badge {{ $st === 'valid' ? 'bg-success' : 'bg-danger' }}">
                                            {{ $st === 'valid' ? 'صحيح' : 'خاطئ' }}
                                        </span>
                                    </td>
                                    <td class="text-muted small">{{ $row['reason'] ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted py-3">لا توجد صفوف.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Send / Cancel --}}
            @if(auth()->user()->canDo('messages.send_excel'))
            <div class="d-flex gap-2 align-items-center mb-3">
                <form method="POST" action="{{ route('admin.sms.excel.send') }}" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-primary"
                            onclick="return confirm('إرسال {{ $preview['valid'] }} رسالة؟')">
                        <x-svg-icon name="send" :size="14" class="me-1" /> إرسال ({{ $preview['valid'] }} صحيح)
                    </button>
                </form>
                <form method="POST" action="{{ route('admin.sms.excel.clear') }}" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-outline-secondary">إلغاء</button>
                </form>
            </div>
            @endif
            @endif
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header"><h6 class="mb-0">تعليمات</h6></div>
                <div class="card-body small text-muted">
                    <ol class="ps-3 mb-0">
                        <li class="mb-1">حمّل نموذج Excel وأدخل أرقام الجوال والأسماء.</li>
                        <li class="mb-1">ارفع الملف واختر القالب أو اكتب الرسالة.</li>
                        <li class="mb-1">راجع المعاينة وتأكد من صحة الأرقام.</li>
                        <li>اضغط «إرسال» لبدء الإرسال.</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var ta  = document.getElementById('excelBody');
    var tpl = document.getElementById('excel-template');

    tpl && tpl.addEventListener('change', function () {
        var opt = tpl.options[tpl.selectedIndex];
        var body = opt.dataset.body || '';
        if (body) { ta.value = body; update(); }
    });

    function isUnicode(t) { return /[^\x00-\x7F]/.test(t); }
    function detectLang(t) { var a=/[؀-ۿ]/.test(t), l=/[A-Za-z]/.test(t); return a&&l?'مختلط':a?'عربي':'إنجليزي'; }
    function update() {
        var t = ta.value, len = [...t].length, uni = isUnicode(t);
        var single = uni ? 70 : 160, multi = uni ? 67 : 153, seg, rem;
        if (len === 0) { seg = 0; rem = single; }
        else if (len <= single) { seg = 1; rem = single - len; }
        else { seg = Math.ceil(len / multi); rem = seg * multi - len; }
        document.getElementById('cLen').textContent = len;
        document.getElementById('cRem').textContent = rem;
        document.getElementById('cSeg').textContent = seg;
        document.getElementById('cLang').textContent = len ? detectLang(t) : '—';
    }
    ta.addEventListener('input', update); update();
})();
</script>
@endpush
