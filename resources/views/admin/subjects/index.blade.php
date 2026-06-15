@extends('layouts.app')

@section('title', __('sprint4.subjects.page_title'))
@section('body_class','theme-light')

@push('styles')
<style>
    /* Subjects — polished, theme-light specific styles */
    body.theme-light .subjects-toolbar { gap: .5rem; }
    body.theme-light .subjects-kpis .card { padding: 1rem 1.1rem; }
    body.theme-light .subjects-kpis .label {
        color: #64748b; font-weight: 600; font-size: .78rem; letter-spacing: .3px;
        text-transform: uppercase; margin-bottom: .35rem;
    }
    body.theme-light .subjects-kpis .value {
        font-size: 1.65rem; font-weight: 800; color: var(--gold-400); letter-spacing: -.5px; line-height: 1;
    }
    body.theme-light .subjects-kpis .icon {
        width: 42px; height: 42px; border-radius: 12px;
        display: inline-flex; align-items: center; justify-content: center;
        background: linear-gradient(135deg, #fff6dd, #fde8ad);
        color: var(--gold-500); font-size: 1.2rem;
        box-shadow: inset 0 0 0 1px rgba(207,160,70,.18);
    }
    body.theme-light .add-subject-btn {
        background: linear-gradient(135deg, var(--gold-200), var(--gold-500)) !important;
        color: #fff !important; border: none; padding: .55rem 1rem;
        border-radius: 10px; font-weight: 600; box-shadow: 0 4px 14px rgba(207,160,70,.25);
    }
    body.theme-light .add-subject-btn:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(207,160,70,.32); }
    body.theme-light .subjects-toolbar .dropdown-menu {
        border: 1px solid #e5e7eb; border-radius: 12px; padding: .35rem;
        box-shadow: 0 12px 32px rgba(15,23,42,.08);
        min-width: 260px;
    }
    body.theme-light .subjects-toolbar .dropdown-item {
        border-radius: 8px; padding: .55rem .75rem; display: flex; align-items: center; gap: .55rem;
    }
    body.theme-light .subjects-toolbar .dropdown-item i {
        width: 28px; height: 28px; border-radius: 8px;
        background: #fff6dd; color: var(--gold-500);
        display: inline-flex; align-items: center; justify-content: center; font-size: 1rem;
    }
    body.theme-light .subjects-toolbar .dropdown-item:hover { background: #f8fafc; }
    body.theme-light .subjects-toolbar .dropdown-item .desc {
        display: block; font-size: .72rem; color: #94a3b8; margin-top: 2px;
    }
    body.theme-light .subjects-toolbar .dropdown-item .badge-soon {
        background: #f1f5f9; color: #64748b; font-size: .65rem; padding: .15rem .45rem; border-radius: 999px;
        margin-inline-start: auto;
    }
    body.theme-light .subjects-toolbar .btn-soft {
        background: #fff; border: 1px solid #e5e7eb; color: #475569;
        border-radius: 10px; padding: .5rem .9rem; font-weight: 500;
    }
    body.theme-light .subjects-toolbar .btn-soft:hover { background: #f8fafc; color: #0f172a; }
    body.theme-light .subjects-search input {
        border-radius: 999px; border: 1px solid #e5e7eb; background: #fff;
        padding: .5rem 1rem .5rem 2.4rem; min-width: 240px;
    }
    body.theme-light .subjects-search { position: relative; }
    body.theme-light .subjects-search i {
        position: absolute; top: 50%; transform: translateY(-50%);
        inset-inline-start: .85rem; color: #94a3b8;
    }
    body.theme-light .subject-name { font-weight: 700; color: #0f172a; }
    body.theme-light .subject-en { color: #64748b; font-size: .8rem; display: block; margin-top: 2px; }
    body.theme-light .grade-chip {
        background: #f1f5f9; color: #475569; padding: .15rem .55rem;
        border-radius: 999px; font-size: .75rem; margin: 1px;
    }
    body.theme-light .src-chip {
        padding: .2rem .6rem; border-radius: 999px; font-size: .72rem; font-weight: 600;
    }
    body.theme-light .src-chip.src-manual { background: #eef2ff; color: #4338ca; }
    body.theme-light .src-chip.src-viewclass { background: #fdf3d8; color: var(--gold-500); }
    body.theme-light .src-chip.src-excel { background: #ecfdf5; color: #047857; }
    body.theme-light .status-pill {
        display: inline-flex; align-items: center; gap: .35rem;
        padding: .15rem .55rem; border-radius: 999px; font-size: .72rem; font-weight: 600;
    }
    body.theme-light .status-pill.on { background: #ecfdf5; color: #047857; }
    body.theme-light .status-pill.off { background: #fef2f2; color: #b91c1c; }
    body.theme-light .row-actions .btn-icon {
        width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center;
        border-radius: 8px; border: 1px solid #e5e7eb; background: #fff; color: #475569;
    }
    body.theme-light .row-actions .btn-icon:hover { background: #f8fafc; color: #0f172a; }
    body.theme-light .row-actions .dropdown-menu { min-width: 220px; border-radius: 12px; }
    body.theme-light .empty-state { padding: 3rem 1rem; text-align: center; }
    body.theme-light .empty-state .icon-wrap {
        width: 64px; height: 64px; border-radius: 16px; margin: 0 auto 1rem;
        background: linear-gradient(135deg, #fff6dd, #fde8ad);
        color: var(--gold-500); font-size: 1.6rem;
        display: inline-flex; align-items: center; justify-content: center;
    }
</style>
@endpush

@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-8 col-12 mb-2">
        <h2 class="content-header-title mb-0">@lang('sprint4.subjects.index_title')</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
                <li class="breadcrumb-item active">@lang('sprint4.subjects.plural')</li>
            </ol>
        </div>
    </div>
</div>

<div class="content-body">
    {{-- KPI tiles --}}
    <div class="row subjects-kpis mb-3">
        <div class="col-md-3 col-6 mb-2">
            <div class="card h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="label">@lang('sprint4.subjects.card_total')</div>
                        <div class="value">{{ $stats['total'] ?? 0 }}</div>
                    </div>
                    <span class="icon"><x-svg-icon name="journal-bookmark-fill" :size="22" class="ic-gold" /></span>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6 mb-2">
            <div class="card h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="label">@lang('sprint4.subjects.card_active')</div>
                        <div class="value">{{ $stats['active'] ?? 0 }}</div>
                    </div>
                    <span class="icon"><x-svg-icon name="check-circle-fill" :size="22" class="ic-success" /></span>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6 mb-2">
            <div class="card h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="label">@lang('sprint4.subjects.card_core')</div>
                        <div class="value">{{ $stats['core'] ?? 0 }}</div>
                    </div>
                    <span class="icon"><x-svg-icon name="star-fill" :size="22" class="ic-warn" /></span>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6 mb-2">
            <div class="card h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="label">@lang('sprint4.subjects.card_templates')</div>
                        <div class="value">{{ $stats['templates'] ?? 0 }}</div>
                    </div>
                    <span class="icon"><x-svg-icon name="collection-fill" :size="22" class="ic-teal" /></span>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex flex-wrap subjects-toolbar align-items-center justify-content-between">
            <div class="d-flex flex-wrap gap-1 align-items-center">
                <div class="dropdown">
                    <button class="btn add-subject-btn dropdown-toggle" type="button" data-toggle="dropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <x-svg-icon name="plus-lg" :size="16" /> @lang('sprint4.subjects.add')
                    </button>
                    <div class="dropdown-menu">
                        <a class="dropdown-item" href="{{ route('admin.subjects.create') }}">
                            <x-svg-icon name="pencil-square" :size="16" class="ic-gold" />
                            <span>
                                @lang('sprint4.subjects.add_manual')
                                <span class="desc">إضافة مادة جديدة يدوياً بكامل البيانات</span>
                            </span>
                        </a>
                        <a class="dropdown-item" href="#" data-toggle="modal" data-bs-toggle="modal" data-target="#excelImportModal" data-bs-target="#excelImportModal">
                            <x-svg-icon name="file-earmark-excel-fill" :size="16" class="ic-success" />
                            <span>
                                @lang('sprint4.subjects.add_excel')
                                <span class="desc">رفع ملف Excel بقالب المنصة</span>
                            </span>
                        </a>
                        <a class="dropdown-item" href="{{ route('admin.subjects.templates.index') }}">
                            <x-svg-icon name="collection-fill" :size="16" class="ic-teal" />
                            <span>
                                @lang('sprint4.subjects.add_template')
                                <span class="desc">إضافة مواد جاهزة من قوالب المنصة</span>
                            </span>
                            @if(($stats['templates'] ?? 0) > 0)
                                <span class="badge-soon">{{ $stats['templates'] }}</span>
                            @endif
                        </a>
                    </div>
                </div>
                <a class="btn btn-soft ms-1" href="{{ route('admin.subjects.credit-hours') }}">
                    <x-svg-icon name="clock-history" :size="16" class="ic-info" /> @lang('sprint4.subjects.set_credit_hours')
                </a>
            </div>
            <form action="{{ route('admin.subjects.index') }}" method="GET" class="subjects-search mt-1 mt-md-0">
                <x-svg-icon name="search" :size="15" class="ic-muted" />
                <input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="@lang('sprint4.subjects.search_placeholder')" />
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width: 30px"><input type="checkbox" id="js-check-all" /></th>
                        <th>@lang('sprint4.subjects.columns.name')</th>
                        <th>@lang('sprint4.subjects.columns.grade')</th>
                        <th>@lang('sprint4.subjects.columns.section')</th>
                        <th>@lang('sprint4.subjects.columns.credit_hours')</th>
                        <th>@lang('sprint4.subjects.columns.certificate_order')</th>
                        <th>@lang('sprint4.subjects.columns.source')</th>
                        <th>@lang('sprint4.subjects.columns.is_active')</th>
                        <th class="text-end" style="width: 80px;">@lang('sprint4.subjects.columns.actions')</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($subjects as $subject)
                        <tr>
                            <td><input type="checkbox" class="js-row" value="{{ $subject->id }}" /></td>
                            <td>
                                <span class="subject-name">
                                    @if($subject->icon)<i class="la {{ $subject->icon }}" style="color: var(--gold-400); margin-inline-end: .35rem;"></i>@endif
                                    {{ $subject->name }}
                                </span>
                                @if($subject->name_en)<span class="subject-en">{{ $subject->name_en }}</span>@endif
                                @if($subject->code)<span class="grade-chip mt-1 d-inline-block">{{ $subject->code }}</span>@endif
                            </td>
                            <td>
                                @forelse($subject->grade_levels ?? [] as $level)
                                    <span class="grade-chip">{{ $level }}</span>
                                @empty
                                    <span class="text-muted">—</span>
                                @endforelse
                            </td>
                            <td>{{ $subject->section ?? '—' }}</td>
                            <td>{{ $subject->credit_hours ?? '—' }}</td>
                            <td>{{ $subject->certificate_order }}</td>
                            <td>
                                <span class="src-chip src-{{ $subject->source }}">
                                    @lang('sprint4.subjects.sources.' . $subject->source)
                                </span>
                            </td>
                            <td>
                                @if($subject->is_active)
                                    <span class="status-pill on"><x-svg-icon name="check-circle-fill" :size="14" class="ic-success" /> @lang('sprint4.subjects.columns.is_active')</span>
                                @else
                                    <span class="status-pill off"><x-svg-icon name="x-circle-fill" :size="14" class="ic-muted" /> —</span>
                                @endif
                            </td>
                            <td class="text-end row-actions">
                                <div class="dropdown">
                                    <button type="button" class="btn-icon dropdown-toggle" data-toggle="dropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                        <x-svg-icon name="three-dots-vertical" :size="16" class="ic-muted" />
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-end">
                                        <a class="dropdown-item" href="{{ route('admin.subjects.edit', $subject->id) }}"><x-svg-icon name="pencil-square" :size="15" class="ic-gold" /> @lang('sprint4.subjects.edit')</a>
                                        <a class="dropdown-item" href="{{ route('admin.subjects.lesson-tree', $subject->id) }}"><x-svg-icon name="diagram-2-fill" :size="15" class="ic-info" /> @lang('sprint4.subjects.lesson_tree') ({{ $subject->units_count }})</a>
                                        <a class="dropdown-item" href="{{ route('admin.subjects.domains', $subject->id) }}"><x-svg-icon name="list-ul" :size="15" class="ic-navy" /> @lang('sprint4.subjects.standards')</a>
                                        <div class="dropdown-divider"></div>
                                        <form action="{{ route('admin.subjects.destroy', $subject->id) }}" method="POST" onsubmit="return confirm('@lang('sprint4.subjects.delete') ?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="dropdown-item text-danger"><x-svg-icon name="trash3-fill" :size="15" class="ic-danger" /> @lang('sprint4.subjects.delete')</button>
                                        </form>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9">
                                <div class="empty-state">
                                    <span class="icon-wrap"><x-svg-icon name="journal-bookmark-fill" :size="28" class="ic-muted" /></span>
                                    <h5 class="mb-1">@lang('common.no_results')</h5>
                                    <p class="text-muted mb-3">ابدأ بإضافة مادة جديدة من زر "إضافة مادة" أعلى الجدول.</p>
                                    <a href="{{ route('admin.subjects.create') }}" class="btn add-subject-btn">
                                        <x-svg-icon name="plus-lg" :size="16" /> @lang('sprint4.subjects.add_manual')
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($subjects->hasPages())
            <div class="card-footer">{{ $subjects->links() }}</div>
        @endif
    </div>
</div>

{{-- Excel-import modal — upload a file built from the platform template. --}}
<div class="modal fade" id="excelImportModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content" style="border-radius: 16px; border: 1px solid #e5e7eb;">
            <form action="{{ route('admin.subjects.import.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-header" style="border-bottom: 1px solid #f1f5f9;">
                    <h5 class="modal-title"><x-svg-icon name="file-earmark-excel-fill" :size="18" class="ic-success" /> @lang('sprint4.subjects.import.title')</h5>
                    <button type="button" class="close" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted">@lang('sprint4.subjects.import.help')</p>
                    <a href="{{ route('admin.subjects.import.template') }}" class="btn btn-soft mb-3">
                        <x-svg-icon name="download" :size="16" class="ic-info" /> @lang('sprint4.subjects.import.download_template')
                    </a>
                    <div class="form-group mb-0">
                        <label class="form-label fw-semibold">@lang('sprint4.subjects.import.choose_file')</label>
                        <input type="file" name="file" class="form-control" accept=".xlsx,.xls,.csv,.txt" required>
                    </div>
                </div>
                <div class="modal-footer" style="border-top: 1px solid #f1f5f9;">
                    <button type="button" class="btn btn-soft" data-dismiss="modal" data-bs-dismiss="modal">@lang('sprint4.subjects.import.cancel')</button>
                    <button type="submit" class="btn add-subject-btn"><x-svg-icon name="upload" :size="16" /> @lang('sprint4.subjects.import.upload')</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var all = document.getElementById('js-check-all');
    if (all) {
        all.addEventListener('change', function () {
            document.querySelectorAll('.js-row').forEach(function (cb) { cb.checked = all.checked; });
        });
    }
});
</script>
@endsection
