@extends('layouts.app')

@section('title', __('users.parents'))
@section('body_class', 'theme-light')

@php
    $isRtl = app()->getLocale() === 'ar';
    $total = $parents->total();
    $withChildren = 0;
    $withoutChildren = 0;
    $activeCount = 0;
    foreach ($parents as $p) {
        $cnt = $p->children?->count() ?? 0;
        if ($cnt > 0) { $withChildren++; } else { $withoutChildren++; }
        if (($p->is_active ?? true) && ($p->status ?? 'active') === 'active') { $activeCount++; }
    }
@endphp

@push('styles')
<style>
    /* ===== Parents — light + gold accent ============================ */
    .pp-header { margin-bottom: 1.25rem; }
    .pp-header h2 {
        font-size: 1.5rem; font-weight: 700; color: #0f172a;
        margin-bottom: .15rem; letter-spacing: -.2px;
    }
    .pp-header .breadcrumb { padding: 0; margin: 0; background: transparent; font-size: .85rem; }
    .pp-header .breadcrumb-item + .breadcrumb-item::before { color: #cbd5e1; }

    /* KPI strip */
    .pp-kpis { display: grid; grid-template-columns: repeat(4, minmax(0,1fr)); gap: .75rem; margin-bottom: 1.25rem; }
    .pp-kpi {
        background: #fff; border: 1px solid #e5e7eb; border-radius: 14px;
        padding: .85rem 1rem; display: flex; align-items: center; gap: .75rem;
        box-shadow: 0 1px 2px rgba(15,23,42,.04), 0 4px 12px rgba(15,23,42,.04);
        transition: transform .2s ease, box-shadow .2s ease;
    }
    .pp-kpi:hover { transform: translateY(-2px); box-shadow: 0 4px 14px rgba(15,23,42,.06), 0 12px 28px rgba(15,23,42,.05); }
    .pp-kpi .ico {
        width: 38px; height: 38px; border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.1rem; flex-shrink: 0;
        background: linear-gradient(135deg, #fef3c7, #fde68a); color: var(--gold-500);
    }
    .pp-kpi .ico.ico-blue   { background: linear-gradient(135deg, #dbeafe, #bfdbfe); color: #1d4ed8; }
    .pp-kpi .ico.ico-amber  { background: linear-gradient(135deg, #fef3c7, #fde68a); color: #92400e; }
    .pp-kpi .ico.ico-green  { background: linear-gradient(135deg, #dcfce7, #bbf7d0); color: #15803d; }
    .pp-kpi .ico.ico-rose   { background: linear-gradient(135deg, #ffe4e6, #fecdd3); color: #be123c; }
    .pp-kpi .num   { font-size: 1.35rem; font-weight: 800; color: var(--gold-400); line-height: 1.1; letter-spacing: -.5px; }
    .pp-kpi .num.muted { color: #0f172a; }
    .pp-kpi .lbl   { font-size: .8rem; color: #64748b; }

    /* Surface */
    .pp-surface .card-header {
        background: #fff; border-bottom: 1px solid #f1f5f9;
        display: flex; align-items: center; justify-content: space-between;
        padding: 1rem 1.1rem; gap: .75rem; flex-wrap: wrap;
    }
    .pp-surface .card-header h5 {
        margin: 0; font-size: 1rem; font-weight: 700; color: #0f172a;
        display: inline-flex; align-items: center; gap: .55rem;
    }
    .pp-surface .card-header h5 i { color: var(--gold-400); }
    .pp-surface .card-header .count-pill {
        background: #f8fafc; border: 1px solid #e5e7eb;
        color: #475569; font-size: .78rem; font-weight: 600;
        padding: .2rem .55rem; border-radius: 999px;
    }

    /* Search */
    .pp-search { display: flex; align-items: center; gap: .35rem; flex: 1 1 auto; max-width: 360px; }
    .pp-search input[type="search"] {
        background: #fff; border: 1px solid #e2e8f0; border-radius: 10px;
        padding: .45rem .7rem; font-size: .88rem;
        transition: border-color .15s ease, box-shadow .15s ease;
    }
    .pp-search input[type="search"]:focus {
        border-color: var(--gold-300); box-shadow: 0 0 0 .2rem rgba(207,160,70,.16); outline: none;
    }
    .pp-search .btn-search {
        background: #fff; border: 1px solid #e2e8f0; color: #475569;
        border-radius: 10px; padding: .45rem .7rem;
        transition: all .15s ease;
    }
    .pp-search .btn-search:hover { border-color: var(--gold-300); color: var(--gold-500); }

    /* Add CTA */
    .pp-add-wrap { position: relative; }
    .btn-gold {
        background: linear-gradient(135deg, var(--gold-300), var(--gold-500));
        border: 1px solid var(--gold-400); color: #fff;
        font-weight: 600; padding: .5rem 1rem; border-radius: 10px;
        box-shadow: 0 1px 2px rgba(207,160,70,.18);
        transition: transform .15s ease, box-shadow .2s ease, background .2s ease;
        display: inline-flex; align-items: center; gap: .45rem;
    }
    .btn-gold:hover {
        background: linear-gradient(135deg, var(--gold-400), var(--gold-500));
        color: #fff; transform: translateY(-1px);
        box-shadow: 0 6px 16px rgba(207,160,70,.22);
    }
    .btn-gold .caret { font-size: .7rem; opacity: .9; }
    .pp-add-menu {
        position: absolute; top: calc(100% + .35rem);
        {{ $isRtl ? 'right:0;' : 'left:0;' }}
        min-width: 220px; background: #fff;
        border: 1px solid #e5e7eb; border-radius: 12px;
        box-shadow: 0 8px 24px rgba(15,23,42,.08), 0 2px 6px rgba(15,23,42,.05);
        padding: .35rem; z-index: 50; display: none;
    }
    .pp-add-menu.is-open { display: block; }
    .pp-add-menu a, .pp-add-menu button {
        display: flex; align-items: center; gap: .6rem;
        padding: .55rem .65rem; border-radius: 8px;
        font-size: .88rem; color: #0f172a; text-decoration: none;
        width: 100%; background: transparent; border: 0; text-align: {{ $isRtl ? 'right' : 'left' }};
        transition: background .15s ease;
    }
    .pp-add-menu a:hover, .pp-add-menu button:hover { background: #fef3c7; color: var(--gold-500); }
    .pp-add-menu .disabled {
        color: #94a3b8; cursor: not-allowed;
    }
    .pp-add-menu .disabled:hover { background: transparent; color: #94a3b8; }
    .pp-add-menu i { width: 18px; text-align: center; color: var(--gold-500); }
    .pp-add-menu .disabled i { color: #cbd5e1; }
    .pp-add-menu small { color: #94a3b8; font-size: .7rem; display: block; margin-top: .1rem; }

    /* Table */
    .pp-table { margin: 0; }
    .pp-table thead th {
        background: #f8fafc !important; color: #475569 !important;
        font-weight: 600; font-size: .78rem; text-transform: uppercase; letter-spacing: .5px;
        border-bottom: 1px solid #e5e7eb; padding: .8rem 1rem; white-space: nowrap;
    }
    .pp-table tbody td { padding: .85rem 1rem; vertical-align: middle; color: #0f172a; }
    .pp-table tbody tr { transition: background .15s ease; }
    .pp-table tbody tr:hover { background: #fafbfc; }
    .pp-table tbody tr + tr td { border-top: 1px solid #f1f5f9; }
    .pp-name-primary { font-weight: 600; color: #0f172a; }
    .pp-name-secondary { color: #64748b; font-size: .8rem; }

    .pp-avatar {
        width: 36px; height: 36px; border-radius: 50%;
        background: linear-gradient(135deg, #fde68a, #fcd34d);
        color: #92400e; font-weight: 700; font-size: .82rem;
        display: inline-flex; align-items: center; justify-content: center;
        flex-shrink: 0; border: 1px solid #fde68a;
    }
    .pp-name-cell { display: flex; align-items: center; gap: .65rem; }

    .pp-pill { display: inline-flex; align-items: center; gap: .3rem;
        padding: .2rem .55rem; border-radius: 999px; font-size: .72rem; font-weight: 600;
        line-height: 1.3; border: 1px solid transparent; }
    .pp-pill .dot { width: 6px; height: 6px; border-radius: 50%; display: inline-block; }
    .pp-pill.linked   { background: #ecfdf5; color: #047857; border-color: #a7f3d0; }
    .pp-pill.linked .dot { background: #10b981; }
    .pp-pill.empty    { background: #fef2f2; color: #b91c1c; border-color: #fecaca; }
    .pp-pill.empty .dot { background: #ef4444; }
    .pp-pill.gold     { background: #fffbeb; color: #92400e; border-color: #fde68a; }
    .pp-pill.muted    { background: #f3f4f6; color: #6b7280; border-color: #e5e7eb; }

    .pp-id-chip {
        background: #f1f5f9; color: #475569; font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
        font-size: .78rem; padding: .15rem .5rem; border-radius: 6px; border: 1px solid #e2e8f0;
    }

    /* Action buttons */
    .pp-actions { display: inline-flex; align-items: center; gap: .35rem; }
    .pp-icon-btn {
        width: 32px; height: 32px; padding: 0;
        display: inline-flex; align-items: center; justify-content: center;
        border-radius: 8px; border: 1px solid #e2e8f0; background: #fff;
        color: #475569; transition: all .15s ease; cursor: pointer;
    }
    .pp-icon-btn:hover { border-color: var(--gold-300); color: var(--gold-500); transform: translateY(-1px); }
    .pp-icon-btn.is-danger { border-color: #fecaca; background: #fff5f5; color: #b91c1c; }
    .pp-icon-btn.is-danger:hover { background: #fee2e2; border-color: #fca5a5; color: #991b1b; }
    .pp-icon-btn.is-primary { border-color: #bfdbfe; background: #eff6ff; color: #1d4ed8; }
    .pp-icon-btn.is-primary:hover { background: #dbeafe; border-color: #93c5fd; color: #1e40af; }

    /* Row dropdown — uses overflow:visible trick on table-responsive to escape clipping */
    .table-responsive.menu-open { overflow: visible !important; }
    .pp-row-menu { position: relative; }
    .pp-row-menu .menu {
        position: absolute; top: calc(100% + .35rem);
        {{ $isRtl ? 'left:0;' : 'right:0;' }}
        min-width: 220px; background: #fff;
        border: 1px solid #e5e7eb; border-radius: 12px;
        box-shadow: 0 8px 24px rgba(15,23,42,.08), 0 2px 6px rgba(15,23,42,.05);
        padding: .35rem; z-index: 1050; display: none;
    }
    .pp-row-menu.is-open .menu { display: block; }
    .pp-row-menu .menu a, .pp-row-menu .menu button {
        display: flex; align-items: center; gap: .55rem;
        padding: .5rem .6rem; border-radius: 8px;
        font-size: .85rem; color: #0f172a; text-decoration: none;
        width: 100%; background: transparent; border: 0; text-align: {{ $isRtl ? 'right' : 'left' }};
        transition: background .15s ease;
    }
    .pp-row-menu .menu a:hover, .pp-row-menu .menu button:hover { background: #fef3c7; color: var(--gold-500); }
    .pp-row-menu .menu .disabled { color: #94a3b8; cursor: not-allowed; }
    .pp-row-menu .menu .disabled:hover { background: transparent; color: #94a3b8; }
    .pp-row-menu .menu i { width: 16px; text-align: center; color: var(--gold-500); }
    .pp-row-menu .menu .disabled i { color: #cbd5e1; }

    /* Empty state */
    .pp-empty { padding: 2.75rem 1rem; text-align: center; color: #94a3b8; }
    .pp-empty i { font-size: 2.5rem; opacity: .55; display: block; margin-bottom: .35rem; color: #cbd5e1; }
    .pp-empty .lbl { font-size: .95rem; color: #64748b; }

    /* Alerts */
    .pp-alert {
        background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46;
        border-radius: 10px; padding: .65rem .85rem; display: flex; align-items: center;
        gap: .55rem; font-size: .9rem; margin-bottom: 1rem;
    }
    .pp-alert i { color: #10b981; font-size: 1.1rem; }
    .pp-alert.err { background: #fef2f2; border-color: #fecaca; color: #991b1b; }
    .pp-alert.err i { color: #ef4444; }

    .pp-children-chip {
        display: inline-flex; align-items: center; gap: .25rem;
        background: #fffbeb; color: #92400e; border: 1px solid #fde68a;
        font-weight: 600; font-size: .8rem; padding: .15rem .55rem; border-radius: 999px;
    }
    .pp-children-chip.is-zero { background: #f8fafc; color: #64748b; border-color: #e5e7eb; }
    .pp-children-chip i { font-size: .85rem; opacity: .9; }

    /* Responsive */
    @media (max-width: 991.98px) {
        .pp-kpis { grid-template-columns: repeat(2, minmax(0,1fr)); }
    }
    @media (max-width: 575.98px) {
        .pp-kpis { grid-template-columns: 1fr 1fr; gap: .55rem; }
        .pp-kpi { padding: .7rem .8rem; }
        .pp-kpi .ico { width: 32px; height: 32px; font-size: .95rem; }
        .pp-kpi .num { font-size: 1.15rem; }
        .pp-kpi .lbl { font-size: .72rem; }
        .pp-surface .card-header { padding: .8rem 1rem; }
        .pp-search { max-width: 100%; }
        .pp-table thead { display: none; }
        .pp-table, .pp-table tbody, .pp-table tr, .pp-table td { display: block; width: 100%; }
        .pp-table tbody tr {
            border: 1px solid #f1f5f9; border-radius: 12px;
            margin-bottom: .65rem; padding: .65rem .8rem; background: #fff;
        }
        .pp-table tbody tr + tr td { border-top: 0; }
        .pp-table tbody td {
            padding: .4rem 0; border: 0; display: flex; align-items: center;
            justify-content: space-between; gap: .75rem; font-size: .9rem;
        }
        .pp-table tbody td::before {
            content: attr(data-label);
            font-size: .72rem; color: #64748b; font-weight: 600;
            text-transform: uppercase; letter-spacing: .5px;
        }
        .pp-table tbody td.actions-cell { justify-content: flex-end; }
        .pp-table tbody td.actions-cell::before { display: none; }
        .pp-name-cell { justify-content: flex-end; }
    }
</style>
@endpush

@section('content')
<div class="content-header pp-header">
    <h2>@lang('users.parents')</h2>
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
        <li class="breadcrumb-item active">@lang('users.parents')</li>
    </ol>
</div>

<div class="content-body">
    @if(session('status'))
        <div class="pp-alert"><x-svg-icon name="check-circle-fill" :size="18" class="ic-success" /><span>{{ session('status') }}</span></div>
    @endif
    @if(session('error'))
        <div class="pp-alert err"><x-svg-icon name="exclamation-triangle-fill" :size="18" class="ic-warn" /><span>{{ session('error') }}</span></div>
    @endif
    @if($errors->any())
        <div class="pp-alert err"><x-svg-icon name="exclamation-triangle-fill" :size="18" class="ic-warn" /><span>{{ $errors->first() }}</span></div>
    @endif

    {{-- KPI strip --}}
    <div class="pp-kpis">
        <div class="pp-kpi">
            <div class="ico"><x-svg-icon name="people-fill" :size="20" class="ic-info" /></div>
            <div>
                <div class="num">{{ $total }}</div>
                <div class="lbl">@lang('users.parent_total')</div>
            </div>
        </div>
        <div class="pp-kpi">
            <div class="ico ico-green"><x-svg-icon name="link-45deg" :size="20" class="ic-success" /></div>
            <div>
                <div class="num muted">{{ $withChildren }}</div>
                <div class="lbl">@lang('users.parent_with_children')</div>
            </div>
        </div>
        <div class="pp-kpi">
            <div class="ico ico-rose"><x-svg-icon name="person-x-fill" :size="20" class="ic-danger" /></div>
            <div>
                <div class="num muted">{{ $withoutChildren }}</div>
                <div class="lbl">@lang('users.parent_without_children')</div>
            </div>
        </div>
        <div class="pp-kpi">
            <div class="ico ico-blue"><x-svg-icon name="check-circle-fill" :size="20" class="ic-success" /></div>
            <div>
                <div class="num muted">{{ $activeCount }}</div>
                <div class="lbl">@lang('users.parent_active')</div>
            </div>
        </div>
    </div>

    <div class="pp-surface">
        <div class="card">
            <div class="card-header">
                <div class="d-flex align-items-center gap-2">
                    <div class="pp-add-wrap">
                        <button type="button" class="btn-gold" id="pp-add-btn">
                            <x-svg-icon name="plus-lg" :size="16" /> @lang('users.add')
                            <span class="caret">▾</span>
                        </button>
                        <div class="pp-add-menu" id="pp-add-menu">
                            <a href="{{ route('admin.users.parents.create') }}">
                                <x-svg-icon name="person-plus-fill" :size="16" class="ic-gold" />
                                <span>@lang('users.add_parent')</span>
                            </a>
                            <a href="{{ route('admin.users.parents.import') }}">
                                <x-svg-icon name="file-earmark-excel-fill" :size="16" class="ic-success" />
                                <span>@lang('users.import_excel')</span>
                            </a>
                            <a href="#" class="js-excel-action" data-target="form-edit-excel">
                                <x-svg-icon name="upload" :size="16" class="ic-info" />
                                <span>@lang('users.edit_excel')</span>
                            </a>
                            <a href="#" class="js-excel-action" data-target="form-link-numbers">
                                <x-svg-icon name="link-45deg" :size="16" class="ic-info" />
                                <span>@lang('users.update_by_student_numbers')</span>
                            </a>
                        </div>
                    </div>
                    <span class="count-pill">{{ $total }}</span>
                </div>
                <form action="{{ route('admin.users.parents.index') }}" method="GET" class="pp-search">
                    <input type="search" name="q" value="{{ $q }}" class="form-control" placeholder="@lang('users.search_placeholder')" />
                    <button type="submit" class="btn-search" title="@lang('users.search')"><x-svg-icon name="search" :size="16" class="ic-muted" /></button>
                </form>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table pp-table">
                        <thead>
                            <tr>
                                <th>@lang('users.name')</th>
                                <th>@lang('users.national_id')</th>
                                <th>@lang('users.phone')</th>
                                <th class="text-center">@lang('users.children_count')</th>
                                <th>@lang('users.gender')</th>
                                <th>@lang('users.last_activity')</th>
                                <th class="text-{{ $isRtl ? 'start' : 'end' }}">@lang('users.actions')</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse($parents as $u)
                            @php
                                $initials = collect(preg_split('/\s+/u', trim($u->name)))
                                    ->filter()->take(2)->map(fn($p) => mb_substr($p, 0, 1))->implode('');
                                $childrenCount = $u->children?->count() ?? 0;
                            @endphp
                            <tr>
                                <td data-label="@lang('users.name')">
                                    <div class="pp-name-cell">
                                        <span class="pp-avatar">{{ $initials ?: '?' }}</span>
                                        <div>
                                            <div class="pp-name-primary">{{ $u->name }}</div>
                                            <div class="pp-name-secondary">{{ $u->username }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td data-label="@lang('users.national_id')">
                                    @if($u->national_id)
                                        <span class="pp-id-chip">{{ $u->national_id }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td data-label="@lang('users.phone')">
                                    {{ $u->phone ?? '—' }}
                                </td>
                                <td data-label="@lang('users.children_count')" class="text-center">
                                    <a href="{{ route('admin.users.parents.students', $u->id) }}" class="text-decoration-none">
                                        <span class="pp-children-chip {{ $childrenCount === 0 ? 'is-zero' : '' }}">
                                            <x-svg-icon name="mortarboard-fill" :size="14" class="ic-info" />{{ $childrenCount }}
                                        </span>
                                    </a>
                                </td>
                                <td data-label="@lang('users.gender')">
                                    @if($u->gender)
                                        <span class="pp-pill {{ $u->gender === 'male' ? 'muted' : 'gold' }}">
                                            @lang('users.gender_'.$u->gender)
                                        </span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td data-label="@lang('users.last_activity')">
                                    {{ $u->last_login_at ? $u->last_login_at->diffForHumans() : '—' }}
                                </td>
                                <td data-label="@lang('users.actions')" class="actions-cell text-{{ $isRtl ? 'start' : 'end' }}">
                                    <div class="pp-actions">
                                        <a href="{{ route('admin.users.parents.show', $u->id) }}" class="pp-icon-btn" title="@lang('users.view')">
                                            <x-svg-icon name="eye-fill" :size="16" class="ic-info" />
                                        </a>
                                        <a href="{{ route('admin.users.parents.edit', $u->id) }}" class="pp-icon-btn is-primary" title="@lang('users.edit')">
                                            <x-svg-icon name="pencil-square" :size="16" />
                                        </a>
                                        <form action="{{ route('admin.users.parents.destroy', $u->id) }}" method="POST" class="d-inline" onsubmit="return confirm('@lang('users.delete')?');">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="pp-icon-btn is-danger" title="@lang('users.delete')"><x-svg-icon name="trash3-fill" :size="16" /></button>
                                        </form>
                                        <div class="pp-row-menu js-row-menu">
                                            <button type="button" class="pp-icon-btn js-row-menu-btn" title="@lang('users.parent_more_actions')">
                                                <x-svg-icon name="three-dots-vertical" :size="16" class="ic-muted" />
                                            </button>
                                            <div class="menu">
                                                <a href="{{ route('admin.users.parents.students', $u->id) }}">
                                                    <x-svg-icon name="mortarboard-fill" :size="16" class="ic-info" />
                                                    <span>@lang('users.students_link')</span>
                                                </a>
                                                <a href="#" class="disabled" title="@lang('users.permissions_link')">
                                                    <x-svg-icon name="key-fill" :size="16" class="ic-warn" />
                                                    <span>@lang('users.permissions_link')</span>
                                                </a>
                                                @if(auth()->user()->isSuperAdmin())
                                                <form action="{{ route('admin.users.impersonate.start', $u->id) }}" method="POST" class="m-0">
                                                    @csrf
                                                    <button type="submit"><x-svg-icon name="incognito" :size="16" class="ic-muted" /><span>@lang('users.login_as')</span></button>
                                                </form>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7">
                                    <div class="pp-empty">
                                        <x-svg-icon name="people-fill" :size="40" class="ic-muted" />
                                        <div class="lbl">@lang('users.no_results')</div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($parents->hasPages())
            <div class="card-footer bg-white">
                {{ $parents->links() }}
            </div>
            @endif
        </div>
    </div>

    {{-- Hidden Excel tool forms (triggered from the Add menu) --}}
    <form id="form-edit-excel" action="{{ route('admin.users.parents.import.update') }}" method="POST" enctype="multipart/form-data" class="d-none">
        @csrf
        <input type="file" name="file" class="js-excel-file" accept=".csv,.xlsx,.xls,.txt" />
    </form>
    <form id="form-link-numbers" action="{{ route('admin.users.parents.link.numbers') }}" method="POST" enctype="multipart/form-data" class="d-none">
        @csrf
        <input type="file" name="file" class="js-excel-file" accept=".csv,.xlsx,.xls,.txt" />
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var addBtn = document.getElementById('pp-add-btn');
    var addMenu = document.getElementById('pp-add-menu');

    // Excel actions that need a file: clicking the menu item opens the picker,
    // and selecting a file auto-submits the matching hidden form.
    document.querySelectorAll('.js-excel-action').forEach(function (link) {
        link.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            var form = document.getElementById(link.dataset.target);
            if (form) form.querySelector('.js-excel-file').click();
            if (addMenu) addMenu.classList.remove('is-open');
        });
    });
    document.querySelectorAll('.js-excel-file').forEach(function (input) {
        input.addEventListener('change', function () {
            if (input.files && input.files.length) input.closest('form').submit();
        });
    });
    if (addBtn && addMenu) {
        addBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            addMenu.classList.toggle('is-open');
            document.querySelectorAll('.js-row-menu.is-open').forEach(function (m) { m.classList.remove('is-open'); });
        });
    }
    // The row menu lives inside .table-responsive (overflow:auto) which clips it.
    // Fix: temporarily set overflow:visible on .table-responsive while a menu is open.
    // The menu itself uses position:absolute with a high z-index (defined in CSS).
    function closeAllRowMenus() {
        document.querySelectorAll('.js-row-menu.is-open').forEach(function (m) {
            m.classList.remove('is-open');
        });
        // Restore overflow on all table-responsive wrappers
        document.querySelectorAll('.table-responsive.menu-open').forEach(function (w) {
            w.classList.remove('menu-open');
        });
    }

    document.querySelectorAll('.js-row-menu-btn').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            var wrap = btn.closest('.js-row-menu');
            var wasOpen = wrap.classList.contains('is-open');
            closeAllRowMenus();
            if (addMenu) addMenu.classList.remove('is-open');
            if (!wasOpen) {
                wrap.classList.add('is-open');
                // Allow the absolute dropdown to escape the overflow:auto clip
                var tableWrap = btn.closest('.table-responsive');
                if (tableWrap) tableWrap.classList.add('menu-open');
            }
        });
    });
    window.addEventListener('resize', closeAllRowMenus);
    window.addEventListener('scroll', closeAllRowMenus, true);
    document.addEventListener('click', function () {
        if (addMenu) addMenu.classList.remove('is-open');
        closeAllRowMenus();
    });
});
</script>
@endsection
