@extends('layouts.app')

@section('title', 'إرسال رسالة واتساب')
@section('page-title', 'إرسال رسالة واتساب')
@section('body_class', 'theme-light')

@php
    $isRtl = app()->getLocale() === 'ar';
    // Audiences that need a "reference" selection (which class / which grade).
    $gradeAudiences = ['grade_students', 'grade_parents'];
    $classAudiences = ['class_students', 'class_parents'];
@endphp

@push('styles')
<style>
    .ws-header { margin-bottom: 1.1rem; }
    .ws-header h2 { font-size: 1.45rem; font-weight: 800; color: #0f172a; margin-bottom: .1rem; }
    .ws-header .breadcrumb { padding: 0; margin: 0; background: transparent; font-size: .85rem; }

    .ws-grid { display: grid; grid-template-columns: 1fr 380px; gap: 1.1rem; align-items: start; }
    @media (max-width: 991.98px) { .ws-grid { grid-template-columns: 1fr; } }

    .ws-card { background:#fff; border:1px solid #e5e7eb; border-radius:14px; box-shadow:0 1px 2px rgba(15,23,42,.04); margin-bottom:1.1rem; }
    .ws-card-head { padding:.85rem 1.1rem; border-bottom:1px solid #f1f5f9; display:flex; align-items:center; gap:.5rem; }
    .ws-card-head h5 { margin:0; font-size:1rem; font-weight:700; color:#0f172a; display:inline-flex; align-items:center; gap:.5rem; }
    .ws-card-head .vc-ico { color:#16a34a; }
    .ws-card-body { padding:1.1rem; }

    /* Message type segmented control */
    .ws-types { display:flex; gap:.5rem; flex-wrap:wrap; }
    .ws-type {
        flex:1 1 0; min-width:110px; cursor:pointer;
        border:1px solid #e2e8f0; border-radius:12px; padding:.7rem .6rem;
        display:flex; align-items:center; justify-content:center; gap:.45rem;
        font-weight:600; color:#475569; background:#fff; transition:all .15s ease;
    }
    .ws-type input { position:absolute; opacity:0; pointer-events:none; }
    .ws-type:hover { border-color:#bbf7d0; color:#15803d; }
    .ws-type.is-active { border-color:#16a34a; background:#f0fdf4; color:#15803d; box-shadow:0 0 0 .15rem rgba(22,163,74,.12); }

    .ws-field { margin-top:1rem; }
    .ws-field > label { display:block; font-weight:600; font-size:.85rem; color:#334155; margin-bottom:.35rem; }
    .ws-help { font-size:.78rem; color:#94a3b8; margin-top:.3rem; }

    .ws-recipients { max-height:320px; overflow:auto; border:1px solid #f1f5f9; border-radius:10px; }
    .ws-recipients table { margin:0; width:100%; }
    .ws-recipients thead th { position:sticky; top:0; background:#f8fafc; font-size:.74rem; text-transform:uppercase; letter-spacing:.4px; color:#64748b; padding:.55rem .7rem; }
    .ws-recipients tbody td { padding:.5rem .7rem; border-top:1px solid #f1f5f9; font-size:.85rem; vertical-align:middle; }

    .ws-stat { display:inline-flex; align-items:center; gap:.3rem; padding:.12rem .5rem; border-radius:999px; font-size:.72rem; font-weight:600; }
    .ws-stat.valid          { background:#dcfce7; color:#15803d; }
    .ws-stat.no_number      { background:#f1f5f9; color:#64748b; }
    .ws-stat.invalid_number { background:#fef2f2; color:#b91c1c; }
    .ws-stat.duplicate      { background:#fef9c3; color:#92400e; }

    .ws-summary { display:grid; grid-template-columns:repeat(2,1fr); gap:.55rem; }
    .ws-summary .box { background:#f8fafc; border:1px solid #eef2f7; border-radius:10px; padding:.6rem .7rem; text-align:center; }
    .ws-summary .box .n { font-size:1.25rem; font-weight:800; color:#0f172a; line-height:1.1; }
    .ws-summary .box .l { font-size:.74rem; color:#64748b; }
    .ws-summary .box.valid .n { color:#15803d; }

    /* specific-users picker */
    .ws-search-results { border:1px solid #e2e8f0; border-radius:10px; margin-top:.4rem; max-height:200px; overflow:auto; display:none; }
    .ws-search-results.is-open { display:block; }
    .ws-search-results .row-item { padding:.5rem .7rem; cursor:pointer; display:flex; justify-content:space-between; gap:.5rem; font-size:.85rem; }
    .ws-search-results .row-item:hover { background:#f0fdf4; }
    .ws-chosen { display:flex; flex-wrap:wrap; gap:.35rem; margin-top:.5rem; }
    .ws-chosen .chip { background:#eef2ff; border:1px solid #c7d2fe; color:#3730a3; border-radius:999px; padding:.15rem .55rem; font-size:.78rem; display:inline-flex; align-items:center; gap:.35rem; }
    .ws-chosen .chip button { border:0; background:transparent; color:#6366f1; cursor:pointer; line-height:1; padding:0; }

    .ws-spinner { display:none; }
    .ws-spinner.is-on { display:inline-flex; }
</style>
@endpush

@section('content')
<section class="ws-wrap" @if($isRtl) dir="rtl" @endif>

    <div class="ws-header" style="display:flex;justify-content:space-between;align-items:flex-end;flex-wrap:wrap;gap:.75rem">
        <div>
            <h2><x-svg-icon name="whatsapp" :size="22" class="ic-success" /> إرسال رسالة واتساب</h2>
            <nav><ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">الرئيسية</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.whatsapp.index') }}">واتساب</a></li>
                <li class="breadcrumb-item active" aria-current="page">إرسال رسالة</li>
            </ol></nav>
        </div>
        <a href="{{ route('admin.whatsapp.logs') }}" class="btn btn-outline-secondary btn-sm">
            <x-svg-icon name="journal-text" :size="15" /> سجل الرسائل
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0" style="padding-inline-start:1.1rem">
                @foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach
            </ul>
        </div>
    @endif

    @if($isAllSchools)
        <div class="alert alert-info" role="alert">
            <x-svg-icon name="info-circle" :size="16" /> أنت تتصفّح كل المدارس (مدير النظام). سيتم استخدام مزوّد السجل (Log) للإرسال.
        </div>
    @elseif($setting && ! $setting->is_enabled)
        <div class="alert alert-warning" role="alert">
            <x-svg-icon name="exclamation-triangle" :size="16" /> خدمة واتساب غير مفعّلة لهذه المدرسة.
            <a href="{{ route('admin.whatsapp.edit', $school) }}">فعّلها من الإعدادات</a> قبل الإرسال.
        </div>
    @endif

    <form method="POST" action="{{ route('admin.whatsapp.send.store') }}" enctype="multipart/form-data" id="ws-form"
          data-resolve-url="{{ route('admin.whatsapp.recipients.resolve') }}"
          data-search-url="{{ route('admin.whatsapp.recipients.search') }}">
        @csrf

        <div class="ws-grid">
            {{-- ============ LEFT: message + audience ============ --}}
            <div>
                {{-- Message --}}
                <div class="ws-card">
                    <div class="ws-card-head"><h5><x-svg-icon name="chat-dots" :size="18" /> الرسالة</h5></div>
                    <div class="ws-card-body">
                        <label class="d-block mb-2" style="font-weight:600;font-size:.85rem;color:#334155">نوع الرسالة</label>
                        <div class="ws-types" role="radiogroup" aria-label="نوع الرسالة">
                            <label class="ws-type is-active" data-type="text">
                                <input type="radio" name="message_type" value="text" checked>
                                <x-svg-icon name="chat-text" :size="16" /> نص
                            </label>
                            <label class="ws-type" data-type="image">
                                <input type="radio" name="message_type" value="image">
                                <x-svg-icon name="image" :size="16" /> صورة
                            </label>
                            <label class="ws-type" data-type="pdf">
                                <input type="radio" name="message_type" value="pdf">
                                <x-svg-icon name="file-earmark-pdf" :size="16" /> ملف PDF
                            </label>
                        </div>

                        {{-- body --}}
                        <div class="ws-field" id="ws-body-field">
                            <label for="ws-body">نص الرسالة</label>
                            <textarea name="body" id="ws-body" rows="5" maxlength="4000" class="form-control"
                                      placeholder="اكتب نص الرسالة…">{{ old('body') }}</textarea>
                            <div class="ws-help">حتى 4000 حرف. <span id="ws-body-count">0</span>/4000</div>
                        </div>

                        {{-- image --}}
                        <div class="ws-field d-none" id="ws-image-field">
                            <label for="ws-image">الصورة</label>
                            <input type="file" name="image" id="ws-image" class="form-control" accept=".jpg,.jpeg,.png,.webp">
                            <div class="ws-help">jpg / jpeg / png / webp — حتى 5 ميجابايت. يمكنك إضافة نص أسفلها (اختياري).</div>
                            <textarea name="body" rows="2" class="form-control mt-2" maxlength="4000"
                                      placeholder="نص مرافق للصورة (اختياري)" form="ws-form" id="ws-image-caption"></textarea>
                        </div>

                        {{-- pdf --}}
                        <div class="ws-field d-none" id="ws-pdf-field">
                            <label for="ws-pdf">ملف PDF</label>
                            <input type="file" name="pdf" id="ws-pdf" class="form-control" accept=".pdf">
                            <div class="ws-help">PDF فقط — حتى 10 ميجابايت. يمكنك إضافة نص مرافق (اختياري).</div>
                            <textarea name="body" rows="2" class="form-control mt-2" maxlength="4000"
                                      placeholder="نص مرافق للملف (اختياري)" id="ws-pdf-caption"></textarea>
                        </div>
                    </div>
                </div>

                {{-- Audience --}}
                <div class="ws-card">
                    <div class="ws-card-head"><h5><x-svg-icon name="people" :size="18" /> المستلمون</h5></div>
                    <div class="ws-card-body">
                        <div class="ws-field" style="margin-top:0">
                            <label for="ws-audience">المجموعة</label>
                            <select name="audience" id="ws-audience" class="form-select">
                                @foreach($audiences as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- grade ref --}}
                        <div class="ws-field d-none" id="ws-grade-field">
                            <label for="ws-grade">الصف</label>
                            <select id="ws-grade" class="form-select">
                                <option value="">— اختر الصف —</option>
                                @foreach($gradeLevels as $g)
                                    <option value="{{ $g }}">الصف {{ $g }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- class ref --}}
                        <div class="ws-field d-none" id="ws-class-field">
                            <label for="ws-class">الفصل</label>
                            <select id="ws-class" class="form-select">
                                <option value="">— اختر الفصل —</option>
                                @foreach($classes as $c)
                                    <option value="{{ $c->id }}">{{ $c->name }}{{ $c->section?->name ? ' — '.$c->section->name : '' }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- specific users --}}
                        <div class="ws-field d-none" id="ws-specific-field">
                            <label for="ws-user-search">بحث عن مستخدمين</label>
                            <input type="text" id="ws-user-search" class="form-control" placeholder="اكتب اسمًا أو رقمًا…" autocomplete="off">
                            <div class="ws-search-results" id="ws-search-results"></div>
                            <div class="ws-chosen" id="ws-chosen"></div>
                            <div class="ws-help">اختر مستخدمًا واحدًا على الأقل ثم اضغط «عرض المستلمين».</div>
                        </div>

                        <div class="ws-field">
                            <button type="button" class="btn btn-outline-success btn-sm" id="ws-resolve-btn">
                                <span class="ws-spinner spinner-border spinner-border-sm me-1" id="ws-resolve-spin"></span>
                                <x-svg-icon name="search" :size="15" /> عرض المستلمين
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ============ RIGHT: recipient preview + send ============ --}}
            <div>
                <div class="ws-card">
                    <div class="ws-card-head"><h5><x-svg-icon name="card-checklist" :size="18" /> المستلمون المحددون</h5></div>
                    <div class="ws-card-body">
                        <div class="ws-summary mb-2">
                            <div class="box valid"><div class="n" id="ws-cnt-valid">0</div><div class="l">أرقام صالحة</div></div>
                            <div class="box"><div class="n" id="ws-cnt-total">0</div><div class="l">إجمالي</div></div>
                        </div>

                        <div class="ws-recipients" id="ws-recipients-wrap" style="display:none">
                            <table>
                                <thead><tr><th>الاسم</th><th>الدور</th><th>الرقم</th><th>الحالة</th></tr></thead>
                                <tbody id="ws-recipients-body"></tbody>
                            </table>
                        </div>

                        <div id="ws-recipients-empty" class="text-center text-muted py-4" style="font-size:.85rem">
                            <x-svg-icon name="inbox" :size="28" class="ic-muted" />
                            <div class="mt-1">اختر مجموعة واضغط «عرض المستلمين».</div>
                        </div>

                        {{-- hidden recipient ids injected here --}}
                        <div id="ws-recipient-ids"></div>

                        <button type="submit" class="btn btn-success w-100 mt-3" id="ws-submit" disabled>
                            <x-svg-icon name="send" :size="16" /> إرسال البث
                        </button>
                        <div class="ws-help text-center mt-1">يُعاد التحقق من المستلمين والأرقام على الخادم قبل الإرسال.</div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</section>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var form        = document.getElementById('ws-form');
    var resolveUrl  = form.dataset.resolveUrl;
    var searchUrl   = form.dataset.searchUrl;
    var token       = document.querySelector('input[name="_token"]').value;

    var audienceSel = document.getElementById('ws-audience');
    var gradeField  = document.getElementById('ws-grade-field');
    var classField  = document.getElementById('ws-class-field');
    var specField   = document.getElementById('ws-specific-field');
    var gradeSel    = document.getElementById('ws-grade');
    var classSel    = document.getElementById('ws-class');

    var resolveBtn  = document.getElementById('ws-resolve-btn');
    var resolveSpin = document.getElementById('ws-resolve-spin');
    var recipBody   = document.getElementById('ws-recipients-body');
    var recipWrap   = document.getElementById('ws-recipients-wrap');
    var recipEmpty  = document.getElementById('ws-recipients-empty');
    var idsHolder   = document.getElementById('ws-recipient-ids');
    var submitBtn   = document.getElementById('ws-submit');
    var cntValid    = document.getElementById('ws-cnt-valid');
    var cntTotal    = document.getElementById('ws-cnt-total');

    var GRADE = ['grade_students', 'grade_parents'];
    var CLASS = ['class_students', 'class_parents'];

    var chosenUsers = {}; // id -> name (specific_users)

    // ---- message type toggle ----
    document.querySelectorAll('.ws-type').forEach(function (lbl) {
        lbl.addEventListener('click', function () {
            document.querySelectorAll('.ws-type').forEach(function (l) { l.classList.remove('is-active'); });
            lbl.classList.add('is-active');
            var type = lbl.dataset.type;
            // Toggle exactly one <textarea name="body"> active so the request has a single body.
            toggle('ws-body-field',  type === 'text');
            toggle('ws-image-field', type === 'image');
            toggle('ws-pdf-field',   type === 'pdf');
            // disable the inactive body inputs so only one name="body" is submitted
            document.getElementById('ws-body').disabled         = (type !== 'text');
            document.getElementById('ws-image-caption').disabled = (type !== 'image');
            document.getElementById('ws-pdf-caption').disabled   = (type !== 'pdf');
        });
    });
    // init disabled state (only text body active)
    document.getElementById('ws-image-caption').disabled = true;
    document.getElementById('ws-pdf-caption').disabled   = true;

    // ---- body counter ----
    var body = document.getElementById('ws-body');
    var cnt  = document.getElementById('ws-body-count');
    function updateCount() { cnt.textContent = body.value.length; }
    body.addEventListener('input', updateCount); updateCount();

    // ---- audience ref toggle ----
    function syncAudience() {
        var a = audienceSel.value;
        toggle('ws-grade-field',    GRADE.indexOf(a) !== -1);
        toggle('ws-class-field',    CLASS.indexOf(a) !== -1);
        toggle('ws-specific-field', a === 'specific_users');
        clearRecipients();
    }
    audienceSel.addEventListener('change', syncAudience);

    function toggle(id, show) { document.getElementById(id).classList.toggle('d-none', !show); }

    // ---- specific-users search ----
    var searchInput = document.getElementById('ws-user-search');
    var searchBox   = document.getElementById('ws-search-results');
    var chosenBox   = document.getElementById('ws-chosen');
    var searchTimer = null;

    searchInput && searchInput.addEventListener('input', function () {
        clearTimeout(searchTimer);
        var q = searchInput.value.trim();
        if (q.length < 2) { searchBox.classList.remove('is-open'); searchBox.innerHTML = ''; return; }
        searchTimer = setTimeout(function () {
            fetch(searchUrl + '?q=' + encodeURIComponent(q), { headers: { 'Accept': 'application/json' } })
                .then(function (r) { return r.json(); })
                .then(function (res) {
                    searchBox.innerHTML = '';
                    (res.data || []).forEach(function (u) {
                        var div = document.createElement('div');
                        div.className = 'row-item';
                        div.innerHTML = '<span>' + escapeHtml(u.name) + '</span><span class="text-muted">' + escapeHtml(u.role || '') + '</span>';
                        div.addEventListener('click', function () { addChosen(u.id, u.name); });
                        searchBox.appendChild(div);
                    });
                    searchBox.classList.add('is-open');
                });
        }, 250);
    });

    function addChosen(id, name) {
        if (chosenUsers[id]) return;
        chosenUsers[id] = name;
        renderChosen();
        searchBox.classList.remove('is-open');
        searchInput.value = '';
    }
    function renderChosen() {
        chosenBox.innerHTML = '';
        Object.keys(chosenUsers).forEach(function (id) {
            var chip = document.createElement('span');
            chip.className = 'chip';
            chip.innerHTML = '<span>' + escapeHtml(chosenUsers[id]) + '</span>';
            var btn = document.createElement('button');
            btn.type = 'button'; btn.textContent = '×';
            btn.addEventListener('click', function () { delete chosenUsers[id]; renderChosen(); });
            chip.appendChild(btn);
            chosenBox.appendChild(chip);
        });
    }

    // ---- resolve recipients ----
    resolveBtn.addEventListener('click', function () {
        var a = audienceSel.value;
        var payload = { audience: a };
        if (GRADE.indexOf(a) !== -1) {
            if (!gradeSel.value) { alert('اختر الصف أولاً.'); return; }
            payload.ref_id = gradeSel.value;
        } else if (CLASS.indexOf(a) !== -1) {
            if (!classSel.value) { alert('اختر الفصل أولاً.'); return; }
            payload.ref_id = classSel.value;
        } else if (a === 'specific_users') {
            var ids = Object.keys(chosenUsers).map(Number);
            if (!ids.length) { alert('اختر مستخدمًا واحدًا على الأقل.'); return; }
            payload.user_ids = ids;
        }

        resolveSpin.classList.add('is-on');
        resolveBtn.disabled = true;

        fetch(resolveUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token },
            body: JSON.stringify(payload),
        })
        .then(function (r) { return r.json(); })
        .then(function (res) { renderRecipients(res.data || []); })
        .catch(function () { alert('تعذّر جلب المستلمين. حاول مرة أخرى.'); })
        .finally(function () { resolveSpin.classList.remove('is-on'); resolveBtn.disabled = false; });
    });

    function renderRecipients(list) {
        recipBody.innerHTML = '';
        idsHolder.innerHTML = '';
        var valid = 0;

        list.forEach(function (rcp) {
            var tr = document.createElement('tr');
            tr.innerHTML =
                '<td>' + escapeHtml(rcp.name) + '</td>' +
                '<td>' + escapeHtml(rcp.role || '') + '</td>' +
                '<td dir="ltr" style="text-align:start">' + escapeHtml(rcp.number || '—') + '</td>' +
                '<td><span class="ws-stat ' + rcp.number_status + '">' + escapeHtml(rcp.number_status_label) + '</span></td>';
            recipBody.appendChild(tr);

            // submit every resolved id — the server re-resolves and accounts for skips/dupes.
            var inp = document.createElement('input');
            inp.type = 'hidden'; inp.name = 'recipient_ids[]'; inp.value = rcp.id;
            idsHolder.appendChild(inp);

            if (rcp.number_status === 'valid') valid++;
        });

        cntValid.textContent = valid;
        cntTotal.textContent = list.length;

        if (list.length) {
            recipWrap.style.display = '';
            recipEmpty.style.display = 'none';
            submitBtn.disabled = false;
        } else {
            clearRecipients();
            recipEmpty.textContent = 'لا يوجد مستلمون لهذه المجموعة.';
        }
    }

    function clearRecipients() {
        recipBody.innerHTML = '';
        idsHolder.innerHTML = '';
        cntValid.textContent = '0';
        cntTotal.textContent = '0';
        recipWrap.style.display = 'none';
        recipEmpty.style.display = '';
        submitBtn.disabled = true;
    }

    function escapeHtml(s) {
        return String(s == null ? '' : s)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }

    // close search dropdown on outside click
    document.addEventListener('click', function (e) {
        if (specField && !specField.contains(e.target)) { searchBox.classList.remove('is-open'); }
    });

    syncAudience();
});
</script>
@endsection
