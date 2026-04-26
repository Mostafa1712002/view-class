@extends('layouts.app')

@section('title', __('schools.permissions'))

@section('content')
<div class="content-header row">
    <div class="content-header-left col-12 mb-2">
        <h2 class="content-header-title float-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }} mb-0">
            @lang('schools.permissions') — {{ app()->getLocale() === 'en' ? ($school->name_en ?: $school->name_ar) : ($school->name_ar ?: $school->name) }}
        </h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.schools.index') }}">@lang('schools.title')</a></li>
                <li class="breadcrumb-item active">@lang('schools.permissions')</li>
            </ol>
        </div>
    </div>
</div>

<div class="content-body">
    @include('components.alerts')

    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-end mb-3">
                <button type="button" class="btn btn-outline-secondary btn-sm" disabled><i class="la la-copy"></i> @lang('schools.copy_permissions')</button>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <h6>@lang('schools.permission_roles')</h6>
                    <div class="list-group">
                        @forelse($roles as $role)
                            <button type="button" class="list-group-item list-group-item-action">{{ $role->name }}</button>
                        @empty
                            <div class="list-group-item text-muted">@lang('common.no_data')</div>
                        @endforelse
                    </div>
                </div>
                <div class="col-md-4">
                    <h6>@lang('schools.permission_main_functions')</h6>
                    <div class="list-group">
                        <div class="list-group-item text-muted">@lang('schools.permissions_pick_role')</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <h6>@lang('schools.permission_sub_functions')</h6>
                    <div class="list-group">
                        <div class="list-group-item text-muted">@lang('schools.permissions_pick_function')</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
