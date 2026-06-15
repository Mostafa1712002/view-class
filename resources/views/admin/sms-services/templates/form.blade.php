@extends('layouts.app')

@php $editing = $template->exists; @endphp
@section('title', $editing ? 'تعديل قالب' : 'إضافة قالب')
@section('page-title', $editing ? 'تعديل قالب' : 'إضافة قالب')
@section('body_class', 'theme-light')

@php
    // Group the flat variable map (key => Arabic label) into the categories the
    // card asks for (user / student / parent / school / attendance). Any key not
    // matched falls into "أخرى" so nothing is ever dropped.
    $varGroups = [
        'user'       => ['title' => 'بيانات المستخدم',         'icon' => 'person',        'keys' => ['first_name','last_name','full_name','username','password','national_id','passport_no','email','mobile','date']],
        'student'    => ['title' => 'بيانات الطالب',           'icon' => 'mortarboard',   'keys' => ['student_name','student_no','grade','class','stage','subject','period','day']],
        'parent'     => ['title' => 'بيانات ولي الأمر',        'icon' => 'people',        'keys' => ['parent_name']],
        'school'     => ['title' => 'بيانات المدرسة',          'icon' => 'building',      'keys' => ['school_name','teacher_name','admin_name','report_link','platform_link']],
        'attendance' => ['title' => 'بيانات الحضور والغياب',   'icon' => 'calendar-check','keys' => ['check_in','check_out','attendance_state','absence_reason']],
    ];
    $assigned = [];
    foreach ($varGroups as $g) { foreach ($g['keys'] as $k) { $assigned[$k] = true; } }
    $other = [];
    foreach ($variables as $k => $label) { if (!isset($assigned[$k])) { $other[$k] = $label; } }
    if ($other) {
        $varGroups['other'] = ['title' => 'أخرى', 'icon' => 'tag', 'keys' => array_keys($other)];
    }
    // sample values for the live preview (illustrative only)
    $sampleValues = [
        'first_name'=>'محمد','last_name'=>'العتيبي','full_name'=>'محمد العتيبي','username'=>'m.alotaibi',
        'password'=>'••••','national_id'=>'1234567890','passport_no'=>'A1234567','email'=>'m@example.com',
        'mobile'=>'0501234567','date'=>now()->format('Y-m-d'),'student_name'=>'سعد محمد','student_no'=>'STU-204',
        'grade'=>'السادس','class'=>'6/أ','stage'=>'الابتدائية','subject'=>'الرياضيات','period'=>'الثالثة','day'=>'الأحد',
        'parent_name'=>'محمد العتيبي','school_name'=>'مدارس المنصة الذهبية','teacher_name'=>'أ. خالد','admin_name'=>'الإدارة',
        'report_link'=>'https://example.com/r/204','platform_link'=>'https://example.com',
        'check_in'=>'07:15','check_out'=>'13:30','attendance_state'=>'حاضر','absence_reason'=>'—',
    ];
