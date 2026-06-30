@extends('layouts.app')

@php $editing = isset($announcement) && $announcement; @endphp

@section('title', $editing ? 'تعديل إعلان' : 'إعلان جديد')
@section('page-title', $editing ? 'تعديل إعلان' : 'إعلان جديد')
@section('body_class', 'theme-light')

@php
    $sel = function ($key, $default = null) use ($announcement, $editing) {
        return old($key, $editing ? data_get($announcement, $key, $default) : $default);
    };
    $targetType = old('target_type', $editing ? $announcement->target_type : 'all');
    $selectedUserTargets = old('user_target_ids', $editing ? $announcement->targets->where('kind','user')->pluck('target_id')->all() : []);
    $selectedRoleTargets = old('role_target_ids', $editing ? $announcement->targets->where('kind','role')->pluck('target_id')->all() : []);
    $selectedJobTitles = old('job_title_ids', $editing ? $announcement->targets->where('kind','job_title')->pluck('target_id')->all() : []);
    $selectedGrades  = old('grade_levels', $editing ? ($announcement->grade_levels ?? []) : []);
    $selectedClasses = old('class_ids', $editing ? ($announcement->class_ids ?? []) : []);
    $selectedSubjects = old('subject_ids', $editing ? ($announcement->subject_ids ?? []) : []);
    $isSuper = auth()->user()->isSuperAdmin();
    $fmt = fn($v) => $v ? \Illuminate\Support\Carbon::parse($v)->format('Y-m-d\TH:i') : '';
@endphp

