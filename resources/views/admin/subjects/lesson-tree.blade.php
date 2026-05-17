@extends('layouts.app')

@section('title', __('sprint4.subjects.lesson_tree_page.title', ['name' => $subject->name]))
@section('body_class','theme-light')

@push('styles')
<style>
    body.theme-light .tree-hero {
        background: linear-gradient(135deg, #fff6dd 0%, #ffffff 65%);
        border: 1px solid #f1e4b8 !important;
    }
    body.theme-light .tree-hero h4 { color: #0f172a; margin-bottom: .25rem; }
    body.theme-light .unit-card { transition: box-shadow .2s ease; }
    body.theme-light .unit-card .unit-title { font-weight: 700; color: #0f172a; }
    body.theme-light .unit-card .unit-en { color: #94a3b8; font-size: .82rem; }
    body.theme-light .lesson-row {
        background: #f8fafc; border-radius: 10px; padding: .55rem .85rem;
        margin-bottom: .35rem; display: flex; justify-content: space-between; align-items: center;
        border: 1px solid #f1f5f9;
    }
    body.theme-light .lesson-row:hover { background: #fff6dd33; border-color: #fde8ad; }
    body.theme-light .add-row {
        background: #fff; border: 1px dashed #cbd5e1; border-radius: 10px;
        padding: .65rem .85rem; margin-top: .5rem;
    }
    body.theme-light .btn-icon-soft {
        width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center;
        border-radius: 8px; border: 1px solid #fecaca; background: #fff; color: #b91c1c;
    }
    body.theme-light .btn-icon-soft:hover { background: #fef2f2; }
    body.theme-light .add-subject-btn {
        background: linear-gradient(135deg, var(--gold-200), var(--gold-500)) !important;
        color: #fff !important; border: none; border-radius: 10px; font-weight: 600;
    }
    body.theme-light .btn-soft {
        background: #fff; border: 1px solid #e5e7eb; color: #475569;
        border-radius: 10px; font-weight: 500;
    }
    body.theme-light .empty-tree { text-align: center; padding: 2rem 1rem; }
</style>
@endpush

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
    {{-- Hero summary --}}
    <div class="card tree-hero mb-3">
        <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h4 class="mb-1"><i class="la la-stream" style="color: var(--gold-400);"></i> {{ $subject->name }}</h4>
                <div class="text-muted small">
                    {{ $subject->units->count() }} وحدة ·
                    {{ $subject->units->sum(fn($u) => $u->lessons->count()) }} درس
                </div>
            </div>
            <a href="{{ route('admin.subjects.edit', $subject->id) }}" class="btn btn-soft">
                <i class="la la-pen"></i> @lang('sprint4.subjects.edit')
            </a>
        </div>
    </div>

    {{-- Add unit form --}}
    <div class="card mb-3">
        <div class="card-header">
            <h5 class="card-title mb-0"><i class="la la-plus-circle" style="color: var(--gold-400);"></i> @lang('sprint4.subjects.lesson_tree_page.add_unit')</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('admin.subjects.units.store', $subject->id) }}" method="POST" class="row g-2 align-items-end">
                @csrf
                <div class="col-md-5">
                    <label class="form-label fw-semibold">@lang('sprint4.subjects.lesson_tree_page.unit_name_ar') <span class="text-danger">*</span></label>
                    <input type="text" name="name_ar" class="form-control" required>
                </div>
                <div class="col-md-5">
                    <label class="form-label fw-semibold">@lang('sprint4.subjects.lesson_tree_page.unit_name_en')</label>
                    <input type="text" name="name_en" class="form-control">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn add-subject-btn w-100"><i class="la la-plus"></i> @lang('sprint4.subjects.lesson_tree_page.add_unit')</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Units list --}}
    @forelse($subject->units as $unit)
        <div class="card unit-card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <span class="unit-title">{{ $unit->name_ar }}</span>
                    @if($unit->name_en)<span class="unit-en ms-2">— {{ $unit->name_en }}</span>@endif
                    <span class="grade-chip ms-2" style="background:#f1f5f9;color:#64748b;padding:.15rem .55rem;border-radius:999px;font-size:.72rem;">
                        {{ $unit->lessons->count() }} درس
                    </span>
                </div>
                <form action="{{ route('admin.subjects.units.destroy', [$subject->id, $unit->id]) }}" method="POST" onsubmit="return confirm('حذف الوحدة وجميع دروسها؟')">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn-icon-soft" title="@lang('sprint4.subjects.delete')"><i class="la la-trash"></i></button>
                </form>
            </div>
            <div class="card-body">
                <div>
                    @forelse($unit->lessons as $lesson)
                        <div class="lesson-row">
                            <span>
                                <i class="la la-bookmark" style="color:#94a3b8;"></i>
                                {{ $lesson->name_ar }}
                                @if($lesson->name_en)<small class="text-muted ms-2">— {{ $lesson->name_en }}</small>@endif
                            </span>
                            <form action="{{ route('admin.subjects.lessons.destroy', [$subject->id, $unit->id, $lesson->id]) }}" method="POST" onsubmit="return confirm('حذف الدرس؟')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn-icon-soft" title="@lang('sprint4.subjects.delete')"><i class="la la-trash"></i></button>
                            </form>
                        </div>
                    @empty
                        <div class="text-muted small py-2">@lang('sprint4.subjects.lesson_tree_page.no_lessons')</div>
                    @endforelse
                </div>
                <form action="{{ route('admin.subjects.lessons.store', [$subject->id, $unit->id]) }}" method="POST" class="add-row row g-2 align-items-center">
                    @csrf
                    <div class="col-md-5">
                        <input type="text" name="name_ar" class="form-control form-control-sm" placeholder="@lang('sprint4.subjects.lesson_tree_page.lesson_name_ar')" required>
                    </div>
                    <div class="col-md-5">
                        <input type="text" name="name_en" class="form-control form-control-sm" placeholder="@lang('sprint4.subjects.lesson_tree_page.lesson_name_en')">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-sm btn-soft w-100"><i class="la la-plus"></i> @lang('sprint4.subjects.lesson_tree_page.add_lesson')</button>
                    </div>
                </form>
            </div>
        </div>
    @empty
        <div class="card">
            <div class="card-body empty-tree">
                <div style="width:64px;height:64px;border-radius:16px;margin:0 auto 1rem;background:linear-gradient(135deg,#fff6dd,#fde8ad);color:var(--gold-500);font-size:1.6rem;display:inline-flex;align-items:center;justify-content:center;">
                    <i class="la la-stream"></i>
                </div>
                <h5>@lang('sprint4.subjects.lesson_tree_page.no_units')</h5>
                <p class="text-muted mb-0">ابدأ بإضافة أول وحدة من الفورم بالأعلى.</p>
            </div>
        </div>
    @endforelse
</div>
@endsection
