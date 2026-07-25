@extends('layouts.app')

@section('title', __('schools.results_title'))

@section('content')
<div class="content-header row">
    <div class="content-header-left col-12 mb-2">
        <h2 class="content-header-title float-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }} mb-0">
            @lang('schools.results_title') — {{ $class->name }}
        </h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.schools.index') }}">@lang('schools.title')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.schools.grade-levels.index', $school) }}">@lang('schools.grade_levels')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.schools.grade-levels.classes.show', [$school, $section, $class]) }}">{{ $class->name }}</a></li>
                <li class="breadcrumb-item active">@lang('schools.results_title')</li>
            </ol>
        </div>
    </div>
</div>

<div class="content-body">
    @include('components.alerts')

    <div class="alert alert-info">
        <i class="la la-info-circle"></i> @lang('schools.results_intro')
    </div>

    <div class="card">
        <form action="{{ route('admin.schools.promotion.results.save', $school) }}" method="POST">
            @csrf
            <input type="hidden" name="class" value="{{ $class->id }}">

            <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
                <h5 class="mb-0">{{ $section->name }} — {{ $class->name }}</h5>
                @if($students->count())
                    <button type="submit" name="mark_all" value="1" class="btn btn-outline-success btn-sm"
                            onclick="return confirm('@lang('schools.results_mark_all_confirm')')">
                        <i class="la la-check-double"></i> @lang('schools.results_mark_all')
                    </button>
                @endif
            </div>

            <div class="card-body">
                @if($students->count())
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle mb-0">
                            <thead>
                                <tr>
                                    <th style="width:50px">#</th>
                                    <th>@lang('schools.student_name')</th>
                                    <th style="width:320px">@lang('schools.result')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($students as $i => $student)
                                    @php $current = $student->pivot->result ?? 'pending'; @endphp
                                    <tr>
                                        <td>{{ $i + 1 }}</td>
                                        <td>{{ $student->name_ar ?: $student->name }}</td>
                                        <td>
                                            <div class="btn-group btn-group-sm btn-group-toggle" data-toggle="buttons">
                                                @foreach(['passed' => 'success', 'failed' => 'danger', 'pending' => 'secondary'] as $value => $variant)
                                                    <label class="btn btn-outline-{{ $variant }} {{ $current === $value ? 'active' : '' }}">
                                                        <input type="radio" name="results[{{ $student->id }}]" value="{{ $value }}"
                                                               {{ $current === $value ? 'checked' : '' }}>
                                                        @lang('schools.result_'.$value)
                                                    </label>
                                                @endforeach
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-muted mb-0">@lang('schools.results_no_students')</p>
                @endif
            </div>

            <div class="card-footer">
                @if($students->count())
                    <button type="submit" class="btn btn-primary">
                        <i class="la la-save"></i> @lang('common.save')
                    </button>
                @endif
                <a href="{{ route('admin.schools.grade-levels.classes.show', [$school, $section, $class]) }}" class="btn btn-outline-secondary">
                    <i class="la la-arrow-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }}"></i> @lang('common.back')
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
