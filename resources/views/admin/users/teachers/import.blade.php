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
    @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    @if($errors->any())
        <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif

    <div class="row">
        {{-- Add teachers from Excel --}}
        <div class="col-lg-6 mb-3">
            <div class="card h-100">
                <div class="card-header bg-white d-flex align-items-center">
                    <i class="la la-file-excel mr-1" style="color:#1f9d55"></i>
                    <strong>@lang('users.import_add_title')</strong>
                </div>
                <div class="card-body">
                    <p class="text-muted">@lang('users.import_add_help')</p>
                    <form action="{{ route('admin.users.teachers.import.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="form-group">
                            <label>@lang('users.upload_excel')</label>
                            <input type="file" name="file" class="form-control" accept=".csv,.txt,.xlsx,.xls" required />
                        </div>
                        <div class="d-flex flex-wrap" style="gap:.5rem">
                            <a href="{{ route('admin.users.teachers.import.template') }}" class="btn btn-outline-success btn-sm">
                                <i class="la la-download"></i> @lang('users.download_template')
                            </a>
                            <button class="btn btn-primary btn-sm" type="submit">
                                <i class="la la-upload"></i> @lang('users.import_add_btn')
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Update existing teachers from Excel --}}
        <div class="col-lg-6 mb-3">
            <div class="card h-100">
                <div class="card-header bg-white d-flex align-items-center">
                    <i class="la la-edit mr-1" style="color:#cfa046"></i>
                    <strong>@lang('users.import_update_title')</strong>
                </div>
                <div class="card-body">
                    <p class="text-muted">@lang('users.import_update_help')</p>
                    <form action="{{ route('admin.users.teachers.import.update') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="form-group">
                            <label>@lang('users.upload_excel')</label>
                            <input type="file" name="file" class="form-control" accept=".csv,.txt,.xlsx,.xls" required />
                        </div>
                        <div class="d-flex flex-wrap" style="gap:.5rem">
                            <a href="{{ route('admin.users.teachers.export') }}" class="btn btn-outline-info btn-sm">
                                <i class="la la-file-download"></i> @lang('users.export_current')
                            </a>
                            <button class="btn btn-primary btn-sm" type="submit">
                                <i class="la la-sync"></i> @lang('users.import_update_btn')
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Bulk photo ZIP --}}
        <div class="col-lg-6 mb-3">
            <div class="card h-100">
                <div class="card-header bg-white d-flex align-items-center">
                    <i class="la la-images mr-1" style="color:#5a8dee"></i>
                    <strong>@lang('users.import_photos_title')</strong>
                </div>
                <div class="card-body">
                    <p class="text-muted">@lang('users.import_photos_help')</p>
                    <form action="{{ route('admin.users.teachers.import.photos') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="form-group">
                            <label>@lang('users.upload_zip')</label>
                            <input type="file" name="archive" class="form-control" accept=".zip" required />
                        </div>
                        <button class="btn btn-primary btn-sm" type="submit">
                            <i class="la la-upload"></i> @lang('users.import_photos_btn')
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Help / steps --}}
        <div class="col-lg-6 mb-3">
            <div class="card h-100">
                <div class="card-header bg-white"><strong>@lang('users.import_excel')</strong></div>
                <div class="card-body">
                    <ol class="pr-3 mb-0" style="line-height:1.9">
                        <li>@lang('users.download_template')</li>
                        <li>@lang('users.upload_excel')</li>
                        <li>@lang('users.save')</li>
                    </ol>
                    <hr>
                    <p class="text-muted small mb-0">@lang('users.import_photos_hint')</p>
                </div>
            </div>
        </div>
    </div>

    <a href="{{ route('admin.users.teachers.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="la la-arrow-right"></i> @lang('users.cancel')
    </a>
</div>
@endsection
