@extends('layouts.app')

@section('title', 'نموذج الرسالة التلقائية')
@section('page-title', 'نموذج الرسالة التلقائية')
@section('body_class', 'theme-light')

@section('content')
<div class="content-header row">
    <div class="content-header-left col-12 mb-2">
        <h2 class="content-header-title mb-0">نموذج الرسالة — {{ $meta['label'] ?? $type }}</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
                <li class="breadcrumb-item">الرسائل القصيرة</li>
                <li class="breadcrumb-item"><a href="{{ route('admin.sms.auto-messages.index') }}">إعدادات رسائل الطلاب المجمعة</a></li>
                <li class="breadcrumb-item active">نموذج الرسالة</li>
            </ol>
        </div>
    </div>
</div>

<div class="content-body">
    @include('components.alerts')

    <form method="POST" action="{{ route('admin.sms.auto-messages.update', $type) }}">
        @csrf
        @method('PUT')

        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">{{ $meta['label'] ?? $type }}</h5>
                    </div>
                    <div class="card-body">

                        {{-- Active toggle --}}
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input type="hidden" name="is_active" value="0">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1"
                                       id="autoMsgActive" {{ $enabled ? 'checked' : '' }}>
                                <label class="form-check-label" for="autoMsgActive">تفعيل هذا الحدث</label>
                            </div>
                        </div>

                        {{-- Threshold (conditional) --}}
                        @if($meta['has_threshold'] ?? false)
                        <div class="mb-3">
                            <label class="form-label">الحد الأدنى <span class="text-danger">*</span></label>
                            <input type="number" name="threshold" min="1"
                                   value="{{ old('threshold', $threshold) }}"
                                   class="form-control @error('threshold') is-invalid @enderror"
                                   style="max-width:180px;">
                            @error('threshold')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text text-muted">الحد الذي عنده يتم إرسال التنبيه.</div>
                        </div>
                        @endif

                        {{-- Body --}}
                        <div class="mb-2">
                            <label class="form-label">نص الرسالة <span class="text-danger">*</span></label>
                            <textarea name="template_body" id="autoMsgBody" rows="6" dir="rtl" required
                                      class="form-control @error('template_body') is-invalid @enderror">{{ old('template_body', $body) }}</textarea>
                            @error('template_body')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="d-flex flex-wrap gap-3 small text-muted mb-1" id="auto-counter">
                            <span>الأحرف: <b id="cLen">0</b></span>
                            <span>المتبقية: <b id="cRem">0</b></span>
                            <span>عدد الرسائل: <b id="cSeg">0</b></span>
                            <span>النوع: <b id="cLang">—</b></span>
                        </div>

                        @if($meta['default'] ?? false)
                        <div class="text-muted small mt-1">
                            النص الافتراضي: <code>{{ $meta['default'] }}</code>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header"><h6 class="mb-0">المتغيرات المتاحة</h6></div>
                    <div class="card-body">
                        <p class="text-muted small">انقر لإدراج المتغير في موضع المؤشر.</p>
                        <div class="d-flex flex-wrap gap-1">
                            @foreach($variables as $key => $label)
                                <button type="button" class="btn btn-sm btn-outline-secondary js-auto-var"
                                        data-token="{{ '{'.$key.'}' }}" title="{{ '{'.$key.'}' }}">{{ $label }}</button>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex gap-2 mt-2">
            <button type="submit" class="btn btn-primary"><x-svg-icon name="save" :size="14" class="me-1" /> حفظ</button>
            <a href="{{ route('admin.sms.auto-messages.index') }}" class="btn btn-outline-secondary">عودة</a>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var ta = document.getElementById('autoMsgBody');

    function insertAtCursor(token) {
        var s = ta.selectionStart, e = ta.selectionEnd, v = ta.value;
        ta.value = v.slice(0, s) + token + v.slice(e);
        ta.selectionStart = ta.selectionEnd = s + token.length;
        ta.focus(); update();
    }
    document.querySelectorAll('.js-auto-var').forEach(function (b) {
        b.addEventListener('click', function () { insertAtCursor(this.dataset.token); });
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
