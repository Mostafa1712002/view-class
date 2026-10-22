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
        <div class="card-header">
            <form action="{{ route('admin.schools.index') }}" method="GET" class="d-flex flex-wrap gap-1">
                <input type="search" name="q" value="{{ $name }}" class="form-control form-control-sm" style="max-width:220px" placeholder="@lang('schools.filter_name')">
                <select name="company_id" class="form-control form-control-sm" style="max-width:200px">
                    <option value="">@lang('schools.filter_all_companies')</option>
                    <option value="__none__" @selected($companyId === '__none__')>@lang('schools.filter_no_company')</option>
                    @foreach($companies as $c)
                        <option value="{{ $c->id }}" @selected((string) $companyId === (string) $c->id)>{{ $c->name_ar ?: $c->name_en }}</option>
                    @endforeach
                </select>
                <select name="compound_id" class="form-control form-control-sm" style="max-width:200px">
                    <option value="">@lang('schools.filter_all_complexes')</option>
                    @foreach($complexes as $cx)
                        <option value="{{ $cx->id }}" @selected((string) $complexId === (string) $cx->id)>{{ $cx->name_ar ?: $cx->name_en }}</option>
                    @endforeach
                </select>
                <select name="stage" class="form-control form-control-sm" style="max-width:170px">
                    <option value="">@lang('schools.filter_all_stages')</option>
                    @foreach(['primary', 'intermediate', 'secondary'] as $s)
                        <option value="{{ $s }}" @selected($stage === $s)>@lang('schools.stage_' . $s)</option>
                    @endforeach
                </select>
                <select name="gender" class="form-control form-control-sm" style="max-width:150px">
                    <option value="">@lang('schools.filter_all_genders')</option>
                    @foreach(['boys', 'girls', 'mixed'] as $g)
                        <option value="{{ $g }}" @selected($gender === $g)>@lang('schools.gender_' . $g)</option>
                    @endforeach
                </select>
                <button type="submit" class="btn btn-outline-secondary btn-sm">@lang('schools.filter_apply')</button>
                @if($name || $companyId || $complexId || $stage || $gender)
                    <a href="{{ route('admin.schools.index') }}" class="btn btn-outline-secondary btn-sm">@lang('common.clear')</a>
                @endif
            </form>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>@lang('schools.name')</th>
                            <th>@lang('schools.educational_company')</th>
                            <th>@lang('schools.educational_complex')</th>
                            <th>@lang('schools.educational_stage')</th>
                            <th>@lang('schools.gender')</th>
                            <th>@lang('schools.manager_user_id')</th>
                            <th>@lang('schools.agent_user_id')</th>
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
                            <td>{{ $loop->iteration + ($schools->currentPage() - 1) * $schools->perPage() }}</td>
                            <td>
                                <strong>{{ app()->getLocale() === 'en' ? ($school->name_en ?: $school->name_ar ?: $school->name) : ($school->name_ar ?: $school->name) }}</strong>
                                @if($school->code)
                                    <br><small class="text-muted">{{ $school->code }}</small>
                                @endif
                            </td>
                            <td>{{ $school->educationalCompany?->name_ar ?: $school->educationalCompany?->name_en ?: __('schools.no_educational_company') }}</td>
                            <td>{{ $school->compounds->first()?->name_ar ?: $school->compounds->first()?->name_en ?: __('schools.no_educational_complex') }}</td>
                            <td>{{ $school->stage ? __('schools.stage_' . $school->stage) : '-' }}</td>
                            <td>{{ $school->student_gender ? __('schools.gender_' . $school->student_gender) : '-' }}</td>
                            <td>{{ $school->manager?->name ?? '-' }}</td>
                            <td>{{ $school->agent?->name ?? '-' }}</td>
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
                            <td colspan="16" class="text-center">@lang('common.no_data')</td>
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
