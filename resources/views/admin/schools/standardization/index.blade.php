@extends('layouts.app')

@section('title', __('schools.standardization'))

@section('content')
<div class="content-header row">
    <div class="content-header-left col-12 mb-2">
        <h2 class="content-header-title float-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }} mb-0">
            @lang('schools.standardization') — {{ app()->getLocale() === 'en' ? ($school->name_en ?: $school->name_ar) : ($school->name_ar ?: $school->name) }}
        </h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.schools.index') }}">@lang('schools.title')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.schools.grade-levels.index', $school) }}">@lang('schools.grade_levels')</a></li>
                <li class="breadcrumb-item active">@lang('schools.standardization')</li>
            </ol>
        </div>
    </div>
</div>

<div class="content-body">
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <div class="alert alert-info">
        <i class="la la-info-circle"></i> @lang('schools.standardization_intro')
    </div>

    @if(count($collisions))
        <div class="alert alert-danger">
            <i class="la la-exclamation-triangle"></i>
            @lang('schools.standardize_grade_collision', ['grades' => collect($collisions)->map(fn($o)=>\App\Modules\Promotion\Support\StandardGrades::name($o))->implode('، ')])
        </div>
    @endif

    <form action="{{ route('admin.schools.standardization.save', $school) }}" method="POST">
        @csrf
        @forelse($sections as $section)
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        {{ $section->name }}
                        @unless($section->grade_number)
                            <span class="badge badge-warning">@lang('schools.needs_grade_number')</span>
                        @endunless
                    </h5>
                    <div style="min-width:320px">
                        <label class="form-label mb-0">@lang('schools.grade_type')</label>
                        <select name="grade_number[{{ $section->id }}]" class="form-control form-control-sm">
                            <option value="">— @lang('schools.grade_type') —</option>
                            @foreach($standardGrades as $ordinal => $grade)
                                <option value="{{ $ordinal }}" @selected($section->grade_number === $ordinal)>{{ $ordinal }} — {{ $grade['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="card-body">
                    @if($section->classes->count())
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>@lang('schools.class_name')</th>
                                        <th>@lang('schools.student_gender')</th>
                                        <th style="width:180px">@lang('schools.class_number')</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($section->classes as $class)
                                        <tr>
                                            <td>{{ $class->name }}</td>
                                            <td>{{ $class->gender ? __('schools.gender_'.$class->gender) : '—' }}</td>
                                            <td>
                                                <input type="number" min="1" max="100" name="class_number[{{ $class->id }}]"
                                                       value="{{ $class->number }}" class="form-control form-control-sm"
                                                       placeholder="@lang('schools.class_number_hint')">
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-muted mb-0">@lang('schools.no_classes_yet')</p>
                    @endif
                </div>
            </div>
        @empty
            <div class="alert alert-secondary">@lang('schools.no_grades_yet')</div>
        @endforelse

        @if($sections->count())
            <button class="btn btn-primary"><i class="la la-save"></i> @lang('common.save')</button>
            <a href="{{ route('admin.schools.grade-levels.index', $school) }}" class="btn btn-outline-secondary">
                @lang('common.back')
            </a>
        @endif
    </form>
</div>
@endsection
