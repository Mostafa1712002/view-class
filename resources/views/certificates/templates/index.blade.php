@extends('layouts.app')

@section('title', __('certificates.tpl.index_title'))
@section('body_class', 'theme-light')

@php $isRtl = app()->getLocale() === 'ar'; @endphp

@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-8 col-12 mb-2">
        <h2 class="content-header-title float-{{ $isRtl ? 'right' : 'left' }} mb-0">
            @lang('certificates.tpl.index_title')
        </h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('certificates.breadcrumb_home')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.certificates.index') }}">@lang('certificates.breadcrumb_index')</a></li>
                <li class="breadcrumb-item active">@lang('certificates.tpl.index_title')</li>
            </ol>
        </div>
    </div>
    <div class="content-header-right col-md-4 col-12 d-flex justify-content-{{ $isRtl ? 'start' : 'end' }} align-items-center">
        <a href="{{ route('admin.certificates.index') }}" class="btn btn-secondary mr-1">
            <i class="la la-arrow-{{ $isRtl ? 'right' : 'left' }}"></i> @lang('certificates.tpl.back')
        </a>
        <a href="{{ route('admin.certificate-templates.create') }}" class="btn btn-primary">
            <i class="la la-plus"></i> @lang('certificates.tpl.add')
        </a>
    </div>
</div>

@if(session('warning'))
    <div class="alert alert-warning">{{ session('warning') }}</div>
@endif
@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="card mb-2">
    <div class="card-content"><div class="card-body py-1">
        <form method="GET" action="{{ route('admin.certificate-templates.index') }}" class="form-row align-items-end">
            <div class="col-md-3 col-sm-6 mb-1">
                <label class="mb-0 small">@lang('certificates.tpl.type')</label>
                <select name="type" class="form-control form-control-sm">
                    <option value="">— @lang('certificates.filter_all') —</option>
                    @foreach(\App\Models\CertificateTemplate::TYPES as $t)
                        <option value="{{ $t }}" @selected(($filters['type'] ?? '') === $t)>{{ __('certificates.tpl.types.' . $t) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-5 col-sm-6 mb-1">
                <label class="mb-0 small">@lang('certificates.tpl.name')</label>
                <input type="text" name="q" class="form-control form-control-sm" value="{{ $filters['q'] ?? '' }}">
            </div>
            <div class="col-md-4 col-sm-12 mb-1 d-flex gap-1">
                <button type="submit" class="btn btn-sm btn-primary"><i class="la la-search"></i> @lang('certificates.filter_apply')</button>
                <a href="{{ route('admin.certificate-templates.index') }}" class="btn btn-sm btn-secondary ml-1"><i class="la la-redo"></i> @lang('certificates.filter_reset')</a>
            </div>
        </form>
    </div></div>
</div>

<div class="card">
    <div class="card-content">
        <div class="table-responsive">
            <table class="table table-bordered table-striped mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>@lang('certificates.tpl.name')</th>
                        <th>@lang('certificates.tpl.background')</th>
                        <th>@lang('certificates.tpl.type')</th>
                        <th>@lang('certificates.tpl.orientation')</th>
                        <th>@lang('certificates.tpl.created_at')</th>
                        <th>@lang('certificates.fields.actions')</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($templates as $tpl)
                        <tr>
                            <td>{{ $tpl->id }}</td>
                            <td>{{ $tpl->name }}</td>
                            <td>
                                @if($tpl->background_path)
                                    <img src="{{ asset('storage/' . $tpl->background_path) }}" alt="bg" style="height:42px;border:1px solid #eee;border-radius:4px;">
                                @else
                                    <span class="text-muted small">@lang('certificates.tpl.no_background')</span>
                                @endif
                            </td>
                            <td>{{ __('certificates.tpl.types.' . $tpl->type) }}</td>
                            <td>{{ __('certificates.tpl.orientations.' . $tpl->orientation) }}</td>
                            <td>{{ $tpl->created_at?->format('Y-m-d') }}</td>
                            <td>
                                <div class="d-flex flex-wrap gap-1">
                                    <a href="{{ route('admin.certificate-templates.edit', $tpl->id) }}" class="btn btn-sm btn-info">
                                        <i class="la la-edit"></i> @lang('certificates.actions.edit')
                                    </a>
                                    <form method="POST" action="{{ route('admin.certificate-templates.destroy', $tpl->id) }}" id="tpl-del-{{ $tpl->id }}" style="display:inline">
                                        @csrf @method('DELETE')
                                        <button type="button" class="btn btn-sm btn-danger"
                                            onclick="vcConfirm({title: @json(__('certificates.confirm_delete')), icon:'error'}).then(function(r){ if(r.isConfirmed) document.getElementById('tpl-del-{{ $tpl->id }}').submit(); })">
                                            <i class="la la-trash"></i> @lang('certificates.actions.delete')
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-3">@lang('certificates.tpl.empty')</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($templates->hasPages())<div class="p-2">{{ $templates->links() }}</div>@endif
    </div>
</div>
@endsection
