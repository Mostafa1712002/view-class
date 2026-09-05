@extends('layouts.app')
@section('body_class','theme-light')
@section('title', 'إدارة رسائل غياب الطالب')
@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-12 col-12 mb-2">
        <h2 class="content-header-title mb-0">إدارة رسائل غياب الطالب</h2>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin.student-attendance.follow-up') }}">المتابعة</a></li>
            <li class="breadcrumb-item active">رسائل الغياب</li>
        </ol>
    </div>
</div>

<div class="content-body">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

    @unless($hasSchoolScope)
        <div class="alert alert-warning">اختر مدرسة من محدّد النطاق أعلى الصفحة لعرض/حفظ إعدادات هذه المدرسة.</div>
    @endif

    {{-- الإعدادات: وضع الإرسال + وقت انتهاء اليوم الدراسي + نص الرسالة --}}
    <div class="card mb-3">
        <div class="card-header"><h5 class="mb-0">الإعدادات</h5></div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.student-attendance.absence-messages.settings.save') }}" class="mb-4" id="settingsForm">
                @csrf
                <div class="form-group">
                    <label class="d-block">طريقة الإرسال</label>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="mode" id="modeAuto" value="auto" {{ $mode==='auto'?'checked':'' }}>
                        <label class="form-check-label" for="modeAuto">إرسال تلقائي بعد انتهاء اليوم الدراسي</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="mode" id="modeManual" value="manual" {{ $mode==='manual'?'checked':'' }}>
                        <label class="form-check-label" for="modeManual">إرسال يدوي</label>
                    </div>
                </div>
                <div class="form-group" id="sendTimeGroup" style="max-width:220px;{{ $mode==='auto'?'':'display:none;' }}">
                    <label>وقت انتهاء اليوم الدراسي</label>
                    <input type="time" name="send_time" class="form-control" value="{{ $sendTime }}">
                </div>
                <button class="btn btn-primary" {{ $hasSchoolScope ? '' : 'disabled' }}><x-svg-icon name="check2-square" /> حفظ الإعدادات</button>
            </form>

            <form method="POST" action="{{ route('admin.student-attendance.absence-messages.template.save') }}">
                @csrf
                <div class="form-group">
                    <label>نص الرسالة</label>
                    <textarea name="body" id="tplBody" rows="4" class="form-control" required maxlength="1600">{{ $body }}</textarea>
                </div>
                <div class="mb-2">
                    <small class="text-muted d-block mb-1">أدرج متغيّر:</small>
                    @foreach($variables as $key => $label)
                        <button type="button" class="btn btn-sm btn-outline-secondary mb-1 var-btn" data-var="{{ $key }}">{{ $label }}</button>
                    @endforeach
                </div>
                <button class="btn btn-primary" {{ $hasSchoolScope ? '' : 'disabled' }}><x-svg-icon name="check2-square" /> حفظ نص الرسالة</button>
            </form>
        </div>
    </div>

    {{-- الإرسال اليدوي --}}
    <div class="card">
        <div class="card-header"><h5 class="mb-0">إرسال يدوي</h5></div>
        <div class="card-body">
            <div class="form-row align-items-end mb-3">
                <div class="col-md-2 mb-2">
                    <label>اليوم</label>
                    <input type="date" id="filterDate" class="form-control" value="{{ $today }}">
                </div>
                <div class="col-md-2 mb-2">
                    <label>المجموعة</label>
                    <select multiple id="filterCompound" class="form-control" size="5"></select>
                </div>
                <div class="col-md-2 mb-2">
                    <label>المدارس</label>
                    <select multiple id="filterSchool" class="form-control" size="5"></select>
                </div>
                <div class="col-md-2 mb-2">
                    <label>الصف</label>
                    <select multiple id="filterGrade" class="form-control" size="5"></select>
                </div>
                <div class="col-md-2 mb-2">
                    <label>الفصل</label>
                    <select multiple id="filterClass" class="form-control" size="5"></select>
                </div>
            </div>
            <button type="button" class="btn btn-outline-secondary" id="clearFilter">تفريغ الفلتر</button>
            <button type="button" class="btn btn-success" id="sendAllBtn"><x-svg-icon name="send" /> إرسال الكل</button>
            <button type="button" class="btn btn-primary" id="pickStudentsBtn" data-toggle="modal" data-target="#pickStudentsModal"><x-svg-icon name="people" /> تحديد الطلاب</button>
        </div>
    </div>
</div>

