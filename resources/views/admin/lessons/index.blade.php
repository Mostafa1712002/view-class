@extends('layouts.app')

@section('title', __('lessons_admin.page_title'))
@section('body_class', 'theme-light')

@php
    $isRtl = app()->getLocale() === 'ar';
    $days = trans('lessons_admin.days');
    $teachersCount = $lessons->getCollection()->pluck('teacher_id')->unique()->count();
    $subjectsCount = $lessons->getCollection()->pluck('subject_id')->unique()->count();
    $classesCount = $lessons->getCollection()->pluck('schedule.classRoom.id')->filter()->unique()->count();
@endphp

@push('styles')
<style>
    /* === Lessons index — light + gold (card 64) ============================== */
    .ls-header { margin-bottom: 1.25rem; }
    .ls-header h2 {
        font-size: 1.5rem; font-weight: 700; color: #0f172a;
        margin-bottom: .15rem; letter-spacing: -.2px;
    }
    .ls-header .breadcrumb { padding: 0; margin: 0; background: transparent; font-size: .85rem; }
    .ls-header .breadcrumb-item + .breadcrumb-item::before { color: #cbd5e1; }

    /* KPI strip */
    .ls-kpis { display: grid; grid-template-columns: repeat(4, minmax(0,1fr)); gap: .75rem; margin-bottom: 1.25rem; }
    .ls-kpi {
        background: #fff; border: 1px solid #e5e7eb; border-radius: 14px;
        padding: .85rem 1rem; display: flex; align-items: center; gap: .75rem;
        box-shadow: 0 1px 2px rgba(15,23,42,.04), 0 4px 12px rgba(15,23,42,.04);
        transition: transform .2s ease, box-shadow .2s ease;
    }
    .ls-kpi:hover { transform: translateY(-2px); box-shadow: 0 4px 14px rgba(15,23,42,.06), 0 12px 28px rgba(15,23,42,.05); }
    .ls-kpi .ico {
        width: 38px; height: 38px; border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.1rem; flex-shrink: 0;
        background: linear-gradient(135deg, #fef3c7, #fde68a); color: var(--gold-500);
    }
    .ls-kpi .ico.b { background: linear-gradient(135deg, #dbeafe, #bfdbfe); color: #1d4ed8; }
    .ls-kpi .ico.v { background: linear-gradient(135deg, #ede9fe, #ddd6fe); color: #6d28d9; }
    .ls-kpi .ico.g { background: linear-gradient(135deg, #dcfce7, #bbf7d0); color: #15803d; }
    .ls-kpi .num   { font-size: 1.35rem; font-weight: 800; color: var(--gold-400); line-height: 1.1; letter-spacing: -.5px; }
    .ls-kpi .num.muted { color: #0f172a; }
    .ls-kpi .lbl   { font-size: .8rem; color: #64748b; }

    /* Filter card */
    .ls-filter {
        background: #fff; border: 1px solid #e5e7eb; border-radius: 14px;
        padding: 1rem 1.1rem; margin-bottom: 1rem;
        box-shadow: 0 1px 2px rgba(15,23,42,.04), 0 4px 12px rgba(15,23,42,.04);
    }
    .ls-filter .se-title {
        display: flex; align-items: center; gap: .55rem;
        font-size: .92rem; font-weight: 700; color: #0f172a; margin-bottom: .6rem;
    }
    .ls-filter .se-title i { color: var(--gold-400); font-size: 1.1rem; }
    .ls-row { display: flex; gap: .55rem; flex-wrap: wrap; }
    .ls-row .form-control, .ls-row select.form-control {
        background: #fff; border: 1px solid #e2e8f0; border-radius: 10px;
        padding: .55rem .85rem; font-size: .93rem; color: #0f172a;
        transition: border-color .15s ease, box-shadow .15s ease;
        flex: 1 1 200px; min-width: 0;
    }
    .ls-row select.form-control { flex: 0 1 200px; }
    .ls-row .form-control:focus { border-color: var(--gold-300); box-shadow: 0 0 0 .2rem rgba(207,160,70,.16); outline: none; }
    .ls-row .btn-gold, .ls-row .btn-reset { flex: 0 0 auto; }
    .btn-reset { background: #fff; border: 1px solid #e2e8f0; color: #475569; font-weight: 600; padding: .55rem 1rem; border-radius: 10px; display: inline-flex; align-items: center; gap: .35rem; transition: all .15s ease; }
    .btn-reset:hover { background: #f8fafc; color: #0f172a; }

    /* Toolbar */
    .ls-toolbar {
        background: #fff; border: 1px solid #e5e7eb; border-radius: 14px 14px 0 0;
        padding: .9rem 1.1rem; display: flex; flex-wrap: wrap; gap: .55rem;
        align-items: center; justify-content: space-between; border-bottom: 0;
    }
    .ls-toolbar .left { display: flex; flex-wrap: wrap; gap: .45rem; align-items: center; }
    .ls-toolbar .count-pill { background: #f8fafc; border: 1px solid #e5e7eb; color: #475569; font-size: .78rem; font-weight: 600; padding: .25rem .65rem; border-radius: 999px; }

    .btn-gold {
        background: linear-gradient(135deg, var(--gold-300), var(--gold-500));
        border: 1px solid var(--gold-400); color: #fff;
        font-weight: 600; padding: .55rem 1.1rem; border-radius: 10px;
        box-shadow: 0 1px 2px rgba(207,160,70,.18);
        transition: transform .15s ease, box-shadow .2s ease;
        display: inline-flex; align-items: center; gap: .45rem;
    }
    .btn-gold:hover { color: #fff; transform: translateY(-1px); box-shadow: 0 6px 16px rgba(207,160,70,.22); }

    .ls-tools { display: flex; flex-wrap: wrap; gap: .45rem; align-items: center; }
    .btn-tool {
        background: #fff; border: 1px solid #e2e8f0; color: #475569;
        font-weight: 600; padding: .55rem .95rem; border-radius: 10px;
        display: inline-flex; align-items: center; gap: .4rem; font-size: .88rem;
        transition: all .15s ease;
    }
    .btn-tool:hover { background: #fffbeb; border-color: var(--gold-300); color: #92400e; }
    .btn-tool i { color: var(--gold-400); }

    /* Surface + table */
    .ls-surface { background: #fff; border: 1px solid #e5e7eb; border-top: 0; border-radius: 0 0 14px 14px; overflow: hidden; }
    .ls-table { margin: 0; }
    .ls-table thead th {
        background: #f8fafc !important; color: #475569 !important;
        font-weight: 600; font-size: .78rem; text-transform: uppercase; letter-spacing: .5px;
        border-bottom: 1px solid #e5e7eb; padding: .8rem 1rem; white-space: nowrap;
    }
    .ls-table tbody td { padding: .85rem 1rem; vertical-align: middle; color: #0f172a; }
    .ls-table tbody tr { transition: background .15s ease; }
    .ls-table tbody tr:hover { background: #fafbfc; }
    .ls-table tbody tr + tr td { border-top: 1px solid #f1f5f9; }

    .ls-pill {
        display: inline-flex; align-items: center; gap: .3rem;
        padding: .2rem .55rem; border-radius: 999px; font-size: .72rem; font-weight: 600;
        line-height: 1.3; border: 1px solid transparent;
    }
    .ls-pill.day { background: #fffbeb; color: #92400e; border-color: #fde68a; }
    .ls-pill.period { background: #eff6ff; color: #1d4ed8; border-color: #bfdbfe; }
    .ls-pill.muted { background: #f1f5f9; color: #64748b; border-color: #e2e8f0; }

    .ls-actions { display: inline-flex; align-items: center; gap: .35rem; }
    .ls-action-btn {
        width: 32px; height: 32px; padding: 0;
        display: inline-flex; align-items: center; justify-content: center;
        border-radius: 8px; border: 1px solid #e2e8f0; background: #fff;
        color: #475569; transition: all .15s ease;
    }
    .ls-action-btn:hover { transform: translateY(-1px); }
    .ls-action-btn.edit:hover { background: #fffbeb; border-color: #fde68a; color: #92400e; }
    .ls-action-btn.del { background: #fff5f5; border-color: #fecaca; color: #b91c1c; }
    .ls-action-btn.del:hover { background: #fee2e2; border-color: #fca5a5; }

    .ls-empty { padding: 3rem 1rem; text-align: center; color: #64748b; }
    .ls-empty .ls-empty-ico { width: 64px; height: 64px; border-radius: 16px; margin: 0 auto 1rem; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #fef3c7, #fde68a); color: var(--gold-500); font-size: 1.7rem; }
    .ls-empty h4 { color: #0f172a; font-weight: 700; margin-bottom: .35rem; }

    @media (max-width: 768px) {
        .ls-kpis { grid-template-columns: repeat(2, minmax(0,1fr)); }
        .ls-row select.form-control { flex: 1 1 calc(50% - .3rem); }
    }
</style>
@endpush

@section('content')
<div class="ls-header">
    <h2>@lang('lessons_admin.index_title')</h2>
    <nav><ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('lessons_admin.breadcrumb_home')</a></li>
        <li class="breadcrumb-item active">@lang('lessons_admin.breadcrumb_index')</li>
    </ol></nav>
</div>

@if(session('success'))
    <div class="alert alert-success" role="alert">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger" role="alert">{{ session('error') }}</div>
@endif

<div class="ls-kpis">
    <div class="ls-kpi"><div class="ico"><i class="la la-clock"></i></div>
        <div><div class="num">{{ number_format($lessons->total()) }}</div><div class="lbl">@lang('lessons_admin.kpi.total')</div></div>
    </div>
    <div class="ls-kpi"><div class="ico b"><i class="la la-chalkboard-teacher"></i></div>
        <div><div class="num muted">{{ number_format($teachersCount) }}</div><div class="lbl">@lang('lessons_admin.kpi.teachers')</div></div>
    </div>
    <div class="ls-kpi"><div class="ico v"><i class="la la-book"></i></div>
        <div><div class="num muted">{{ number_format($subjectsCount) }}</div><div class="lbl">@lang('lessons_admin.kpi.subjects')</div></div>
    </div>
    <div class="ls-kpi"><div class="ico g"><i class="la la-school"></i></div>
        <div><div class="num muted">{{ number_format($classesCount) }}</div><div class="lbl">@lang('lessons_admin.kpi.classes')</div></div>
    </div>
</div>

<div class="ls-filter">
    <div class="se-title"><i class="la la-filter"></i>@lang('lessons_admin.filter.title')</div>
    <form method="GET" action="{{ route('admin.lessons.index') }}" class="ls-row" id="lessonsFilter">
        <select name="section_id" class="form-control">
            <option value="">@lang('lessons_admin.filter.section') — @lang('lessons_admin.filter.all')</option>
            @foreach($sections as $s)
                <option value="{{ $s->id }}" @selected(($filters['section_id'] ?? '') == $s->id)>{{ $s->name }}</option>
            @endforeach
        </select>
        <select name="class_id" class="form-control">
            <option value="">@lang('lessons_admin.filter.class') — @lang('lessons_admin.filter.all')</option>
            @foreach($classes as $c)
                <option value="{{ $c->id }}" @selected(($filters['class_id'] ?? '') == $c->id)>{{ $c->name }}</option>
            @endforeach
        </select>
        <select name="teacher_id" class="form-control">
            <option value="">@lang('lessons_admin.filter.teacher') — @lang('lessons_admin.filter.all')</option>
            @foreach($teachers as $t)
                <option value="{{ $t->id }}" @selected(($filters['teacher_id'] ?? '') == $t->id)>{{ $t->name }}</option>
            @endforeach
        </select>
        <select name="subject_id" class="form-control">
            <option value="">@lang('lessons_admin.filter.subject') — @lang('lessons_admin.filter.all')</option>
            @foreach($subjects as $sub)
                <option value="{{ $sub->id }}" @selected(($filters['subject_id'] ?? '') == $sub->id)>{{ $sub->name }}</option>
            @endforeach
        </select>
        <select name="day_of_week" class="form-control">
            <option value="">@lang('lessons_admin.filter.day') — @lang('lessons_admin.filter.all')</option>
            @foreach($days as $i => $d)
                <option value="{{ $i }}" @selected(isset($filters['day_of_week']) && $filters['day_of_week'] !== '' && $filters['day_of_week'] !== null && (int) $filters['day_of_week'] === $i)>{{ $d }}</option>
            @endforeach
        </select>
        <input type="text" name="search" class="form-control" placeholder="{{ __('lessons_admin.filter.search_placeholder') }}" value="{{ $filters['search'] ?? '' }}">
        <button type="submit" class="btn-gold"><i class="la la-search"></i>@lang('lessons_admin.filter.apply')</button>
        <a href="{{ route('admin.lessons.index') }}" class="btn-reset"><i class="la la-redo"></i>@lang('lessons_admin.filter.reset')</a>
    </form>
</div>

<div class="ls-toolbar">
    <div class="left">
        <span class="count-pill">{{ number_format($lessons->total()) }} @lang('lessons_admin.kpi.total')</span>
    </div>
    <div class="ls-tools">
        <a href="{{ route('admin.lessons.create') }}" class="btn-gold"><i class="la la-plus"></i>@lang('lessons_admin.actions.add')</a>
        <a href="{{ route('admin.lessons.time-slots.index') }}" class="btn-tool"><i class="la la-clock"></i>@lang('lessons_admin.timeslots.title')</a>
        <a href="{{ route('admin.lessons.advanced') }}" class="btn-tool"><i class="la la-th"></i>@lang('lessons_admin.advanced.title')</a>
        <a href="{{ route('admin.school-schedule.pdf') }}" class="btn-tool" target="_blank"><i class="la la-file-pdf"></i>@lang('lessons_admin.toolbar.export')</a>
    </div>
</div>

<div class="ls-surface">
    @if($lessons->count() === 0)
        <div class="ls-empty">
            <div class="ls-empty-ico"><i class="la la-clock"></i></div>
            <h4>@lang('lessons_admin.table.empty_title')</h4>
            <p>@lang('lessons_admin.table.empty_hint')</p>
            <a href="{{ route('admin.lessons.create') }}" class="btn-gold" style="margin-top:.5rem"><i class="la la-plus"></i>@lang('lessons_admin.actions.add')</a>
        </div>
    @else
        <div class="table-responsive">
            <table class="table ls-table" id="lessonsTable">
                <thead>
                    <tr>
                        <th>@lang('lessons_admin.table.teacher')</th>
                        <th>@lang('lessons_admin.table.subject')</th>
                        <th>@lang('lessons_admin.table.section')</th>
                        <th>@lang('lessons_admin.table.class')</th>
                        <th>@lang('lessons_admin.table.day')</th>
                        <th>@lang('lessons_admin.table.period')</th>
                        <th>@lang('lessons_admin.table.time')</th>
                        <th>@lang('lessons_admin.table.room')</th>
                        <th>@lang('lessons_admin.table.substitute')</th>
                        <th>@lang('lessons_admin.table.students')</th>
                        <th style="text-align:{{ $isRtl ? 'left' : 'right' }}">@lang('lessons_admin.table.actions')</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($lessons as $lesson)
                    <tr>
                        <td>{{ optional($lesson->teacher)->name ?? '—' }}</td>
                        <td>{{ optional($lesson->subject)->name ?? '—' }}</td>
                        <td>{{ optional(optional($lesson->schedule)->classRoom)->section->name ?? '—' }}</td>
                        <td>{{ optional(optional($lesson->schedule)->classRoom)->name ?? '—' }}</td>
                        <td><span class="ls-pill day">{{ $days[$lesson->day_of_week] ?? $lesson->day_of_week }}</span></td>
                        <td><span class="ls-pill period">{{ $lesson->period_number }}</span></td>
                        <td>
                            @if($lesson->start_time && $lesson->end_time)
                                <span class="ls-pill muted">{{ $lesson->start_time->format('H:i') }} - {{ $lesson->end_time->format('H:i') }}</span>
                            @else
                                <span style="color:#94a3b8">—</span>
                            @endif
                        </td>
                        <td>{{ $lesson->room ?? '—' }}</td>
                        <td>
                            @if($lesson->substitute_teacher_id)
                                <span class="ls-pill" style="background:#fef9c3;color:#854d0e">{{ optional($lesson->substituteTeacher)->name ?? '—' }}</span>
                            @else
                                <span style="color:#94a3b8">—</span>
                            @endif
                        </td>
                        <td><span class="ls-pill muted">{{ $lesson->students_count ?? 0 }}</span></td>
                        <td style="text-align:{{ $isRtl ? 'left' : 'right' }}">
                            <div class="ls-actions">
                                <a href="{{ route('admin.lessons.students.index', $lesson->id) }}" class="ls-action-btn" title="@lang('lessons_admin.students.title')" style="background:#eef2ff;border:1px solid #e0e7ff;color:#4338ca"><i class="la la-users"></i></a>
                                <a href="{{ route('admin.lessons.edit', $lesson->id) }}" class="ls-action-btn edit" title="@lang('lessons_admin.actions.edit')"><i class="la la-edit"></i></a>
                                <form action="{{ route('admin.lessons.destroy', $lesson->id) }}" method="POST" style="display:inline" onsubmit="return confirm('{{ __('lessons_admin.confirm_delete') }}');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="ls-action-btn del" title="@lang('lessons_admin.actions.delete')"><i class="la la-trash"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @if($lessons->hasPages())
            <div style="padding:.85rem 1rem; border-top:1px solid #f1f5f9; background:#fff">
                {{ $lessons->links() }}
            </div>
        @endif
    @endif
</div>
@endsection
