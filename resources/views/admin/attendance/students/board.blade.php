@extends('layouts.app')
@section('body_class','theme-light')
@section('title', 'الحضور والغياب — الطلاب')
@section('content')
@php
    $statusLabels = ['present'=>'حاضر','absent'=>'غائب','late'=>'متأخر','excused'=>'مستأذن'];
    $statusColors = ['present'=>'success','absent'=>'danger','late'=>'warning','excused'=>'info'];
    $canNote   = auth()->user()?->canDo('attendance.add_note');
    $canExcuse = auth()->user()?->canDo('attendance.add_excuse');
@endphp
<div class="content-header row">
    <div class="content-header-left col-md-7 col-12 mb-2">
        <h2 class="content-header-title mb-0">الحضور والغياب — الطلاب</h2>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
            <li class="breadcrumb-item active">الحضور والغياب</li>
        </ol>
    </div>
    <div class="content-header-right col-md-5 col-12 text-md-right">
        <a href="{{ route('admin.student-attendance.follow-up') }}" class="btn btn-outline-secondary btn-sm">
            <x-svg-icon name="bell" /> تقارير المستخدمين
        </a>
    </div>
</div>

<div class="content-body">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

    {{-- Tabs --}}
    <ul class="nav nav-tabs mb-3">
        <li class="nav-item">
            <a class="nav-link {{ $mode === 'daily' ? 'active' : '' }}" href="{{ route('admin.student-attendance.daily') }}">
                <x-svg-icon name="calendar-day" /> إدارة حضور وغياب يومي
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $mode === 'period' ? 'active' : '' }}" href="{{ route('admin.student-attendance.period') }}">
                <x-svg-icon name="clock" /> إدارة حضور وغياب حصة
            </a>
        </li>
    </ul>

    {{-- Stat cards --}}
    <div class="row mb-3">
        @php $cards = [['حضور','present','success','check-circle'],['غياب','absent','danger','x-circle'],['تأخير','late','warning','clock'],['استئذان','excused','info','person-check']]; @endphp
        @foreach($cards as [$lbl,$key,$col,$ic])
        <div class="col-md-3 col-6 mb-2">
            <div class="card border-{{ $col }}">
                <div class="card-body d-flex align-items-center justify-content-between py-2">
                    <div><div class="text-muted small">إجمالي ال{{ $lbl }}</div><h3 class="mb-0 text-{{ $col }}" data-stat="{{ $key }}">{{ $counts[$key] }}</h3></div>
                    <x-svg-icon :name="$ic" :size="28" class="text-{{ $col }}" />
                </div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- Filters --}}
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ $mode === 'daily' ? route('admin.student-attendance.daily') : route('admin.student-attendance.period') }}" class="form-row align-items-end">
                <div class="col-md-2 mb-2">
                    <label>التاريخ</label>
                    <input type="date" name="date" value="{{ $date }}" class="form-control">
                </div>
                <div class="col-md-3 mb-2">
                    <label>الصف / الفصل</label>
                    <select name="class_id" class="form-control">
                        <option value="">— اختر الفصل —</option>
                        @foreach($classes as $c)
                            <option value="{{ $c->id }}" {{ (string)request('class_id')===(string)$c->id ? 'selected' : '' }}>{{ $c->name }} ({{ $c->division }})</option>
                        @endforeach
                    </select>
                </div>
                @if($mode === 'period')
                <div class="col-md-2 mb-2">
                    <label>المادة</label>
                    <select name="subject_id" class="form-control">
                        <option value="">— المادة —</option>
                        @foreach($subjects as $s)
                            <option value="{{ $s->id }}" {{ (string)request('subject_id')===(string)$s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-1 mb-2">
                    <label>الحصة</label>
                    <input type="number" name="period" min="1" max="12" value="{{ $period }}" class="form-control">
                </div>
                @endif
                <div class="col-md-2 mb-2">
                    <label>اسم الطالب</label>
                    <input type="text" name="name" value="{{ request('name') }}" class="form-control" placeholder="بحث بالاسم">
                </div>
                <div class="col-md-2 mb-2">
                    <label>رقم الهوية</label>
                    <input type="text" name="national_id" value="{{ request('national_id') }}" class="form-control" placeholder="رقم الهوية">
                </div>
                <div class="col-md-2 mb-2">
                    <button class="btn btn-primary"><x-svg-icon name="search" /> بحث</button>
                </div>
            </form>
        </div>
    </div>

    @if($selectedClass)
    <form method="POST" action="{{ route('admin.student-attendance.store') }}" id="attendanceForm">
        @csrf
        <input type="hidden" name="class_id" value="{{ $selectedClass->id }}">
        <input type="hidden" name="date" value="{{ $date }}">
        @if($mode === 'period')
            <input type="hidden" name="period" value="{{ $period }}">
            <input type="hidden" name="subject_id" value="{{ request('subject_id') }}">
        @endif

        {{-- Bulk bar --}}
        <div class="card mb-2"><div class="card-body py-2 d-flex flex-wrap align-items-center" style="gap:.5rem">
            <span class="text-muted small">إجراء جماعي على المحدد:</span>
            @foreach(['present'=>'حاضر','absent'=>'غائب','late'=>'متأخر','excused'=>'مستأذن'] as $k=>$v)
                <button type="button" class="btn btn-sm btn-outline-{{ $statusColors[$k] }} js-bulk" data-status="{{ $k }}">{{ $v }}</button>
            @endforeach
        </div></div>

        <div class="card">
            <div class="card-body table-responsive">
                @if($rows->isEmpty())
                    <div class="ds-empty">
                        <div class="ds-empty-icon"><x-svg-icon name="person-slash" :size="32" /></div>
                        <div class="ds-empty-title">لا يوجد طلاب</div>
                        <div class="ds-empty-desc">لا يوجد طلاب في هذا الفصل أو لا تتطابق نتائج البحث.</div>
                    </div>
                @else
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="checkAll"></th>
                            <th>#</th>
                            <th>الصورة</th>
                            <th>اسم الطالب</th>
                            <th>الرقم الأكاديمي</th>
                            <th>رقم الهوية</th>
                            <th>أيام الحضور</th>
                            <th>الحالة</th>
                            <th>عذر</th>
                            <th>ملاحظات</th>
                            <th>التحكم</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rows as $i => $row)
                        @php $st = $row['student']; @endphp
                        <tr data-student="{{ $st->id }}" data-attendance-id="{{ $row['attendance_id'] }}">
                            <td><input type="checkbox" class="rowCheck" value="{{ $st->id }}"></td>
                            <td>{{ $i + 1 }}</td>
                            <td>
                                @if($st->avatar)
                                    <img src="{{ asset('storage/'.$st->avatar) }}" class="rounded-circle" width="36" height="36" alt="">
                                @else
                                    <span class="badge badge-light rounded-circle p-2"><x-svg-icon name="person" /></span>
                                @endif
                            </td>
                            <td>{{ $st->name }}</td>
                            <td>{{ $st->employee_id ?? '—' }}</td>
                            <td>{{ $st->national_id ?? '—' }}</td>
                            <td><span class="badge badge-light">{{ $row['present_days'] }}</span></td>
                            <td>
                                <input type="hidden" name="rows[{{ $i }}][student_id]" value="{{ $st->id }}">
                                <span class="status-badge badge badge-{{ $statusColors[$row['status']] ?? 'secondary' }}">
                                    {{ $statusLabels[$row['status']] ?? 'لم يتم التسجيل' }}
                                </span>
                                <input type="hidden" class="status-input" name="rows[{{ $i }}][status]" value="{{ $row['status'] ?? 'present' }}">
                                <div class="btn-group btn-group-sm mt-1" role="group">
                                    @foreach(['present'=>['حاضر','success','check-lg'],'absent'=>['غائب','danger','x-lg'],'late'=>['متأخر','warning','clock'],'excused'=>['مستأذن','info','person-check']] as $k=>[$lbl,$col,$ic])
                                        <button type="button" class="btn btn-outline-{{ $col }} js-status" data-status="{{ $k }}" title="{{ $lbl }}"><x-svg-icon :name="$ic" :size="16" /></button>
                                    @endforeach
                                </div>
                            </td>
                            <td style="min-width:170px">
                                @if($canExcuse)
                                    <input type="text" class="form-control form-control-sm js-excuse" value="{{ $row['excuse_text'] }}" data-orig="{{ $row['excuse_text'] }}" placeholder="اكتب العذر…" title="يحدّد الحالة كمستأذن عند الحفظ">
                                    <span class="inline-save small"></span>
                                @elseif($row['excuse_status'])
                                    <span class="badge badge-{{ $row['excuse_status']==='accepted'?'success':($row['excuse_status']==='rejected'?'danger':'warning') }}">{{ ['pending'=>'قيد المراجعة','accepted'=>'مقبول','rejected'=>'مرفوض'][$row['excuse_status']] }}</span>
                                @else — @endif
                            </td>
                            <td style="min-width:170px">
                                @if($canNote)
                                    <input type="text" class="form-control form-control-sm js-note" name="rows[{{ $i }}][notes]" value="{{ $row['notes'] }}" data-orig="{{ $row['notes'] }}" placeholder="ملاحظة…">
                                    <span class="inline-save small"></span>
                                @else
                                    <span class="small text-muted">{{ \Illuminate\Support\Str::limit($row['notes'], 30) ?: '—' }}</span>
                                @endif
                            </td>
                            <td>
                                @if($row['attendance_id'])
                                <a href="{{ route('admin.users.students.attendance', $st->id) }}" class="btn btn-sm btn-link" title="سجل الحضور"><x-svg-icon name="clock-history" /></a>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @endif
            </div>
            @if($rows->isNotEmpty())
            <div class="card-footer text-left">
                <button type="submit" class="btn btn-primary"><x-svg-icon name="save" /> حفظ الحضور</button>
            </div>
            @endif
        </div>
    </form>

    {{-- Hidden bulk form --}}
    <form method="POST" action="{{ route('admin.student-attendance.bulk') }}" id="bulkForm" class="d-none">
        @csrf
        <input type="hidden" name="class_id" value="{{ $selectedClass->id }}">
        <input type="hidden" name="date" value="{{ $date }}">
        @if($mode === 'period')<input type="hidden" name="period" value="{{ $period }}"><input type="hidden" name="subject_id" value="{{ request('subject_id') }}">@endif
        <input type="hidden" name="status" id="bulkStatus">
        <div id="bulkIds"></div>
    </form>
    @else
        <div class="card"><div class="card-body">
            <div class="ds-empty">
                <div class="ds-empty-icon"><x-svg-icon name="search" :size="32" /></div>
                <div class="ds-empty-title">ابدأ بتحديد الفصل</div>
                <div class="ds-empty-desc">اختر الفصل والتاريخ ثم اضغط "بحث" لعرض الطلاب.</div>
            </div>
        </div></div>
    @endif
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var labels = {present:'حاضر',absent:'غائب',late:'متأخر',excused:'مستأذن'};
    var colors = {present:'success',absent:'danger',late:'warning',excused:'info'};
    // Individual status buttons
    document.querySelectorAll('.js-status').forEach(function (b) {
        b.addEventListener('click', function () {
            var tr = b.closest('tr'); var s = b.dataset.status;
            tr.querySelector('.status-input').value = s;
            var badge = tr.querySelector('.status-badge');
            badge.textContent = labels[s];
            badge.className = 'status-badge badge badge-' + colors[s];
        });
    });
    // Check all
    var ca = document.getElementById('checkAll');
    if (ca) ca.addEventListener('change', function () {
        document.querySelectorAll('.rowCheck').forEach(function (c) { c.checked = ca.checked; });
    });
    // Bulk
    document.querySelectorAll('.js-bulk').forEach(function (b) {
        b.addEventListener('click', function () {
            var ids = Array.from(document.querySelectorAll('.rowCheck:checked')).map(function (c) { return c.value; });
            if (!ids.length) { alert('يرجى تحديد طالب واحد على الأقل.'); return; }
            if (!confirm('تطبيق الحالة "' + labels[b.dataset.status] + '" على ' + ids.length + ' طالب؟')) return;
            document.getElementById('bulkStatus').value = b.dataset.status;
            var box = document.getElementById('bulkIds'); box.innerHTML = '';
            ids.forEach(function (id) {
                var inp = document.createElement('input'); inp.type = 'hidden'; inp.name = 'student_ids[]'; inp.value = id; box.appendChild(inp);
            });
            document.getElementById('bulkForm').submit();
        });
    });

    // Inline excuse/notes auto-save (#261)
    var form = document.getElementById('attendanceForm');
    if (form) {
        var token = form.querySelector('[name="_token"]').value;
        var base  = '{{ url('admin/attendance/students') }}';

        function flash(input, ok, msg) {
            var fb = input.parentElement.querySelector('.inline-save');
            input.classList.remove('is-valid', 'is-invalid');
            input.classList.add(ok ? 'is-valid' : 'is-invalid');
            if (fb) { fb.textContent = msg || (ok ? '✓ تم الحفظ' : '✗ تعذّر الحفظ'); fb.className = 'inline-save small ' + (ok ? 'text-success' : 'text-danger'); }
            if (ok) setTimeout(function () { input.classList.remove('is-valid'); if (fb) fb.textContent = ''; }, 2500);
        }
        function post(url, params) {
            return fetch(url, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json', 'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-TOKEN': token },
                body: params.toString()
            });
        }
        // Create a row via store when none exists yet; resolves to the new attendance id.
        function ensureRow(tr, status) {
            if (tr.dataset.attendanceId) return Promise.resolve(tr.dataset.attendanceId);
            var sid = tr.dataset.student;
            var p = new URLSearchParams();
            p.append('_token', token);
            p.append('class_id', form.querySelector('[name="class_id"]').value);
            p.append('date', form.querySelector('[name="date"]').value);
            var per = form.querySelector('[name="period"]'); if (per) p.append('period', per.value);
            var subj = form.querySelector('[name="subject_id"]'); if (subj) p.append('subject_id', subj.value);
            p.append('rows[0][student_id]', sid);
            p.append('rows[0][status]', status);
            return post(base + '/store', p).then(function (r) { return r.ok ? r.json() : null; })
                .then(function (d) { var id = d && d.ids ? d.ids[sid] : null; if (id) tr.dataset.attendanceId = id; return id; });
        }
        function saveNote(inp) {
            if (inp.value === inp.dataset.orig) return;
            var tr = inp.closest('tr');
            if (!tr.dataset.attendanceId) { flash(inp, true, 'سيُحفظ مع الحضور'); inp.dataset.orig = inp.value; return; }
            var p = new URLSearchParams(); p.append('_token', token); p.append('notes', inp.value);
            post(base + '/' + tr.dataset.attendanceId + '/note', p)
                .then(function (r) { if (r.ok) { flash(inp, true); inp.dataset.orig = inp.value; } else flash(inp, false); })
                .catch(function () { flash(inp, false); });
        }
        function saveExcuse(inp) {
            if (inp.value === inp.dataset.orig) return;
            if (inp.value.trim().length < 3) { flash(inp, false, 'العذر 3 أحرف على الأقل'); return; }
            var tr = inp.closest('tr');
            ensureRow(tr, 'excused').then(function (id) {
                if (!id) { flash(inp, false); return; }
                var p = new URLSearchParams(); p.append('_token', token); p.append('excuse_text', inp.value);
                return post(base + '/' + id + '/excuse', p).then(function (r) {
                    if (!r.ok) { flash(inp, false); return; }
                    flash(inp, true); inp.dataset.orig = inp.value;
                    var si = tr.querySelector('.status-input'); if (si) si.value = 'excused';
                    var badge = tr.querySelector('.status-badge'); if (badge) { badge.textContent = labels.excused; badge.className = 'status-badge badge badge-' + colors.excused; }
                });
            }).catch(function () { flash(inp, false); });
        }
        function wire(sel, fn) {
            document.querySelectorAll(sel).forEach(function (inp) {
                inp.addEventListener('blur', function () { fn(inp); });
                inp.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); inp.blur(); } });
            });
        }
        wire('.js-note', saveNote);
        wire('.js-excuse', saveExcuse);
    }
});
</script>
@endpush
@endsection
