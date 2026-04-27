@extends('layouts.app')
@section('title', __('users.teachers'))
@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-8 col-12 mb-2">
        <h2 class="content-header-title mb-0">@lang('users.teachers')</h2>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
            <li class="breadcrumb-item active">@lang('users.teachers')</li>
        </ol>
    </div>
    <div class="content-header-right col-md-4 col-12">
        <form action="{{ route('admin.users.teachers.index') }}" method="GET" class="d-flex">
            <input type="search" name="q" value="{{ $q }}" class="form-control form-control-sm me-1" placeholder="@lang('users.search_placeholder')" />
            <button class="btn btn-outline-primary btn-sm"><i class="la la-search"></i></button>
        </form>
    </div>
</div>
<div class="content-body">
    @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif

    <div class="card">
        <div class="card-header d-flex flex-wrap gap-1 justify-content-between align-items-center">
            <div class="btn-group btn-group-sm">
                <a href="{{ route('admin.users.teachers.create') }}" class="btn btn-primary"><i class="la la-plus"></i> @lang('users.add_teacher')</a>
                <button class="btn btn-outline-secondary disabled"><i class="la la-file-excel"></i> @lang('users.import_excel')</button>
                <a href="{{ route('admin.users.teachers.workloads') }}" class="btn btn-outline-info"><i class="la la-chart-bar"></i> @lang('users.workloads_btn')</a>
            </div>
            <div class="text-muted small">{{ $teachers->total() }} @lang('users.teachers')</div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="thead-light">
                    <tr>
                        <th><input type="checkbox" /></th>
                        <th>@lang('users.name')</th>
                        <th>@lang('users.username')</th>
                        <th>@lang('users.national_id')</th>
                        <th>@lang('users.employee_id')</th>
                        <th>@lang('users.specialization')</th>
                        <th>@lang('users.last_activity')</th>
                        <th>@lang('users.actions')</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($teachers as $u)
                    <tr>
                        <td><input type="checkbox" value="{{ $u->id }}" /></td>
                        <td>{{ $u->name }}</td>
                        <td>{{ $u->username }}</td>
                        <td>{{ $u->national_id ?? '—' }}</td>
                        <td>{{ $u->employee_id ?? '—' }}</td>
                        <td>{{ $u->specialization ?? '—' }}</td>
                        <td>{{ $u->last_login_at ? $u->last_login_at->diffForHumans() : '—' }}</td>
                        <td>
                            <a href="{{ route('admin.users.teachers.edit', $u->id) }}" class="btn btn-sm btn-outline-primary"><i class="la la-edit"></i></a>
                            <form action="{{ route('admin.users.teachers.destroy', $u->id) }}" method="POST" class="d-inline" onsubmit="return confirm('@lang('users.delete')?');">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"><i class="la la-trash"></i></button>
                            </form>
                            @if(auth()->user()->isSuperAdmin())
                            <form action="{{ route('admin.users.impersonate.start', $u->id) }}" method="POST" class="d-inline">
                                @csrf
                                <button class="btn btn-sm btn-outline-warning" title="@lang('users.login_as')"><i class="la la-user-secret"></i></button>
                            </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">@lang('users.no_results')</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $teachers->links() }}</div>
    </div>
</div>
@endsection
