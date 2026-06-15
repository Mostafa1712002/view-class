@extends('layouts.app')

@php $editing = $template->exists; @endphp
@section('title', $editing ? 'تعديل قالب' : 'إضافة قالب')
@section('page-title', $editing ? 'تعديل قالب' : 'إضافة قالب')
@section('body_class', 'theme-light')

@section('content')
<div class="content-header row">
    <div class="content-header-left col-12 mb-2">
        <h2 class="content-header-title mb-0">{{ $editing ? 'تعديل قالب' : 'إضافة قالب' }}</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
                <li class="breadcrumb-item">الرسائل القصيرة</li>
                <li class="breadcrumb-item"><a href="{{ route('admin.sms.templates.index') }}">قوالب الرسائل القصيرة</a></li>
                <li class="breadcrumb-item active">{{ $editing ? 'تعديل' : 'إضافة قالب' }}</li>
            </ol>
        </div>
    </div>
</div>

<div class="content-body">
    @include('components.alerts')
    <form method="POST" action="{{ $editing ? route('admin.sms.templates.update', $template->id) : route('admin.sms.templates.store') }}">
        @csrf
        @if($editing) @method('PUT') @endif
        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">عنوان القالب <span class="text-danger">*</span></label>
                            <input type="text" name="title" minlength="3" maxlength="150" required
                                   value="{{ old('title', $template->title) }}"
                                   class="form-control @error('title') is-invalid @enderror">
                            @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-2">
                            <label class="form-label">نص القالب <span class="text-danger">*</span></label>
                            <textarea name="body" id="tplBody" rows="6" dir="rtl" required
                                      class="form-control @error('body') is-invalid @enderror">{{ old('body', $template->body) }}</textarea>
                            @error('body')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="d-flex flex-wrap gap-3 small text-muted" id="counter">
                            <span>الأحرف: <b id="cLen">0</b></span>
                            <span>المتبقية: <b id="cRem">0</b></span>
                            <span>عدد الرسائل: <b id="cSeg">0</b></span>
                            <span>النوع: <b id="cLang">—</b></span>
                        </div>
                        <div class="form-check form-switch mt-3">
                            <input type="hidden" name="is_active" value="0">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="isActive"
                                   {{ old('is_active', $template->is_active ?? true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="isActive">مفعّل</label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header"><h6 class="mb-0">المتغيرات</h6></div>
                    <div class="card-body">
                        <p class="text-muted small">انقر لإدراج المتغير في موضع المؤشر.</p>
                        <div class="d-flex flex-wrap gap-1">
                            @foreach($variables as $key => $label)
                                <button type="button" class="btn btn-sm btn-outline-secondary js-var" data-token="{{ '{'.$key.'}' }}" title="{{ '{'.$key.'}' }}">{{ $label }}</button>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="d-flex gap-2 mt-2">
            <button type="submit" class="btn btn-primary"><x-svg-icon name="save" :size="14" class="me-1" /> حفظ</button>
            <a href="{{ route('admin.sms.templates.index') }}" class="btn btn-outline-secondary">عودة</a>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
(function(){
    var ta = document.getElementById('tplBody');
    function insertAtCursor(token){
        var s = ta.selectionStart, e = ta.selectionEnd, v = ta.value;
        ta.value = v.slice(0,s) + token + v.slice(e);
        ta.selectionStart = ta.selectionEnd = s + token.length;
        ta.focus(); update();
    }
    document.querySelectorAll('.js-var').forEach(function(b){
        b.addEventListener('click', function(){ insertAtCursor(this.dataset.token); });
    });
    function isUnicode(t){ return /[^\x00-\x7F]/.test(t); }
    function detectLang(t){ var a=/[؀-ۿ]/.test(t), l=/[A-Za-z]/.test(t); return a&&l?'مختلط':a?'عربي':'إنجليزي'; }
    function update(){
        var t = ta.value, len = [...t].length, uni = isUnicode(t);
        var single = uni?70:160, multi = uni?67:153, seg, rem;
        if(len===0){seg=0;rem=single;}
        else if(len<=single){seg=1;rem=single-len;}
        else {seg=Math.ceil(len/multi);rem=seg*multi-len;}
        document.getElementById('cLen').textContent=len;
        document.getElementById('cRem').textContent=rem;
        document.getElementById('cSeg').textContent=seg;
        document.getElementById('cLang').textContent=len?detectLang(t):'—';
    }
    ta.addEventListener('input', update); update();
})();
</script>
@endpush
