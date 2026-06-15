@extends('layouts.app')

@php
    $isRtl = app()->getLocale() === 'ar';
    $isEdit = (bool) $template;
    $lines = $isEdit ? ($template->body['lines'] ?? []) : [];
@endphp

@section('title', $isEdit ? __('certificates.tpl.edit_title') : __('certificates.tpl.create_title'))
@section('body_class', 'theme-light')

@section('content')
<div class="content-header row">
    <div class="content-header-left col-12 mb-2">
        <h2 class="content-header-title float-{{ $isRtl ? 'right' : 'left' }} mb-0">
            {{ $isEdit ? __('certificates.tpl.edit_title') : __('certificates.tpl.create_title') }}
        </h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.certificate-templates.index') }}">@lang('certificates.tpl.index_title')</a></li>
                <li class="breadcrumb-item active">{{ $isEdit ? __('certificates.tpl.edit_title') : __('certificates.tpl.create_title') }}</li>
            </ol>
        </div>
    </div>
</div>

@if($errors->any())
    <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
@endif

<div class="card"><div class="card-content"><div class="card-body">
    <form action="{{ $isEdit ? route('admin.certificate-templates.update', $template->id) : route('admin.certificate-templates.store') }}"
          method="POST" enctype="multipart/form-data">
        @csrf
        @if($isEdit) @method('PUT') @endif

        <div class="row">
            <div class="col-md-6 mb-1">
                <label>@lang('certificates.tpl.name') <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control" maxlength="255"
                       value="{{ old('name', $isEdit ? $template->name : '') }}">
            </div>

            <div class="col-md-3 mb-1">
                <label>@lang('certificates.tpl.type') <span class="text-danger">*</span></label>
                <select name="type" class="form-control">
                    @foreach(\App\Models\CertificateTemplate::TYPES as $t)
                        <option value="{{ $t }}" @selected(old('type', $isEdit ? $template->type : 'appreciation') === $t)>
                            {{ __('certificates.tpl.types.' . $t) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-3 mb-1">
                <label>@lang('certificates.tpl.orientation') <span class="text-danger">*</span></label>
                <select name="orientation" class="form-control">
                    @foreach(\App\Models\CertificateTemplate::ORIENTATIONS as $o)
                        <option value="{{ $o }}" @selected(old('orientation', $isEdit ? $template->orientation : 'landscape') === $o)>
                            {{ __('certificates.tpl.orientations.' . $o) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-6 mb-1">
                <label>@lang('certificates.tpl.background')</label>
                <input type="file" name="background" class="form-control" accept=".jpg,.jpeg,.png,.webp"
                       onchange="ctPreview(this)">
                <small class="text-muted d-block">@lang('certificates.tpl.background_hint')</small>
            </div>

            <div class="col-md-3 mb-1">
                <label>@lang('certificates.tpl.text_color')</label>
                <input type="color" name="text_color" class="form-control"
                       value="{{ old('text_color', $isEdit ? $template->text_color : '#222222') }}">
            </div>
            <div class="col-md-3 mb-1">
                <label>@lang('certificates.tpl.name_color')</label>
                <input type="color" name="name_color" class="form-control"
                       value="{{ old('name_color', $isEdit ? $template->name_color : '#1a3c6e') }}">
            </div>

            <div class="col-12 mb-1">
                <label class="d-block">@lang('certificates.tpl.preview_image')</label>
                <img id="ct-preview"
                     src="{{ $isEdit && $template->background_path ? asset('storage/' . $template->background_path) : '' }}"
                     alt="" style="max-height:200px;border:1px solid #eee;border-radius:6px;{{ $isEdit && $template->background_path ? '' : 'display:none;' }}">
            </div>

            {{-- Body lines with insert-placeholder buttons (شكر certificates). --}}
            <div class="col-12 mb-1">
                <label class="d-block">@lang('certificates.tpl.lines')</label>
                <div class="mb-1 d-flex flex-wrap gap-1" id="ct-vars">
                    @foreach(\App\Models\CertificateTemplate::PLACEHOLDERS as $ph)
                        <button type="button" class="btn btn-sm btn-outline-primary mr-1 mb-1" data-token="{{ '{' . $ph . '}' }}">
                            <i class="la la-plus"></i> @lang('certificates.tpl.placeholders.' . $ph)
                        </button>
                    @endforeach
                </div>
                @for($i = 0; $i < 5; $i++)
                    <input type="text" name="lines[]" class="form-control mb-1 ct-line" maxlength="500"
                           placeholder="@lang('certificates.tpl.line') {{ $i + 1 }}"
                           value="{{ old('lines.' . $i, $lines[$i] ?? '') }}">
                @endfor
            </div>
        </div>

        <div class="mt-2 d-flex gap-1">
            <button type="submit" class="btn btn-primary"><i class="la la-save"></i> @lang('certificates.actions.save')</button>
            <a href="{{ route('admin.certificate-templates.index') }}" class="btn btn-secondary ml-1"><i class="la la-arrow-{{ $isRtl ? 'right' : 'left' }}"></i> @lang('certificates.tpl.back')</a>
        </div>
    </form>
</div></div></div>

@push('scripts')
<script>
    function ctPreview(input) {
        var img = document.getElementById('ct-preview');
        if (input.files && input.files[0]) {
            var r = new FileReader();
            r.onload = function (e) { img.src = e.target.result; img.style.display = 'inline-block'; };
            r.readAsDataURL(input.files[0]);
        }
    }
    (function () {
        var lastLine = null;
        document.querySelectorAll('.ct-line').forEach(function (el) {
            el.addEventListener('focus', function () { lastLine = el; });
        });
        document.querySelectorAll('#ct-vars [data-token]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var target = lastLine || document.querySelector('.ct-line');
                if (!target) return;
                var pos = target.selectionStart || target.value.length;
                target.value = target.value.slice(0, pos) + btn.dataset.token + target.value.slice(pos);
                target.focus();
            });
        });
    })();
</script>
@endpush
@endsection
