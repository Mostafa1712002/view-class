@extends('layouts.app')
@section('title', __('users.parents'))
@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-8 col-12 mb-2">
        <h2 class="content-header-title mb-0">@lang('users.parents')</h2>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
            <li class="breadcrumb-item active">@lang('users.parents')</li>
        </ol>
    </div>
    <div class="content-header-right col-md-4 col-12">
        <form action="{{ route('admin.users.parents.index') }}" method="GET" class="d-flex">
            <input type="search" name="q" value="{{ $q }}" class="form-control form-control-sm me-1" placeholder="@lang('users.search_placeholder')" />
            <button class="btn btn-outline-primary btn-sm"><i class="la la-search"></i></button>
        </form>
    </div>
</div>
<div class="content-body">
    @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div class="dropdown">
                <button class="btn btn-primary btn-sm dropdown-toggle" data-toggle="dropdown" data-bs-toggle="dropdown">
                    <i class="la la-plus"></i> @lang('users.add') ▼
                </button>
                <div class="dropdown-menu">
                    <a class="dropdown-item" href="{{ route('admin.users.parents.create') }}"><i class="la la-user-plus"></i> @lang('users.add_parent')</a>
                    <a class="dropdown-item disabled" href="#"><i class="la la-file-excel"></i> @lang('users.import_excel')</a>
                </div>
            </div>
            <div class="text-muted small">{{ $parents->total() }} @lang('users.parents')</div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="thead-light">
                    <tr>
                        <th><input type="checkbox" /></th>
                        <th>@lang('users.name')</th>
                        <th>@lang('users.username')</th>
                        <th>@lang('users.national_id')</th>
                        <th>@lang('users.gender')</th>
                        <th>@lang('users.phone')</th>
                        <th>@lang('users.children_count')</th>
                        <th>@lang('users.last_activity')</th>
                        <th>@lang('users.actions')</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($parents as $u)
                    <tr>
                        <td><input type="checkbox" value="{{ $u->id }}" /></td>
                        <td>{{ $u->name }}</td>
                        <td>{{ $u->username }}</td>
                        <td>{{ $u->national_id ?? '—' }}</td>
                        <td>{{ $u->gender ? __('users.gender_'.$u->gender) : '—' }}</td>
                        <td>{{ $u->phone ?? '—' }}</td>
                        <td>{{ $u->children?->count() ?? 0 }}</td>
                        <td>{{ $u->last_login_at ? $u->last_login_at->diffForHumans() : '—' }}</td>
                        <td>
                            <a href="{{ route('admin.users.parents.edit', $u->id) }}" class="btn btn-sm btn-outline-primary"><i class="la la-edit"></i></a>
                            <form action="{{ route('admin.users.parents.destroy', $u->id) }}" method="POST" class="d-inline" onsubmit="return confirm('@lang('users.delete')?');">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"><i class="la la-trash"></i></button>
                            </form>
                            <div class="dropdown d-inline">
                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-toggle="dropdown" data-bs-toggle="dropdown">
                                    <i class="la la-ellipsis-h"></i>
                                </button>
                                <div class="dropdown-menu">
                                    <a class="dropdown-item" href="{{ route('admin.users.parents.students', $u->id) }}"><i class="la la-user-graduate"></i> @lang('users.students_link')</a>
                                    <a class="dropdown-item disabled" href="#"><i class="la la-key"></i> @lang('users.permissions_link')</a>
                                    @if(auth()->user()->isSuperAdmin())
                                    <form action="{{ route('admin.users.impersonate.start', $u->id) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="dropdown-item"><i class="la la-user-secret"></i> @lang('users.login_as')</button>
                                    </form>
                                    @endif
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
        <div class="card-footer">{{ $parents->links() }}</div>
    </div>
</div>
@endsection
