@extends('layouts.app')
@section('title', __('users.import_teachers_title'))
@section('body_class', 'theme-light')
@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-8 col-12 mb-2">
        <h2 class="content-header-title mb-0">@lang('users.import_teachers_title')</h2>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.users.teachers.index') }}">@lang('users.teachers')</a></li>
            <li class="breadcrumb-item active">@lang('users.import_excel')</li>
        </ol>
    </div>
</div>

<div class="content-body">
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body text-center py-5">
                    <div class="mb-3">
                        <i class="la la-file-excel" style="font-size:4rem; color:#1f9d55;"></i>
                    </div>
                    <h4 class="mb-2">@lang('users.import_teachers_title')</h4>
                    <p class="text-muted mb-4">@lang('users.import_coming_soon')</p>

                    <div class="d-flex justify-content-center gap-2 flex-wrap">
                        <a href="#" onclick="alert('@lang('users.import_coming_soon')'); return false;" class="btn btn-outline-success">
                            <i class="la la-download"></i> @lang('users.download_template')
                        </a>
                        <a href="{{ route('admin.users.teachers.index') }}" class="btn btn-outline-secondary">
                            <i class="la la-arrow-right"></i> @lang('users.cancel')
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header"><strong>@lang('users.import_excel')</strong></div>
                <div class="card-body">
                    <ol class="ps-3 mb-0" style="line-height:1.9">
                        <li>@lang('users.download_template')</li>
                        <li>@lang('users.upload_excel')</li>
                        <li>@lang('users.save')</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
