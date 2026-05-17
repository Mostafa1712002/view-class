@extends('layouts.app')
@section('title', __('users.teachers'))
@section('body_class', 'theme-light')
@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-8 col-12 mb-2">
        <h2 class="content-header-title mb-0">@lang('users.teachers')</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
                <li class="breadcrumb-item active">@lang('users.teachers')</li>
            </ol>
        </div>
    </div>
    <div class="content-header-right col-md-4 col-12">
        <form action="{{ route('admin.users.teachers.index') }}" method="GET" class="d-flex">
            <input type="search" name="q" value="{{ $q }}" class="form-control form-control-sm me-1" placeholder="@lang('users.search_placeholder')" />
            <button class="btn btn-outline-primary btn-sm" type="submit"><i class="la la-search"></i></button>
        </form>
    </div>
</div>

<div class="content-body">
    @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

    <div class="card">
        <div class="card-header d-flex flex-wrap gap-2 justify-content-between align-items-center">
            <div class="btn-group btn-group-sm" role="group">
                <a href="{{ route('admin.users.teachers.create') }}" class="btn btn-primary">
                    <i class="la la-user-plus"></i> @lang('users.add_teacher')
                </a>
                <a href="{{ route('admin.users.teachers.import') }}" class="btn btn-outline-success">
                    <i class="la la-file-excel"></i> @lang('users.import_excel')
                </a>
                <a href="{{ route('admin.users.teachers.workloads') }}" class="btn btn-outline-info">
                    <i class="la la-chart-bar"></i> @lang('users.workloads_btn')
                </a>
            </div>
            <div class="text-muted small">
                <i class="la la-users"></i>
                {{ $teachers->total() }} @lang('users.teachers')
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="thead-light">
                    <tr>
                        <th style="width:32px"><input type="checkbox" id="js-check-all" /></th>
                        <th>@lang('users.name')</th>
                        <th>@lang('users.username')</th>
                        <th>@lang('users.national_id')</th>
                        <th>@lang('users.employee_id')</th>
                        <th>@lang('users.specialization')</th>
                        <th>@lang('users.status')</th>
                        <th>@lang('users.last_activity')</th>
                        <th class="text-end">@lang('users.actions')</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($teachers as $u)
                    <tr>
                        <td><input type="checkbox" class="js-row-check" value="{{ $u->id }}" /></td>
                        <td>
                            <strong>{{ $u->name }}</strong>
                            @if($u->email)<br><small class="text-muted">{{ $u->email }}</small>@endif
                        </td>
                        <td><code>{{ $u->username ?? '—' }}</code></td>
                        <td>{{ $u->national_id ?? '—' }}</td>
                        <td>{{ $u->employee_id ?? '—' }}</td>
                        <td>{{ $u->specialization ?? '—' }}</td>
                        <td>
                            @if($u->is_active)
                                <span class="badge bg-success">@lang('users.teacher_status_active')</span>
                            @else
                                <span class="badge bg-secondary">@lang('users.teacher_status_inactive')</span>
                            @endif
                        </td>
                        <td>{{ $u->last_login_at ? $u->last_login_at->diffForHumans() : '—' }}</td>
                        <td class="text-end">
                            <a href="{{ route('admin.users.teachers.show', $u->id) }}" class="btn btn-sm btn-outline-info" title="@lang('users.view')">
                                <i class="la la-eye"></i>
                            </a>
                            <a href="{{ route('admin.users.teachers.edit', $u->id) }}" class="btn btn-sm btn-outline-primary" title="@lang('users.edit')">
                                <i class="la la-edit"></i>
                            </a>
                            <form action="{{ route('admin.users.teachers.destroy', $u->id) }}" method="POST" class="d-inline" onsubmit="return confirm('@lang('users.delete')?');">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" title="@lang('users.delete')"><i class="la la-trash"></i></button>
                            </form>
                            <div class="dropdown d-inline">
                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-toggle="dropdown" data-bs-toggle="dropdown">
                                    <i class="la la-ellipsis-h"></i>
                                </button>
                                <div class="dropdown-menu dropdown-menu-end">
                                    @if(auth()->user()->isSuperAdmin())
                                    <form action="{{ route('admin.users.impersonate.start', $u->id) }}" method="POST">
                                        @csrf
                                        <button class="dropdown-item" type="submit"><i class="la la-user-secret"></i> @lang('users.login_as')</button>
                                    </form>
                                    @endif
                                    <a class="dropdown-item" href="{{ route('admin.users.teachers.workloads') }}"><i class="la la-calendar"></i> @lang('users.schedule_link')</a>
                                    <a class="dropdown-item disabled" href="#"><i class="la la-shield-alt"></i> @lang('users.permissions_link')</a>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="text-center text-muted py-4">@lang('users.no_results')</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $teachers->links() }}</div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var checkAll = document.getElementById('js-check-all');
    var rows = document.querySelectorAll('.js-row-check');
    if (checkAll) {
        checkAll.addEventListener('change', function () {
            rows.forEach(function (r) { r.checked = checkAll.checked; });
        });
    }
});
</script>
@endsection
