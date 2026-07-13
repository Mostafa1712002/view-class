@extends('layouts.app')

@section('title', __('roles.title'))
@section('body_class', 'theme-light')

@push('styles')
<style>
.rl-header { margin-bottom: 1.25rem; }
.rl-header h2 { font-size: 1.4rem; font-weight: 700; color: #0f172a; display: flex; align-items: center; gap: .5rem; }
.rl-header .breadcrumb { padding: 0; margin: 0; background: transparent; font-size: .85rem; }

.rl-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 14px;
    box-shadow: 0 1px 2px rgba(15,23,42,.04); margin-bottom: 1.5rem; }
.rl-card .card-header { background: #f8fafc; border-bottom: 1px solid #e5e7eb; border-radius: 14px 14px 0 0;
    padding: .9rem 1.1rem; display: flex; align-items: center; gap: .5rem; }
.rl-card .card-header h5 { margin: 0; font-size: 1rem; font-weight: 700; color: #0f172a;
    display: flex; align-items: center; gap: .5rem; }
.rl-card .card-header h5 i { color: #C9A227; }

.rl-alert { display: flex; align-items: center; gap: .75rem;
    background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 10px;
    padding: .75rem 1rem; margin-bottom: 1rem; color: #166534; font-size: .88rem; }

.rl-table { width: 100%; border-collapse: collapse; font-size: .9rem; }
.rl-table thead th { background: #f1f5f9; color: #475569; font-weight: 600; font-size: .78rem;
    text-transform: uppercase; letter-spacing: .5px; padding: .6rem 1rem; border-bottom: 2px solid #e2e8f0; text-align: right; }
.rl-table tbody td { padding: .7rem 1rem; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
.rl-table tbody tr:hover { background: #fafbfc; }
.rl-table .role-name { font-weight: 700; color: #0f172a; }
.rl-table .role-slug { color: #64748b; font-size: .8rem; }
.rl-badge { display: inline-block; padding: .15rem .55rem; border-radius: 999px; font-size: .72rem; font-weight: 600; }
.rl-badge.on  { background: #dcfce7; color: #15803d; }
.rl-badge.off { background: #fee2e2; color: #b91c1c; }
.rl-count { background: #f1f5f9; color: #334155; border-radius: 999px; padding: .15rem .6rem; font-size: .8rem; font-weight: 600; }

.btn-gold { background: linear-gradient(135deg, #C9A227, #a07d1b);
    color: #fff; border: none; padding: .4rem 1rem; border-radius: 8px;
    font-weight: 600; font-size: .85rem; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: .35rem; }
.btn-gold:hover { opacity: .9; color: #fff; text-decoration: none; }
</style>
@endpush

@section('content')
<div class="content-header rl-header">
    <h2><i class="la la-user-shield"></i> @lang('roles.title')</h2>
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('roles.home')</a></li>
        <li class="breadcrumb-item active">@lang('roles.title')</li>
    </ol>
</div>

<div class="content-body">
    @if(session('status'))
        <div class="rl-alert"><i class="la la-check-circle"></i><span>{{ session('status') }}</span></div>
    @endif

    <div class="rl-card">
        <div class="card-header">
            <h5><i class="la la-user-shield"></i> @lang('roles.list_heading')</h5>
        </div>
        <div class="table-responsive">
            <table class="rl-table">
                <thead>
                    <tr>
                        <th>@lang('roles.col_role')</th>
                        <th>@lang('roles.col_description')</th>
                        <th style="width:120px;">@lang('roles.col_users')</th>
                        <th style="width:110px;">@lang('roles.col_status')</th>
                        <th style="width:150px;"></th>
                    </tr>
                </thead>
                <tbody>
                @foreach($roles as $role)
                    <tr>
                        <td>
                            <div class="role-name">{{ $role->name }}</div>
                            <div class="role-slug">{{ $role->slug }}</div>
                        </td>
                        <td>{{ $role->description }}</td>
                        <td><span class="rl-count">{{ $role->users_count }}</span></td>
                        <td>
                            <span class="rl-badge {{ $role->is_active ? 'on' : 'off' }}">
                                {{ $role->is_active ? __('roles.active') : __('roles.inactive') }}
                            </span>
                        </td>
                        <td>
                            <a href="{{ route('admin.roles.permissions.edit', $role) }}" class="btn-gold">
                                <i class="la la-list-check"></i> @lang('roles.manage_permissions')
                            </a>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