@endphp

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
                <div class="ds-card card">
                    <div class="ds-card-header card-header">
                        <h5 class="ds-card-title mb-0"><x-svg-icon name="pencil-square" :size="18" class="me-1" /> محتوى القالب</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">عنوان القالب <span class="text-danger">*</span></label>
                            <input type="text" name="title" minlength="3" maxlength="150" required
                                   value="{{ old('title', $template->title) }}"
                                   class="form-control @error('title') is-invalid @enderror">
                            @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-1">
                            <label class="form-label">نص القالب <span class="text-danger">*</span></label>
                            <textarea name="body" id="tplBody" rows="9" dir="rtl" required
                                      class="form-control @error('body') is-invalid @enderror"
                                      placeholder="اكتب نص الرسالة وأدرج المتغيرات من اللوحة على اليسار…">{{ old('body', $template->body) }}</textarea>
                            @error('body')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        {{-- live counter directly under the editor --}}
                        <div class="d-flex flex-wrap gap-3 small text-muted mb-3" id="counter">
                            <span>الأحرف: <b id="cLen" class="text-navy">0</b></span>
                            <span>المتبقية: <b id="cRem">0</b></span>
                            <span>عدد الرسائل: <b id="cSeg" class="text-navy">0</b></span>
                            <span>النوع: <b id="cLang">—</b></span>
                        </div>

                        {{-- live preview with sample substitution --}}
                        <label class="form-label d-flex align-items-center gap-1">
                            <x-svg-icon name="eye" :size="15" class="text-gold" /> معاينة الرسالة (بقيم تجريبية)
                        </label>
                        <div id="tplPreview" class="p-2 rounded"
                             style="white-space:pre-wrap;min-height:64px;background:#fdf8ee;border:1px solid #e8d5a3;color:#3a3320"
                             dir="rtl"></div>

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
                <div class="ds-card card">
                    <div class="ds-card-header card-header">
                        <h6 class="ds-card-title mb-0"><x-svg-icon name="braces" :size="16" class="me-1" /> المتغيرات</h6>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small mb-2">انقر على المتغير لإدراجه في موضع المؤشر.</p>
                        <div class="mb-2">
                            <input type="search" id="varSearch" class="form-control form-control-sm"
                                   placeholder="ابحث في المتغيرات…" autocomplete="off" aria-label="بحث في المتغيرات">
                        </div>
                        <div id="varGroups">
                            @foreach($varGroups as $gid => $group)
                                <div class="var-group mb-3" data-group="{{ $gid }}">
                                    <div class="text-navy fw-semibold small mb-1 d-flex align-items-center gap-1">
                                        <x-svg-icon name="{{ $group['icon'] }}" :size="14" /> {{ $group['title'] }}
                                    </div>
                                    <div class="d-flex flex-wrap gap-1">
                                        @foreach($group['keys'] as $key)
                                            @continue(!isset($variables[$key]))
                                            <button type="button"
                                                    class="btn btn-sm js-var var-chip"
                                                    data-token="{{ '{'.$key.'}' }}"
                                                    data-label="{{ $variables[$key] }}"
                                                    data-key="{{ $key }}"
                                                    title="{{ '{'.$key.'}' }}"
                                                    style="border:1px solid #e8d5a3;background:#fffdf7;color:#5a4a1f;border-radius:999px;font-size:.78rem;padding:.18rem .6rem;">
                                                {{ $variables[$key] }}
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                            <div id="varNoResults" class="ds-empty py-3" style="display:none">
                                <div class="ds-empty-icon" style="width:48px;height:48px"><x-svg-icon name="search" :size="20" /></div>
                                <div class="ds-empty-desc mb-0">لا يوجد متغيّر مطابق.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="d-flex gap-2 mt-2">
            <button type="submit" class="btn btn-primary"><x-svg-icon name="check-circle" :size="14" class="me-1" /> حفظ</button>
            <a href="{{ route('admin.sms.templates.index') }}" class="btn btn-outline-secondary">
                <x-svg-icon name="arrow-right" :size="14" class="me-1" /> عودة
            </a>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
(function(){
    var ta      = document.getElementById('tplBody');
    var preview = document.getElementById('tplPreview');
    var SAMPLE  = @json($sampleValues);

    function insertAtCursor(token){
        var s = ta.selectionStart, e = ta.selectionEnd, v = ta.value;
        ta.value = v.slice(0,s) + token + v.slice(e);
        ta.selectionStart = ta.selectionEnd = s + token.length;
        ta.focus(); update();
    }
    document.querySelectorAll('.js-var').forEach(function(b){
        b.addEventListener('click', function(){ insertAtCursor(this.dataset.token); });
    });

    // ---- variable search filter ----
    var varSearch = document.getElementById('varSearch');
    var noResults = document.getElementById('varNoResults');
    varSearch && varSearch.addEventListener('input', function(){
        var q = this.value.trim().toLowerCase();
        var anyVisible = false;
        document.querySelectorAll('.var-group').forEach(function(grp){
            var groupHasVisible = false;
            grp.querySelectorAll('.var-chip').forEach(function(chip){
                var hay = (chip.dataset.label + ' ' + chip.dataset.key + ' ' + chip.dataset.token).toLowerCase();
                var show = !q || hay.indexOf(q) !== -1;
                chip.style.display = show ? '' : 'none';
                if (show) groupHasVisible = true;
            });
            grp.style.display = groupHasVisible ? '' : 'none';
            if (groupHasVisible) anyVisible = true;
        });
        noResults.style.display = anyVisible ? 'none' : '';
    });

    function isUnicode(t){ return /[^\x00-\x7F]/.test(t); }
    function detectLang(t){ var a=/[؀-ۿ]/.test(t), l=/[A-Za-z]/.test(t); return a&&l?'مختلط':a?'عربي':'إنجليزي'; }

    function renderPreview(t){
        var out = t.replace(/\{([a-z_]+)\}/g, function(m, key){
            return Object.prototype.hasOwnProperty.call(SAMPLE, key) ? SAMPLE[key] : m;
        });
        preview.textContent = out || 'ستظهر هنا معاينة الرسالة بعد كتابتها…';
    }

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
        renderPreview(t);
    }
    ta.addEventListener('input', update); update();
})();
</script>
@endpush
