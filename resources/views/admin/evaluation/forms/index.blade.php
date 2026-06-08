@extends('layouts.app')

@section('title', __('evaluation.forms.page_title'))
@section('body_class','theme-light')

@push('styles')
<style>
    body.theme-light .ev-kpis .card { padding: 1rem 1.1rem; }
    body.theme-light .ev-kpis .label { color:#64748b; font-weight:600; font-size:.78rem; letter-spacing:.3px; text-transform:uppercase; margin-bottom:.35rem; }
    body.theme-light .ev-kpis .value { font-size:1.65rem; font-weight:800; color:var(--gold-400); line-height:1; }
    body.theme-light .ev-kpis .icon { width:42px; height:42px; border-radius:12px; display:inline-flex; align-items:center; justify-content:center; background:linear-gradient(135deg,#fff6dd,#fde8ad); color:var(--gold-500); font-size:1.2rem; }
    body.theme-light .ev-add-btn { background:linear-gradient(135deg,var(--gold-200),var(--gold-500))!important; color:#fff!important; border:none; padding:.55rem 1rem; border-radius:10px; font-weight:600; box-shadow:0 4px 14px rgba(207,160,70,.25); }
    body.theme-light .ev-add-btn:hover { transform:translateY(-1px); }
    body.theme-light .filters-card .form-label { font-size:.78rem; color:#64748b; font-weight:600; margin-bottom:.25rem; }
    body.theme-light .filters-card .form-control, body.theme-light .filters-card select { border-radius:10px; border:1px solid #e5e7eb; font-size:.88rem; padding:.45rem .7rem; }
    body.theme-light .ev-pill { display:inline-flex; align-items:center; gap:.3rem; padding:.15rem .55rem; border-radius:999px; font-size:.72rem; font-weight:600; }
    body.theme-light .ev-pill.draft { background:#f1f5f9; color:#475569; }
    body.theme-light .ev-pill.ready { background:#e0f2fe; color:#0369a1; }
    body.theme-light .ev-pill.published { background:#ecfdf5; color:#047857; }
    body.theme-light .ev-pill.closed { background:#fef2f2; color:#b91c1c; }
    body.theme-light .ev-pill.archived { background:#ede9fe; color:#5b21b6; }
    body.theme-light .ev-empty { padding:3.5rem 1rem; text-align:center; }
    body.theme-light .ev-empty .icon-wrap { width:72px; height:72px; border-radius:18px; margin:0 auto 1rem; background:linear-gradient(135deg,#fff6dd,#fde8ad); color:var(--gold-500); font-size:1.8rem; display:inline-flex; align-items:center; justify-content:center; }
</style>
@endpush

@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-8 col-12 mb-2">
        <h2 class="content-header-title mb-0">@lang('evaluation.forms.page_title')</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
                <li class="breadcrumb-item active">@lang('evaluation.forms.page_title')</li>
            </ol>
        </div>
    </div>
    <div class="content-header-right col-md-4 col-12 text-end">
        <button type="button" class="btn ev-add-btn" disabled title="@lang('evaluation.forms.add_soon')">
            <i class="la la-plus"></i> @lang('evaluation.forms.add')
        </button>
    </div>
</div>

<div class="content-body">
    {{-- KPI tiles --}}
    <div class="row ev-kpis mb-3">
        @foreach (['total' => 'la-file-alt', 'published' => 'la-check-circle', 'draft' => 'la-edit', 'closed' => 'la-lock'] as $key => $icon)
            <div class="col-md-3 col-6 mb-2">
                <div class="card h-100">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="label">@lang('evaluation.forms.kpis.'.$key)</div>
                            <div class="value">{{ $stats[$key] }}</div>
                        </div>
                        <span class="icon"><i class="la {{ $icon }}"></i></span>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Filters --}}
    <form action="{{ route('admin.evaluations.index') }}" method="GET" class="card filters-card p-3 mb-3">
        <div class="row g-2 align-items-end">
            <div class="col-md-3 col-6">
                <label class="form-label">@lang('evaluation.forms.filters.search')</label>
                <input type="text" name="q" value="{{ $filters['search'] }}" class="form-control" placeholder="@lang('evaluation.forms.filters.search')">
            </div>
            <div class="col-md-2 col-6">
                <label class="form-label">@lang('evaluation.forms.columns.type')</label>
                <select name="type" class="form-control">
                    <option value="">—</option>
                    @foreach ($types as $val => $label)
                        <option value="{{ $val }}" {{ ($filters['type'] ?? '') === $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 col-6">
                <label class="form-label">@lang('evaluation.forms.columns.domain')</label>
                <select name="usage_domain" class="form-control">
                    <option value="">—</option>
                    @foreach ($domains as $val => $label)
                        <option value="{{ $val }}" {{ ($filters['usage_domain'] ?? '') === $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 col-6">
                <label class="form-label">@lang('evaluation.forms.columns.status')</label>
                <select name="status" class="form-control">
                    <option value="">—</option>
                    @foreach ($statuses as $val => $label)
                        <option value="{{ $val }}" {{ ($filters['status'] ?? '') === $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 col-12 d-flex gap-1 align-items-end">
                <button type="submit" class="btn ev-add-btn flex-grow-1"><i class="la la-search"></i> @lang('evaluation.forms.filters.show')</button>
                <a href="{{ route('admin.evaluations.index') }}" class="btn btn-outline-secondary" title="@lang('evaluation.forms.filters.reset')"><i class="la la-redo"></i></a>
            </div>
        </div>
    </form>

    {{-- Forms table --}}
    <div class="card">
        @if ($forms->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>@lang('evaluation.forms.columns.name')</th>
                            <th>@lang('evaluation.forms.columns.type')</th>
                            <th>@lang('evaluation.forms.columns.domain')</th>
                            <th>@lang('evaluation.forms.columns.status')</th>
                            <th>@lang('evaluation.forms.columns.items')</th>
                            <th>@lang('evaluation.forms.columns.indicators')</th>
                            <th>@lang('evaluation.forms.columns.evaluators')</th>
                            <th>@lang('evaluation.forms.columns.targets')</th>
                            <th>@lang('evaluation.forms.columns.created')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($forms as $form)
                            <tr>
                                <td class="fw-bold">{{ $form->title }}</td>
                                <td>{{ $form->type?->label() }}</td>
                                <td>{{ $form->usage_domain?->label() }}</td>
                                <td><span class="ev-pill {{ $form->status?->value }}">{{ $form->status?->label() }}</span></td>
                                <td>{{ $form->items_count }}</td>
                                <td>{{ $form->indicators_count }}</td>
                                <td>{{ $form->assignments_count }}</td>
                                <td>{{ $form->targets_count }}</td>
                                <td><span class="text-muted small">{{ $form->created_at?->format('Y-m-d') }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($forms->hasPages())
                <div class="card-footer">{{ $forms->links() }}</div>
            @endif
        @else
            <div class="ev-empty">
                <span class="icon-wrap"><i class="la la-clipboard-list"></i></span>
                <h5 class="mb-1">@lang('evaluation.forms.empty.title')</h5>
                <p class="text-muted">@lang('evaluation.forms.empty.subtitle')</p>
            </div>
        @endif
    </div>
</div>
@endsection
