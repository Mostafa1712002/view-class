@extends('layouts.app')

@section('title', __('appointments.settings_title'))
@section('body_class', 'theme-light')

@php
    $isRtl      = app()->getLocale() === 'ar';
    $targetTypes = [
        'role'           => __('appointments.target_type_role'),
        'job_title'      => __('appointments.target_type_job_title'),
        'user'           => __('appointments.target_type_user'),
        'subject_teacher'=> __('appointments.target_type_subject_teacher'),
    ];
@endphp

@push('styles')
<style>
    .ap-roles-table { background:#fff; border:1px solid #e5e7eb; border-radius:14px; overflow:hidden; }
    .ap-roles-table table { margin:0; }
    .ap-roles-table thead th { background:#f8fafc !important; color:#475569 !important; font-weight:600; font-size:.78rem; text-transform:uppercase; letter-spacing:.5px; border-bottom:1px solid #e5e7eb; padding:.8rem 1rem; white-space:nowrap; }
    .ap-roles-table tbody td { padding:.85rem 1rem; vertical-align:middle; }
    .ap-roles-table tbody tr + tr td { border-top:1px solid #f1f5f9; }
    .ap-badge { display:inline-flex; align-items:center; padding:.2rem .6rem; border-radius:999px; font-size:.72rem; font-weight:600; }
    .ap-badge.on  { background:#dcfce7; color:#15803d; }
    .ap-badge.off { background:#f1f5f9; color:#64748b; }
</style>
@endpush

@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-9 col-12 mb-2">
        <h2 class="content-header-title float-{{ $isRtl ? 'right' : 'left' }} mb-0">
            @lang('appointments.settings_title')
        </h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('appointments.breadcrumb_home')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('manage.appointment-schedules.index') }}">@lang('appointments.breadcrumb_schedules')</a></li>
                <li class="breadcrumb-item active">@lang('appointments.breadcrumb_settings')</li>
            </ol>
        </div>
    </div>
    <div class="content-header-right text-md-{{ $isRtl ? 'left' : 'right' }} col-md-3 col-12 d-flex justify-content-{{ $isRtl ? 'start' : 'end' }}">
        <a href="{{ route('manage.appointment-schedules.index') }}" class="btn btn-secondary">
            @lang('appointments.breadcrumb_schedules')
        </a>
    </div>
</div>

@include('components.alerts')

{{-- Add role form --}}
<div class="card mb-2">
    <div class="card-header">
        <h4 class="card-title">@lang('appointments.btn_add_role')</h4>
    </div>
    <div class="card-content collapse show">
        <div class="card-body">
            <form action="{{ route('admin.appointment-settings.store') }}" method="POST">
                @csrf
                <div class="row">
                    <div class="col-md-4 form-group">
                        <label>@lang('appointments.field_label') <span class="text-danger">*</span></label>
                        <input type="text" name="label" class="form-control @error('label') is-invalid @enderror"
                               value="{{ old('label') }}" required maxlength="255">
                        @error('label')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3 form-group">
                        <label>@lang('appointments.field_target_type') <span class="text-danger">*</span></label>
                        <select name="target_type" class="form-control @error('target_type') is-invalid @enderror" required>
                            @foreach($targetTypes as $key => $label)
                                <option value="{{ $key }}" @selected(old('target_type') === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('target_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-2 form-group">
                        <label>@lang('appointments.field_target_id')</label>
                        <input type="number" name="target_id" class="form-control @error('target_id') is-invalid @enderror"
                               value="{{ old('target_id') }}" min="1">
                        @error('target_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-2 form-group">
                        <label>@lang('appointments.field_sort')</label>
                        <input type="number" name="sort" class="form-control" value="{{ old('sort', 0) }}" min="0">
                    </div>
                    <div class="col-md-1 form-group d-flex align-items-end">
                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="la la-plus"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Roles list --}}
<div class="card">
    <div class="card-header">
        <h4 class="card-title">@lang('appointments.bookable_roles')</h4>
    </div>
    <div class="card-content collapse show">
        <div class="card-body p-0">
            @if($roles->isEmpty())
                <div class="text-center text-muted py-4">
                    <i class="la la-list" style="font-size:2rem"></i>
                    <p class="mt-2">@lang('appointments.empty_roles_title')</p>
                    <small>@lang('appointments.empty_roles_hint')</small>
                </div>
            @else
                <div class="ap-roles-table">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>@lang('appointments.field_label')</th>
                                    <th>@lang('appointments.field_target_type')</th>
                                    <th>@lang('appointments.field_target_id')</th>
                                    <th>@lang('appointments.field_sort')</th>
                                    <th>@lang('appointments.field_is_active')</th>
                                    <th style="text-align:{{ $isRtl ? 'left' : 'right' }}">@lang('appointments.table_actions')</th>
                                </tr>
                            </thead>
                            <tbody>
                            @foreach($roles as $role)
                                <tr>
                                    <td>{{ $role->id }}</td>
                                    <td>{{ $role->label }}</td>
                                    <td>{{ $targetTypes[$role->target_type] ?? $role->target_type }}</td>
                                    <td>{{ $role->target_id ?? '—' }}</td>
                                    <td>{{ $role->sort }}</td>
                                    <td>
                                        <span class="ap-badge {{ $role->is_active ? 'on' : 'off' }}">
                                            {{ $role->is_active ? __('appointments.status_active') : __('appointments.status_inactive') }}
                                        </span>
                                    </td>
                                    <td style="text-align:{{ $isRtl ? 'left' : 'right' }}">
                                        {{-- Toggle --}}
                                        <form action="{{ route('admin.appointment-settings.toggle', $role->id) }}" method="POST" style="display:inline" class="form-toggle-role">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-{{ $role->is_active ? 'warning' : 'success' }}"
                                                data-confirm="{{ $role->is_active ? __('appointments.confirm_toggle_close') : __('appointments.confirm_toggle_open') }}">
                                                <i class="la la-{{ $role->is_active ? 'pause' : 'play' }}"></i>
                                            </button>
                                        </form>

                                        {{-- Delete --}}
                                        <form action="{{ route('admin.appointment-settings.destroy', $role->id) }}" method="POST" style="display:inline" class="form-delete-role">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger"
                                                data-confirm="{{ __('appointments.confirm_delete_role') }}">
                                                <i class="la la-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.form-toggle-role button[data-confirm], .form-delete-role button[data-confirm]').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            var form = btn.closest('form');
            var msg  = btn.getAttribute('data-confirm');
            if (window.vcConfirm) {
                window.vcConfirm(msg, function () { form.submit(); });
            } else if (confirm(msg)) {
                form.submit();
            }
        });
    });
});
</script>
@endpush
