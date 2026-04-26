@extends('layouts.app')

@section('title', __('schools.academic_years'))

@section('content')
<div class="content-header row">
    <div class="content-header-left col-12 mb-2">
        <h2 class="content-header-title float-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }} mb-0">
            @lang('schools.academic_years') — {{ app()->getLocale() === 'en' ? ($school->name_en ?: $school->name_ar) : ($school->name_ar ?: $school->name) }}
        </h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.schools.index') }}">@lang('schools.title')</a></li>
                <li class="breadcrumb-item active">@lang('schools.academic_years')</li>
            </ol>
        </div>
    </div>
</div>

<div class="content-body">
    @include('components.alerts')

    <div class="card">
        <div class="card-body">
            <div class="d-flex flex-wrap gap-1 mb-3">
                <a href="{{ route('admin.academic-years.create') }}" class="btn btn-primary btn-sm"><i class="la la-plus"></i> @lang('schools.add_academic_year')</a>
                <button type="button" class="btn btn-outline-secondary btn-sm" disabled><i class="la la-share"></i> @lang('schools.promote_to_new_year')</button>
                <button type="button" class="btn btn-outline-secondary btn-sm" disabled><i class="la la-calendar-week"></i> @lang('schools.study_weeks')</button>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>@lang('schools.academic_year')</th>
                            <th>@lang('schools.year_start')</th>
                            <th>@lang('schools.year_end')</th>
                            <th>@lang('common.status')</th>
                            <th>@lang('common.actions')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($years as $year)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $year->name }}</td>
                                <td>{{ optional($year->start_date)->format('Y-m-d') }}</td>
                                <td>{{ optional($year->end_date)->format('Y-m-d') }}</td>
                                <td>
                                    @if($year->is_current)
                                        <span class="badge bg-success">@lang('schools.current')</span>
                                    @else
                                        <span class="badge bg-secondary">—</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('admin.academic-years.edit', $year) }}" class="btn btn-sm btn-outline-warning"><i class="la la-pen"></i></a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center">@lang('common.no_data')</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
