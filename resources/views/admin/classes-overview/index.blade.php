@extends('layouts.app')

@section('title', __('classes_overview.title'))

@section('body_class', 'theme-light')

@section('content')
<div class="content-header row">
    <div class="content-header-left col-12 mb-2">
        <h2 class="content-header-title float-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }} mb-0">@lang('classes_overview.title')</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
                <li class="breadcrumb-item active">@lang('classes_overview.title')</li>
            </ol>
        </div>
    </div>
</div>

<div class="content-body">
    @include('components.alerts')

    <div class="card">
        <div class="card-header">
            <form action="{{ route('admin.classes-overview.index') }}" method="GET" class="d-flex flex-wrap gap-1">
                <select name="school_id" class="form-control form-control-sm" style="max-width:240px" onchange="this.form.submit()">
                    <option value="">@lang('classes_overview.filter_all_schools')</option>
                    @foreach($schools as $s)
                        <option value="{{ $s->id }}" @selected((string) $schoolId === (string) $s->id)>{{ $s->name_ar ?: $s->name_en }}</option>
                    @endforeach
                </select>
            </form>
        </div>
        <div class="table-responsive">
            <table class="table table-bordered table-striped align-middle mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>@lang('classes_overview.company')</th>
                        <th>@lang('classes_overview.complex')</th>
                        <th>@lang('classes_overview.school')</th>
                        <th>@lang('classes_overview.class_name')</th>
                        <th>@lang('classes_overview.students_count')</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($classes as $class)
                        @php $school = $class->section?->school; @endphp
                        <tr>
                            <td>{{ $loop->iteration + ($classes->currentPage() - 1) * $classes->perPage() }}</td>
                            <td>{{ $school?->educationalCompany?->name_ar ?: $school?->educationalCompany?->name_en ?: __('schools.no_educational_company') }}</td>
                            <td>{{ $school?->compounds->first()?->name_ar ?: $school?->compounds->first()?->name_en ?: __('schools.no_educational_complex') }}</td>
                            <td>{{ $school?->name_ar ?: $school?->name_en ?: '—' }}</td>
                            <td>{{ $class->name }}</td>
                            <td>{{ $class->students_count }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center">@lang('common.no_data')</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-body">
            {{ $classes->links() }}
        </div>
    </div>
</div>
@endsection
