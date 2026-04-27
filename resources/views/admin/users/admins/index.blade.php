@extends('layouts.app')
@section('title', __('users.admins'))
@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-8 col-12 mb-2">
        <h2 class="content-header-title mb-0">@lang('users.admins')</h2>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
            <li class="breadcrumb-item active">@lang('users.admins')</li>
        </ol>
    </div>
    <div class="content-header-right col-md-4 col-12">
        <form action="{{ route('admin.users.admins.index') }}" method="GET" class="d-flex">
            <input type="search" name="q" value="{{ $q }}" class="form-control form-control-sm me-1" placeholder="@lang('users.search_placeholder')" />
            <button class="btn btn-outline-primary btn-sm"><i class="la la-search"></i></button>
        </form>
    </div>
</div>
<div class="content-body">
    @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div class="btn-group btn-group-sm">
                <div class="dropdown">
                    <button class="btn btn-primary dropdown-toggle" data-toggle="dropdown" data-bs-toggle="dropdown">
                        <i class="la la-plus"></i> @lang('users.add') ▼
                    </button>
                    <div class="dropdown-menu">
                        @foreach($jobTitles as $jt)
                            <a class="dropdown-item" href="{{ route('admin.users.admins.create', ['job_title_id' => $jt->id]) }}">
                                <i class="la la-tag"></i> {{ $jt->localized_name }}
                            </a>
                        @endforeach
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="{{ route('admin.users.admins.create') }}">
                            <i class="la la-user-plus"></i> @lang('users.add_admin')
                        </a>
                    </div>
                </div>
                <a class="btn btn-outline-secondary ms-1" href="{{ route('admin.users.job-titles.index') }}">
                    <i class="la la-tag"></i> @lang('users.manage_job_titles')
                </a>
            </div>
            <div class="text-muted small">{{ $admins->total() }} @lang('users.admins')</div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>@lang('users.name')</th>
                        <th>@lang('users.username')</th>
                        <th>@lang('users.job_title')</th>
                        <th>@lang('users.last_activity')</th>
                        <th>@lang('users.actions')</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($admins as $u)
                    <tr>
                        <td>{{ $u->name }}</td>
                        <td>{{ $u->username }}</td>
                        <td>
                            @if($u->jobTitle){{ $u->jobTitle->localized_name }}@else<span class="text-muted">—</span>@endif
                            @foreach($u->roles as $r)<span class="badge bg-secondary ms-1">{{ $r->name }}</span>@endforeach
                        </td>
                        <td>{{ $u->last_login_at ? $u->last_login_at->diffForHumans() : '—' }}</td>
                        <td>
                            <a href="{{ route('admin.users.admins.edit', $u->id) }}" class="btn btn-sm btn-outline-primary"><i class="la la-edit"></i></a>
                            <form action="{{ route('admin.users.admins.destroy', $u->id) }}" method="POST" class="d-inline" onsubmit="return confirm('@lang('users.delete')?');">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"><i class="la la-trash"></i></button>
                            </form>
                            @if($u->jobTitle && in_array($u->jobTitle->slug, ['supervisor', 'counselor']))
                                <a href="{{ route('admin.users.admins.supervisees', $u->id) }}" class="btn btn-sm btn-outline-info" title="@lang('users.supervisees_synced')"><i class="la la-users"></i></a>
                            @endif
                            @if(auth()->user()->isSuperAdmin())
                                <form action="{{ route('admin.users.impersonate.start', $u->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button class="btn btn-sm btn-outline-warning" title="@lang('users.login_as')"><i class="la la-user-secret"></i></button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">@lang('users.no_results')</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $admins->links() }}</div>
    </div>
</div>
@endsection