@section('content')
<section class="vc-ann-form">

    {{-- Page header + breadcrumb --}}
    <div style="display:flex;justify-content:space-between;align-items:flex-end;flex-wrap:wrap;gap:.75rem;margin-bottom:1rem">
        <div>
            <h2 style="margin:0;font-size:1.45rem;font-weight:800;color:var(--gray-900)">
                {{ $editing ? 'تعديل إعلان' : 'إعلان جديد' }}
            </h2>
            <nav><ol class="breadcrumb" style="margin:0;padding:0;background:transparent">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">الرئيسية</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.announcements.index') }}">الإعلانات</a></li>
                <li class="breadcrumb-item active" aria-current="page">{{ $editing ? 'تعديل' : 'جديد' }}</li>
            </ol></nav>
        </div>
        <a href="{{ route('admin.announcements.index') }}" class="btn btn-outline-secondary btn-sm">
            <x-svg-icon name="arrow-right" :size="15" /> عودة
        </a>
    </div>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul style="margin:0;padding-inline-start:1.2rem">
                @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
            </ul>
        </div>
    @endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

    <form method="POST"
          action="{{ $editing ? route('admin.announcements.update', $announcement->id) : route('admin.announcements.store') }}"
          id="announcementForm">
        @csrf
        @if($editing) @method('PUT') @endif
        <input type="hidden" name="action" id="annAction" value="draft">

        {{-- Main content card --}}
        <div class="ds-card card" style="margin-bottom:1rem">
            <div class="ds-card-header card-header">
                <h5 class="ds-card-title" style="margin:0;display:flex;align-items:center;gap:.35rem">
                    <x-svg-icon name="megaphone" :size="16" /> محتوى الإعلان
                </h5>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label class="form-label">العنوان <span class="text-danger">*</span></label>
                    <input type="text" name="title" class="form-control" required value="{{ $sel('title') }}">
                </div>

                <div class="form-group">
                    <label class="form-label">التفاصيل</label>
                    <textarea name="body" id="annBody" rows="6" class="form-control">{{ $sel('body') }}</textarea>
                </div>

                @if($isSuper)
                    <div class="form-group">
                        <label class="form-label">المدرسة</label>
                        <select name="school_id" id="annSchool" class="form-control">
                            @foreach($schools as $s)
                                <option value="{{ $s->id }}" @selected((int)old('school_id', $editing ? $announcement->school_id : 0) === $s->id)>{{ $s->name }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted">اختيار المدرسة يحدّد الفصول المتاحة للإعلان.</small>
                    </div>
                @endif

                <div class="form-group">
                    <label class="form-label">النوع</label>
                    <select name="type" class="form-control">
                        <option value="normal"    @selected($sel('type','normal')==='normal')>عادي</option>
                        <option value="important" @selected($sel('type')==='important')>مهم</option>
                        <option value="popup"     @selected($sel('type')==='popup')>منبثق</option>
                    </select>
                </div>
            </div>
        </div>

        {{-- Targeting card --}}
        <div class="ds-card card" style="margin-bottom:1rem">
            <div class="ds-card-header card-header">
                <h5 class="ds-card-title" style="margin:0;display:flex;align-items:center;gap:.35rem">
                    <x-svg-icon name="people" :size="16" /> الفئة المستهدفة
                </h5>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label class="form-label">الجمهور المستهدف</label>
                    <select name="target_type" id="annTargetType" class="form-control">
                        <option value="all"            @selected($targetType==='all')>كل المستخدمين</option>
                        <option value="students"       @selected($targetType==='students')>الطلاب</option>
                        <option value="teachers"       @selected($targetType==='teachers')>المعلمون</option>
                        <option value="parents"        @selected($targetType==='parents')>أولياء الأمور</option>
                        <option value="admins"         @selected($targetType==='admins')>الإداريون</option>
                        <option value="job_titles"     @selected($targetType==='job_titles')>مسميات وظيفية محددة</option>
                        <option value="specific_users" @selected($targetType==='specific_users')>مستخدمون محددون</option>
                        <option value="specific_roles" @selected($targetType==='specific_roles')>أدوار محددة</option>
                    </select>
                </div>

                <x-audience-selector
                    :grids="['job_titles', 'users', 'roles', 'grades', 'classes', 'subjects']"
                    :conditional="true"
                    :school-select="$isSuper ? 'annSchool' : null"
                    :job-titles="$jobTitles"
                    :users="$users"
                    :roles="$roles"
                    :grade-levels="$gradeLevels"
                    :classes="$classes"
                    :subjects="$subjects"
                    :selected-job-titles="$selectedJobTitles"
                    :selected-users="$selectedUserTargets"
                    :selected-roles="$selectedRoleTargets"
                    :selected-grades="$selectedGrades"
                    :selected-classes="$selectedClasses"
                    :selected-subjects="$selectedSubjects"
                />
            </div>
        </div>

        {{-- Schedule & options card --}}
        <div class="ds-card card" style="margin-bottom:1rem">
            <div class="ds-card-header card-header">
                <h5 class="ds-card-title" style="margin:0;display:flex;align-items:center;gap:.35rem">
                    <x-svg-icon name="calendar-event" :size="16" /> الجدول والخيارات
                </h5>
            </div>
            <div class="card-body">
                <div style="display:flex;gap:1rem;flex-wrap:wrap">
                    <div class="form-group" style="flex:1;min-width:220px">
                        <label class="form-label">تاريخ البداية</label>
                        <input type="datetime-local" name="starts_at" class="form-control" value="{{ old('starts_at', $editing ? $fmt($announcement->starts_at) : '') }}">
                    </div>
                    <div class="form-group" style="flex:1;min-width:220px">
                        <label class="form-label">تاريخ النهاية</label>
                        <input type="datetime-local" name="ends_at" class="form-control" value="{{ old('ends_at', $editing ? $fmt($announcement->ends_at) : '') }}">
                    </div>
                </div>

                <hr>
                <div style="display:flex;flex-direction:column;gap:.5rem">
                    @php
                        $toggles = [
                            'show_on_login'    => 'يظهر عند تسجيل الدخول؟',
                            'require_read_ack' => 'يحتاج تأكيد قراءة؟',
                            'notify_internal'  => 'إشعار داخلي؟',
                            'notify_sms'       => 'إشعار SMS؟',
                            'notify_whatsapp'  => 'إشعار واتساب؟',
                        ];
                    @endphp
                    @foreach($toggles as $name => $label)
                        <label style="display:flex;gap:.5rem;align-items:center;font-weight:500;cursor:pointer">
                            <input type="hidden" name="{{ $name }}" value="0">
                            <input type="checkbox" name="{{ $name }}" value="1" @checked(old($name, $editing ? $announcement->$name : false))>
                            {{ $label }}
                        </label>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Form actions --}}
        <div style="display:flex;gap:.6rem;flex-wrap:wrap;margin-bottom:2rem">
            <button type="submit" class="btn btn-outline-secondary" onclick="document.getElementById('annAction').value='draft'">
                <x-svg-icon name="save" :size="16" /> حفظ كمسودة
            </button>
            <button type="submit" class="btn btn-primary" onclick="document.getElementById('annAction').value='publish'">
                <x-svg-icon name="megaphone-fill" :size="16" /> نشر
            </button>
            <a href="{{ route('admin.announcements.index') }}" class="btn btn-light">
                <x-svg-icon name="arrow-right" :size="16" /> عودة
            </a>
        </div>
    </form>

