@extends('layouts.app')
@section('title', __('lessons_admin.services.import_title'))
@section('body_class', 'theme-light')
@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-8 col-12 mb-2">
        <h2 class="content-header-title mb-0">@lang('lessons_admin.services.import_title')</h2>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('lessons_admin.breadcrumb_home')</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.lessons.index') }}">@lang('lessons_admin.breadcrumb_index')</a></li>
            <li class="breadcrumb-item active">@lang('lessons_admin.services.import_title')</li>
        </ol>
    </div>
    <div class="content-header-right col-md-4 col-12 text-md-left">
        <a href="{{ route('admin.lessons.import.template') }}" class="btn btn-outline-primary btn-sm"><i class="la la-download"></i> @lang('lessons_admin.services.download_template')</a>
    </div>
</div>
<div class="content-body">
    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0 pr-3">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
    <div class="card"><div class="card-body">
        <p class="text-muted">@lang('lessons_admin.services.import_intro')</p>
        <form method="POST" action="{{ route('admin.lessons.import.run') }}" enctype="multipart/form-data">
            @csrf
            <div class="form-group mb-3">
                <input type="file" name="file" class="form-control" accept=".csv,.txt" required>
            </div>
            <button type="submit" class="btn btn-primary"><i class="la la-file-import"></i> @lang('lessons_admin.services.upload')</button>
        </form>
        <hr>
        <h6>@lang('lessons_admin.services.columns_title')</h6>
        <div class="table-responsive"><table class="table table-sm table-bordered text-center mb-0">
            <thead style="background:#1d4ed8;color:#fff;"><tr>@foreach($columns as $c)<th>{{ $c }}</th>@endforeach</tr></thead>
            <tbody><tr>@foreach($columns as $c)<td>&nbsp;</td>@endforeach</tr></tbody>
        </table></div>
    </div></div>
</div>
@endsection
