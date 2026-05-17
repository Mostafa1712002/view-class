@extends('layouts.app')

@section('title', trans('grades_admin.reports_title'))

@section('body_class', 'theme-light')

@section('content')
@php($isRtl = app()->getLocale() === 'ar')
@php($typeLabels = [
    'dynamic'    => trans('grades_admin.type_dynamic'),
    'static'     => trans('grades_admin.type_static'),
    'gradesheet' => trans('grades_admin.type_gradesheet'),
])
@php($typeBadges = [
    'dynamic'    => 'primary',
    'static'     => 'info',
    'gradesheet' => 'success',
])
<div class="content-header row">
    <div class="content-header-left col-md-9 col-12 mb-2">
        <h2 class="content-header-title float-{{ $isRtl ? 'right' : 'left' }} mb-0">
            {{ trans('grades_admin.reports_title') }}
        </h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('grades_admin.home')</a></li>
                <li class="breadcrumb-item active">{{ trans('grades_admin.reports_title') }}</li>
            </ol>
        </div>
    </div>
    <div class="content-header-right text-md-{{ $isRtl ? 'left' : 'right' }} col-md-3 col-12">
        <a href="{{ route('admin.grade-reports.create') }}" class="btn btn-primary">
            <i class="la la-plus"></i> {{ trans('grades_admin.create_report') }}
        </a>
    </div>
</div>

<div class="content-body">
    @include('components.alerts')

    <div class="alert alert-info">
        <i class="la la-info-circle"></i>
        {{ trans('grades_admin.reports_intro') }}
    </div>

    {{-- Type filter --}}
    <div class="card mb-3">
        <div class="card-body py-2">
            <div class="btn-group">
                <a href="{{ route('admin.grade-reports.index') }}"
                   class="btn btn-sm btn-outline-{{ !request('type') ? 'primary' : 'secondary' }}">
                    {{ trans('grades_admin.filter_all') }}
                </a>
                @foreach($typeLabels as $k => $label)
                    <a href="{{ route('admin.grade-reports.index', ['type' => $k]) }}"
                       class="btn btn-sm btn-outline-{{ request('type') === $k ? 'primary' : 'secondary' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>
        </div>
    </div>

    @if($reports->isEmpty())
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="la la-file-alt la-3x text-muted"></i>
                <p class="mt-3 mb-0">{{ trans('grades_admin.no_reports') }}
                    <a href="{{ route('admin.grade-reports.create') }}">{{ trans('grades_admin.create_report') }}</a>
                </p>
            </div>
        </div>
    @else
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>{{ trans('grades_admin.report_title') }}</th>
                                <th>{{ trans('grades_admin.report_type') }}</th>
                                <th>{{ trans('grades_admin.report_status') }}</th>
                                <th>{{ trans('grades_admin.class') }}</th>
                                <th>{{ trans('grades_admin.subject') }}</th>
                                <th>{{ trans('grades_admin.columns_count') }}</th>
                                <th>{{ trans('grades_admin.opens_at') }}</th>
                                <th>{{ trans('grades_admin.closes_at') }}</th>
                                <th class="text-center" style="width:160px;">{{ trans('common.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($reports as $r)
                                <tr>
                                    <td>
                                        @if($r->is_locked) <i class="la la-lock text-warning" title="{{ trans('grades_admin.locked') }}"></i> @endif
                                        {{ $r->title }}
                                    </td>
                                    <td>
                                        <span class="badge badge-{{ $typeBadges[$r->type] ?? 'secondary' }}">
                                            {{ $typeLabels[$r->type] ?? $r->type }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($r->is_active)
                                            <span class="badge badge-success">{{ trans('grades_admin.active') }}</span>
                                        @else
                                            <span class="badge badge-secondary">{{ trans('grades_admin.inactive') }}</span>
                                        @endif
                                    </td>
                                    <td>{{ $r->classRoom?->name ?? '—' }}</td>
                                    <td>{{ $r->subject?->name ?? '—' }}</td>
                                    <td>{{ $r->columns_count }}</td>
                                    <td>{{ $r->opens_at?->format('Y-m-d') ?? '—' }}</td>
                                    <td>{{ $r->closes_at?->format('Y-m-d') ?? '—' }}</td>
                                    <td class="text-center">
                                        <a href="{{ route('admin.grade-reports.edit', $r->id) }}"
                                           class="btn btn-sm btn-outline-primary"
                                           title="{{ trans('grades_admin.edit_report') }}">
                                            <i class="la la-edit"></i>
                                        </a>
                                        <a href="{{ route('admin.grade-reports.show', $r->id) }}"
                                           class="btn btn-sm btn-outline-info"
                                           title="{{ trans('common.show') }}">
                                            <i class="la la-eye"></i>
                                        </a>
                                        <form method="POST"
                                              action="{{ route('admin.grade-reports.destroy', $r->id) }}"
                                              class="d-inline"
                                              onsubmit="return confirm('{{ trans('grades_admin.delete_confirm') }}');">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger"
                                                    title="{{ trans('grades_admin.delete_report') }}">
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
            @if($reports->hasPages())
                <div class="card-footer">{{ $reports->links() }}</div>
            @endif
        </div>
    @endif
</div>
@endsection