</section>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/tinymce@6/tinymce.min.js" referrerpolicy="origin" onerror="window.__noTiny=true"></script>
<script>
(function () {
    // ── Conditional target fields ─────────────────────────────────────────
    var tt = document.getElementById('annTargetType');
    function syncCond() {
        var v = tt.value;
        document.querySelectorAll('.ann-cond').forEach(function (el) {
            el.style.display = (el.dataset.show === v) ? '' : 'none';
        });
    }
    if (tt) { tt.addEventListener('change', syncCond); syncCond(); }

    // School-based class/grade filtering is handled inside the audience-selector component.

    // ── TinyMCE full classic toolbar (textarea stays source of truth) ──────
    if (window.tinymce && !window.__noTiny) {
        tinymce.init({
            selector: '#annBody',
            directionality: 'rtl',
            language: 'ar',
            language_url: 'https://cdn.jsdelivr.net/npm/tinymce-i18n@24/langs6/ar.js',
            menubar: 'file edit view insert format tools table help',
            height: 420,
            branding: true,
            promotion: false,
            plugins: 'advlist autolink lists link image charmap preview anchor '
                + 'searchreplace visualblocks code fullscreen insertdatetime '
                + 'media table help wordcount directionality',
            toolbar1: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | '
                + 'forecolor backcolor | alignleft aligncenter alignright alignjustify',
            toolbar2: 'ltr rtl | bullist numlist outdent indent | link image media table | '
                + 'charmap | code preview fullscreen | print',
            font_family_formats:
                'Helvetica=helvetica,arial,sans-serif; Arial=arial,helvetica,sans-serif; '
                + 'Tahoma=tahoma,arial,sans-serif; Times New Roman=times new roman,times,serif; '
                + 'Courier New=courier new,courier,monospace',
            font_size_formats: '10px 12px 14px 16px 18px 24px 36px',
            content_style: 'body{font-family:helvetica,arial,sans-serif;font-size:14px;direction:rtl}',
            setup: function (ed) {
                ed.on('change keyup', function () { ed.save(); });
            }
        });
        document.getElementById('announcementForm').addEventListener('submit', function () {
            if (window.tinymce) { tinymce.triggerSave(); }
        });
        // Watchdog: if the editor fails to render (a CDN skin/plugin/language
        // sub-resource didn't load), TinyMCE may have already hidden the
        // textarea — restore it so the «تفاصيل الرسالة» field is never missing (#232).
        setTimeout(function () {
            if (!document.querySelector('.tox-tinymce')) {
                var ta = document.getElementById('annBody');
                if (ta) { ta.style.display = ''; }
            }
        }, 5000);
    } else {
        // TinyMCE itself failed to load — guarantee the raw field is usable.
        var taFallback = document.getElementById('annBody');
        if (taFallback) { taFallback.style.display = ''; }
    }
})();
</script>
@endpush
