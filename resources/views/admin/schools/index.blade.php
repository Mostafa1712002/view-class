@extends('layouts.app')

@section('title', __('schools.title'))

@section('body_class', 'theme-light')

@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-8 col-12 mb-2">
        <div class="row breadcrumbs-top">
            <div class="col-12">
                <h2 class="content-header-title float-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }} mb-0">@lang('schools.title')</h2>
                <div class="breadcrumb-wrapper">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
                        <li class="breadcrumb-item active">@lang('schools.title')</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    <div class="content-header-right col-md-4 col-12">
        <div class="d-flex flex-wrap gap-1 justify-content-md-end">
            <a href="{{ route('admin.schools.create') }}" class="btn btn-primary btn-sm">
                <x-svg-icon name="plus" /> @lang('schools.add_school')
            </a>
            <a href="{{ route('admin.school-branches.index') }}" class="btn btn-outline-secondary btn-sm" title="@lang('schools.branches')">
                <x-svg-icon name="diagram-3" /> @lang('schools.branches')
            </a>
            <a href="{{ route('admin.sms-services.index') }}" class="btn btn-outline-secondary btn-sm" title="@lang('schools.extra_services')">
                <x-svg-icon name="puzzle" /> @lang('schools.extra_services')
            </a>
        </div>
    </div>
</div>

<div class="content-body">
    @include('components.alerts')

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>@lang('schools.name')</th>
                            <th>@lang('schools.branch')</th>
                            <th>@lang('schools.sort_order')</th>
                            <th>@lang('schools.sections_count')</th>
                            <th>@lang('schools.classes_count')</th>
                            <th>@lang('schools.students_count')</th>
                            <th>@lang('schools.licensed_students_count')</th>
                            <th>@lang('common.status')</th>
                            <th>@lang('common.actions')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($schools as $school)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>
                                <strong>{{ app()->getLocale() === 'en' ? ($school->name_en ?: $school->name_ar ?: $school->name) : ($school->name_ar ?: $school->name) }}</strong>
                                @if($school->code)
                                    <br><small class="text-muted">{{ $school->code }}</small>
                                @endif
                            </td>
                            <td>{{ $school->branch ?? '-' }}</td>
                            <td>{{ $school->sort_order ?? '-' }}</td>
                            <td>{{ $school->sections_count }}</td>
                            <td>{{ $school->classes_count }}</td>
                            <td>{{ $school->students_count }}</td>
                            <td>{{ $school->licensed_students_count }}</td>
                            <td>
                                @if($school->is_active)
                                    <span class="badge bg-success">@lang('common.active')</span>
                                @else
                                    <span class="badge bg-secondary">@lang('common.inactive')</span>
                                @endif
                            </td>
                            <td>
                                <div class="dropdown">
                                    <button type="button" class="btn btn-sm btn-outline-primary dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                                        <x-svg-icon name="gear" />
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="{{ route('admin.schools.settings.show', $school) }}"><x-svg-icon name="gear-wide-connected" /> @lang('schools.general_settings')</a></li>
                                        <li><a class="dropdown-item" href="{{ route('admin.schools.academic-years.index', $school) }}"><x-svg-icon name="calendar" /> @lang('schools.academic_years')</a></li>
                                        <li><a class="dropdown-item" href="{{ route('admin.schools.grade-levels.index', $school) }}"><x-svg-icon name="stack" /> @lang('schools.grade_levels')</a></li>
                                        <li><a class="dropdown-item" href="{{ route('admin.schools.permissions.index', $school) }}"><x-svg-icon name="key" /> @lang('schools.permissions')</a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item" href="{{ route('admin.schools.show', $school) }}"><x-svg-icon name="eye" /> @lang('common.view')</a></li>
                                        <li><a class="dropdown-item" href="{{ route('admin.schools.edit', $school) }}"><x-svg-icon name="pencil" /> @lang('common.edit')</a></li>
                                        <li>
                                            <form action="{{ route('admin.schools.destroy', $school) }}" method="POST" onsubmit="return confirm(@json(__('common.confirm_delete')))">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="dropdown-item text-danger"><x-svg-icon name="trash" /> @lang('common.delete')</button>
                                            </form>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="10" class="text-center">@lang('common.no_data')</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $schools->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
