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
    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0 pr-3">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

    @php $result = session('photo_result'); @endphp
    @if($result)
        <div class="alert alert-success">
            @lang('users.photos_matched_count', ['n' => count($result['matched'])])
            @if(count($result['unmatched'])) — @lang('users.photos_unmatched_count', ['n' => count($result['unmatched'])]) @endif
        </div>
        <div class="row">
            @if(count($result['matched']))
            <div class="col-md-6 mb-3"><div class="card"><div class="card-header"><h6 class="mb-0 text-success">@lang('users.photos_matched')</h6></div>
                <div class="card-body"><ul class="mb-0">@foreach($result['matched'] as $m)<li>{{ $m }}</li>@endforeach</ul></div></div></div>
            @endif
            @if(count($result['unmatched']))
            <div class="col-md-6 mb-3"><div class="card"><div class="card-header"><h6 class="mb-0 text-danger">@lang('users.photos_unmatched')</h6></div>
                <div class="card-body"><ul class="mb-0">@foreach($result['unmatched'] as $m)<li>{{ $m }}</li>@endforeach</ul></div></div></div>
            @endif
        </div>
    @endif

    <div class="card"><div class="card-body">
        <p class="text-muted">@lang('users.photos_intro')</p>
        <form method="POST" action="{{ route('admin.users.students.photos.import') }}" enctype="multipart/form-data">
            @csrf
            <div class="form-group mb-3">
                <label class="form-label">@lang('users.photos_field')</label>
                <input type="file" name="photos[]" class="form-control" accept="image/*" multiple required>
                <small class="form-text text-muted">@lang('users.photos_match_hint')</small>
            </div>
            <button type="submit" class="btn btn-primary"><i class="la la-upload"></i> @lang('users.photos_import_btn')</button>
            <a href="{{ route('admin.users.students.index') }}" class="btn btn-outline-secondary">@lang('users.cancel')</a>
        </form>
    </div></div>
</div>
@endsection
