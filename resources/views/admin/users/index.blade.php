@extends('layouts.app')

@section('title', __('common.users'))

@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-9 col-12 mb-2">
        <div class="row breadcrumbs-top">
            <div class="col-12">
                <h2 class="content-header-title float-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }} mb-0">@lang('common.users')</h2>
                <div class="breadcrumb-wrapper">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
                        <li class="breadcrumb-item active">@lang('common.users')</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    <div class="content-header-right text-md-left col-md-3 col-12">
        <a href="{{ route('manage.users.create') }}" class="btn btn-primary">
            <i data-feather="plus"></i> @lang('common.create')
        </a>
    </div>
</div>

<div class="content-body">
    @include('components.alerts')

    <!-- Search & Filter -->
    <div class="card mb-3">
        <div class="card-body">
            <form action="{{ route('manage.users.index') }}" method="GET" class="row g-3">
                <div class="col-md-4">
                    <input type="text" class="form-control" name="search" placeholder="بحث بالاسم أو البريد..." value="{{ request('search') }}">
                </div>
                <div class="col-md-3">
                    <select class="form-select" name="role">
                        <option value="">كل الأدوار</option>
                        @foreach($roles as $role)
                            <option value="{{ $role->slug }}" {{ request('role') == $role->slug ? 'selected' : '' }}>
                                {{ $role->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">بحث</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>@lang('common.name')</th>
                            <th>@lang('common.email')</th>
                            <th>الأدوار</th>
                            @if(Auth::user()->isSuperAdmin())
                            <th>@lang('common.school')</th>
                            @endif
                            <th>@lang('common.status')</th>
                            <th>@lang('common.actions')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $user)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $user->name }}</td>
                            <td>{{ $user->email }}</td>
                            <td>
                                @foreach($user->roles as $role)
                                    <span class="badge bg-primary">{{ $role->name }}</span>
                                @endforeach
                            </td>
                            @if(Auth::user()->isSuperAdmin())
                            <td>{{ $user->school->name ?? '-' }}</td>
                            @endif
                            <td>
                                @if($user->is_active)
                                    <span class="badge bg-success">@lang('common.active')</span>
                                @else
                                    <span class="badge bg-secondary">@lang('common.inactive')</span>
                                @endif
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <a href="{{ route('manage.users.show', $user) }}" class="btn btn-sm btn-info">
                                        <i data-feather="eye"></i>
                                    </a>
                                    <a href="{{ route('manage.users.edit', $user) }}" class="btn btn-sm btn-warning">
                                        <i data-feather="edit"></i>
                                    </a>
                                    @if($user->id !== Auth::id())
                                    <form action="{{ route('manage.users.destroy', $user) }}" method="POST" class="d-inline" onsubmit="return confirm(@json(__('common.confirm_delete')))">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger">
                                            <i data-feather="trash-2"></i>
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="{{ Auth::user()->isSuperAdmin() ? 7 : 6 }}" class="text-center">لا يوجد مستخدمين</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $users->appends(request()->query())->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
