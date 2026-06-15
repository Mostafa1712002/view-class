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
    <form action="{{ route('admin.certificates.issue') }}" method="POST">
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

        <div class="mt-2 d-flex gap-1">
            <button type="submit" class="btn btn-primary"><i class="la la-certificate"></i> @lang('certificates.issue_page.submit')</button>
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
</script>
@endpush
@endsection
