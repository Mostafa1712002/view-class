@extends('layouts.app')

@section('title', __('sprint4.subjects.lesson_tree_page.title', ['name' => $subject->name]))

@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-12 col-12 mb-2">
        <h2 class="content-header-title mb-0">@lang('sprint4.subjects.lesson_tree_page.title', ['name' => $subject->name])</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.subjects.index') }}">@lang('sprint4.subjects.plural')</a></li>
                <li class="breadcrumb-item active">@lang('sprint4.subjects.lesson_tree')</li>
            </ol>
        </div>
    </div>
</div>

<div class="content-body">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

    <div class="card mb-3">
        <div class="card-header">
            <h5 class="mb-0">@lang('sprint4.subjects.lesson_tree_page.add_unit')</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('admin.subjects.units.store', $subject->id) }}" method="POST" class="row g-2 align-items-end">
                @csrf
                <div class="col-md-5">
                    <label class="form-label">@lang('sprint4.subjects.lesson_tree_page.unit_name_ar') <span class="text-danger">*</span></label>
                    <input type="text" name="name_ar" class="form-control" required>
                </div>
                <div class="col-md-5">
                    <label class="form-label">@lang('sprint4.subjects.lesson_tree_page.unit_name_en')</label>
                    <input type="text" name="name_en" class="form-control">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100"><i class="la la-plus"></i> @lang('sprint4.subjects.lesson_tree_page.add_unit')</button>
                </div>
            </form>
        </div>
    </div>

    @forelse($subject->units as $unit)
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <strong>{{ $unit->name_ar }}</strong>
                    @if($unit->name_en)<small class="text-muted ms-2">{{ $unit->name_en }}</small>@endif
                </div>
                <form action="{{ route('admin.subjects.units.destroy', [$subject->id, $unit->id]) }}" method="POST" onsubmit="return confirm('?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="la la-trash"></i></button>
                </form>
            </div>
            <div class="card-body">
                <h6 class="text-muted">@lang('sprint4.subjects.lesson_tree_page.lessons')</h6>
                <ul class="list-group mb-3">
                    @forelse($unit->lessons as $lesson)
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span>
                                {{ $lesson->name_ar }}
                                @if($lesson->name_en)<small class="text-muted ms-2">— {{ $lesson->name_en }}</small>@endif
                            </span>
                            <form action="{{ route('admin.subjects.lessons.destroy', [$subject->id, $unit->id, $lesson->id]) }}" method="POST" onsubmit="return confirm('?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-link text-danger"><i class="la la-trash"></i></button>
                            </form>
                        </li>
                    @empty
                        <li class="list-group-item text-muted">@lang('sprint4.subjects.lesson_tree_page.no_lessons')</li>
                    @endforelse
                </ul>
                <form action="{{ route('admin.subjects.lessons.store', [$subject->id, $unit->id]) }}" method="POST" class="row g-2 align-items-end">
                    @csrf
                    <div class="col-md-5">
                        <input type="text" name="name_ar" class="form-control form-control-sm" placeholder="@lang('sprint4.subjects.lesson_tree_page.lesson_name_ar')" required>
                    </div>
                    <div class="col-md-5">
                        <input type="text" name="name_en" class="form-control form-control-sm" placeholder="@lang('sprint4.subjects.lesson_tree_page.lesson_name_en')">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-sm btn-outline-primary w-100"><i class="la la-plus"></i> @lang('sprint4.subjects.lesson_tree_page.add_lesson')</button>
                    </div>
                </form>
            </div>
        </div>
    @empty
        <div class="alert alert-light">@lang('sprint4.subjects.lesson_tree_page.no_units')</div>
    @endforelse
</div>
@endsection
