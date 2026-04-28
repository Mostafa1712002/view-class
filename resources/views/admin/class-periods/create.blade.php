@extends('layouts.app')

@section('title', __('sprint4.class_periods.add'))

@section('content')
<div class="content-header row">
    <div class="content-header-left col-12 mb-2">
        <h2 class="content-header-title mb-0">@lang('sprint4.class_periods.add')</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.class-periods.index') }}">@lang('sprint4.class_periods.page_title')</a></li>
                <li class="breadcrumb-item active">@lang('sprint4.class_periods.add')</li>
            </ol>
        </div>
    </div>
</div>

<div class="content-body">
    <div class="card">
        <div class="card-body">
            <form action="{{ route('admin.class-periods.store') }}" method="POST">
                @csrf
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">@lang('sprint4.class_periods.form.teacher') <span class="text-danger">*</span></label>
                        <select name="teacher_id" class="form-select" required>
                            <option value="">—</option>
                            @foreach($teachers as $t)
                                <option value="{{ $t->id }}">{{ $t->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">@lang('sprint4.class_periods.form.subject') <span class="text-danger">*</span></label>
                        <select name="subject_id" class="form-select" required>
                            <option value="">—</option>
                            @foreach($subjects as $s)
                                <option value="{{ $s->id }}">{{ $s->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">@lang('sprint4.class_periods.form.grade_level') <span class="text-danger">*</span></label>
                        <input type="number" name="grade_level" min="1" max="12" class="form-control" value="{{ old('grade_level', 1) }}" required>
                    </div>
                    <div class="col-md-9 mb-3">
                        <label class="form-label">@lang('sprint4.class_periods.form.classroom') <span class="text-danger">*</span></label>
                        <select name="class_id" class="form-select" required>
                            <option value="">—</option>
                            @foreach($classes as $c)
                                <option value="{{ $c->id }}">{{ $c->name }} ({{ $c->grade_level }}/{{ $c->division }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-12 mb-3">
                        <label class="form-label">@lang('sprint4.class_periods.form.substitute_teacher')</label>
                        <select name="substitute_teacher_id" class="form-select">
                            <option value="">@lang('sprint4.class_periods.form.no_choice')</option>
                            @foreach($teachers as $t)
                                <option value="{{ $t->id }}">{{ $t->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="text-end">
                    <a href="{{ route('admin.class-periods.index') }}" class="btn btn-outline-secondary">@lang('sprint4.class_periods.form.cancel')</a>
                    <button type="submit" class="btn btn-primary">@lang('sprint4.class_periods.form.save')</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
