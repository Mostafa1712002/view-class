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
    @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0 pr-3">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

    <div class="card mb-3"><div class="card-body">
        <form method="GET" action="{{ route('admin.users.students.status') }}" class="form-row align-items-end">
            <div class="form-group col-md-5 mb-0"><input type="text" name="q" value="{{ $q ?? '' }}" class="form-control form-control-sm" placeholder="@lang('users.search_name')"></div>
            <div class="form-group col-md-2 mb-0"><button type="submit" class="btn btn-primary btn-sm"><i class="la la-search"></i> @lang('users.search')</button></div>
        </form>
    </div></div>

    <form method="POST" action="{{ route('admin.users.students.bulk') }}">
        @csrf
        <div class="card">
            <div class="card-body d-flex flex-wrap align-items-end" style="gap:.75rem;">
                <div class="form-group mb-0">
                    <label class="form-label small mb-1">@lang('users.status_new')</label>
                    <select name="action" class="custom-select custom-select-sm" required>
                        <option value="license">@lang('users.status_active')</option>
                        <option value="unlicense">@lang('users.status_inactive')</option>
                        <option value="waiting">@lang('users.status_pending')</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary btn-sm"><i class="la la-save"></i> @lang('users.status_apply')</button>
                <small class="text-muted">@lang('users.status_apply_hint')</small>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th style="width:42px"><input type="checkbox" id="chk-all"></th>
                            <th>@lang('users.name')</th>
                            <th>@lang('users.status_current')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($students as $s)
                            <tr>
                                <td><input type="checkbox" name="ids[]" value="{{ $s->id }}" class="chk-row"></td>
                                <td>{{ $s->name }}</td>
                                <td>
                                    @if($s->is_active)<span class="badge badge-success">@lang('users.status_active')</span>
                                    @elseif(($s->status ?? '') === 'pending')<span class="badge badge-warning">@lang('users.status_pending')</span>
                                    @else<span class="badge badge-secondary">@lang('users.status_inactive')</span>@endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center py-4 text-muted">@lang('users.no_students_found')</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
jQuery(function ($) {
    $('#chk-all').on('change', function () { $('.chk-row').prop('checked', this.checked); });
});
</script>
@endpush
@endsection
