@extends('layouts.app')

@section('title', __('question_banks.page_title'))
@section('body_class', 'theme-light')

@php
    $isRtl = app()->getLocale() === 'ar';
    $total = $stats['total'] ?? 0;
    $publicCount = $stats['public'] ?? 0;
    $privateCount = $stats['private'] ?? 0;
    $activeCount = $stats['active'] ?? 0;

    $hasAnyFilter = collect($filters)->filter(fn($v) => $v !== null && $v !== '')->isNotEmpty();
@endphp

@push('styles')
<style>
    /* ===== Question Banks index — light + gold accent (card 62) ============= */
    .qb-header { margin-bottom: 1.25rem; }
    .qb-header h2 {
        font-size: 1.5rem; font-weight: 700; color: #0f172a;
        margin-bottom: .15rem; letter-spacing: -.2px;
    }
    .qb-header .breadcrumb { padding: 0; margin: 0; background: transparent; font-size: .85rem; }
    .qb-header .breadcrumb-item + .breadcrumb-item::before { color: #cbd5e1; }

    .qb-kpis { display: grid; grid-template-columns: repeat(4, minmax(0,1fr)); gap: .75rem; margin-bottom: 1.25rem; }
    .qb-kpi {
        background: #fff; border: 1px solid #e5e7eb; border-radius: 14px;
        padding: .85rem 1rem; display: flex; align-items: center; gap: .75rem;
        box-shadow: 0 1px 2px rgba(15,23,42,.04), 0 4px 12px rgba(15,23,42,.04);
        transition: transform .2s ease, box-shadow .2s ease;
    }
    .qb-kpi:hover { transform: translateY(-2px); box-shadow: 0 4px 14px rgba(15,23,42,.06), 0 12px 28px rgba(15,23,42,.05); }
    .qb-kpi .ico {
        width: 38px; height: 38px; border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.1rem; flex-shrink: 0;
        background: linear-gradient(135deg, #fef3c7, #fde68a); color: var(--gold-500);
    }
    .qb-kpi .ico.ico-blue   { background: linear-gradient(135deg, #dbeafe, #bfdbfe); color: #1d4ed8; }
    .qb-kpi .ico.ico-violet { background: linear-gradient(135deg, #ede9fe, #ddd6fe); color: #6d28d9; }
    .qb-kpi .ico.ico-green  { background: linear-gradient(135deg, #dcfce7, #bbf7d0); color: #15803d; }
    .qb-kpi .num   { font-size: 1.35rem; font-weight: 800; color: var(--gold-400); line-height: 1.1; letter-spacing: -.5px; }
    .qb-kpi .num.muted { color: #0f172a; }
    .qb-kpi .lbl   { font-size: .8rem; color: #64748b; }

    .qb-filter-card {
        background: #fff; border: 1px solid #e5e7eb; border-radius: 14px;
        padding: 1rem 1.1rem; margin-bottom: 1rem;
        box-shadow: 0 1px 2px rgba(15,23,42,.04), 0 4px 12px rgba(15,23,42,.04);
    }
    .qb-filter-card .se-title {
        display: flex; align-items: center; gap: .55rem;
        font-size: .92rem; font-weight: 700; color: #0f172a; margin-bottom: .75rem;
    }
    .qb-filter-card .se-title i { color: var(--gold-400); font-size: 1.1rem; }
    .qb-filter-row { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: .55rem; }
    .qb-filter-row .form-control, .qb-filter-row select.form-control {
        background: #fff; border: 1px solid #e2e8f0; border-radius: 10px;
        padding: .55rem .85rem; font-size: .9rem; color: #0f172a;
    }
    .qb-filter-row .form-control:focus, .qb-filter-row select.form-control:focus {
        border-color: var(--gold-300);
        box-shadow: 0 0 0 .2rem rgba(207,160,70,.16); outline: none;
    }
    .qb-filter-actions { display: flex; gap: .45rem; margin-top: .75rem; }

    .qb-toolbar {
        background: #fff; border: 1px solid #e5e7eb; border-radius: 14px 14px 0 0;
        padding: .9rem 1.1rem; display: flex; flex-wrap: wrap; gap: .55rem;
        align-items: center; justify-content: space-between;
        border-bottom: 0;
    }
    .qb-toolbar .left { display: flex; flex-wrap: wrap; gap: .45rem; align-items: center; }
    .qb-toolbar .count-pill {
        background: #f8fafc; border: 1px solid #e5e7eb;
        color: #475569; font-size: .78rem; font-weight: 600;
        padding: .25rem .65rem; border-radius: 999px;
    }

    .btn-gold {
        background: linear-gradient(135deg, var(--gold-300), var(--gold-500));
        border: 1px solid var(--gold-400); color: #fff;
        font-weight: 600; padding: .55rem 1.1rem; border-radius: 10px;
        box-shadow: 0 1px 2px rgba(207,160,70,.18);
        transition: transform .15s ease, box-shadow .2s ease, background .2s ease;
        display: inline-flex; align-items: center; gap: .45rem;
    }
    .btn-gold:hover { background: linear-gradient(135deg, var(--gold-400), var(--gold-500)); color: #fff; transform: translateY(-1px); box-shadow: 0 6px 16px rgba(207,160,70,.22); }
    .btn-gold:active { transform: translateY(0); }

    .btn-ghost {
        background: #fff; border: 1px solid #e2e8f0; color: #334155;
        font-weight: 600; padding: .55rem 1rem; border-radius: 10px;
        display: inline-flex; align-items: center; gap: .35rem; transition: all .15s ease;
    }
    .btn-ghost:hover { background: #f8fafc; color: #0f172a; border-color: #cbd5e1; }
    .btn-ghost i { color: var(--gold-400); }

    .btn-reset {
        background: #fff; border: 1px solid #e2e8f0; color: #475569;
        font-weight: 600; padding: .55rem 1rem; border-radius: 10px;
        display: inline-flex; align-items: center; gap: .35rem; transition: all .15s ease;
    }
    .btn-reset:hover { background: #f8fafc; color: #0f172a; }

    .qb-surface {
        background: #fff; border: 1px solid #e5e7eb; border-top: 0;
        border-radius: 0 0 14px 14px; overflow: hidden;
    }

    .qb-table { margin: 0; }
    .qb-table thead th {
        background: #f8fafc !important; color: #475569 !important;
        font-weight: 600; font-size: .76rem; text-transform: uppercase; letter-spacing: .5px;
        border-bottom: 1px solid #e5e7eb; padding: .8rem 1rem; white-space: nowrap;
    }
    .qb-table tbody td { padding: .85rem 1rem; vertical-align: middle; color: #0f172a; }
    .qb-table tbody tr { transition: background .15s ease; }
    .qb-table tbody tr:hover { background: #fafbfc; }
    .qb-table tbody tr + tr td { border-top: 1px solid #f1f5f9; }

    .qb-name { font-weight: 600; color: #0f172a; line-height: 1.25; }
    .qb-name-en { color: #64748b; font-size: .8rem; }
    .qb-secondary { color: #64748b; font-size: .82rem; }

    .qb-pill {
        display: inline-flex; align-items: center; gap: .3rem;
        padding: .2rem .55rem; border-radius: 999px; font-size: .72rem; font-weight: 600;
        line-height: 1.3; border: 1px solid transparent;
    }
    .qb-pill .dot { width: 6px; height: 6px; border-radius: 50%; display: inline-block; }
    .qb-pill.vis-public  { background: #eff6ff; color: #1d4ed8; border-color: #bfdbfe; }
    .qb-pill.vis-public .dot  { background: #1d4ed8; }
    .qb-pill.vis-private { background: #fffbeb; color: #92400e; border-color: #fde68a; }
    .qb-pill.vis-private .dot { background: #d97706; }

    .qb-pill.status-active   { background: #ecfdf5; color: #047857; border-color: #a7f3d0; }
    .qb-pill.status-active .dot   { background: #10b981; }
    .qb-pill.status-inactive { background: #f3f4f6; color: #6b7280; border-color: #e5e7eb; }
    .qb-pill.status-inactive .dot { background: #9ca3af; }
    .qb-pill.status-under_review { background: #fef3c7; color: #92400e; border-color: #fde68a; }
    .qb-pill.status-under_review .dot { background: #f59e0b; }
    .qb-pill.status-archived { background: #f1f5f9; color: #475569; border-color: #cbd5e1; }
    .qb-pill.status-archived .dot { background: #64748b; }

    .qb-pill.source { background: #f8fafc; color: #475569; border-color: #e2e8f0; }
    .qb-pill.subject { background: #fffbeb; color: #92400e; border-color: #fde68a; }

    .qb-actions { display: inline-flex; align-items: center; gap: .35rem; }
    .qb-action-btn {
        width: 32px; height: 32px; padding: 0;
        display: inline-flex; align-items: center; justify-content: center;
        border-radius: 8px; border: 1px solid #e2e8f0; background: #fff;
        color: #475569; transition: all .15s ease;
    }
    .qb-action-btn:hover { transform: translateY(-1px); }
    .qb-action-btn.view:hover { background: #eff6ff; border-color: #bfdbfe; color: #1d4ed8; }
    .qb-action-btn.edit:hover { background: #fffbeb; border-color: #fde68a; color: #92400e; }
    .qb-action-btn.del { background: #fff5f5; border-color: #fecaca; color: #b91c1c; }
    .qb-action-btn.del:hover { background: #fee2e2; border-color: #fca5a5; }
    .qb-action-btn.more { background: #fff; border-color: #e2e8f0; color: #64748b; }
    .qb-action-btn.more:hover { background: #f8fafc; color: #0f172a; }
    .qb-actions form { display: inline; margin: 0; }

    .qb-empty { padding: 2.75rem 1rem; text-align: center; color: #94a3b8; }
    .qb-empty i { font-size: 2.5rem; opacity: .55; display: block; margin-bottom: .5rem; color: #cbd5e1; }
    .qb-empty .lbl { font-size: .95rem; color: #64748b; }
    .qb-empty .sub { font-size: .8rem; color: #94a3b8; margin-top: .25rem; }

    .qb-alert {
        background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46;
        border-radius: 10px; padding: .65rem .85rem; display: flex; align-items: center;
        gap: .55rem; font-size: .9rem; margin-bottom: 1rem;
    }
    .qb-alert i { color: #10b981; font-size: 1.1rem; }

    .qb-footer { padding: .85rem 1rem; background: #fff; border-top: 1px solid #f1f5f9;
        display: flex; justify-content: space-between; align-items: center; }
    .qb-footer .pagination { margin: 0; }

    @media (max-width: 1199.98px) {
        .qb-filter-row { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    }
    @media (max-width: 991.98px) {
        .qb-kpis { grid-template-columns: repeat(2, minmax(0,1fr)); }
        .qb-filter-row { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
    @media (max-width: 575.98px) {
        .qb-kpis { gap: .55rem; }
        .qb-kpi { padding: .7rem .8rem; }
        .qb-kpi .ico { width: 32px; height: 32px; font-size: .95rem; }
        .qb-kpi .num { font-size: 1.15rem; }
        .qb-kpi .lbl { font-size: .72rem; }
        .qb-filter-row { grid-template-columns: 1fr; }
        .qb-toolbar { padding: .75rem .85rem; gap: .4rem; }
        .qb-table thead { display: none; }
        .qb-table, .qb-table tbody, .qb-table tr, .qb-table td { display: block; width: 100%; }
        .qb-table tbody tr {
            border: 1px solid #f1f5f9; border-radius: 12px;
            margin: .5rem .65rem; padding: .65rem .8rem; background: #fff;
        }
        .qb-table tbody tr + tr td { border-top: 0; }
        .qb-table tbody td {
            padding: .35rem 0; border: 0; display: flex; align-items: center;
            justify-content: space-between; gap: .75rem; font-size: .88rem;
        }
        .qb-table tbody td::before {
            content: attr(data-label);
            font-size: .68rem; color: #64748b; font-weight: 700;
            text-transform: uppercase; letter-spacing: .5px;
        }
        .qb-table tbody td.actions-cell { justify-content: flex-end; }
        .qb-table tbody td.actions-cell::before { display: none; }
    }
</style>
@endpush

@section('content')
<div class="content-header qb-header">
    <h2>@lang('question_banks.page_title')</h2>
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('question_banks.breadcrumb_home')</a></li>
        <li class="breadcrumb-item">@lang('question_banks.breadcrumb_subjects')</li>
        <li class="breadcrumb-item active">@lang('question_banks.page_title')</li>
    </ol>
</div>

<div class="content-body">
    @if(session('success'))
        <div class="qb-alert"><i class="la la-check-circle"></i><span>{{ session('success') }}</span></div>
    @endif

    {{-- KPI strip --}}
    <div class="qb-kpis">
        <div class="qb-kpi">
            <div class="ico"><i class="la la-database"></i></div>
            <div>
                <div class="num">{{ $total }}</div>
                <div class="lbl">@lang('question_banks.kpi_total')</div>
            </div>
        </div>
        <div class="qb-kpi">
            <div class="ico ico-blue"><i class="la la-globe"></i></div>
            <div>
                <div class="num muted">{{ $publicCount }}</div>
                <div class="lbl">@lang('question_banks.kpi_public')</div>
            </div>
        </div>
        <div class="qb-kpi">
            <div class="ico ico-violet"><i class="la la-lock"></i></div>
            <div>
                <div class="num muted">{{ $privateCount }}</div>
                <div class="lbl">@lang('question_banks.kpi_private')</div>
            </div>
        </div>
        <div class="qb-kpi">
            <div class="ico ico-green"><i class="la la-check-circle"></i></div>
            <div>
                <div class="num muted">{{ $activeCount }}</div>
                <div class="lbl">@lang('question_banks.kpi_active')</div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <form action="{{ route('admin.question-banks.index') }}" method="GET" class="qb-filter-card">
        <div class="se-title">
            <i class="la la-filter"></i>
            <span>@lang('question_banks.search_engine')</span>
        </div>
        <div class="qb-filter-row">
            <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control" placeholder="@lang('question_banks.search_hint')">

            <select name="visibility" class="form-control" onchange="this.form.submit()">
                <option value="">@lang('question_banks.filter_visibility') — @lang('question_banks.filter_all')</option>
                @foreach($visibilities as $k => $label)
                    <option value="{{ $k }}" @selected(($filters['visibility'] ?? '') === $k)>{{ $label }}</option>
                @endforeach
            </select>

            <select name="status" class="form-control" onchange="this.form.submit()">
                <option value="">@lang('question_banks.filter_status') — @lang('question_banks.filter_all')</option>
                @foreach($statuses as $k => $label)
                    <option value="{{ $k }}" @selected(($filters['status'] ?? '') === $k)>{{ $label }}</option>
                @endforeach
            </select>

            <select name="source" class="form-control" onchange="this.form.submit()">
                <option value="">@lang('question_banks.filter_source') — @lang('question_banks.filter_all')</option>
                @foreach($sources as $k => $label)
                    <option value="{{ $k }}" @selected(($filters['source'] ?? '') === $k)>{{ $label }}</option>
                @endforeach
            </select>

            <select name="subject_id" class="form-control" onchange="this.form.submit()">
                <option value="">@lang('question_banks.filter_subject') — @lang('question_banks.filter_all')</option>
                @foreach($subjects as $s)
                    <option value="{{ $s->id }}" @selected((string)($filters['subject_id'] ?? '') === (string)$s->id)>{{ $s->name }}</option>
                @endforeach
            </select>

            <select name="grade_level" class="form-control" onchange="this.form.submit()">
                <option value="">@lang('question_banks.filter_grade') — @lang('question_banks.filter_all')</option>
                @foreach($grades as $g => $label)
                    <option value="{{ $g }}" @selected((string)($filters['grade_level'] ?? '') === (string)$g)>{{ $label }}</option>
                @endforeach
            </select>

            <select name="creator_id" class="form-control" onchange="this.form.submit()">
                <option value="">@lang('question_banks.filter_creator') — @lang('question_banks.filter_all')</option>
                @foreach($creators as $u)
                    <option value="{{ $u->id }}" @selected((string)($filters['creator_id'] ?? '') === (string)$u->id)>{{ $u->name ?? $u->username }}</option>
                @endforeach
            </select>

            <div></div>
        </div>
        <div class="qb-filter-actions">
            <button class="btn-gold" type="submit"><i class="la la-search"></i> @lang('question_banks.search_engine')</button>
            @if($hasAnyFilter)
                <a href="{{ route('admin.question-banks.index') }}" class="btn-reset"><i class="la la-times"></i> @lang('question_banks.reset')</a>
            @endif
        </div>
    </form>

    {{-- Toolbar --}}
    <div class="qb-toolbar">
        <div class="left">
            <a class="btn-gold" href="{{ route('admin.question-banks.create') }}">
                <i class="la la-plus"></i> @lang('question_banks.add')
            </a>
            <a class="btn-ghost" href="{{ route('admin.question-banks.library') }}">
                <i class="la la-book"></i> @lang('question_banks.open_library')
            </a>
        </div>
        <span class="count-pill">@lang('question_banks.count_pill'): {{ $banks->total() }}</span>
    </div>

    {{-- Table --}}
    <div class="qb-surface">
        <div class="table-responsive">
            <table class="table qb-table">
                <thead>
                    <tr>
                        <th>@lang('question_banks.col_name')</th>
                        <th>@lang('question_banks.col_visibility')</th>
                        <th>@lang('question_banks.col_school')</th>
                        <th>@lang('question_banks.col_subject')</th>
                        <th>@lang('question_banks.col_grade')</th>
                        <th>@lang('question_banks.col_creator')</th>
                        <th>@lang('question_banks.col_questions_count')</th>
                        <th>@lang('question_banks.col_status')</th>
                        <th>@lang('question_banks.col_source')</th>
                        <th>@lang('question_banks.col_created_at')</th>
                        <th class="text-{{ $isRtl ? 'start' : 'end' }}">@lang('question_banks.col_actions')</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($banks as $bank)
                    @php
                        $visibility = $bank->visibility ?? 'private';
                        $status = $bank->status ?? 'active';
                        $source = $bank->source ?? 'manual';
                    @endphp
                    <tr>
                        <td data-label="@lang('question_banks.col_name')">
                            <div class="qb-name">{{ $bank->name_ar }}</div>
                            @if($bank->name_en)
                                <div class="qb-name-en">{{ $bank->name_en }}</div>
                            @endif
                        </td>
                        <td data-label="@lang('question_banks.col_visibility')">
                            <span class="qb-pill vis-{{ $visibility }}">
                                <span class="dot"></span>{{ $visibilities[$visibility] ?? $visibility }}
                            </span>
                        </td>
                        <td data-label="@lang('question_banks.col_school')">
                            @if($visibility === 'public' && ($bank->shared_schools_count ?? 0) > 0)
                                <span class="qb-secondary">{{ __('question_banks.school_shared', ['count' => $bank->shared_schools_count]) }}</span>
                            @elseif($visibility === 'public')
                                <span class="qb-secondary">@lang('question_banks.school_platform')</span>
                            @elseif($bank->school)
                                <span class="qb-secondary">{{ $bank->school->name }}</span>
                            @else
                                <span class="qb-secondary">—</span>
                            @endif
                        </td>
                        <td data-label="@lang('question_banks.col_subject')">
                            @if($bank->subjects->isEmpty())
                                <span class="qb-secondary">—</span>
                            @else
                                @foreach($bank->subjects->take(2) as $s)
                                    <span class="qb-pill subject">{{ $s->name }}</span>
                                @endforeach
                                @if($bank->subjects->count() > 2)
                                    <span class="qb-secondary">+{{ $bank->subjects->count() - 2 }}</span>
                                @endif
                            @endif
                        </td>
                        <td data-label="@lang('question_banks.col_grade')">
                            <span class="qb-secondary">{{ $bank->grade_level ? ($grades[$bank->grade_level] ?? '#'.$bank->grade_level) : '—' }}</span>
                        </td>
                        <td data-label="@lang('question_banks.col_creator')">
                            <span class="qb-secondary">{{ $bank->creator->name ?? $bank->creator->username ?? '—' }}</span>
                        </td>
                        <td data-label="@lang('question_banks.col_questions_count')">
                            <strong>{{ $bank->questions_count }}</strong>
                        </td>
                        <td data-label="@lang('question_banks.col_status')">
                            <span class="qb-pill status-{{ $status }}">
                                <span class="dot"></span>{{ $statuses[$status] ?? $status }}
                            </span>
                        </td>
                        <td data-label="@lang('question_banks.col_source')">
                            <span class="qb-pill source">{{ $sources[$source] ?? $source }}</span>
                        </td>
                        <td data-label="@lang('question_banks.col_created_at')">
                            <span class="qb-secondary">{{ optional($bank->created_at)->format('Y-m-d') }}</span>
                        </td>
                        <td data-label="@lang('question_banks.col_actions')" class="actions-cell text-{{ $isRtl ? 'start' : 'end' }}">
                            <div class="qb-actions">
                                <a href="{{ route('admin.question-banks.questions.index', $bank->id) }}"
                                   class="qb-action-btn view"
                                   title="@lang('question_banks.action_view_questions')">
                                    <i class="la la-eye"></i>
                                </a>
                                <a href="{{ route('admin.question-banks.edit', $bank->id) }}"
                                   class="qb-action-btn edit"
                                   title="@lang('question_banks.action_edit')">
                                    <i class="la la-edit"></i>
                                </a>
                                <form action="{{ route('admin.question-banks.destroy', $bank->id) }}" method="POST"
                                      onsubmit="return confirm('@lang('question_banks.confirm_delete')');">
                                    @csrf @method('DELETE')
                                    <button class="qb-action-btn del" type="submit"
                                            title="@lang('question_banks.action_delete')">
                                        <i class="la la-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11">
                            <div class="qb-empty">
                                <i class="la la-database"></i>
                                <div class="lbl">
                                    @if($hasAnyFilter)
                                        @lang('question_banks.empty_filtered')
                                    @else
                                        @lang('question_banks.empty_title')
                                    @endif
                                </div>
                                @if(! $hasAnyFilter)
                                    <div class="sub">@lang('question_banks.empty_sub')</div>
                                @else
                                    <div class="sub">
                                        <a href="{{ route('admin.question-banks.index') }}" style="color:var(--gold-500);">
                                            @lang('question_banks.reset')
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
        @if($banks->hasPages())
            <div class="qb-footer">
                <div class="qb-secondary">{{ $banks->total() }}</div>
                <div>{{ $banks->withQueryString()->links() }}</div>
            </div>
        @endif
    </div>
</div>
@endsection