@include('admin.attendance.absence-messages._pick-modal')

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var scope = @json($scopeData);
    var previewUrl = "{{ route('admin.student-attendance.absence-messages.preview') }}";
    var sendUrl = "{{ route('admin.student-attendance.absence-messages.send') }}";
    var csrf = "{{ csrf_token() }}";

    // ---- settings card ----
    document.querySelectorAll('input[name=mode]').forEach(function (r) {
        r.addEventListener('change', function () {
            document.getElementById('sendTimeGroup').style.display = (this.value === 'auto') ? '' : 'none';
        });
    });
    document.querySelectorAll('.var-btn').forEach(function (b) {
        b.addEventListener('click', function () {
            var ta = document.getElementById('tplBody');
            var pos = ta.selectionStart || ta.value.length;
            var token = '{' + b.dataset.var + '}';
            ta.value = ta.value.slice(0, pos) + token + ta.value.slice(pos);
            ta.focus();
        });
    });

    // ---- cascade filter (client-side, no "all" option) ----
    var elCompound = document.getElementById('filterCompound');
    var elSchool = document.getElementById('filterSchool');
    var elGrade = document.getElementById('filterGrade');
    var elClass = document.getElementById('filterClass');

    function fillOptions(el, items) {
        el.innerHTML = '';
        items.forEach(function (it) {
            var o = document.createElement('option');
            o.value = it.id; o.textContent = it.name;
            el.appendChild(o);
        });
    }
    function selected(el) {
        return Array.from(el.selectedOptions).map(function (o) { return parseInt(o.value, 10); });
    }

    fillOptions(elCompound, scope.compounds);
    fillOptions(elSchool, scope.schools);

    function refreshSchools() {
        var compoundIds = selected(elCompound);
        var schools = compoundIds.length
            ? scope.schools.filter(function (s) { return compoundIds.indexOf(s.compound_id) !== -1; })
            : scope.schools;
        fillOptions(elSchool, schools);
        refreshGrades();
    }
    function refreshGrades() {
        var schoolIds = selected(elSchool);
        var classes = schoolIds.length ? scope.classes.filter(function (c) { return schoolIds.indexOf(c.school_id) !== -1; }) : [];
        var grades = [];
        classes.forEach(function (c) {
            if (grades.indexOf(c.grade_level) === -1) grades.push(c.grade_level);
        });
        grades.sort(function (a, b) { return a - b; });
        fillOptions(elGrade, grades.map(function (g) { return { id: g, name: scope.gradeLabels[g] || ('الصف ' + g) }; }));
        refreshClasses();
    }
    function refreshClasses() {
        var schoolIds = selected(elSchool);
        var gradeIds = selected(elGrade);
        var classes = scope.classes.filter(function (c) {
            return schoolIds.length && schoolIds.indexOf(c.school_id) !== -1
                && (!gradeIds.length || gradeIds.indexOf(c.grade_level) !== -1);
        });
        fillOptions(elClass, classes);
    }

    elCompound.addEventListener('change', refreshSchools);
    elSchool.addEventListener('change', refreshGrades);
    elGrade.addEventListener('change', refreshClasses);

    document.getElementById('clearFilter').addEventListener('click', function () {
        [elCompound, elSchool, elGrade, elClass].forEach(function (el) {
            Array.from(el.options).forEach(function (o) { o.selected = false; });
        });
        refreshSchools();
    });

    function currentFilter() {
        return {
            date: document.getElementById('filterDate').value,
            school_ids: selected(elSchool),
            grade_levels: selected(elGrade),
            class_ids: selected(elClass),
        };
    }

    // ---- إرسال الكل ----
    document.getElementById('sendAllBtn').addEventListener('click', function () {
        if (!confirm('سيتم إرسال رسالة الغياب لكل الطلاب الغائبين ضمن الفلتر الحالي. متابعة؟')) return;
        postSend(currentFilter());
    });

    // ---- تحديد الطلاب: يفتح المودال ويجلب الغائبين فقط ----
    document.getElementById('pickStudentsBtn').addEventListener('click', function () {
        var f = currentFilter();
        var qs = new URLSearchParams();
        if (f.date) qs.set('date', f.date);
        f.school_ids.forEach(function (v) { qs.append('school_ids[]', v); });
        f.grade_levels.forEach(function (v) { qs.append('grade_levels[]', v); });
        f.class_ids.forEach(function (v) { qs.append('class_ids[]', v); });

        var tbody = document.querySelector('#pickStudentsModal tbody');
        tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted">جارٍ التحميل...</td></tr>';

        fetch(previewUrl + '?' + qs.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                tbody.innerHTML = '';
                if (!d.students.length) {
                    tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted">لا يوجد طلاب غائبون ضمن هذا الفلتر.</td></tr>';
                    return;
                }
                d.students.forEach(function (s) {
                    var tr = document.createElement('tr');
                    var cb = document.createElement('td');
                    var check = document.createElement('input');
                    check.type = 'checkbox'; check.className = 'pickRow'; check.value = s.student_id;
                    cb.appendChild(check);
                    tr.appendChild(cb);
                    [s.name || '', s.national_id || '—', s.class_name || ''].forEach(function (text) {
                        var td = document.createElement('td');
                        td.textContent = text;
                        tr.appendChild(td);
                    });
                    var statusTd = document.createElement('td');
                    var badge = document.createElement('span');
                    badge.className = 'badge badge-danger';
                    badge.textContent = s.status;
                    statusTd.appendChild(badge);
                    tr.appendChild(statusTd);
                    tbody.appendChild(tr);
                });
            });
    });

    document.getElementById('pickCheckAll').addEventListener('change', function () {
        var ca = this;
        document.querySelectorAll('#pickStudentsModal .pickRow').forEach(function (c) { c.checked = ca.checked; });
    });

    document.getElementById('pickSendBtn').addEventListener('click', function () {
        var ids = Array.from(document.querySelectorAll('#pickStudentsModal .pickRow:checked')).map(function (c) { return parseInt(c.value, 10); });
        if (!ids.length) { alert('اختر طالبًا واحدًا على الأقل.'); return; }
        var f = currentFilter();
        f.student_ids = ids;
        postSend(f);
    });

    function postSend(payload) {
        var form = document.createElement('form');
        form.method = 'POST'; form.action = sendUrl;
        function add(name, value) {
            var i = document.createElement('input'); i.type = 'hidden'; i.name = name; i.value = value;
            form.appendChild(i);
        }
        add('_token', csrf);
        if (payload.date) add('date', payload.date);
        (payload.school_ids || []).forEach(function (v) { add('school_ids[]', v); });
        (payload.grade_levels || []).forEach(function (v) { add('grade_levels[]', v); });
        (payload.class_ids || []).forEach(function (v) { add('class_ids[]', v); });
        (payload.student_ids || []).forEach(function (v) { add('student_ids[]', v); });
        document.body.appendChild(form);
        form.submit();
    }
});
</script>
@endpush
@endsection
