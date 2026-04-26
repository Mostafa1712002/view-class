@extends('layouts.app')

@section('title', __('schools.grade_levels'))

@section('content')
<div class="content-header row">
    <div class="content-header-left col-12 mb-2">
        <h2 class="content-header-title float-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }} mb-0">
            @lang('schools.grade_levels') — {{ app()->getLocale() === 'en' ? ($school->name_en ?: $school->name_ar) : ($school->name_ar ?: $school->name) }}
        </h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.schools.index') }}">@lang('schools.title')</a></li>
                <li class="breadcrumb-item active">@lang('schools.grade_levels')</li>
            </ol>
        </div>
    </div>
</div>

<div class="content-body">
    @include('components.alerts')

    <div class="card">
        <div class="card-body">
            <div class="d-flex flex-wrap gap-1 mb-3">
                <a href="{{ route('manage.sections.create') }}" class="btn btn-primary btn-sm"><i class="la la-plus"></i> @lang('schools.add_section')</a>
                <button type="button" class="btn btn-outline-secondary btn-sm" disabled><i class="la la-list-ol"></i> @lang('schools.assign_grade_levels')</button>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>@lang('common.section')</th>
                            <th>@lang('schools.stage')</th>
                            <th>@lang('schools.classes_count')</th>
                            <th>@lang('common.actions')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($sections as $section)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $section->name }}</td>
                                <td>{{ $section->stage ?? '-' }}</td>
                                <td>{{ $section->classes_count }}</td>
                                <td>
                                    <a href="{{ route('manage.sections.edit', $section) }}" class="btn btn-sm btn-outline-warning"><i class="la la-pen"></i></a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center">@lang('common.no_data')</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
