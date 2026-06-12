@extends('layouts.app')

@section('title', __('special_education.title'))
@section('body_class', 'theme-light')

@php $isRtl = app()->getLocale() === 'ar'; @endphp

@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-9 col-12 mb-2">
        <h2 class="content-header-title float-{{ $isRtl ? 'right' : 'left' }} mb-0">
            @lang('special_education.title')
        </h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('special_education.breadcrumb_home')</a></li>
                <li class="breadcrumb-item active">@lang('special_education.title')</li>
            </ol>
        </div>
    </div>
    <div class="content-header-right text-md-{{ $isRtl ? 'left' : 'right' }} col-md-3 col-12 d-flex justify-content-{{ $isRtl ? 'start' : 'end' }} gap-2 flex-wrap">
        <a href="{{ route('manage.special-education.create') }}" class="btn btn-primary">
            <i class="la la-plus"></i> @lang('special_education.btn_add')
        </a>
    </div>
</div>


{{-- Filters --}}
<div class="card mb-1">
    <div class="card-content collapse show">
        <div class="card-body py-1">
            <form method="GET" action="{{ route('manage.special-education.index') }}" class="form-row align-items-end">
                <div class="col-md-3 col-12 mb-1">
                    <input type="text" name="search" class="form-control form-control-sm"
                        placeholder="@lang('special_education.filter_search')"
                        value="{{ $filters['search'] ?? '' }}">
                </div>
                <div class="col-md-2 col-6 mb-1">
                    <select name="category" class="form-control form-control-sm">
                        <option value="">@lang('special_education.filter_all_categories')</option>
                        @foreach(['learning_disability','gifted','speech','physical','behavioral','visual','hearing','other'] as $cat)
                            <option value="{{ $cat }}" {{ ($filters['category'] ?? '') === $cat ? 'selected' : '' }}>
                                @lang('special_education.category_' . $cat)
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 col-6 mb-1">
                    <select name="status" class="form-control form-control-sm">
                        <option value="">@lang('special_education.filter_all_statuses')</option>
                        @foreach(['active','inactive','graduated'] as $st)
                            <option value="{{ $st }}" {{ ($filters['status'] ?? '') === $st ? 'selected' : '' }}>
                                @lang('special_education.student_status_' . $st)
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto mb-1 d-flex gap-1">
                    <button type="submit" class="btn btn-sm btn-primary">
                        <i class="la la-search"></i> @lang('special_education.btn_filter')
                    </button>
                    <a href="{{ route('manage.special-education.index') }}" class="btn btn-sm btn-secondary">
                        @lang('special_education.btn_reset')
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-content collapse show">
        <div class="card-body p-0">
            @forelse($students as $seStudent)
            @if($loop->first)
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>@lang('special_education.field_student')</th>
                            <th>@lang('special_education.field_category')</th>
                            <th>@lang('special_education.field_severity')</th>
                            <th>@lang('special_education.field_status')</th>
                            <th>@lang('special_education.field_assigned_specialist')</th>
                            <th>@lang('special_education.field_actions')</th>
                        </tr>
                    </thead>
                    <tbody>
            @endif
                        <tr>
                            <td>
                                <a href="{{ route('manage.special-education.show', $seStudent->id) }}" class="font-weight-bold">
                                    {{ $isRtl && $seStudent->student?->name_ar ? $seStudent->student->name_ar : $seStudent->student?->name }}
                                </a>
                            </td>
                            <td>
                                <span class="badge badge-info">{{ $seStudent->categoryLabel() }}</span>
                            </td>
                            <td>
                                @if($seStudent->severity)
                                    <span class="badge badge-{{ $seStudent->severity === 'severe' ? 'danger' : ($seStudent->severity === 'moderate' ? 'warning' : 'success') }}">
                                        {{ $seStudent->severityLabel() }}
                                    </span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge badge-{{ $seStudent->status === 'active' ? 'success' : ($seStudent->status === 'graduated' ? 'primary' : 'secondary') }}">
                                    {{ $seStudent->statusLabel() }}
                                </span>
                            </td>
                            <td>
                                {{ $seStudent->specialist ? ($isRtl && $seStudent->specialist->name_ar ? $seStudent->specialist->name_ar : $seStudent->specialist->name) : '—' }}
                            </td>
                            <td>
                                <a href="{{ route('manage.special-education.show', $seStudent->id) }}" class="btn btn-sm btn-outline-info" title="@lang('special_education.btn_show')">
                                    <i class="la la-eye"></i>
                                </a>
                                <a href="{{ route('manage.special-education.edit', $seStudent->id) }}" class="btn btn-sm btn-outline-primary" title="@lang('special_education.btn_edit')">
                                    <i class="la la-edit"></i>
                                </a>
                                <form action="{{ route('manage.special-education.destroy', $seStudent->id) }}" method="POST" class="d-inline" id="del-form-{{ $seStudent->id }}">
                                    @csrf @method('DELETE')
                                    <button type="button" class="btn btn-sm btn-outline-danger btn-delete" data-id="{{ $seStudent->id }}" title="@lang('special_education.btn_delete')">
                                        <i class="la la-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
            @if($loop->last)
                    </tbody>
                </table>
            </div>
            @endif
            @empty
                <p class="p-2 text-muted text-center">@lang('special_education.no_students')</p>
            @endforelse
        </div>
    </div>
</div>

@if($students->hasPages())
<div class="mt-1">
    {{ $students->links() }}
</div>
@endif
@endsection

@push('scripts')
<script>
$(document).on('click', '.btn-delete', function () {
    var id  = $(this).data('id');
    var msg = '@lang('special_education.confirm_delete')';
    window.vcConfirm({ title: msg }).then(function (r) {
        if (r.isConfirmed) {
            document.getElementById('del-form-' + id).submit();
        }
    });
});
</script>
@endpush
