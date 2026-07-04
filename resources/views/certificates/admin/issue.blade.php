@extends('layouts.app')

@php $isRtl = app()->getLocale() === 'ar'; @endphp

@section('title', __('certificates.issue_page.title'))
@section('body_class', 'theme-light')

@section('content')
<div class="content-header row">
    <div class="content-header-left col-12 mb-2">
        <h2 class="content-header-title float-{{ $isRtl ? 'right' : 'left' }} mb-0">@lang('certificates.issue_page.title')</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.certificates.index') }}">@lang('certificates.breadcrumb_index')</a></li>
                <li class="breadcrumb-item active">@lang('certificates.issue_page.title')</li>
            </ol>
        </div>
    </div>
</div>

@if($errors->any())
    <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
@endif

<div class="card"><div class="card-content"><div class="card-body">
    <p class="text-muted">@lang('certificates.issue_page.subtitle')</p>

    @if($templates->isEmpty())
        <div class="alert alert-warning">
            @lang('certificates.issue_page.no_templates')
            <a href="{{ route('admin.certificate-templates.create') }}" class="btn btn-sm btn-primary ml-1">@lang('certificates.tpl.add')</a>
        </div>
    @else
    <form action="{{ route('admin.certificates.issue') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="row">
            <div class="col-md-6 mb-1">
                <label>@lang('certificates.fields.title') <span class="text-danger">*</span></label>
                <input type="text" name="title" class="form-control" maxlength="255" value="{{ old('title') }}">
            </div>
            <div class="col-md-3 mb-1">
                <label>@lang('certificates.fields.type') <span class="text-danger">*</span></label>
                <select name="type" class="form-control">
                    @foreach(\App\Models\Certificate::TYPES as $t)
                        <option value="{{ $t }}" @selected(old('type', 'appreciation') === $t)>{{ __('certificates.types.' . $t) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 mb-1">
                <label>@lang('certificates.fields.issue_date') <span class="text-danger">*</span></label>
                <input type="date" name="issue_date" class="form-control" value="{{ old('issue_date', now()->format('Y-m-d')) }}">
            </div>

            <div class="col-md-6 mb-1">
                <label>@lang('certificates.issue_page.select_template') <span class="text-danger">*</span></label>
                <select name="template_id" class="form-control select2">
                    <option value="">— @lang('certificates.issue_page.select_template') —</option>
                    @foreach($templates as $tpl)
                        <option value="{{ $tpl->id }}" @selected((string) old('template_id') === (string) $tpl->id)>
                            {{ $tpl->name }} ({{ __('certificates.tpl.types.' . $tpl->type) }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6 mb-1">
                <label>@lang('certificates.fields.note')</label>
                <input type="text" name="note" class="form-control" maxlength="2000" value="{{ old('note') }}">
            </div>

            <div class="col-12 mb-1">
                <label class="d-flex justify-content-between align-items-center">
                    <span>@lang('certificates.issue_page.select_students') <span class="text-danger">*</span></span>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="cert-select-all">@lang('certificates.issue_page.select_all')</button>
                </label>
                <select name="recipient_ids[]" class="form-control select2" multiple size="10" id="cert-students">
                    @foreach($students as $s)
                        <option value="{{ $s->id }}">{{ $s->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- Design & signature block --}}
        <fieldset class="border rounded p-1 mt-1">
            <legend class="w-auto px-1" style="font-size:1rem;">@lang('certificates.design.signature')</legend>
            <div class="row">
                <div class="col-md-4 mb-1">
                    <label>@lang('certificates.fields.signer_name')</label>
                    <input type="text" name="signer_name" class="form-control" maxlength="255" value="{{ old('signer_name') }}">
                </div>
                <div class="col-md-8 mb-1">
                    <label>@lang('certificates.design.signature_type')</label>
                    <div>
                        <label class="mr-2">
                            <input type="radio" name="signature_type" value="manual" id="sig-type-manual" @checked(old('signature_type', 'manual') === 'manual')>
                            @lang('certificates.design.signature_manual')
                        </label>
                        <label>
                            <input type="radio" name="signature_type" value="file" id="sig-type-file" @checked(old('signature_type') === 'file')>
                            @lang('certificates.design.signature_file')
                        </label>
                    </div>

                    <div id="sig-manual-block" class="mt-1">
                        <div class="text-muted small mb-1">@lang('certificates.design.signature_draw_hint')</div>
                        <canvas id="sig-pad" width="360" height="120" style="border:1px solid #ccc; background:#fff; touch-action:none;"></canvas>
                        <div class="mt-1">
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="sig-clear">@lang('certificates.design.signature_clear')</button>
                        </div>
                        <input type="hidden" name="signature_data" id="sig-data" value="{{ old('signature_data') }}">
                    </div>

                    <div id="sig-file-block" class="mt-1" style="display:none;">
                        <input type="file" name="signature_file" class="form-control-file" accept="image/jpeg,image/png,image/webp">
                    </div>
                </div>

                <div class="col-md-4 mb-1">
                    <label>@lang('certificates.design.logo')</label>
                    <input type="file" name="logo" class="form-control-file" accept="image/jpeg,image/png,image/webp">
                </div>
                <div class="col-md-4 mb-1">
                    <label>@lang('certificates.design.stamp')</label>
                    <input type="file" name="stamp" class="form-control-file" accept="image/jpeg,image/png,image/webp">
                </div>
                <div class="col-12">
                    <small class="text-muted">@lang('certificates.design.image_hint')</small>
                </div>
            </div>
        </fieldset>

        {{-- Free-text body: shown only for the 'general' certificate type --}}
        <fieldset class="border rounded p-1 mt-1" id="cert-general-block" style="display:none;">
            <legend class="w-auto px-1" style="font-size:1rem;">@lang('certificates.design.body')</legend>
            <div class="btn-toolbar mb-1">
                <div class="btn-group mr-1">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-cmd="bold"><b>@lang('certificates.design.bold')</b></button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-cmd="italic"><i>@lang('certificates.design.italic')</i></button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-cmd="underline"><u>@lang('certificates.design.underline')</u></button>
                </div>
                <div class="btn-group">
                    <button type="button" class="btn btn-sm btn-outline-primary cert-var" data-var="{student_name}">{student_name}</button>
                    <button type="button" class="btn btn-sm btn-outline-primary cert-var" data-var="{school}">{school}</button>
                    <button type="button" class="btn btn-sm btn-outline-primary cert-var" data-var="{grade}">{grade}</button>
                    <button type="button" class="btn btn-sm btn-outline-primary cert-var" data-var="{date}">{date}</button>
                </div>
            </div>
            <div id="cert-body-editor" contenteditable="true" class="form-control" style="min-height:140px; text-align:right;">{!! old('body_html') !!}</div>
            <textarea name="body_html" id="cert-body-html" class="d-none">{{ old('body_html') }}</textarea>
        </fieldset>

        <div class="mt-2 d-flex gap-1">
            <button type="submit" class="btn btn-primary"><x-svg-icon name="award" /> @lang('certificates.issue_page.submit')</button>
            <a href="{{ route('admin.certificates.index') }}" class="btn btn-secondary ml-1">@lang('certificates.actions.cancel')</a>
        </div>
    </form>
    @endif
</div></div></div>

@push('scripts')
<script>
    document.getElementById('cert-select-all')?.addEventListener('click', function () {
        var sel = document.getElementById('cert-students');
        for (var i = 0; i < sel.options.length; i++) sel.options[i].selected = true;
        if (window.jQuery && jQuery(sel).hasClass('select2-hidden-accessible')) jQuery(sel).trigger('change');
    });

    // Signature type toggle (manual canvas vs uploaded file).
    (function () {
        var manual = document.getElementById('sig-type-manual');
        var file = document.getElementById('sig-type-file');
        var manualBlock = document.getElementById('sig-manual-block');
        var fileBlock = document.getElementById('sig-file-block');
        function sync() {
            var isFile = file && file.checked;
            if (manualBlock) manualBlock.style.display = isFile ? 'none' : '';
            if (fileBlock) fileBlock.style.display = isFile ? '' : 'none';
        }
        manual && manual.addEventListener('change', sync);
        file && file.addEventListener('change', sync);
        sync();
    })();

    // Canvas signature pad → PNG data URL into the hidden input.
    (function () {
        var canvas = document.getElementById('sig-pad');
        if (!canvas) return;
        var ctx = canvas.getContext('2d');
        var data = document.getElementById('sig-data');
        var drawing = false, dirty = false;
        ctx.lineWidth = 2;
        ctx.lineCap = 'round';
        ctx.strokeStyle = '#1a1a1a';

        function pos(e) {
            var r = canvas.getBoundingClientRect();
            var t = e.touches ? e.touches[0] : e;
            return { x: t.clientX - r.left, y: t.clientY - r.top };
        }
        function start(e) { drawing = true; var p = pos(e); ctx.beginPath(); ctx.moveTo(p.x, p.y); e.preventDefault(); }
        function move(e) { if (!drawing) return; var p = pos(e); ctx.lineTo(p.x, p.y); ctx.stroke(); dirty = true; e.preventDefault(); }
        function end() { if (!drawing) return; drawing = false; if (dirty) data.value = canvas.toDataURL('image/png'); }

        canvas.addEventListener('mousedown', start);
        canvas.addEventListener('mousemove', move);
        canvas.addEventListener('mouseup', end);
        canvas.addEventListener('mouseleave', end);
        canvas.addEventListener('touchstart', start);
        canvas.addEventListener('touchmove', move);
        canvas.addEventListener('touchend', end);

        document.getElementById('sig-clear')?.addEventListener('click', function () {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            data.value = '';
            dirty = false;
        });
    })();

    // Toggle the free-text body section from the type select.
    (function () {
        var typeSel = document.querySelector('select[name="type"]');
        var block = document.getElementById('cert-general-block');
        function sync() {
            if (block) block.style.display = (typeSel && typeSel.value === 'general') ? '' : 'none';
        }
        typeSel && typeSel.addEventListener('change', sync);
        sync();
    })();

    // Rich body editor: formatting + variable insert, synced into the textarea.
    (function () {
        var editor = document.getElementById('cert-body-editor');
        var html = document.getElementById('cert-body-html');
        if (!editor) return;
        function syncHtml() { html.value = editor.innerHTML; }
        editor.addEventListener('input', syncHtml);
        document.querySelectorAll('#cert-general-block [data-cmd]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                editor.focus();
                document.execCommand(btn.getAttribute('data-cmd'), false, null);
                syncHtml();
            });
        });
        document.querySelectorAll('#cert-general-block .cert-var').forEach(function (btn) {
            btn.addEventListener('click', function () {
                editor.focus();
                document.execCommand('insertText', false, btn.getAttribute('data-var'));
                syncHtml();
            });
        });
    })();
</script>
@endpush
@endsection
