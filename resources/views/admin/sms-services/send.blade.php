@extends('layouts.app')

@section('title', 'إرسال رسالة قصيرة')
@section('page-title', 'إرسال رسالة قصيرة')
@section('body_class', 'theme-light')

@php
    $gradeAudiences = array_filter(array_keys((array)$audiences), fn($k) => str_starts_with($k, 'grade_'));
    $classAudiences = array_filter(array_keys((array)$audiences), fn($k) => str_starts_with($k, 'class_'));
@endphp

@push('styles')
<style>
    .sms-header { margin-bottom: 1.1rem; }
    .sms-header h2 { font-size: 1.45rem; font-weight: 800; color: #0f172a; margin-bottom: .1rem; }
    .sms-header .breadcrumb { padding: 0; margin: 0; background: transparent; font-size: .85rem; }

    .sms-grid { display: grid; grid-template-columns: 1fr 380px; gap: 1.1rem; align-items: start; }
    @media (max-width: 991.98px) { .sms-grid { grid-template-columns: 1fr; } }

    .sms-card { background:#fff; border:1px solid #e5e7eb; border-radius:14px; box-shadow:0 1px 2px rgba(15,23,42,.04); margin-bottom:1.1rem; }
    .sms-card-head { padding:.85rem 1.1rem; border-bottom:1px solid #f1f5f9; display:flex; align-items:center; gap:.5rem; }
    .sms-card-head h5 { margin:0; font-size:1rem; font-weight:700; color:#0f172a; display:inline-flex; align-items:center; gap:.5rem; }
    .sms-card-body { padding:1.1rem; }

    .sms-field { margin-top:1rem; }
    .sms-field > label { display:block; font-weight:600; font-size:.85rem; color:#334155; margin-bottom:.35rem; }
    .sms-help { font-size:.78rem; color:#94a3b8; margin-top:.3rem; }

    .sms-recipients { max-height:320px; overflow:auto; border:1px solid #f1f5f9; border-radius:10px; }
    .sms-recipients table { margin:0; width:100%; }
    .sms-recipients thead th { position:sticky; top:0; background:#f8fafc; font-size:.74rem; text-transform:uppercase; letter-spacing:.4px; color:#64748b; padding:.55rem .7rem; }
    .sms-recipients tbody td { padding:.5rem .7rem; border-top:1px solid #f1f5f9; font-size:.85rem; vertical-align:middle; }

    .sms-stat { display:inline-flex; align-items:center; gap:.3rem; padding:.12rem .5rem; border-radius:999px; font-size:.72rem; font-weight:600; }
    .sms-stat.valid          { background:#dcfce7; color:#15803d; }
    .sms-stat.no_number      { background:#f1f5f9; color:#64748b; }
    .sms-stat.invalid_number { background:#fef2f2; color:#b91c1c; }
    .sms-stat.duplicate      { background:#fef9c3; color:#92400e; }

    .sms-summary { display:grid; grid-template-columns:repeat(2,1fr); gap:.55rem; }
    .sms-summary .box { background:#f8fafc; border:1px solid #eef2f7; border-radius:10px; padding:.6rem .7rem; text-align:center; }
    .sms-summary .box .n { font-size:1.25rem; font-weight:800; color:#0f172a; line-height:1.1; }
    .sms-summary .box .l { font-size:.74rem; color:#64748b; }
    .sms-summary .box.valid .n { color:#15803d; }

    .sms-search-results { border:1px solid #e2e8f0; border-radius:10px; margin-top:.4rem; max-height:200px; overflow:auto; display:none; }
    .sms-search-results.is-open { display:block; }
    .sms-search-results .row-item { padding:.5rem .7rem; cursor:pointer; display:flex; justify-content:space-between; gap:.5rem; font-size:.85rem; }
    .sms-search-results .row-item:hover { background:#eff6ff; }
    .sms-chosen { display:flex; flex-wrap:wrap; gap:.35rem; margin-top:.5rem; }
    .sms-chosen .chip { background:#eef2ff; border:1px solid #c7d2fe; color:#3730a3; border-radius:999px; padding:.15rem .55rem; font-size:.78rem; display:inline-flex; align-items:center; gap:.35rem; }
    .sms-chosen .chip button { border:0; background:transparent; color:#6366f1; cursor:pointer; line-height:1; padding:0; }

    .sms-preview-box { background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:.75rem 1rem; font-size:.85rem; white-space:pre-wrap; }
    .sms-spinner { display:none; }
    .sms-spinner.is-on { display:inline-flex; }
    .var-btn { cursor:pointer; }
    .sms-credit-hint { font-size:.82rem; color:#334155; background:#f0f9ff; border:1px solid #bae6fd; border-radius:8px; padding:.45rem .8rem; display:inline-flex; align-items:center; gap:.4rem; }
</style>
@endpush

@section('content')
<section class="sms-wrap" dir="rtl">

    <div class="sms-header" style="display:flex;justify-content:space-between;align-items:flex-end;flex-wrap:wrap;gap:.75rem">
        <div>
            <h2><x-svg-icon name="chat-left-text" :size="22" class="ic-primary" /> إرسال رسالة قصيرة</h2>
            <nav><ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
                <li class="breadcrumb-item">الرسائل القصيرة</li>
                <li class="breadcrumb-item active" aria-current="page">إرسال رسالة</li>
            </ol></nav>
        </div>
        <a href="{{ route('admin.sms.reports.index') }}" class="btn btn-outline-secondary btn-sm">
            <x-svg-icon name="journal-text" :size="15" /> سجل الرسائل
        </a>
    </div>

    @include('components.alerts')

    @if($isAllSchools)
        <div class="alert alert-info" role="alert">
            <x-svg-icon name="info-circle" :size="16" /> أنت تتصفّح كل المدارس (مدير النظام). سيتم الإرسال بصلاحيات النظام الكاملة.
        </div>
    @elseif($setting && ! $setting->is_active)
        <div class="alert alert-warning" role="alert">
            <x-svg-icon name="exclamation-triangle" :size="16" /> خدمة الرسائل القصيرة غير مفعّلة لهذه المدرسة.
        </div>
    @endif

    <form method="POST" action="{{ route('admin.sms.send.store') }}" id="sms-form"
          data-resolve-url="{{ route('admin.sms.send.recipients') }}"
          data-search-url="{{ route('admin.sms.send.search') }}"
          data-preview-url="{{ route('admin.sms.send.preview') }}">
        @csrf

        <div class="sms-grid">
            {{-- ============ LEFT: message + audience ============ --}}
            <div>
                {{-- Message card --}}
                <div class="sms-card">
                    <div class="sms-card-head"><h5><x-svg-icon name="chat-left-text" :size="18" /> الرسالة</h5></div>
                    <div class="sms-card-body">

                        {{-- Sender --}}
                        <div class="sms-field" style="margin-top:0">
                            <label for="sms-sender">اسم المرسل</label>
                            <select name="sender_id" id="sms-sender" class="form-select @error('sender_id') is-invalid @enderror">
                                <option value="">— اختر اسم المرسل —</option>
                                @foreach($senders as $s)
                                    <option value="{{ $s->id }}" {{ old('sender_id') == $s->id ? 'selected' : '' }}>{{ $s->name_ar }} ({{ $s->name_en }})</option>
                                @endforeach
                            </select>
                            @error('sender_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        {{-- Template --}}
                        <div class="sms-field">
                            <label for="sms-template">القالب (اختياري)</label>
                            <select name="template_id" id="sms-template" class="form-select">
                                <option value="">— اختر قالبًا —</option>
                                @foreach($templates as $tpl)
                                    <option value="{{ $tpl->id }}"
                                            data-body="{{ $tpl->body }}"
                                            {{ old('template_id') == $tpl->id ? 'selected' : '' }}>{{ $tpl->title }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Body --}}
                        <div class="sms-field">
                            <label for="smsBody">نص الرسالة <span class="text-danger">*</span></label>
                            <textarea name="body" id="smsBody" rows="5" class="form-control @error('body') is-invalid @enderror"
                                      placeholder="اكتب نص الرسالة…">{{ old('body') }}</textarea>
                            @error('body')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="d-flex flex-wrap gap-3 small text-muted mt-1" id="sms-counter">
                                <span>الأحرف: <b id="cLen">0</b></span>
                                <span>المتبقية: <b id="cRem">0</b></span>
                                <span>عدد الرسائل: <b id="cSeg">0</b></span>
                                <span>النوع: <b id="cLang">—</b></span>
                            </div>
                        </div>

                        {{-- Variable insert --}}
                        @if($variables)
                        <div class="sms-field">
                            <label class="text-muted small">المتغيرات — انقر لإدراج في المؤشر:</label>
                            <div class="d-flex flex-wrap gap-1 mt-1">
                                @foreach($variables as $key => $label)
                                    <button type="button" class="btn btn-sm btn-outline-secondary var-btn" data-token="{{ '{'.$key.'}' }}" title="{{ '{'.$key.'}' }}">{{ $label }}</button>
                                @endforeach
                            </div>
                        </div>
                        @endif

                        {{-- Preview --}}
                        <div class="sms-field">
                            <button type="button" class="btn btn-sm btn-outline-info" id="sms-preview-btn">
                                <span class="sms-spinner spinner-border spinner-border-sm me-1" id="sms-preview-spin"></span>
                                <x-svg-icon name="eye-fill" :size="15" /> معاينة
                            </button>
                        </div>
                        <div id="sms-preview-area" style="display:none" class="sms-field">
                            <div class="sms-preview-box" id="sms-preview-text"></div>
                            <div class="small text-muted mt-1" id="sms-preview-stats"></div>
                        </div>

                        {{-- Credit hint --}}
                        <div class="sms-field">
                            <span class="sms-credit-hint">
                                <x-svg-icon name="cash-coin" :size="16" />
                                الرصيد المتاح: <b>{{ number_format($available) }}</b> رسالة
                            </span>
                        </div>

                        {{-- on_missing --}}
                        <div class="sms-field">
                            <label for="sms-on-missing">عند غياب الرقم</label>
                            <select name="on_missing" id="sms-on-missing" class="form-select">
                                <option value="">تخطّي المستخدم</option>
                                <option value="-">استبدال بشرطة</option>
                            </select>
                        </div>
                    </div>
                </div>

                {{-- Audience card --}}
                <div class="sms-card">
                    <div class="sms-card-head"><h5><x-svg-icon name="people" :size="18" /> المستلمون</h5></div>
                    <div class="sms-card-body">
                        <div class="sms-field" style="margin-top:0">
                            <label for="sms-audience">المجموعة</label>
                            <select name="audience" id="sms-audience" class="form-select">
                                @foreach($audiences as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- grade ref --}}
                        <div class="sms-field d-none" id="sms-grade-field">
                            <label for="sms-grade-sel">الصف</label>
                            <select id="sms-grade-sel" class="form-select">
                                <option value="">— اختر الصف —</option>
                                @foreach($gradeLevels as $g)
                                    <option value="{{ $g }}">الصف {{ $g }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- class ref --}}
                        <div class="sms-field d-none" id="sms-class-field">
                            <label for="sms-class-sel">الفصل</label>
                            <select id="sms-class-sel" class="form-select">
                                <option value="">— اختر الفصل —</option>
                                @foreach($classes as $c)
                                    <option value="{{ $c->id }}">{{ $c->name }} (الصف {{ $c->grade_level }})</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- specific users --}}
                        <div class="sms-field d-none" id="sms-specific-field">
                            <label for="sms-user-search">بحث عن مستخدمين</label>
                            <input type="text" id="sms-user-search" class="form-control" placeholder="اكتب اسمًا أو رقمًا…" autocomplete="off">
                            <div class="sms-search-results" id="sms-search-results"></div>
                            <div class="sms-chosen" id="sms-chosen"></div>
                            <div class="sms-help">اختر مستخدمًا واحدًا على الأقل ثم اضغط «عرض المستلمين».</div>
                        </div>

                        <div class="sms-field">
                            <button type="button" class="btn btn-outline-primary btn-sm" id="sms-resolve-btn">
                                <span class="sms-spinner spinner-border spinner-border-sm me-1" id="sms-resolve-spin"></span>
                                <x-svg-icon name="search" :size="15" /> عرض المستلمين
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ============ RIGHT: recipients + send ============ --}}
            <div>
                <div class="sms-card">
                    <div class="sms-card-head"><h5><x-svg-icon name="card-checklist" :size="18" /> المستلمون المحددون</h5></div>
                    <div class="sms-card-body">
                        <div class="sms-summary mb-2">
                            <div class="box valid"><div class="n" id="sms-cnt-valid">0</div><div class="l">أرقام صالحة</div></div>
                            <div class="box"><div class="n" id="sms-cnt-total">0</div><div class="l">إجمالي</div></div>
                        </div>

                        <div class="sms-recipients" id="sms-recipients-wrap" style="display:none">
                            <table>
                                <thead><tr>
                                    <th><input type="checkbox" id="sms-check-all" checked></th>
                                    <th>الاسم</th>
                                    <th>الدور</th>
                                    <th>الرقم</th>
                                    <th>الحالة</th>
                                </tr></thead>
                                <tbody id="sms-recipients-body"></tbody>
                            </table>
                        </div>

                        <div id="sms-recipients-empty" class="text-center text-muted py-4" style="font-size:.85rem">
                            <x-svg-icon name="inbox" :size="28" class="ic-muted" />
                            <div class="mt-1">اختر مجموعة واضغط «عرض المستلمين».</div>
                        </div>

                        <div id="sms-recipient-ids"></div>

                        @if(auth()->user()->canDo('sms.send'))
                        <button type="submit" class="btn btn-primary w-100 mt-3" id="sms-submit" disabled>
                            <x-svg-icon name="send" :size="16" /> إرسال الرسائل
                        </button>
                        @endif
                        <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary w-100 mt-2">عودة</a>
                        <div class="sms-help text-center mt-1">يُعاد التحقق من المستلمين والأرقام على الخادم قبل الإرسال.</div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</section>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var form        = document.getElementById('sms-form');
    var resolveUrl  = form.dataset.resolveUrl;
    var searchUrl   = form.dataset.searchUrl;
    var previewUrl  = form.dataset.previewUrl;
    var token       = document.querySelector('input[name="_token"]').value;

    var audienceSel = document.getElementById('sms-audience');
    var gradeField  = document.getElementById('sms-grade-field');
    var classField  = document.getElementById('sms-class-field');
    var specField   = document.getElementById('sms-specific-field');
    var gradeSel    = document.getElementById('sms-grade-sel');
    var classSel    = document.getElementById('sms-class-sel');
    var resolveBtn  = document.getElementById('sms-resolve-btn');
    var resolveSpin = document.getElementById('sms-resolve-spin');
    var recipBody   = document.getElementById('sms-recipients-body');
    var recipWrap   = document.getElementById('sms-recipients-wrap');
    var recipEmpty  = document.getElementById('sms-recipients-empty');
    var idsHolder   = document.getElementById('sms-recipient-ids');
    var submitBtn   = document.getElementById('sms-submit');
    var cntValid    = document.getElementById('sms-cnt-valid');
    var cntTotal    = document.getElementById('sms-cnt-total');
    var checkAll    = document.getElementById('sms-check-all');
    var ta          = document.getElementById('smsBody');
    var tplSel      = document.getElementById('sms-template');

    var chosenUsers = {};

    // ---- template fill ----
    tplSel && tplSel.addEventListener('change', function () {
        var opt = tplSel.options[tplSel.selectedIndex];
        var body = opt.dataset.body || '';
        if (body) { ta.value = body; update(); }
    });

    // ---- body counter (SMS-specific: Arabic=70/67, English=160/153) ----
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

    // ---- variable insert ----
    function insertAtCursor(token) {
        var s = ta.selectionStart, e = ta.selectionEnd, v = ta.value;
        ta.value = v.slice(0, s) + token + v.slice(e);
        ta.selectionStart = ta.selectionEnd = s + token.length;
        ta.focus(); update();
    }
    document.querySelectorAll('.var-btn').forEach(function (b) {
        b.addEventListener('click', function () { insertAtCursor(this.dataset.token); });
    });

    // ---- preview ----
    var previewBtn  = document.getElementById('sms-preview-btn');
    var previewSpin = document.getElementById('sms-preview-spin');
    var previewArea = document.getElementById('sms-preview-area');
    var previewText = document.getElementById('sms-preview-text');
    var previewStats = document.getElementById('sms-preview-stats');

    previewBtn && previewBtn.addEventListener('click', function () {
        if (!ta.value.trim()) { alert('أدخل نص الرسالة أولاً.'); return; }
        previewSpin.classList.add('is-on');
        previewBtn.disabled = true;
        fetch(previewUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token },
            body: JSON.stringify({ body: ta.value }),
        })
        .then(function (r) { return r.json(); })
        .then(function (res) {
            if (res.success && res.data) {
                previewText.textContent = res.data.rendered || ta.value;
                previewStats.textContent = 'الأحرف: ' + (res.data.length || 0) + ' | الرسائل: ' + (res.data.segments || 0) + ' | ' + (res.data.lang || '');
            } else {
                previewText.textContent = ta.value;
                previewStats.textContent = '';
            }
            previewArea.style.display = '';
        })
        .catch(function () { previewText.textContent = ta.value; previewArea.style.display = ''; })
        .finally(function () { previewSpin.classList.remove('is-on'); previewBtn.disabled = false; });
    });

    // ---- audience ref toggle ----
    function syncAudience() {
        var a = audienceSel.value;
        toggle('sms-grade-field', a.indexOf('grade_') === 0);
        toggle('sms-class-field', a.indexOf('class_') === 0);
        toggle('sms-specific-field', a === 'specific_users');
        clearRecipients();
    }
    audienceSel.addEventListener('change', syncAudience);

    function toggle(id, show) {
        var el = document.getElementById(id);
        if (el) el.classList.toggle('d-none', !show);
    }

    // ---- specific users search ----
    var searchInput  = document.getElementById('sms-user-search');
    var searchBox    = document.getElementById('sms-search-results');
    var chosenBox    = document.getElementById('sms-chosen');
    var searchTimer  = null;

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
        if (searchInput) searchInput.value = '';
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
        if (a.indexOf('grade_') === 0) {
            if (!gradeSel || !gradeSel.value) { alert('اختر الصف أولاً.'); return; }
            payload.ref_id = gradeSel.value;
        } else if (a.indexOf('class_') === 0) {
            if (!classSel || !classSel.value) { alert('اختر الفصل أولاً.'); return; }
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

    // Auto-resolve recipients once the audience selection is complete, so the
    // user doesn't have to discover the «عرض المستلمين» step before the send
    // button enables (QA #238). specific_users still needs a manual pick.
    function maybeAutoResolve() {
        var a = audienceSel.value;
        if (!a || a === 'specific_users') { return; }
        if (a.indexOf('grade_') === 0 && (!gradeSel || !gradeSel.value)) { return; }
        if (a.indexOf('class_') === 0 && (!classSel || !classSel.value)) { return; }
        resolveBtn.click();
    }
    audienceSel.addEventListener('change', maybeAutoResolve);
    gradeSel && gradeSel.addEventListener('change', maybeAutoResolve);
    classSel && classSel.addEventListener('change', maybeAutoResolve);

    function renderRecipients(list) {
        recipBody.innerHTML = '';
        idsHolder.innerHTML = '';
        var valid = 0;

        list.forEach(function (rcp) {
            var isValid = rcp.status === 'valid';
            var tr = document.createElement('tr');
            var cb = document.createElement('input');
            cb.type = 'checkbox'; cb.className = 'sms-recip-cb'; cb.value = rcp.id;
            cb.checked = isValid;
            cb.setAttribute('data-id', rcp.id);
            cb.addEventListener('change', syncHiddenIds);

            var tdCb = document.createElement('td');
            tdCb.appendChild(cb);

            tr.appendChild(tdCb);
            tr.innerHTML += '<td>' + escapeHtml(rcp.name) + '</td>' +
                '<td>' + escapeHtml(rcp.role || '') + '</td>' +
                '<td dir="ltr" style="text-align:start">' + escapeHtml(rcp.number || '—') + '</td>' +
                '<td><span class="sms-stat ' + escapeHtml(rcp.status) + '">' + escapeHtml(statusLabel(rcp.status)) + '</span></td>';

            recipBody.appendChild(tr);
            // re-attach cb after innerHTML replacement
            recipBody.lastChild.cells[0].appendChild(cb);
            if (isValid) valid++;
        });

        cntValid.textContent = valid;
        cntTotal.textContent = list.length;

        if (list.length) {
            recipWrap.style.display = '';
            recipEmpty.style.display = 'none';
            syncHiddenIds();
            if (submitBtn) submitBtn.disabled = false;
        } else {
            clearRecipients();
            recipEmpty.textContent = 'لا يوجد مستلمون لهذه المجموعة.';
        }
    }

    function syncHiddenIds() {
        idsHolder.innerHTML = '';
        var checked = recipBody.querySelectorAll('.sms-recip-cb:checked');
        checked.forEach(function (cb) {
            var inp = document.createElement('input');
            inp.type = 'hidden'; inp.name = 'recipient_ids[]'; inp.value = cb.value;
            idsHolder.appendChild(inp);
        });
        if (submitBtn) submitBtn.disabled = checked.length === 0;
    }

    // check-all toggle
    checkAll && checkAll.addEventListener('change', function () {
        recipBody.querySelectorAll('.sms-recip-cb').forEach(function (cb) { cb.checked = checkAll.checked; });
        syncHiddenIds();
    });

    function statusLabel(s) {
        var map = { valid: 'صالح', no_number: 'لا رقم', invalid_number: 'رقم خاطئ', duplicate: 'مكرر' };
        return map[s] || s;
    }

    function clearRecipients() {
        recipBody.innerHTML = '';
        idsHolder.innerHTML = '';
        cntValid.textContent = '0';
        cntTotal.textContent = '0';
        recipWrap.style.display = 'none';
        recipEmpty.style.display = '';
        if (submitBtn) submitBtn.disabled = true;
    }

    function escapeHtml(s) {
        return String(s == null ? '' : s)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }

    // close search dropdown on outside click
    document.addEventListener('click', function (e) {
        if (specField && !specField.contains(e.target)) {
            searchBox && searchBox.classList.remove('is-open');
        }
    });

    syncAudience();
});
</script>
@endpush
