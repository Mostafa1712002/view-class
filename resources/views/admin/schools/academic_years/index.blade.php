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

    {{-- Current year summary --}}
    @if($current)
        <div class="card mb-3 border-success">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div>
                        <small class="text-muted">@lang('schools.current_year')</small>
                        <h5 class="mb-0">{{ $current->name }}</h5>
                        <small>{{ optional($current->start_date)->format('Y-m-d') }} → {{ optional($current->end_date)->format('Y-m-d') }}</small>
                    </div>
                    <form action="{{ route('admin.schools.academic-years.promote', [$school, $current]) }}" method="POST" onsubmit="return confirm(@json(__('schools.confirm_promote')))">
                        @csrf
                        <button class="btn btn-warning"><i class="la la-share"></i> @lang('schools.promote_to_new_year')</button>
                    </form>
                </div>
            </div>
        </div>
    @endif

    {{-- Add academic year --}}
    <div class="card mb-3">
        <div class="card-header"><h5 class="mb-0">@lang('schools.add_academic_year')</h5></div>
        <div class="card-body">
            <form action="{{ route('admin.schools.academic-years.store', $school) }}" method="POST" class="row g-2 align-items-end">
                @csrf
                <div class="col-md-3">
                    <label class="form-label">@lang('schools.academic_year')</label>
                    <input type="text" class="form-control" name="name" value="{{ old('name') }}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">@lang('schools.year_start')</label>
                    <input type="date" class="form-control" name="start_date" value="{{ old('start_date') }}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">@lang('schools.year_end')</label>
                    <input type="date" class="form-control" name="end_date" value="{{ old('end_date') }}" required>
                </div>
                <div class="col-md-2">
                    <div class="form-check mt-4">
                        <input type="checkbox" class="form-check-input" id="is_current_new" name="is_current" value="1">
                        <label class="form-check-label" for="is_current_new">@lang('schools.set_as_current')</label>
                    </div>
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn btn-primary w-100"><i class="la la-plus"></i></button>
                </div>
            </form>
        </div>
    </div>

    @forelse($years as $year)
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <strong>{{ $year->name }}</strong>
                    <small class="text-muted ms-2">{{ optional($year->start_date)->format('Y-m-d') }} → {{ optional($year->end_date)->format('Y-m-d') }}</small>
                    @if($year->is_current)
                        <span class="badge bg-success ms-2">@lang('schools.current')</span>
                    @endif
                </div>
                <a href="{{ route('manage.academic-years.edit', $year) }}" class="btn btn-sm btn-outline-warning"><i class="la la-pen"></i></a>
            </div>
            <div class="card-body">
                <h6 class="text-muted mb-2">@lang('schools.terms')</h6>
                <div class="table-responsive mb-3">
                    <table class="table table-sm table-bordered align-middle">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>@lang('schools.term_name')</th>
                                <th>@lang('schools.year_start')</th>
                                <th>@lang('schools.year_end')</th>
                                <th>@lang('schools.weeks_count')</th>
                                <th>@lang('common.status')</th>
                                <th>@lang('common.actions')</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($year->terms as $term)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $term->name }}</td>
                                    <td>{{ optional($term->start_date)->format('Y-m-d') ?? '-' }}</td>
                                    <td>{{ optional($term->end_date)->format('Y-m-d') ?? '-' }}</td>
                                    <td>{{ $term->weeks->count() }}</td>
                                    <td>
                                        @if($term->is_current)
                                            <span class="badge bg-success">@lang('schools.current')</span>
                                        @else
                                            <form action="{{ route('admin.schools.academic-years.terms.set-current', [$school, $year, $term]) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('PUT')
                                                <button class="btn btn-sm btn-outline-success">@lang('schools.set_current')</button>
                                            </form>
                                        @endif
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-outline-info" data-bs-toggle="collapse" data-bs-target="#weeks-{{ $term->id }}"><i class="la la-calendar-week"></i></button>
                                        <form action="{{ route('admin.schools.academic-years.terms.destroy', [$school, $year, $term]) }}" method="POST" class="d-inline" onsubmit="return confirm(@json(__('common.confirm_delete')))">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger"><i class="la la-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                                <tr class="collapse" id="weeks-{{ $term->id }}">
                                    <td colspan="7">
                                        <strong>@lang('schools.study_weeks')</strong>
                                        <table class="table table-sm mt-2 mb-2">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>@lang('schools.week_name')</th>
                                                    <th>@lang('schools.year_start')</th>
                                                    <th>@lang('schools.year_end')</th>
                                                    <th></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($term->weeks as $week)
                                                    <tr>
                                                        <td>{{ $loop->iteration }}</td>
                                                        <td>{{ $week->name }}</td>
                                                        <td>{{ optional($week->start_date)->format('Y-m-d') }}</td>
                                                        <td>{{ optional($week->end_date)->format('Y-m-d') }}</td>
                                                        <td>
                                                            <form action="{{ route('admin.schools.academic-years.terms.weeks.destroy', [$school, $year, $term, $week]) }}" method="POST" class="d-inline" onsubmit="return confirm(@json(__('common.confirm_delete')))">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button class="btn btn-sm btn-outline-danger"><i class="la la-trash"></i></button>
                                                            </form>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                        <form action="{{ route('admin.schools.academic-years.terms.weeks.store', [$school, $year, $term]) }}" method="POST" class="row g-2 align-items-end">
                                            @csrf
                                            <div class="col-md-4">
                                                <input type="text" class="form-control form-control-sm" name="name" placeholder="@lang('schools.week_name')" required>
                                            </div>
                                            <div class="col-md-3">
                                                <input type="date" class="form-control form-control-sm" name="start_date" required>
                                            </div>
                                            <div class="col-md-3">
                                                <input type="date" class="form-control form-control-sm" name="end_date" required>
                                            </div>
                                            <div class="col-md-2">
                                                <button class="btn btn-sm btn-primary w-100"><i class="la la-plus"></i> @lang('schools.add_week')</button>
                                            </div>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-center text-muted">@lang('schools.no_terms_yet')</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <form action="{{ route('admin.schools.academic-years.terms.store', [$school, $year]) }}" method="POST" class="row g-2 align-items-end">
                    @csrf
                    <div class="col-md-4">
                        <input type="text" class="form-control form-control-sm" name="name" placeholder="@lang('schools.term_name')" required>
                    </div>
                    <div class="col-md-3">
                        <input type="date" class="form-control form-control-sm" name="start_date">
                    </div>
                    <div class="col-md-3">
                        <input type="date" class="form-control form-control-sm" name="end_date">
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-sm btn-primary w-100"><i class="la la-plus"></i> @lang('schools.add_term')</button>
                    </div>
                </form>
            </div>
        </div>
    @empty
        <div class="card"><div class="card-body text-center text-muted">@lang('schools.no_years_yet')</div></div>
    @endforelse
</div>
@endsection
