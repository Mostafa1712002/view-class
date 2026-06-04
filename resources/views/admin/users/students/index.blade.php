@extends('layouts.app')

@section('title', __('users.students'))
@section('body_class','theme-light')

@push('styles')
<style>
    /* Students — card 52 polish */
    body.theme-light .students-kpis .card { padding: .9rem 1.05rem; }
    body.theme-light .students-kpis .label {
        color: #64748b; font-weight: 600; font-size: .74rem; letter-spacing: .3px;
        text-transform: uppercase; margin-bottom: .35rem;
    }
    body.theme-light .students-kpis .value {
        font-size: 1.55rem; font-weight: 800; color: var(--gold-400);
        letter-spacing: -.5px; line-height: 1;
    }
    body.theme-light .students-kpis .icon {
        width: 40px; height: 40px; border-radius: 12px;
        display: inline-flex; align-items: center; justify-content: center;
        background: linear-gradient(135deg, #fff6dd, #fde8ad);
        color: var(--gold-500); font-size: 1.15rem;
        box-shadow: inset 0 0 0 1px rgba(207,160,70,.18);
    }
    body.theme-light .students-toolbar {
        display: flex; flex-wrap: wrap; gap: .55rem; align-items: center; justify-content: space-between;
    }
    body.theme-light .add-student-btn {
        background: linear-gradient(135deg, var(--gold-200), var(--gold-500)) !important;
        color: #fff !important; border: none; padding: .55rem 1rem;
        border-radius: 10px; font-weight: 600; box-shadow: 0 4px 14px rgba(207,160,70,.25);
    }
    body.theme-light .add-student-btn:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(207,160,70,.32); }
    body.theme-light .students-toolbar .btn-soft {
        background: #fff; border: 1px solid #e5e7eb; color: #475569;
        border-radius: 10px; padding: .5rem .9rem; font-weight: 500;
    }
    body.theme-light .students-toolbar .btn-soft:hover { background: #f8fafc; color: #0f172a; }
    body.theme-light .students-toolbar .dropdown-menu {
        border: 1px solid #e5e7eb; border-radius: 12px; padding: .35rem;
        box-shadow: 0 12px 32px rgba(15,23,42,.08);
        min-width: 240px;
    }
    body.theme-light .students-toolbar .dropdown-item {
        border-radius: 8px; padding: .55rem .75rem; display: flex; align-items: center; gap: .55rem; color: #0f172a;
    }
    body.theme-light .students-toolbar .dropdown-item i {
        width: 26px; height: 26px; border-radius: 8px;
        background: #fff6dd; color: var(--gold-500);
        display: inline-flex; align-items: center; justify-content: center; font-size: .95rem;
    }
    body.theme-light .students-toolbar .dropdown-item:hover { background: #f8fafc; }
    body.theme-light .students-toolbar .dropdown-item .badge-soon {
        background: #f1f5f9; color: #64748b; font-size: .65rem; padding: .15rem .45rem; border-radius: 999px;
        margin-inline-start: auto;
    }
    body.theme-light .students-search input {
        border-radius: 999px; border: 1px solid #e5e7eb; background: #fff;
        padding: .5rem 1rem .5rem 2.4rem; min-width: 220px;
    }
    body.theme-light .students-search {
        position: relative; flex: 0 1 280px;
    }
    body.theme-light .students-search > i {
        position: absolute; top: 50%; transform: translateY(-50%);
        inset-inline-start: .85rem; color: #94a3b8; pointer-events: none;
    }

    /* Table polish */
    body.theme-light .students-table { width: 100%; }
    body.theme-light .students-table thead th {
        font-size: .76rem; font-weight: 700; text-transform: uppercase;
        letter-spacing: .3px; color: #64748b;
    }
    body.theme-light .students-table tbody td {
        vertical-align: middle; padding: .9rem .85rem;
    }
    body.theme-light .student-avatar {
        width: 36px; height: 36px; border-radius: 50%;
        background: linear-gradient(135deg, #fef6df, #fde2a8);
        color: var(--gold-500); display: inline-flex; align-items: center; justify-content: center;
        font-weight: 700; font-size: .85rem;
        margin-inline-end: .65rem;
    }
    body.theme-light .student-name { font-weight: 700; color: #0f172a; }
    body.theme-light .student-sub { color: #94a3b8; font-size: .76rem; display: block; margin-top: 2px; }
    body.theme-light .grade-chip {
        background: #eef2ff; color: #4338ca; padding: .15rem .55rem;
        border-radius: 999px; font-size: .72rem; font-weight: 600;
    }
    body.theme-light .class-chip {
        background: #ecfdf5; color: #047857; padding: .15rem .55rem;
        border-radius: 999px; font-size: .72rem; font-weight: 600;
    }
    body.theme-light .gender-chip {
        padding: .15rem .5rem; border-radius: 999px; font-size: .72rem; font-weight: 600;
    }
    body.theme-light .gender-chip.male { background: #eff6ff; color: #1d4ed8; }
    body.theme-light .gender-chip.female { background: #fdf2f8; color: #be185d; }
    body.theme-light .status-pill {
        display: inline-flex; align-items: center; gap: .35rem;
        padding: .2rem .6rem; border-radius: 999px; font-size: .72rem; font-weight: 600;
    }
    body.theme-light .status-pill.on { background: #ecfdf5; color: #047857; }
    body.theme-light .status-pill.off { background: #fef2f2; color: #b91c1c; }
    body.theme-light .row-actions { display: inline-flex; gap: .35rem; align-items: center; }
    body.theme-light .row-actions .btn-icon {
        width: 34px; height: 34px; display: inline-flex; align-items: center; justify-content: center;
        border-radius: 9px; border: 1px solid #e5e7eb; background: #fff; color: #475569;
        transition: all .15s ease;
    }
    body.theme-light .row-actions .btn-icon:hover { background: var(--gold-100, #fff6dd); color: var(--gold-500); border-color: var(--gold-200, #fde2a8); }
    body.theme-light .row-actions .btn-icon.danger:hover { background: #fef2f2; color: #b91c1c; border-color: #fecaca; }
    body.theme-light .row-actions .dropdown-menu {
        min-width: 230px; border-radius: 12px; padding: .35rem;
        border: 1px solid #e5e7eb; box-shadow: 0 12px 32px rgba(15,23,42,.08);
    }
    body.theme-light .row-actions .dropdown-item {
        border-radius: 8px; padding: .5rem .65rem; display: flex; align-items: center; gap: .55rem;
        color: #0f172a; font-size: .85rem;
    }
    body.theme-light .row-actions .dropdown-item i {
        width: 24px; height: 24px; border-radius: 6px;
        background: #fff6dd; color: var(--gold-500);
        display: inline-flex; align-items: center; justify-content: center; font-size: .9rem;
    }
    body.theme-light .row-actions .dropdown-item:hover { background: #f8fafc; }

    body.theme-light .empty-state { padding: 3.5rem 1rem; text-align: center; }
    body.theme-light .empty-state .icon-wrap {
        width: 64px; height: 64px; border-radius: 16px; margin: 0 auto 1rem;
        background: linear-gradient(135deg, #fff6dd, #fde8ad);
        color: var(--gold-500); font-size: 1.6rem;
        display: inline-flex; align-items: center; justify-content: center;
    }
    body.theme-light .empty-state h4 { color: #0f172a; font-weight: 700; margin-bottom: .35rem; }
    body.theme-light .empty-state p { color: #64748b; margin-bottom: 0; }

    /* Mobile — collapse table into cards under 576px */
    @media (max-width: 575.98px) {
        body.theme-light .students-table { display: none; }
        body.theme-light .students-mobile { display: block; }
    }
    @media (min-width: 576px) {
        body.theme-light .students-mobile { display: none; }
    }
    body.theme-light .student-card-mobile {
        background: #fff; border: 1px solid #e5e7eb; border-radius: 12px;
        padding: .9rem; margin-bottom: .65rem;
    }
    body.theme-light .student-card-mobile .head {
        display: flex; align-items: center; gap: .65rem; margin-bottom: .5rem;
    }
    body.theme-light .student-card-mobile .meta {
        display: flex; flex-wrap: wrap; gap: .35rem .55rem;
        font-size: .78rem; color: #64748b; margin-bottom: .5rem;
    }
    body.theme-light .student-card-mobile .meta strong { color: #0f172a; }
    body.theme-light .student-card-mobile .actions {
        display: flex; gap: .35rem; flex-wrap: wrap;
    }
</style>
@endpush

@section('content')
@php
    $totalStudents = $students->total();
    $rolePage = 'students';
@endphp

<div class="content-header row">
    <div class="content-header-left col-md-8 col-12 mb-2">
        <h2 class="content-header-title mb-0">@lang('users.students')</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
                <li class="breadcrumb-item active">@lang('users.students')</li>
            </ol>
        </div>
    </div>
</div>

<div class="content-body">
    @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

    <div class="row students-kpis">
        <div class="col-6 col-md-3">
            <div class="card d-flex flex-row align-items-center justify-content-between">
                <div>
                    <div class="label">@lang('users.student_total')</div>
                    <div class="value">{{ $totalStudents }}</div>
                </div>
                <span class="icon"><i class="la la-user-graduate"></i></span>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card d-flex flex-row align-items-center justify-content-between">
                <div>
                    <div class="label">@lang('users.student_active')</div>
                    <div class="value">{{ $students->getCollection()->where('is_active', true)->count() }}</div>
                </div>
                <span class="icon"><i class="la la-check-circle"></i></span>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card d-flex flex-row align-items-center justify-content-between">
                <div>
                    <div class="label">@lang('users.student_with_class')</div>
                    <div class="value">{{ $students->getCollection()->whereNotNull('class_room_id')->count() }}</div>
                </div>
                <span class="icon"><i class="la la-chalkboard"></i></span>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card d-flex flex-row align-items-center justify-content-between">
                <div>
                    <div class="label">@lang('users.student_with_parent')</div>
                    <div class="value">
                        @php
                            $linkedParents = \DB::table('parent_student')
                                ->whereIn('student_id', $students->getCollection()->pluck('id'))
                                ->distinct('student_id')->count('student_id');
                        @endphp
                        {{ $linkedParents }}
                    </div>
                </div>
                <span class="icon"><i class="la la-user-friends"></i></span>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header students-toolbar">
            <div class="d-flex flex-wrap gap-1 align-items-center">
                <div class="dropdown">
                    <button class="btn btn-sm add-student-btn dropdown-toggle" data-toggle="dropdown" data-bs-toggle="dropdown">
                        <i class="la la-plus me-1"></i> @lang('users.add_student')
                    </button>
                    <div class="dropdown-menu">
                        <a class="dropdown-item" href="{{ route('admin.users.students.create') }}">
                            <i class="la la-user-plus"></i> @lang('users.add_student')
                        </a>
                        <a class="dropdown-item" href="{{ route('admin.users.students.import.form') }}">
                            <i class="la la-file-excel"></i> @lang('users.import_excel')
                        </a>
                        <a class="dropdown-item" href="{{ route('admin.noor.form') }}">
                            <i class="la la-cloud-download-alt"></i> @lang('users.import_noor')
                        </a> {{-- === Noor card 58 === --}}
                        <a class="dropdown-item" href="{{ route('admin.users.students.photos') }}">
                            <i class="la la-images"></i> @lang('users.import_photos')
                        </a>
                        <a class="dropdown-item" href="{{ route('admin.users.students.status') }}">
                            <i class="la la-sync"></i> @lang('users.refresh_status')
                        </a>
                    </div>
                </div>

                <div class="dropdown">
                    <button class="btn btn-sm btn-soft dropdown-toggle" data-toggle="dropdown" data-bs-toggle="dropdown">
                        <i class="la la-th-list me-1"></i> @lang('users.other_options')
                    </button>
                    <div class="dropdown-menu">
                        <a class="dropdown-item" href="{{ route('admin.users.students.index', ['filter' => 'graduates']) }}"><i class="la la-graduation-cap"></i> @lang('users.graduates')</a>
                        <form action="{{ route('admin.users.students.graduates.delete') }}" method="POST" class="m-0" onsubmit="return confirm('@lang('users.confirm_delete_graduates')');">
                            @csrf
                            <button type="submit" class="dropdown-item text-danger"><i class="la la-trash"></i> @lang('users.delete_graduates')</button>
                        </form>
                        <a class="dropdown-item" href="{{ route('admin.users.students.index', ['advanced' => 1]) }}"><i class="la la-list"></i> @lang('users.advanced_list')</a>
                        <a class="dropdown-item" href="{{ route('admin.users.students.index', ['view' => 'counts']) }}"><i class="la la-chart-bar"></i> @lang('users.counts')</a>
                        <a class="dropdown-item" href="{{ route('admin.users.students.index', ['filter' => 'no_parents']) }}"><i class="la la-unlink"></i> @lang('users.unlinked_to_parents')</a>
                        <a class="dropdown-item" href="{{ route('admin.users.students.global-search') }}"><i class="la la-search"></i> @lang('users.global_search')</a>
                    </div>
                </div>

                <div class="dropdown">
                    <button class="btn btn-sm btn-soft dropdown-toggle" data-toggle="dropdown" data-bs-toggle="dropdown">
                        <i class="la la-cogs me-1"></i> @lang('users.operations')
                    </button>
                    <div class="dropdown-menu">
                        @foreach(['hide_grades','show_grades','hide_report','show_report','license','unlicense','waiting'] as $op)
                            <button type="button" class="dropdown-item js-bulk" data-op="{{ $op }}">
                                <i class="la la-tasks"></i> @lang('users.op_'.$op)
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>

            <form action="{{ route('admin.users.students.index') }}" method="GET" class="students-search">
                <i class="la la-search"></i>
                <input type="search" name="q" value="{{ $q }}" placeholder="@lang('users.student_search_hint')" />
            </form>
        </div>

        @if(($filter ?? '') === 'graduates' || ($filter ?? '') === 'no_parents')
            <div class="px-3 pt-3">
                <span class="badge badge-info" style="font-size:.85rem;padding:.5rem .8rem;">
                    <i class="la la-filter"></i>
                    {{ $filter === 'graduates' ? __('users.filter_active_graduates') : __('users.filter_active_no_parents') }}
                </span>
                <a href="{{ route('admin.users.students.index') }}" class="btn btn-sm btn-link text-danger">
                    <i class="la la-times"></i> @lang('users.clear_filter')
                </a>
            </div>
        @endif

        @if($advanced ?? false)
            <div class="px-3 pt-3">
                <form action="{{ route('admin.users.students.index') }}" method="GET" class="card border p-3 mb-0">
                    <div class="form-row align-items-end">
                        <div class="col-md-3 col-6 mb-2">
                            <label class="small mb-1">@lang('users.grade_level')</label>
                            <select name="section_id" class="form-control form-control-sm">
                                <option value="">@lang('users.all_grades')</option>
                                @foreach($sections as $s)
                                    <option value="{{ $s->id }}" @selected(($sectionId ?? null) == $s->id)>{{ $s->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 col-6 mb-2">
                            <label class="small mb-1">@lang('users.class')</label>
                            <select name="class_room_id" class="form-control form-control-sm">
                                <option value="">@lang('users.all_classes')</option>
                                @foreach($classes as $c)
                                    <option value="{{ $c->id }}" @selected(($classId ?? null) == $c->id)>{{ $c->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2 col-6 mb-2">
                            <label class="small mb-1">@lang('users.gender')</label>
                            <select name="gender" class="form-control form-control-sm">
                                <option value="">@lang('users.all_genders')</option>
                                <option value="male" @selected(($gender ?? '') === 'male')>@lang('users.gender_male')</option>
                                <option value="female" @selected(($gender ?? '') === 'female')>@lang('users.gender_female')</option>
                            </select>
                        </div>
                        <div class="col-md-2 col-6 mb-2">
                            <label class="small mb-1">@lang('users.filter_status')</label>
                            <select name="status" class="form-control form-control-sm">
                                <option value="">@lang('users.filter_status_all')</option>
                                <option value="active" @selected(($status ?? '') === 'active')>@lang('users.student_status_active')</option>
                                <option value="inactive" @selected(($status ?? '') === 'inactive')>@lang('users.student_status_inactive')</option>
                            </select>
                        </div>
                        <div class="col-md-2 col-12 mb-2">
                            <input type="hidden" name="advanced" value="1">
                            <button type="submit" class="btn btn-sm btn-primary"><i class="la la-filter"></i> @lang('users.filter_apply')</button>
                            <a href="{{ route('admin.users.students.index', ['advanced' => 1]) }}" class="btn btn-sm btn-soft">@lang('users.filter_reset')</a>
                        </div>
                    </div>
                </form>
            </div>
        @endif

        @if(($view ?? '') === 'counts' && $counts)
            <div class="px-3 pt-3">
                <div class="row">
                    @foreach([
                        ['count_total', $counts['total'], '#1d4ed8'],
                        ['count_active', $counts['active'], '#16a34a'],
                        ['count_inactive', $counts['inactive'], '#dc2626'],
                        ['count_no_parents', $counts['no_parents'], '#b45309'],
                        ['count_graduates', $counts['graduates'], '#7c3aed'],
                    ] as $stat)
                        <div class="col-md-2 col-6 mb-2">
                            <div class="card text-center mb-0">
                                <div class="card-body p-2">
                                    <div class="text-muted small">@lang('users.'.$stat[0])</div>
                                    <h3 class="fw-bold mb-0" style="color:{{ $stat[2] }};">{{ $stat[1] }}</h3>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                @if($counts['per_section']->count())
                    <div class="card border mb-0">
                        <div class="card-header py-2"><strong>@lang('users.count_per_section')</strong></div>
                        <div class="card-body p-0">
                            <table class="table table-sm mb-0">
                                <tbody>
                                    @foreach($counts['per_section'] as $name => $n)
                                        <tr><td>{{ $name }}</td><td class="text-end">{{ $n }}</td></tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            </div>
        @endif

        <div class="table-responsive d-none d-sm-block">
            <table class="table table-hover students-table align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width:36px;"><input type="checkbox" id="js-check-all" /></th>
                        <th>@lang('users.name')</th>
                        <th>@lang('users.national_id')</th>
                        <th>@lang('users.grade_level')</th>
                        <th>@lang('users.class')</th>
                        <th>@lang('users.gender')</th>
                        <th>@lang('users.status')</th>
                        <th>@lang('users.last_activity')</th>
                        <th style="text-align:end;">@lang('users.actions')</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($students as $u)
                    @php
                        $initials = mb_substr($u->name, 0, 1);
                        $isActive = (bool) $u->is_active;
                    @endphp
                    <tr>
                        <td><input type="checkbox" class="js-row-check" value="{{ $u->id }}" /></td>
                        <td>
                            <span class="d-inline-flex align-items-center">
                                <span class="student-avatar">{{ $initials }}</span>
                                <span>
                                    <a href="{{ route('admin.users.students.show', $u->id) }}" class="student-name text-decoration-none">{{ $u->name }}</a>
                                    <small class="student-sub">{{ '@'.$u->username }}</small>
                                </span>
                            </span>
                        </td>
                        <td>{{ $u->national_id ?? '—' }}</td>
                        <td>
                            @if(optional($u->section)->name)
                                <span class="grade-chip">{{ $u->section->name }}</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            @if(optional($u->classRoom)->name)
                                <span class="class-chip">{{ $u->classRoom->name }}</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            @if($u->gender)
                                <span class="gender-chip {{ $u->gender }}">@lang('users.gender_'.$u->gender)</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            <span class="status-pill {{ $isActive ? 'on' : 'off' }}">
                                <i class="la la-circle"></i>
                                {{ $isActive ? __('users.student_status_active') : __('users.student_status_inactive') }}
                            </span>
                        </td>
                        <td><small class="text-muted">{{ $u->last_login_at ? $u->last_login_at->diffForHumans() : '—' }}</small></td>
                        <td style="text-align:end;">
                            <div class="row-actions">
                                <a href="{{ route('admin.users.students.show', $u->id) }}" class="btn-icon" title="@lang('users.student_view')"><i class="la la-eye"></i></a>
                                <a href="{{ route('admin.users.students.edit', $u->id) }}" class="btn-icon" title="@lang('users.student_edit')"><i class="la la-edit"></i></a>
                                <form action="{{ route('admin.users.students.destroy', $u->id) }}" method="POST" class="d-inline m-0" onsubmit="return confirm('@lang('users.delete') ؟');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn-icon danger" title="@lang('users.student_delete')"><i class="la la-trash"></i></button>
                                </form>
                                <div class="dropdown d-inline">
                                    <button class="btn-icon" data-toggle="dropdown" data-bs-toggle="dropdown" title="@lang('users.student_more_actions')">
                                        <i class="la la-ellipsis-v"></i>
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-end">
                                        @if(auth()->user()->isSuperAdmin())
                                        <form action="{{ route('admin.users.impersonate.start', $u->id) }}" method="POST" class="m-0">
                                            @csrf
                                            <button class="dropdown-item" type="submit"><i class="la la-user-secret"></i> @lang('users.login_as')</button>
                                        </form>
                                        @endif
                                        <a class="dropdown-item" href="{{ route('admin.users.students.parents', $u->id) }}"><i class="la la-user-friends"></i> @lang('users.parents_link')</a>
                                        <a class="dropdown-item" href="{{ route('admin.users.students.schedule', $u->id) }}"><i class="la la-calendar"></i> @lang('users.schedule_link')</a>
                                        <a class="dropdown-item" href="{{ route('admin.users.students.lessons', $u->id) }}"><i class="la la-chalkboard"></i> @lang('users.classes_link')</a>
                                        <a class="dropdown-item" href="{{ route('admin.users.students.attendance', $u->id) }}"><i class="la la-times-circle"></i> @lang('users.absences_link')</a>
                                        <a class="dropdown-item" href="{{ route('admin.users.students.behavior', $u->id) }}"><i class="la la-balance-scale"></i> @lang('users.behavior_link')</a>
                                        <a class="dropdown-item" href="{{ route('admin.users.students.medical', $u->id) }}"><i class="la la-notes-medical"></i> @lang('users.medical_link')</a>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9">
                            <div class="empty-state">
                                <div class="icon-wrap"><i class="la la-user-graduate"></i></div>
                                <h4>@lang('users.no_results')</h4>
                                <p>@lang('users.student_search_hint')</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        {{-- Mobile card layout --}}
        <div class="students-mobile p-2">
            @forelse($students as $u)
                @php $isActive = (bool) $u->is_active; @endphp
                <div class="student-card-mobile">
                    <div class="head">
                        <input type="checkbox" class="js-row-check" value="{{ $u->id }}" />
                        <span class="student-avatar">{{ mb_substr($u->name, 0, 1) }}</span>
                        <div class="flex-grow-1">
                            <a href="{{ route('admin.users.students.show', $u->id) }}" class="student-name text-decoration-none d-block">{{ $u->name }}</a>
                            <small class="student-sub">{{ '@'.$u->username }}</small>
                        </div>
                        <span class="status-pill {{ $isActive ? 'on' : 'off' }}">{{ $isActive ? __('users.student_status_active') : __('users.student_status_inactive') }}</span>
                    </div>
                    <div class="meta">
                        <span><strong>@lang('users.national_id'):</strong> {{ $u->national_id ?? '—' }}</span>
                        <span><strong>@lang('users.grade_level'):</strong> {{ optional($u->section)->name ?? '—' }}</span>
                        <span><strong>@lang('users.class'):</strong> {{ optional($u->classRoom)->name ?? '—' }}</span>
                    </div>
                    <div class="actions">
                        <a class="btn btn-sm btn-soft" href="{{ route('admin.users.students.show', $u->id) }}"><i class="la la-eye"></i></a>
                        <a class="btn btn-sm btn-soft" href="{{ route('admin.users.students.edit', $u->id) }}"><i class="la la-edit"></i></a>
                        <a class="btn btn-sm btn-soft" href="{{ route('admin.users.students.parents', $u->id) }}"><i class="la la-user-friends"></i></a>
                        <a class="btn btn-sm btn-soft" href="{{ route('admin.users.students.attendance', $u->id) }}"><i class="la la-times-circle"></i></a>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-soft dropdown-toggle" data-toggle="dropdown" data-bs-toggle="dropdown"><i class="la la-ellipsis-v"></i></button>
                            <div class="dropdown-menu dropdown-menu-end">
                                @if(auth()->user()->isSuperAdmin())
                                <form action="{{ route('admin.users.impersonate.start', $u->id) }}" method="POST" class="m-0">
                                    @csrf
                                    <button class="dropdown-item" type="submit"><i class="la la-user-secret"></i> @lang('users.login_as')</button>
                                </form>
                                @endif
                                <a class="dropdown-item" href="{{ route('admin.users.students.schedule', $u->id) }}"><i class="la la-calendar"></i> @lang('users.schedule_link')</a>
                                <a class="dropdown-item" href="{{ route('admin.users.students.lessons', $u->id) }}"><i class="la la-chalkboard"></i> @lang('users.classes_link')</a>
                                <a class="dropdown-item" href="{{ route('admin.users.students.behavior', $u->id) }}"><i class="la la-balance-scale"></i> @lang('users.behavior_link')</a>
                                <a class="dropdown-item" href="{{ route('admin.users.students.medical', $u->id) }}"><i class="la la-notes-medical"></i> @lang('users.medical_link')</a>
                                <form action="{{ route('admin.users.students.destroy', $u->id) }}" method="POST" class="m-0" onsubmit="return confirm('@lang('users.delete') ؟');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="dropdown-item text-danger"><i class="la la-trash"></i> @lang('users.delete')</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="empty-state">
                    <div class="icon-wrap"><i class="la la-user-graduate"></i></div>
                    <h4>@lang('users.no_results')</h4>
                    <p>@lang('users.student_search_hint')</p>
                </div>
            @endforelse
        </div>

        <div class="card-footer d-flex flex-wrap align-items-center justify-content-between gap-1">
            <small class="text-muted">{{ $students->total() }} @lang('users.students')</small>
            <div>{{ $students->links() }}</div>
        </div>
    </div>
</div>

<form id="js-bulk-form" action="{{ route('admin.users.students.bulk') }}" method="POST" class="d-none">
    @csrf
    <input type="hidden" name="action" id="js-bulk-action" />
</form>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var checkAll = document.getElementById('js-check-all');
    function rowChecks() { return document.querySelectorAll('.js-row-check'); }
    if (checkAll) {
        checkAll.addEventListener('change', function () {
            rowChecks().forEach(function (r) { r.checked = checkAll.checked; });
        });
    }
    // Row 3-dot dropdown clipping is handled globally in layouts/app.blade.php
    // (relocates the open menu to <body> so it escapes .table-responsive and any
    // transformed .card ancestor). No per-view handling needed here.

    document.querySelectorAll('.js-bulk').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var ids = [];
            rowChecks().forEach(function (r) { if (r.checked) ids.push(r.value); });
            if (ids.length === 0) { alert('@lang('users.no_results')'); return; }
            if (!confirm('@lang('users.operations'): ' + btn.textContent.trim() + ' (' + ids.length + ')')) return;
            var f = document.getElementById('js-bulk-form');
            document.getElementById('js-bulk-action').value = btn.dataset.op;
            f.querySelectorAll('input[name="ids[]"]').forEach(function (el) { el.remove(); });
            ids.forEach(function (id) {
                var inp = document.createElement('input');
                inp.type = 'hidden';
                inp.name = 'ids[]';
                inp.value = id;
                f.appendChild(inp);
            });
            f.submit();
        });
    });
});
</script>
@endsection
