@extends('layouts.app')
@section('body_class','theme-light')
@section('title', __('users.import_photos'))
@section('content')
<div class="content-header row">
    <div class="content-header-left col-12 mb-2">
        <h2 class="content-header-title mb-0">@lang('users.import_photos')</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.users.students.index') }}">@lang('users.students')</a></li>
                <li class="breadcrumb-item active">@lang('users.import_photos')</li>
            </ol>
        </div>
    </div>
</div>
<div class="content-body">
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0 pr-3">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

    @php $result = session('photo_result'); @endphp
    @if($result)
        <div class="alert {{ $result['rejected'] ? 'alert-warning' : 'alert-success' }}">
            @lang('users.photos_updated_count', ['n' => $result['updated']])
            @if($result['rejected']) — @lang('users.photos_rejected_count', ['n' => $result['rejected']]) @endif
        </div>
        <div class="card mb-3">
            <div class="table-responsive"><table class="table table-sm mb-0">
                <thead><tr><th>@lang('users.photos_file')</th><th>@lang('users.photos_status')</th><th>@lang('users.photos_reason')</th></tr></thead>
                <tbody>
                    @foreach($result['rows'] as $r)
                        <tr>
                            <td>{{ $r['file'] }}</td>
                            <td>@if($r['ok'])<span class="badge badge-success">@lang('users.photos_ok')</span>@else<span class="badge badge-danger">@lang('users.photos_failed')</span>@endif</td>
                            <td>{{ $r['reason'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table></div>
        </div>
    @endif

    <div class="card"><div class="card-body">
        <div class="alert alert-info">
            <ul class="mb-0 pr-3">
                <li>@lang('users.photos_zip_rule')</li>
                <li>@lang('users.photos_zip_name')</li>
                <li>@lang('users.photos_zip_ext')</li>
                <li>@lang('users.photos_zip_scope')</li>
            </ul>
        </div>
        <form method="POST" action="{{ route('admin.users.students.photos.import') }}" enctype="multipart/form-data">
            @csrf
            <div class="form-group mb-3">
                <label class="form-label">@lang('users.photos_zip_field')</label>
                <input type="file" name="archive" class="form-control" accept=".zip" required>
            </div>
            <button type="submit" class="btn btn-primary"><i class="la la-upload"></i> @lang('users.photos_import_btn')</button>
            <a href="{{ route('admin.users.students.index') }}" class="btn btn-outline-secondary">@lang('users.cancel')</a>
        </form>
    </div></div>
</div>
@endsection
