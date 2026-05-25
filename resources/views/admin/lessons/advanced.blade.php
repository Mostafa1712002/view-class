@extends('layouts.app')

@section('title', __('lessons_admin.advanced.title'))
@section('body_class', 'theme-light')

@php $isRtl = app()->getLocale() === 'ar'; @endphp

@push('styles')
<style>
    .ls-card { background:#fff; border:1px solid #e5e7eb; border-radius:14px; box-shadow:0 1px 2px rgba(15,23,42,.04), 0 4px 12px rgba(15,23,42,.04); margin-bottom:1.25rem; }
    .ls-card .ls-card-body { padding:1.1rem; }
    .ls-filter-row { display:flex; gap:.55rem; flex-wrap:wrap; }
    .ls-filter-row select { background:#fff; border:1px solid #e2e8f0; border-radius:10px; padding:.55rem .85rem; font-size:.92rem; color:#0f172a; flex:1 1 180px; }
    .ls-filter-row select:focus { border-color:var(--gold-300); box-shadow:0 0 0 .2rem rgba(207,160,70,.16); outline:none; }
    .btn-gold { background:linear-gradient(135deg, var(--gold-300), var(--gold-500)); border:1px solid var(--gold-400); color:#fff; font-weight:600; padding:.55rem 1.1rem; border-radius:10px; display:inline-flex; align-items:center; gap:.45rem; }
    .btn-gold:hover { color:#fff; }
    .btn-back { background:#fff; border:1px solid #e2e8f0; color:#475569; font-weight:600; padding:.55rem 1rem; border-radius:10px; display:inline-flex; align-items:center; gap:.35rem; }
    .board { width:100%; border-collapse:separate; border-spacing:4px; }
    .board th { background:#f8fafc; color:#475569; font-size:.8rem; font-weight:700; padding:.55rem; border-radius:8px; text-align:center; white-space:nowrap; }
    .board th.day-col { background:linear-gradient(135deg, #fef3c7, #fde68a); color:#92400e; }
    .board td { background:#fff; border:1px solid #eef2f7; border-radius:8px; padding:.4rem; vertical-align:top; min-width:130px; }
    .cell-lesson { background:#fffbeb; border:1px solid #fde68a; border-radius:8px; padding:.4rem .5rem; font-size:.78rem; line-height:1.35; }
    .cell-lesson .sub { font-weight:700; color:#0f172a; }
    .cell-lesson .tch { color:#64748b; }
    .cell-lesson .cls { color:#92400e; }
    .cell-lesson .sub-badge { display:inline-block; background:#ede9fe; color:#6d28d9; border-radius:999px; padding:0 .4rem; font-size:.68rem; margin-top:.15rem; }
    .cell-empty { color:#cbd5e1; text-align:center; font-size:.8rem; padding:.6rem 0; }
    .legend { color:#64748b; font-size:.82rem; margin-top:.5rem; }
</style>
@endpush

@section('content')
<div style="margin-bottom:1.25rem; display:flex; justify-content:space-between; align-items:flex-start; gap:1rem; flex-wrap:wrap">
    <div>
        <h2 style="font-size:1.5rem;font-weight:700;color:#0f172a;margin-bottom:.15rem">@lang('lessons_admin.advanced.title')</h2>
        <nav><ol class="breadcrumb" style="padding:0;margin:0;background:transparent;font-size:.85rem">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('lessons_admin.breadcrumb_home')</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.lessons.index') }}">@lang('lessons_admin.breadcrumb_index')</a></li>
            <li class="breadcrumb-item active">@lang('lessons_admin.advanced.title')</li>
        </ol></nav>
    </div>
    <a href="{{ route('admin.lessons.index') }}" class="btn-back"><i class="la la-arrow-{{ $isRtl ? 'right' : 'left' }}"></i>@lang('lessons_admin.actions.back')</a>
</div>

<div class="ls-card">
    <div class="ls-card-body">
        <form method="GET" action="{{ route('admin.lessons.advanced') }}" class="ls-filter-row">
            <select name="class_id">
                <option value="">@lang('lessons_admin.filter.class') — @lang('lessons_admin.filter.all')</option>
                @foreach($classes as $c)
                    <option value="{{ $c->id }}" @selected((string)($filters['class_id'] ?? '') === (string)$c->id)>{{ optional($c->section)->name ? $c->section->name.' — ' : '' }}{{ $c->name }}</option>
                @endforeach
            </select>
            <select name="teacher_id">
                <option value="">@lang('lessons_admin.filter.teacher') — @lang('lessons_admin.filter.all')</option>
                @foreach($teachers as $t)
                    <option value="{{ $t->id }}" @selected((string)($filters['teacher_id'] ?? '') === (string)$t->id)>{{ $t->name }}</option>
                @endforeach
            </select>
            <select name="subject_id">
                <option value="">@lang('lessons_admin.filter.subject') — @lang('lessons_admin.filter.all')</option>
                @foreach($subjects as $s)
                    <option value="{{ $s->id }}" @selected((string)($filters['subject_id'] ?? '') === (string)$s->id)>{{ $s->name }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn-gold"><i class="la la-eye"></i>@lang('lessons_admin.advanced.show')</button>
        </form>
    </div>
</div>

<div class="ls-card">
    <div class="ls-card-body" style="overflow-x:auto">
        <table class="board">
            <thead>
                <tr>
                    <th class="day-col">@lang('lessons_admin.advanced.day_period')</th>
                    @for($p = 1; $p <= $maxPeriod; $p++)
                        <th>@lang('lessons_admin.table.period') {{ $p }}</th>
                    @endfor
                </tr>
            </thead>
            <tbody>
                @foreach($days as $dayIndex => $dayName)
                    <tr>
                        <th class="day-col">{{ $dayName }}</th>
                        @for($p = 1; $p <= $maxPeriod; $p++)
                            @php $cell = $grid->get($dayIndex.'-'.$p); @endphp
                            <td>
                                @if($cell)
                                    @foreach($cell as $lesson)
                                        <div class="cell-lesson">
                                            <div class="sub">{{ optional($lesson->subject)->name ?? '—' }}</div>
                                            <div class="tch"><i class="la la-user"></i> {{ optional($lesson->teacher)->name ?? '—' }}</div>
                                            <div class="cls">{{ optional(optional($lesson->schedule)->classRoom)->name ?? '—' }}</div>
                                            @if($lesson->substitute_teacher_id)
                                                <span class="sub-badge">@lang('lessons_admin.substitute.badge'): {{ optional($lesson->substituteTeacher)->name }}</span>
                                            @endif
                                        </div>
                                    @endforeach
                                @else
                                    <div class="cell-empty">—</div>
                                @endif
                            </td>
                        @endfor
                    </tr>
                @endforeach
            </tbody>
        </table>
        <p class="legend">@lang('lessons_admin.advanced.legend')</p>
    </div>
</div>
@endsection
