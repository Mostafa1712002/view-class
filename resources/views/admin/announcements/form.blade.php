@extends('layouts.app')

@php $editing = isset($announcement) && $announcement; @endphp

@section('title', $editing ? 'تعديل إعلان' : 'إعلان جديد')
@section('page-title', $editing ? 'تعديل إعلان' : 'إعلان جديد')

@php
    $sel = function ($key, $default = null) use ($announcement, $editing) {
        return old($key, $editing ? data_get($announcement, $key, $default) : $default);
    };
    $targetType = old('target_type', $editing ? $announcement->target_type : 'all');
    $selectedUserTargets = old('user_target_ids', $editing ? $announcement->targets->where('kind','user')->pluck('target_id')->all() : []);
    $selectedRoleTargets = old('role_target_ids', $editing ? $announcement->targets->where('kind','role')->pluck('target_id')->all() : []);
    $selectedGrades  = old('grade_levels', $editing ? ($announcement->grade_levels ?? []) : []);
    $selectedClasses = old('class_ids', $editing ? ($announcement->class_ids ?? []) : []);
    $selectedSubjects = old('subject_ids', $editing ? ($announcement->subject_ids ?? []) : []);
    $isSuper = auth()->user()->isSuperAdmin();
    $fmt = fn($v) => $v ? \Illuminate\Support\Carbon::parse($v)->format('Y-m-d\TH:i') : '';
@endphp

@section('content')
<section class="vc-ann-form">
    <div class="ls-header" style="margin-bottom:1rem">
        <h2 style="margin:0">{{ $editing ? 'تعديل إعلان' : 'إعلان جديد' }}</h2>
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

        <div class="card" style="margin-bottom:1rem"><div class="card-body">
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
                    <select name="school_id" class="form-control">
                        @foreach($schools as $s)
                            <option value="{{ $s->id }}" @selected((int)old('school_id', $editing ? $announcement->school_id : 0) === $s->id)>{{ $s->name }}</option>
                        @endforeach
                    </select>
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
        </div></div>

        {{-- Targeting --}}
        <div class="card" style="margin-bottom:1rem"><div class="card-body">
            <h5>الفئة المستهدفة</h5>
            <div class="form-group">
                <select name="target_type" id="annTargetType" class="form-control">
                    <option value="all"            @selected($targetType==='all')>كل المستخدمين</option>
                    <option value="students"       @selected($targetType==='students')>الطلاب</option>
                    <option value="teachers"       @selected($targetType==='teachers')>المعلمون</option>
                    <option value="parents"        @selected($targetType==='parents')>أولياء الأمور</option>
                    <option value="admins"         @selected($targetType==='admins')>الإداريون</option>
                    <option value="specific_users" @selected($targetType==='specific_users')>مستخدمون محددون</option>
                    <option value="specific_roles" @selected($targetType==='specific_roles')>أدوار محددة</option>
                </select>
            </div>

            <div class="form-group ann-cond" data-show="specific_users">
                <label class="form-label">اختر المستخدمين</label>
                <select name="user_target_ids[]" class="form-control" multiple size="6">
                    @foreach($users as $u)
                        <option value="{{ $u->id }}" @selected(in_array($u->id, $selectedUserTargets))>{{ $u->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group ann-cond" data-show="specific_roles">
                <label class="form-label">اختر الأدوار</label>
                <select name="role_target_ids[]" class="form-control" multiple size="5">
                    @foreach($roles as $r)
                        <option value="{{ $r->id }}" @selected(in_array($r->id, $selectedRoleTargets))>{{ $r->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group ann-cond" data-show="students">
                <label class="form-label">الصفوف</label>
                <select name="grade_levels[]" class="form-control" multiple size="5">
                    @foreach($gradeLevels as $g)
                        <option value="{{ $g }}" @selected(in_array($g, $selectedGrades))>الصف {{ $g }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group ann-cond" data-show="students">
                <label class="form-label">الفصول</label>
                <select name="class_ids[]" class="form-control" multiple size="5">
                    @foreach($classes as $c)
                        <option value="{{ $c->id }}" @selected(in_array($c->id, $selectedClasses))>{{ $c->name }} (صف {{ $c->grade_level }})</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">المواد (اختياري)</label>
                <select name="subject_ids[]" class="form-control" multiple size="4">
                    @foreach($subjects as $sub)
                        <option value="{{ $sub->id }}" @selected(in_array($sub->id, $selectedSubjects))>{{ $sub->name }}</option>
                    @endforeach
                </select>
            </div>
        </div></div>

        {{-- Window + toggles --}}
        <div class="card" style="margin-bottom:1rem"><div class="card-body">
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
                    <label style="display:flex;gap:.5rem;align-items:center">
                        <input type="hidden" name="{{ $name }}" value="0">
                        <input type="checkbox" name="{{ $name }}" value="1" @checked(old($name, $editing ? $announcement->$name : false))>
                        {{ $label }}
                    </label>
                @endforeach
            </div>
        </div></div>

        <div style="display:flex;gap:.6rem;flex-wrap:wrap;margin-bottom:2rem">
            <button type="submit" class="btn btn-outline-secondary" onclick="document.getElementById('annAction').value='draft'">
                <x-svg-icon name="save" :size="16" /> حفظ كمسودة
            </button>
            <button type="submit" class="btn btn-primary" onclick="document.getElementById('annAction').value='publish'">
                <x-svg-icon name="megaphone-fill" :size="16" /> نشر
            </button>
            <a href="{{ route('admin.announcements.index') }}" class="btn btn-light"><x-svg-icon name="arrow-right" :size="16" /> عودة</a>
        </div>
    </form>
</section>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/tinymce@6/tinymce.min.js" referrerpolicy="origin" onerror="window.__noTiny=true"></script>
<script>
(function () {
    // Conditional target fields
    var tt = document.getElementById('annTargetType');
    function syncCond() {
        var v = tt.value;
        document.querySelectorAll('.ann-cond').forEach(function (el) {
            el.style.display = (el.dataset.show === v) ? '' : 'none';
        });
    }
    if (tt) { tt.addEventListener('change', syncCond); syncCond(); }

    // Progressive rich-text enhancement (textarea stays the source of truth)
    if (window.tinymce && !window.__noTiny) {
        tinymce.init({
            selector: '#annBody',
            directionality: 'rtl',
            menubar: false,
            height: 280,
            plugins: 'lists link',
            toolbar: 'undo redo | bold italic underline | bullist numlist | link',
            setup: function (ed) {
                ed.on('change', function () { ed.save(); });
            }
        });
        document.getElementById('announcementForm').addEventListener('submit', function () {
            if (window.tinymce) tinymce.triggerSave();
        });
    }
})();
</script>
@endpush
