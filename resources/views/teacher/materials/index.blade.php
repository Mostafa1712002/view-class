@extends('layouts.admin')

@section('title', 'إدارة المواد')

@section('content')
<div class="container-fluid" id="materials-hub"
     data-grades-url="{{ route('teacher.materials.grades') }}"
     data-classes-url="{{ route('teacher.materials.classes') }}"
     data-results-url="{{ route('teacher.materials.results') }}">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0"><i class="la la-folder-open text-primary"></i> إدارة المواد</h1>
    </div>

    @if($subjects->isEmpty())
        <div class="alert alert-info">
            <i class="la la-info-circle"></i>
            لا توجد مواد مسندة إليك. تظهر هنا المواد التي تدرّسها فقط.
        </div>
    @else
    {{-- Cascading filters --}}
    <div class="card mb-4">
        <div class="card-body">
            <div class="form-row">
                <div class="form-group col-md-3">
                    <label class="font-weight-bold">المادة</label>
                    <select id="f-subject" class="custom-select">
                        <option value="">— اختر المادة —</option>
                        @foreach($subjects as $s)
                            <option value="{{ $s->id }}">{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-3">
                    <label class="font-weight-bold">الصف</label>
                    <select id="f-grade" class="custom-select" disabled>
                        <option value="">كل الصفوف</option>
                    </select>
                </div>
                <div class="form-group col-md-3">
                    <label class="font-weight-bold">الفصل / الشعبة</label>
                    <select id="f-class" class="custom-select" disabled>
                        <option value="">كل الفصول</option>
                    </select>
                </div>
                <div class="form-group col-md-3">
                    <label class="font-weight-bold">نوع المحتوى</label>
                    <select id="f-type" class="custom-select" disabled>
                        <option value="">— اختر النوع —</option>
                        @foreach($types as $key => $t)
                            <option value="{{ $key }}">{{ $t['label'] }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>

    {{-- Results --}}
    <div id="results-panel">
        <div class="text-muted text-center py-5">
            <i class="la la-hand-pointer" style="font-size:2rem"></i>
            <p class="mt-2 mb-0">اختر المادة ونوع المحتوى لعرض العناصر.</p>
        </div>
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
jQuery(function ($) {
    var $hub    = $('#materials-hub');
    var urls    = { grades: $hub.data('grades-url'), classes: $hub.data('classes-url'), results: $hub.data('results-url') };
    var $subject = $('#f-subject'), $grade = $('#f-grade'), $class = $('#f-class'), $type = $('#f-type');
    var $panel   = $('#results-panel');

    function esc(s) { return $('<div>').text(s == null ? '' : s).html(); }
    function info(html) { $panel.html('<div class="text-muted text-center py-5">' + html + '</div>'); }

    function fill($sel, items, placeholder, valKey, labelKey) {
        $sel.empty().append($('<option>').val('').text(placeholder));
        items.forEach(function (it) {
            $sel.append($('<option>').val(it[valKey]).text(it[labelKey]));
        });
    }

    function loadGrades() {
        var subject = $subject.val();
        $grade.prop('disabled', true).empty().append($('<option>').val('').text('كل الصفوف'));
        $class.prop('disabled', true).empty().append($('<option>').val('').text('كل الفصول'));
        if (!subject) { $type.prop('disabled', true).val(''); info('اختر المادة ونوع المحتوى لعرض العناصر.'); return; }
        $type.prop('disabled', false);

        $.getJSON(urls.grades, { subject_id: subject }).done(function (r) {
            (r.grades || []).forEach(function (g) { $grade.append($('<option>').val(g.value).text(g.label)); });
            $grade.prop('disabled', false);
        });
        loadClasses();
        loadResults();
    }

    function loadClasses() {
        var subject = $subject.val();
        $class.prop('disabled', true).empty().append($('<option>').val('').text('كل الفصول'));
        if (!subject) return;
        $.getJSON(urls.classes, { subject_id: subject, grade: $grade.val() || '' }).done(function (r) {
            (r.classes || []).forEach(function (c) { $class.append($('<option>').val(c.id).text(c.name)); });
            $class.prop('disabled', ((r.classes || []).length === 0));
        });
    }

    function loadResults() {
        var subject = $subject.val(), type = $type.val();
        if (!subject || !type) { info('اختر المادة ونوع المحتوى لعرض العناصر.'); return; }
        info('<i class="la la-spinner la-spin" style="font-size:1.6rem"></i><p class="mt-2 mb-0">جاري التحميل…</p>');

        $.getJSON(urls.results, {
            subject_id: subject, type: type,
            grade: $grade.val() || '', class_id: $class.val() || ''
        }).done(function (r) {
            var items = r.items || [];
            if (!items.length) { info('<i class="la la-inbox" style="font-size:1.8rem"></i><p class="mt-2 mb-0">لا يوجد محتوى مطابق.</p>'); return; }
            var html = '<div class="list-group">';
            items.forEach(function (it) {
                var open = it.url ? ('<a href="' + esc(it.url) + '" class="btn btn-sm btn-outline-primary">فتح</a>') : '';
                var badges = (it.badges || []).map(function (b) { return '<span class="badge badge-light border mr-1">' + esc(b) + '</span>'; }).join('');
                var date = it.date ? ('<small class="text-muted mr-2"><i class="la la-calendar"></i> ' + esc(it.date) + '</small>') : '';
                html += '<div class="list-group-item d-flex justify-content-between align-items-center">'
                     +  '<div><i class="' + esc(it.icon) + ' text-primary mr-2"></i>' + esc(it.title) + ' ' + badges + '</div>'
                     +  '<div>' + date + open + '</div>'
                     +  '</div>';
            });
            html += '</div>';
            $panel.html(html);
        }).fail(function (x) {
            info('<span class="text-danger"><i class="la la-exclamation-triangle"></i> تعذّر تحميل المحتوى (' + x.status + ').</span>');
        });
    }

    $subject.on('change', loadGrades);
    $grade.on('change', function () { loadClasses(); loadResults(); });
    $class.on('change', loadResults);
    $type.on('change', loadResults);

    // Preselect a subject when arriving from a subject card (?subject=ID).
    var preselect = new URLSearchParams(window.location.search).get('subject');
    if (preselect && $subject.find('option[value="' + preselect + '"]').length) {
        $subject.val(preselect).trigger('change');
    }
});
</script>
@endpush
