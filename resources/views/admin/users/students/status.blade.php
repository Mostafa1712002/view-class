@extends('layouts.app')
@section('body_class','theme-light')
@section('title', __('users.refresh_status'))
@section('content')
<div class="content-header row">
    <div class="content-header-left col-12 mb-2">
        <h2 class="content-header-title mb-0">@lang('users.refresh_status')</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.users.students.index') }}">@lang('users.students')</a></li>
                <li class="breadcrumb-item active">@lang('users.refresh_status')</li>
            </ol>
        </div>
    </div>
</div>
<div class="content-body">
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0 pr-3">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

    @php $result = session('update_result'); @endphp
    @if($result)
        <div class="alert {{ $result['skipped'] ? 'alert-warning' : 'alert-success' }}">
            @lang('users.update_updated_count', ['n' => $result['updated']])
            @if($result['skipped']) — @lang('users.update_skipped_count', ['n' => $result['skipped']]) @endif
        </div>
        <div class="card mb-3">
            <div class="table-responsive"><table class="table table-sm mb-0">
                <thead><tr><th>@lang('users.update_row')</th><th>@lang('users.update_key')</th><th>@lang('users.photos_status')</th><th>@lang('users.photos_reason')</th></tr></thead>
                <tbody>
                    @foreach($result['rows'] as $r)
                        <tr>
                            <td>{{ $r['row'] }}</td>
                            <td>{{ $r['key'] }}</td>
                            <td>@if($r['ok'])<span class="badge badge-success">@lang('users.photos_ok')</span>@else<span class="badge badge-danger">@lang('users.photos_failed')</span>@endif</td>
                            <td>{{ $r['reason'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table></div>
        </div>
    @endif

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0"><i class="la la-file-excel"></i> @lang('users.update_card_title')</h4>
            <a href="{{ route('admin.users.students.import.template') }}" class="btn btn-light btn-sm">
                <i class="la la-download"></i> @lang('users.update_download_template')
            </a>
        </div>
        <div class="card-body">
            <div class="alert alert-info">
                <ul class="mb-0 pr-3">
                    <li>@lang('users.update_rule_match')</li>
                    <li>@lang('users.update_rule_nocreate')</li>
                    <li>@lang('users.update_rule_nopassword')</li>
                </ul>
            </div>
            <form method="POST" action="{{ route('admin.users.students.status.update') }}" enctype="multipart/form-data">
                @csrf
                <div class="form-group mb-3">
                    <label class="form-label">@lang('users.update_field')</label>
                    <input type="file" name="file" class="form-control" accept=".csv,.txt,.xlsx,.xls" required>
                </div>
                <button type="submit" class="btn btn-primary"><i class="la la-save"></i> @lang('users.update_run_btn')</button>
                <a href="{{ route('admin.users.students.index') }}" class="btn btn-outline-secondary">@lang('users.cancel')</a>
            </form>
        </div>
    </div>
</div>
@endsection
