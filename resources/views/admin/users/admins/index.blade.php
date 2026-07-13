@extends('layouts.app')

@section('title', __('users.admins'))
@section('body_class', 'theme-light')

@php
    $isRtl = app()->getLocale() === 'ar';
    $total = $stats['total'] ?? 0;
    $active = $stats['active'] ?? 0;
    $withJob = $stats['withJob'] ?? 0;
    $super = $stats['super'] ?? 0;
@endphp

@push('styles')
<style>
    /* ===== Admins index — light + gold accent (card 55) ============= */
    .ad-header { margin-bottom: 1.25rem; }
    .ad-header h2 {
        font-size: 1.5rem; font-weight: 700; color: #0f172a;
        margin-bottom: .15rem; letter-spacing: -.2px;
    }
    .ad-header .breadcrumb { padding: 0; margin: 0; background: transparent; font-size: .85rem; }
    .ad-header .breadcrumb-item + .breadcrumb-item::before { color: #cbd5e1; }

    /* KPI strip */
    .ad-kpis { display: grid; grid-template-columns: repeat(4, minmax(0,1fr));
        gap: .75rem; margin-bottom: 1.25rem; }
    .ad-kpi {
        background: #fff; border: 1px solid #e5e7eb; border-radius: 14px;
        padding: .85rem 1rem; display: flex; align-items: center; gap: .75rem;
        box-shadow: 0 1px 2px rgba(15,23,42,.04), 0 4px 12px rgba(15,23,42,.04);
        transition: transform .2s ease, box-shadow .2s ease;
    }
    .ad-kpi:hover { transform: translateY(-2px); box-shadow: 0 4px 14px rgba(15,23,42,.06), 0 12px 28px rgba(15,23,42,.05); }
    .ad-kpi .ico {
        width: 38px; height: 38px; border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.1rem; flex-shrink: 0;
        background: linear-gradient(135deg, #fef3c7, #fde68a); color: var(--gold-500);
    }
    .ad-kpi .ico.ico-blue   { background: linear-gradient(135deg, #dbeafe, #bfdbfe); color: #1d4ed8; }
    .ad-kpi .ico.ico-violet { background: linear-gradient(135deg, #ede9fe, #ddd6fe); color: #6d28d9; }
    .ad-kpi .ico.ico-green  { background: linear-gradient(135deg, #dcfce7, #bbf7d0); color: #15803d; }
    .ad-kpi .num   { font-size: 1.35rem; font-weight: 800; color: var(--gold-400); line-height: 1.1; letter-spacing: -.5px; }
    .ad-kpi .num.muted { color: #0f172a; }
    .ad-kpi .lbl   { font-size: .8rem; color: #64748b; }

    /* Search engine card */
    .ad-search-card {
        background: #fff; border: 1px solid #e5e7eb; border-radius: 14px;
        padding: 1rem 1.1rem; margin-bottom: 1rem;
        box-shadow: 0 1px 2px rgba(15,23,42,.04), 0 4px 12px rgba(15,23,42,.04);
    }
    .ad-search-card .se-title {
        display: flex; align-items: center; gap: .55rem;
        font-size: .92rem; font-weight: 700; color: #0f172a; margin-bottom: .6rem;
    }
    .ad-search-card .se-title i { color: var(--gold-400); font-size: 1.1rem; }
    .ad-search-card .se-hint { color: #64748b; font-size: .8rem; font-weight: 400; margin-{{ $isRtl ? 'right' : 'left' }}: auto; }
    .ad-search-row { display: flex; gap: .55rem; flex-wrap: wrap; }
    .ad-search-row .form-control {
        flex: 1 1 240px; min-width: 0;
        background: #fff; border: 1px solid #e2e8f0; border-radius: 10px;
        padding: .55rem .85rem; font-size: .93rem; color: #0f172a;
        transition: border-color .15s ease, box-shadow .15s ease;
    }
    .ad-search-row .form-control:focus {
        border-color: var(--gold-300);
        box-shadow: 0 0 0 .2rem rgba(207,160,70,.16); outline: none;
    }
    .ad-search-row select.form-control { flex: 0 1 220px; }
    .ad-search-row .btn-gold, .ad-search-row .btn-reset { flex: 0 0 auto; }
    .btn-reset {
        background: #fff; border: 1px solid #e2e8f0; color: #475569;
        font-weight: 600; padding: .55rem 1rem; border-radius: 10px;
        display: inline-flex; align-items: center; gap: .35rem;
        transition: all .15s ease;
    }
    .btn-reset:hover { background: #f8fafc; color: #0f172a; }

    /* Toolbar */
    .ad-toolbar {
        background: #fff; border: 1px solid #e5e7eb; border-radius: 14px 14px 0 0;
        padding: .9rem 1.1rem; display: flex; flex-wrap: wrap; gap: .55rem;
        align-items: center; justify-content: space-between;
        border-bottom: 0;
    }
    .ad-toolbar .left { display: flex; flex-wrap: wrap; gap: .45rem; align-items: center; }
    .ad-toolbar .count-pill {
        background: #f8fafc; border: 1px solid #e5e7eb;
        color: #475569; font-size: .78rem; font-weight: 600;
        padding: .25rem .65rem; border-radius: 999px;
    }

    /* Gold CTA */
    .btn-gold {
        background: linear-gradient(135deg, var(--gold-300), var(--gold-500));
        border: 1px solid var(--gold-400); color: #fff;
        font-weight: 600; padding: .55rem 1.1rem; border-radius: 10px;
        box-shadow: 0 1px 2px rgba(207,160,70,.18);
        transition: transform .15s ease, box-shadow .2s ease, background .2s ease;
        display: inline-flex; align-items: center; gap: .45rem;
    }
    .btn-gold:hover {
        background: linear-gradient(135deg, var(--gold-400), var(--gold-500));
        color: #fff; transform: translateY(-1px);
        box-shadow: 0 6px 16px rgba(207,160,70,.22);
    }
    .btn-gold:active { transform: translateY(0); }

    .btn-ghost {
        background: #fff; border: 1px solid #e2e8f0; color: #334155;
        font-weight: 600; padding: .55rem 1rem; border-radius: 10px;
        display: inline-flex; align-items: center; gap: .35rem;
        transition: all .15s ease;
    }
    .btn-ghost:hover { background: #f8fafc; color: #0f172a; border-color: #cbd5e1; }
    .btn-ghost i { color: var(--gold-400); }

    /* Surface */
    .ad-surface {
        background: #fff; border: 1px solid #e5e7eb; border-top: 0;
        border-radius: 0 0 14px 14px; overflow: hidden;
    }

    /* Table */
    .ad-table { margin: 0; }
    .ad-table thead th {
        background: #f8fafc !important; color: #475569 !important;
        font-weight: 600; font-size: .78rem; text-transform: uppercase; letter-spacing: .5px;
        border-bottom: 1px solid #e5e7eb; padding: .8rem 1rem;
        white-space: nowrap;
    }
    .ad-table tbody td { padding: .85rem 1rem; vertical-align: middle; color: #0f172a; }
    .ad-table tbody tr { transition: background .15s ease; }
    .ad-table tbody tr:hover { background: #fafbfc; }
    .ad-table tbody tr + tr td { border-top: 1px solid #f1f5f9; }

    /* User cell */
    .ad-user { display: flex; align-items: center; gap: .65rem; }
    .ad-avatar {
        width: 38px; height: 38px; border-radius: 10px;
        background: linear-gradient(135deg, #fef3c7, #fde68a);
        color: var(--gold-500); font-weight: 700; font-size: .88rem;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
    }
    .ad-name { font-weight: 600; color: #0f172a; line-height: 1.25; }
    .ad-secondary { color: #64748b; font-size: .82rem; }

    /* Pills */
    .ad-pill {
        display: inline-flex; align-items: center; gap: .3rem;
        padding: .2rem .55rem; border-radius: 999px; font-size: .72rem; font-weight: 600;
        line-height: 1.3; border: 1px solid transparent;
    }
    .ad-pill .dot { width: 6px; height: 6px; border-radius: 50%; display: inline-block; }
    .ad-pill.active   { background: #ecfdf5; color: #047857; border-color: #a7f3d0; }
    .ad-pill.active .dot { background: #10b981; }
    .ad-pill.inactive { background: #f3f4f6; color: #6b7280; border-color: #e5e7eb; }
    .ad-pill.inactive .dot { background: #9ca3af; }
    .ad-pill.role     { background: #fffbeb; color: #92400e; border-color: #fde68a; }
    .ad-pill.role-super { background: #ede9fe; color: #6d28d9; border-color: #ddd6fe; }
    .ad-pill.role-muted { background: #f1f5f9; color: #64748b; border-color: #e2e8f0; }

    /* Action buttons */
    .ad-actions { display: inline-flex; align-items: center; gap: .35rem; }
    .ad-action-btn {
        width: 32px; height: 32px; padding: 0;
        display: inline-flex; align-items: center; justify-content: center;
        border-radius: 8px; border: 1px solid #e2e8f0; background: #fff;
        color: #475569; transition: all .15s ease;
    }
    .ad-action-btn:hover { transform: translateY(-1px); }
    .ad-action-btn.edit:hover { background: #fffbeb; border-color: #fde68a; color: #92400e; }
    .ad-action-btn.del { background: #fff5f5; border-color: #fecaca; color: #b91c1c; }
    .ad-action-btn.del:hover { background: #fee2e2; border-color: #fca5a5; }
    .ad-action-btn.warn:hover { background: #fff7ed; border-color: #fed7aa; color: #9a3412; }
    .ad-action-btn.info:hover { background: #eff6ff; border-color: #bfdbfe; color: #1d4ed8; }
    .ad-action-btn.more {
        background: #fff; border-color: #e2e8f0; color: #64748b;
    }
    .ad-action-btn.more:hover { background: #f8fafc; color: #0f172a; }

    /* Let row action dropdowns float above the table instead of being clipped. */
    body.theme-light .ad-table-wrap, body.theme-light .table-responsive { overflow: visible; }
    body.theme-light .dropdown-menu.is-floating {
        position: fixed; z-index: 1080;
        box-shadow: 0 8px 24px rgba(15,23,42,.12), 0 2px 6px rgba(15,23,42,.06);
    }

    /* Dropdown menu polish */
    .ad-actions .dropdown-menu {
        border: 1px solid #e5e7eb; border-radius: 12px; padding: .35rem;
        box-shadow: 0 10px 30px rgba(15,23,42,.08), 0 2px 8px rgba(15,23,42,.04);
        min-width: 240px;
    }
    .ad-actions .dropdown-item {
        border-radius: 8px; padding: .55rem .75rem; font-size: .88rem;
        display: flex; align-items: center; gap: .55rem; color: #334155;
    }
    .ad-actions .dropdown-item:hover { background: #f8fafc; color: #0f172a; }
    .ad-actions .dropdown-item i { color: var(--gold-400); width: 18px; text-align: center; }
    .ad-actions .dropdown-item.danger { color: #b91c1c; }
    .ad-actions .dropdown-item.danger:hover { background: #fee2e2; color: #991b1b; }
    .ad-actions .dropdown-item.danger i { color: #b91c1c; }
    .ad-actions .dropdown-item button {
        background: transparent; border: 0; padding: 0; width: 100%;
        text-align: {{ $isRtl ? 'right' : 'left' }}; color: inherit; font: inherit;
        display: flex; align-items: center; gap: .55rem;
    }

    /* Add menu — pretty picker */
    .ad-add-menu { min-width: 320px; max-height: 70vh; overflow-y: auto; padding: .5rem !important; }
    .ad-add-menu .dropdown-header {
        font-size: .78rem; color: #94a3b8; text-transform: uppercase; letter-spacing: .5px;
        padding: .55rem .55rem .35rem; font-weight: 700;
    }
    .ad-add-menu .ad-job-item {
        display: flex; align-items: center; gap: .65rem;
        padding: .55rem .65rem; border-radius: 8px;
        color: #0f172a; text-decoration: none; font-size: .9rem;
        transition: background .15s ease;
    }
    .ad-add-menu .ad-job-item:hover { background: #fffbeb; color: #92400e; }
    .ad-add-menu .ad-job-item .jt-ico {
        width: 30px; height: 30px; border-radius: 8px;
        background: linear-gradient(135deg, #fef3c7, #fde68a);
        color: var(--gold-500); font-size: .9rem;
        display: flex; align-items: center; justify-content: center; flex-shrink: 0;
    }
    .ad-add-menu .ad-job-item .jt-meta { display: flex; flex-direction: column; line-height: 1.2; }
    .ad-add-menu .ad-job-item .jt-slug { font-size: .72rem; color: #94a3b8; }
    .ad-add-menu .dropdown-divider { margin: .35rem 0; }
    .ad-add-menu .ad-blank {
        display: flex; align-items: center; gap: .55rem;
        padding: .55rem .65rem; border-radius: 8px;
        color: #334155; font-size: .88rem;
    }
    .ad-add-menu .ad-blank:hover { background: #f8fafc; }

    /* Empty state */
    .ad-empty { padding: 2.75rem 1rem; text-align: center; color: #94a3b8; }
    .ad-empty i { font-size: 2.5rem; opacity: .55; display: block; margin-bottom: .5rem; color: #cbd5e1; }
    .ad-empty .lbl { font-size: .95rem; color: #64748b; }
    .ad-empty .sub { font-size: .8rem; color: #94a3b8; margin-top: .25rem; }

    /* Alerts */
    .ad-alert {
        background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46;
        border-radius: 10px; padding: .65rem .85rem; display: flex; align-items: center;
        gap: .55rem; font-size: .9rem; margin-bottom: 1rem;
    }
    .ad-alert i { color: #10b981; font-size: 1.1rem; }
    .ad-alert.err { background: #fef2f2; border-color: #fecaca; color: #991b1b; }

    /* Footer */
    .ad-footer {
        padding: .85rem 1rem; background: #fff; border-top: 1px solid #f1f5f9;
        display: flex; justify-content: space-between; align-items: center;
    }
    .ad-footer .pagination { margin: 0; }

    /* Responsive */
    @media (max-width: 991.98px) {
        .ad-kpis { grid-template-columns: repeat(2, minmax(0,1fr)); }
        .ad-search-row select.form-control { flex: 1 1 100%; }
    }
    @media (max-width: 575.98px) {
        .ad-kpis { gap: .55rem; }
        .ad-kpi { padding: .7rem .8rem; }
        .ad-kpi .ico { width: 32px; height: 32px; font-size: .95rem; }
        .ad-kpi .num { font-size: 1.15rem; }
        .ad-kpi .lbl { font-size: .72rem; }
        .ad-toolbar { padding: .75rem .85rem; gap: .4rem; }
        .ad-table thead { display: none; }
        .ad-table, .ad-table tbody, .ad-table tr, .ad-table td { display: block; width: 100%; }
        .ad-table tbody tr {
            border: 1px solid #f1f5f9; border-radius: 12px;
            margin: .5rem .65rem; padding: .65rem .8rem; background: #fff;
        }
        .ad-table tbody tr + tr td { border-top: 0; }
        .ad-table tbody td {
            padding: .35rem 0; border: 0; display: flex; align-items: center;
            justify-content: space-between; gap: .75rem; font-size: .88rem;
        }
        .ad-table tbody td::before {
            content: attr(data-label);
            font-size: .68rem; color: #64748b; font-weight: 700;
            text-transform: uppercase; letter-spacing: .5px;
        }
        .ad-table tbody td.actions-cell { justify-content: flex-end; }
        .ad-table tbody td.actions-cell::before { display: none; }
        .ad-user { gap: .55rem; }
        .ad-avatar { width: 34px; height: 34px; font-size: .8rem; }
    }
</style>
@endpush

@section('content')
<div class="content-header ad-header">
    <h2>@lang('users.admins')</h2>
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
        <li class="breadcrumb-item active">@lang('users.admins')</li>
    </ol>
</div>

<div class="content-body">
    @if(session('status'))
        <div class="ad-alert"><x-svg-icon name="check-circle-fill" :size="16" class="ic-success" /><span>{{ session('status') }}</span></div>
    @endif
    @if(session('error'))
        <div class="ad-alert err"><x-svg-icon name="exclamation-triangle-fill" :size="16" class="ic-danger" /><span>{{ session('error') }}</span></div>
    @endif

    {{-- KPI strip --}}
    <div class="ad-kpis">
        <div class="ad-kpi">
            <div class="ico"><x-svg-icon name="shield-lock-fill" :size="20" /></div>
            <div>
                <div class="num">{{ $total }}</div>
                <div class="lbl">@lang('users.admin_total')</div>
            </div>
        </div>
        <div class="ad-kpi">
            <div class="ico ico-green"><x-svg-icon name="check-circle-fill" :size="20" /></div>
            <div>
                <div class="num muted">{{ $active }}</div>
                <div class="lbl">@lang('users.admin_active')</div>
            </div>
        </div>
        <div class="ad-kpi">
            <div class="ico ico-blue"><x-svg-icon name="person-badge-fill" :size="20" /></div>
            <div>
                <div class="num muted">{{ $withJob }}</div>
                <div class="lbl">@lang('users.admin_with_role')</div>
            </div>
        </div>
        <div class="ad-kpi">
            <div class="ico ico-violet"><x-svg-icon name="gem" :size="20" /></div>
            <div>
                <div class="num muted">{{ $super }}</div>
                <div class="lbl">@lang('users.admin_super')</div>
            </div>
        </div>
    </div>

    {{-- Search engine --}}
    <div class="ad-search-card">
        <div class="se-title">
            <x-svg-icon name="search" :size="16" class="ic-gold" />
            <span>@lang('users.admin_search_engine')</span>
            <span class="se-hint">@lang('users.admin_search_hint')</span>
        </div>
        <form action="{{ route('admin.users.admins.index') }}" method="GET" class="ad-search-row">
            <input type="search" name="q" value="{{ $q }}"
                   class="form-control"
                   placeholder="@lang('users.admin_search_hint')" />
            <select name="job_title_id" class="form-control" onchange="this.form.submit()">
                <option value="">@lang('users.filter_job_title') — @lang('users.all')</option>
                @foreach($jobTitles as $jt)
                    <option value="{{ $jt->id }}" @selected(request('job_title_id') == $jt->id)>{{ $jt->localized_name }}</option>
                @endforeach
            </select>
            <button class="btn-gold" type="submit">
                <x-svg-icon name="search" :size="16" /> @lang('users.search')
            </button>
            @if($q || request('job_title_id'))
                <a href="{{ route('admin.users.admins.index') }}" class="btn-reset">
                    <x-svg-icon name="x-circle-fill" :size="16" class="ic-muted" /> @lang('users.cancel')
                </a>
            @endif
        </form>
    </div>

    {{-- Toolbar --}}
    <div class="ad-toolbar">
        <div class="left">
            <div class="dropdown">
                <button class="btn-gold dropdown-toggle" type="button"
                        data-toggle="dropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <x-svg-icon name="plus-lg" :size="16" /> @lang('users.add_admin')
                </button>
                <div class="dropdown-menu ad-add-menu">
                    <div class="dropdown-header">@lang('users.admin_picker_title')</div>
                    @forelse($jobTitles as $jt)
                        <a class="ad-job-item" href="{{ route('admin.users.admins.create', ['job_title_id' => $jt->id]) }}">
                            <span class="jt-ico"><x-svg-icon name="tag-fill" :size="16" /></span>
                            <span class="jt-meta">
                                <span>{{ $jt->localized_name }}</span>
                                <span class="jt-slug">{{ $jt->slug }}</span>
                            </span>
                        </a>
                    @empty
                        <div class="ad-empty" style="padding:1rem .5rem;">
                            <x-svg-icon name="tag-fill" :size="40" class="ic-muted" />
                            <div class="lbl">@lang('users.no_results')</div>
                        </div>
                    @endforelse
                    <div class="dropdown-divider"></div>
                    <a class="ad-blank" href="{{ route('admin.users.admins.create') }}">
                        <x-svg-icon name="person-plus-fill" :size="16" class="ic-gold" /> @lang('users.admin_picker_blank')
                    </a>
                </div>
            </div>
            <a class="btn-ghost" href="{{ route('admin.users.job-titles.index') }}">
                <x-svg-icon name="gear-fill" :size="16" class="ic-gold" /> @lang('users.admin_manage_job_titles')
            </a>
        </div>
        <span class="count-pill">{{ $admins->total() }} / {{ $total }}</span>
    </div>

    {{-- Table --}}
    <div class="ad-surface">
        <div class="table-responsive">
            <table class="table ad-table">
                <thead>
                    <tr>
                        <th>@lang('users.name')</th>
                        @if(auth()->user()->isSuperAdmin())<th>@lang('users.school')</th>@endif
                        <th>@lang('users.username')</th>
                        <th>@lang('users.job_title')</th>
                        <th>@lang('users.status')</th>
                        <th>@lang('users.last_activity')</th>
                        <th class="text-{{ $isRtl ? 'start' : 'end' }}">@lang('users.actions')</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($admins as $u)
                    @php
                        $initials = collect(preg_split('/\s+/u', trim($u->name ?: $u->username ?: '')))
                            ->filter()
                            ->take(2)
                            ->map(fn($p) => mb_substr($p, 0, 1))
                            ->implode('');
                        $isSuper = $u->roles->contains(fn($r) => $r->slug === 'super-admin');
                        $isCurrentlyActive = ($u->is_active ?? true) && ($u->status ?? 'active') !== 'inactive';
                    @endphp
                    <tr>
                        <td data-label="@lang('users.name')">
                            <div class="ad-user">
                                <div class="ad-avatar">{{ $initials ?: '?' }}</div>
                                <div>
                                    <div class="ad-name">{{ $u->name }}</div>
                                    @if($u->email)
                                        <div class="ad-secondary">{{ $u->email }}</div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        @if(auth()->user()->isSuperAdmin())<td data-label="@lang('users.school')"><span class="ad-secondary">{{ optional($u->school)->name_ar ?? '—' }}</span></td>@endif
                        <td data-label="@lang('users.username')">
                            <span class="ad-secondary">{{ $u->username ?? '—' }}</span>
                        </td>
                        <td data-label="@lang('users.job_title')">
                            @if($u->jobTitle)
                                <span class="ad-pill role">{{ $u->jobTitle->localized_name }}</span>
                            @else
                                <span class="ad-pill role-muted">—</span>
                            @endif
                            @if($isSuper)
                                <span class="ad-pill role-super">@lang('users.card_role_admin')</span>
                            @endif
                        </td>
                        <td data-label="@lang('users.status')">
                            @if($isCurrentlyActive)
                                <span class="ad-pill active"><span class="dot"></span>@lang('users.admin_status_active')</span>
                            @else
                                <span class="ad-pill inactive"><span class="dot"></span>@lang('users.admin_status_inactive')</span>
                            @endif
                        </td>
                        <td data-label="@lang('users.last_activity')">
                            <span class="ad-secondary">{{ $u->last_login_at ? $u->last_login_at->diffForHumans() : '—' }}</span>
                        </td>
                        <td data-label="@lang('users.actions')" class="actions-cell text-{{ $isRtl ? 'start' : 'end' }}">
                            <div class="ad-actions">
                                <a href="{{ route('admin.users.admins.edit', $u->id) }}"
                                   class="ad-action-btn edit"
                                   title="@lang('users.edit')">
                                    <x-svg-icon name="pencil-square" :size="16" class="ic-gold" />
                                </a>
                                <div class="dropdown">
                                    <button type="button"
                                            class="ad-action-btn more dropdown-toggle"
                                            data-toggle="dropdown" data-bs-toggle="dropdown"
                                            aria-expanded="false" title="@lang('users.admin_more_actions')">
                                        <x-svg-icon name="three-dots" :size="16" class="ic-muted" />
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-{{ $isRtl ? 'start' : 'end' }}">
                                        <a class="dropdown-item" href="{{ route('admin.users.admins.show', $u->id) }}">
                                            <x-svg-icon name="eye-fill" :size="16" class="ic-info" /> @lang('users.admin_view')
                                        </a>
                                        <a class="dropdown-item" href="{{ route('admin.users.admins.edit', $u->id) }}">
                                            <x-svg-icon name="pencil-square" :size="16" class="ic-gold" /> @lang('users.edit')
                                        </a>
                                        @if($u->jobTitle && in_array($u->jobTitle->slug, ['supervisor', 'counselor']))
                                            <a class="dropdown-item" href="{{ route('admin.users.admins.supervisees', $u->id) }}">
                                                <x-svg-icon name="people-fill" :size="16" class="ic-info" /> @lang('users.admin_supervisees')
                                            </a>
                                        @endif
                                        <a class="dropdown-item" href="{{ route('admin.users.job-titles.index') }}">
                                            <x-svg-icon name="gear-fill" :size="16" class="ic-muted" /> @lang('users.admin_manage_job_titles')
                                        </a>
                                        @if(auth()->user()->isSuperAdmin())
                                            <div class="dropdown-divider"></div>
                                            <form action="{{ route('admin.users.impersonate.start', $u->id) }}" method="POST">
                                                @csrf
                                                <button class="dropdown-item" type="submit">
                                                    <x-svg-icon name="box-arrow-in-right" :size="16" class="ic-warn" /> @lang('users.login_as')
                                                </button>
                                            </form>
                                        @endif
                                        <div class="dropdown-divider"></div>
                                        <button type="button" class="dropdown-item danger js-user-delete"
                                                data-url="{{ route('admin.users.admins.destroy', $u->id) }}"
                                                data-name="{{ $u->name }}">
                                            <x-svg-icon name="trash3-fill" :size="16" class="ic-danger" /> @lang('users.delete')
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ auth()->user()->isSuperAdmin() ? 7 : 6 }}">
                            <div class="ad-empty">
                                <x-svg-icon name="shield-lock-fill" :size="40" class="ic-muted" />
                                <div class="lbl">
                                    @if($q || request('job_title_id'))
                                        @lang('users.admin_no_filter_results')
                                    @else
                                        @lang('users.no_results')
                                    @endif
                                </div>
                                @if($q || request('job_title_id'))
                                    <div class="sub">
                                        <a href="{{ route('admin.users.admins.index') }}" style="color:var(--gold-500);">
                                            @lang('users.cancel')
                                        </a>
                                    </div>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if($admins->hasPages())
            <div class="ad-footer">
                <div class="ad-secondary">{{ $admins->total() }} @lang('users.admins')</div>
                <div>{{ $admins->withQueryString()->links() }}</div>
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
// Float row action dropdowns above the table using position:fixed (avoid clipping).
document.addEventListener('DOMContentLoaded', function () {
    if (!window.jQuery) { return; }
    jQuery(document)
        .on('shown.bs.dropdown', '.ad-table .dropdown', function () {
            var $toggle = jQuery(this).find('[data-toggle="dropdown"],[data-bs-toggle="dropdown"]').first();
            var $menu = jQuery(this).find('.dropdown-menu').first();
            if (!$toggle.length || !$menu.length) { return; }
            var r = $toggle[0].getBoundingClientRect();
            var mw = $menu.outerWidth();
            $menu.addClass('is-floating').css({
                top: (r.bottom + 4) + 'px',
                left: Math.max(8, r.right - mw) + 'px',
                right: 'auto'
            });
        })
        .on('hidden.bs.dropdown', '.ad-table .dropdown', function () {
            jQuery(this).find('.dropdown-menu').removeClass('is-floating').css({ top: '', left: '', right: '' });
        });
});
</script>
@endpush

@include('admin.users.partials.delete-modal')
@endsection
