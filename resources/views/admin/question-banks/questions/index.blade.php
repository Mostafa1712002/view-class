@extends('layouts.app')

@section('title', __('questions.page_title') . ' — ' . $bank->name_ar)
@section('body_class', 'theme-light')

@php
    $isRtl = app()->getLocale() === 'ar';
    $typesList = ['true_false','mcq','essay','matching','fill_blank','short'];
    $contentTypes = ['text','image','mixed'];
@endphp

@push('styles')
<style>
    /* ── filters ── */
    .q-filters .form-label { font-size: 12px; font-weight: 600; color: #475569; }
    .q-filters .form-select, .q-filters .form-control { font-size: 14px; }

    /* ── table ── */
    .q-table td { vertical-align: middle; }
    .q-body-preview { max-width: 340px; color: #1e293b; line-height: 1.55; font-size: 13px; }
    .q-body-preview small { color: #64748b; }

    /* ── badges ── */
    .badge-type {
        background: rgba(212,175,55,.13); color: #7a5d12; font-weight: 600;
        border: 1px solid rgba(212,175,55,.35); padding: 5px 10px; border-radius: 999px;
        font-size: 11px; white-space: nowrap;
    }
    .badge-ctype {
        background: #e0f2fe; color: #075985; font-size: 11px;
        padding: 3px 8px; border-radius: 999px; white-space: nowrap;
    }
    .badge-diff-1 { background:#dcfce7; color:#166534; padding:4px 9px; border-radius:999px; font-size:11px; }
    .badge-diff-2 { background:#fef3c7; color:#92400e; padding:4px 9px; border-radius:999px; font-size:11px; }
    .badge-diff-3 { background:#fee2e2; color:#991b1b; padding:4px 9px; border-radius:999px; font-size:11px; }
    .badge-status-draft          { background:#e2e8f0; color:#475569; padding:4px 9px; border-radius:999px; font-size:11px; }
    .badge-status-pending_review { background:#fef3c7; color:#92400e; padding:4px 9px; border-radius:999px; font-size:11px; }
    .badge-status-approved       { background:#dcfce7; color:#166534; padding:4px 9px; border-radius:999px; font-size:11px; }
    .badge-status-rejected       { background:#fee2e2; color:#991b1b; padding:4px 9px; border-radius:999px; font-size:11px; }
    .badge-status-archived       { background:#fde68a; color:#78350f; padding:4px 9px; border-radius:999px; font-size:11px; }
    .badge-status-published      { background:#dcfce7; color:#166534; padding:4px 9px; border-radius:999px; font-size:11px; }
    .badge-vis-public  { background:#f0fdf4; color:#166534; border:1px solid #bbf7d0; font-size:11px; padding:3px 9px; border-radius:999px; }
    .badge-vis-private { background:#eff6ff; color:#1e40af; border:1px solid #bfdbfe; font-size:11px; padding:3px 9px; border-radius:999px; }
    .badge-bank-status { font-size:11px; padding:3px 9px; border-radius:999px; }

    /* ── bank header ── */
    .bank-header-card { border:1px solid #e2e8f0; border-radius:12px; padding:18px 22px; background:#fff; }
    .bank-header-card .bank-title { font-size:18px; font-weight:700; color:#0f172a; margin:0; }
    .bank-header-card .bank-meta { display:flex; flex-wrap:wrap; gap:10px; margin-top:10px; font-size:13px; color:#475569; }
    .bank-header-card .bank-meta span { display:inline-flex; align-items:center; gap:4px; }
    .bank-header-card .bank-actions { display:flex; flex-wrap:wrap; gap:8px; margin-top:14px; }
    .type-stat-chip {
        background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px;
        padding:4px 11px; font-size:12px; color:#374151; font-weight:500;
    }
    .type-stat-chip span { color:#2563eb; font-weight:700; }

    /* ── add-type menu ── */
    .add-type-menu .dropdown-item { padding: 10px 16px; font-size: 14px; }
    .q-actions .btn { padding: 4px 8px; }

    /* ── title row ── */
    .q-bank-title-row { display:flex; align-items:center; gap:14px; flex-wrap:wrap; }
    .q-bank-title-row .subj-chip {
        background:#f1f5f9; color:#0f172a; font-size:12px; padding:4px 10px; border-radius:999px;
    }

    /* ── empty & preview ── */
    .q-empty { padding:48px 16px; text-align:center; color:#64748b; }
    .q-empty .icon { font-size:42px; color:#cbd5e1; }
    .modal-preview .modal-body { background:#f8fafc; }
    .preview-card { background:#fff; padding:18px; border-radius:10px; border:1px solid #e2e8f0; }
    .preview-card .row-meta { display:flex; gap:14px; flex-wrap:wrap; margin-bottom:14px; font-size:13px; color:#475569; }
    .preview-card .row-meta b { color:#0f172a; }
    .preview-card .ans-list { margin:0; padding-{{ $isRtl ? 'right' : 'left' }}:0; list-style:none; }
    .preview-card .ans-list li { padding:8px 12px; background:#f8fafc; border-radius:8px; margin-bottom:6px; }
    .preview-card .ans-list li.correct { background:#dcfce7; color:#166534; font-weight:600; }
    .preview-card .pair-row { display:flex; gap:8px; align-items:center; margin-bottom:6px; }
    .preview-card .pair-row .col-cell { flex:1; padding:8px 12px; background:#f8fafc; border-radius:8px; }
    .preview-card .pair-row .arrow { color:#94a3b8; }

    /* ── thumb ── */
    .q-thumb { max-height:60px; max-width:90px; object-fit:contain; border-radius:4px; border:1px solid #e2e8f0; cursor:pointer; }
    .code-pill { font-size:12px; font-family:monospace; background:#f1f5f9; padding:2px 7px; border-radius:4px; color:#1e40af; font-weight:700; }

    /* ── full-image lightbox overlay ── */
    #imgLightbox {
        display:none; position:fixed; inset:0; background:rgba(0,0,0,.75);
        z-index:99999; align-items:center; justify-content:center; cursor:zoom-out;
    }
    #imgLightbox.active { display:flex; }
    #imgLightbox img { max-width:90vw; max-height:90vh; object-fit:contain; border-radius:8px; }

    @media (max-width: 640px) {
        .q-body-preview { max-width: 100%; }
        .q-filters .col-md-2 { margin-bottom: 8px; }
    }
</style>
@endpush

@section('content')
<div class="content-header row">
    <div class="content-header-left col-12 mb-2">
        <div class="q-bank-title-row">
            <h2 class="content-header-title mb-0">{{ $bank->name_ar }}</h2>
            @foreach($bank->subjects as $s)
                <span class="subj-chip">{{ $s->name }}</span>
            @endforeach
        </div>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('questions.breadcrumb.home')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.question-banks.index') }}">@lang('questions.breadcrumb.banks')</a></li>
                <li class="breadcrumb-item active">@lang('questions.breadcrumb.questions')</li>
            </ol>
        </div>
    </div>
</div>

<div class="content-body">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

    {{-- ══ Bank Header Card ══ --}}
    <div class="bank-header-card mb-3">
        <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">
            <div>
                <h3 class="bank-title">
                    {{ $bank->name_ar }}
                    @if($bank->name_en && $bank->name_en !== $bank->name_ar)
                        <small class="text-muted fw-normal" style="font-size:14px;margin-{{ $isRtl ? 'right' : 'left' }}:6px;">{{ $bank->name_en }}</small>
                    @endif
                </h3>
                <div class="bank-meta mt-2">
                    {{-- Visibility --}}
                    <span>
                        <i class="la la-eye"></i>
                        <span class="badge badge-vis-{{ $bank->visibility }}">
                            @if($bank->visibility === 'public') @lang('question_banks.visibility_public') @else @lang('question_banks.visibility_private') @endif
                        </span>
                    </span>
                    {{-- Status --}}
                    <span>
                        <i class="la la-circle"></i>
                        <span class="badge badge-bank-status badge-status-{{ $bank->status }}">
                            @lang('question_banks.status_' . $bank->status)
                        </span>
                    </span>
                    {{-- Source --}}
                    @if($bank->source)
                    <span>
                        <i class="la la-database"></i>
                        @lang('question_banks.source_' . $bank->source)
                    </span>
                    @endif
                    {{-- Grade --}}
                    @if($bank->grade_level)
                    <span>
                        <i class="la la-graduation-cap"></i>
                        @lang('question_banks.grades.' . $bank->grade_level)
                    </span>
                    @endif
                    {{-- Total questions --}}
                    <span>
                        <i class="la la-question-circle"></i>
                        <strong>{{ $questions->total() }}</strong>&nbsp;@lang('questions.index_title')
                    </span>
                </div>
                {{-- Per-type chips --}}
                @if($typeCounts->isNotEmpty())
                <div class="d-flex flex-wrap gap-2 mt-2">
                    @foreach($typeCounts as $tKey => $tCount)
                    <span class="type-stat-chip">@lang('questions.types.'.$tKey): <span>{{ $tCount }}</span></span>
                    @endforeach
                </div>
                @endif
            </div>
            <div class="bank-actions">
                {{-- Add question dropdown --}}
                <div class="dropdown">
                    <button class="btn btn-primary btn-sm dropdown-toggle" data-toggle="dropdown" data-bs-toggle="dropdown" type="button">
                        <i class="la la-plus"></i> @lang('questions.add_btn')
                    </button>
                    <ul class="dropdown-menu add-type-menu">
                        @foreach($typesList as $t)
                            <li>
                                <a class="dropdown-item" href="{{ route('admin.question-banks.questions.create', $bank->id) }}?type={{ $t }}">
                                    @lang('questions.types.'.$t)
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
                {{-- Import Excel --}}
                <a href="{{ route('admin.question-banks.questions.import.form', $bank->id) }}"
                   class="btn btn-success btn-sm">
                    <i class="la la-file-excel"></i> @lang('question_import.page_title')
                </a>
                {{-- Download template --}}
                <a href="{{ route('admin.question-banks.questions.import.template', $bank->id) }}"
                   class="btn btn-outline-secondary btn-sm">
                    <i class="la la-download"></i> @lang('question_import.download_template')
                </a>
                {{-- Back to banks --}}
                <a href="{{ route('admin.question-banks.index') }}"
                   class="btn btn-outline-secondary btn-sm">
                    <i class="la la-arrow-{{ $isRtl ? 'right' : 'left' }}"></i> @lang('questions.form.back')
                </a>
            </div>
        </div>
    </div>

    {{-- ══ Enhanced Filters Card ══ --}}
    <div class="card mb-2">
        <div class="card-body py-2">
            <form action="{{ route('admin.question-banks.questions.index', $bank->id) }}" method="GET" class="q-filters">
                <div class="row g-2 align-items-end">
                    {{-- Text search --}}
                    <div class="col-md-3 col-12">
                        <label class="form-label">@lang('questions.filters.search')</label>
                        <input type="search" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="@lang('questions.filters.search')">
                    </div>
                    {{-- Code search --}}
                    <div class="col-md-2 col-6">
                        <label class="form-label">@lang('questions.filters.code')</label>
                        <input type="search" name="code" value="{{ request('code') }}" class="form-control form-control-sm" placeholder="Q-001">
                    </div>
                    {{-- Type --}}
                    <div class="col-md-2 col-6">
                        <label class="form-label">@lang('questions.filters.type')</label>
                        <select name="type" class="form-select form-select-sm">
                            <option value="">@lang('questions.filters.all')</option>
                            @foreach($typesList as $t)
                                <option value="{{ $t }}" {{ request('type') === $t ? 'selected' : '' }}>@lang('questions.types.'.$t)</option>
                            @endforeach
                        </select>
                    </div>
                    {{-- Content type --}}
                    <div class="col-md-2 col-6">
                        <label class="form-label">@lang('questions.filters.content_type')</label>
                        <select name="content_type" class="form-select form-select-sm">
                            <option value="">@lang('questions.filters.all')</option>
                            @foreach($contentTypes as $ct)
                                <option value="{{ $ct }}" {{ request('content_type') === $ct ? 'selected' : '' }}>@lang('questions.content_type.'.$ct)</option>
                            @endforeach
                        </select>
                    </div>
                    {{-- Difficulty --}}
                    <div class="col-md-1 col-6">
                        <label class="form-label">@lang('questions.filters.difficulty')</label>
                        <select name="difficulty" class="form-select form-select-sm">
                            <option value="">@lang('questions.filters.all')</option>
                            @foreach([1,2,3] as $d)
                                <option value="{{ $d }}" {{ (string)request('difficulty') === (string)$d ? 'selected' : '' }}>@lang('questions.difficulty.'.$d)</option>
                            @endforeach
                        </select>
                    </div>
                    {{-- Status --}}
                    <div class="col-md-2 col-6">
                        <label class="form-label">@lang('questions.filters.status')</label>
                        <select name="status" class="form-select form-select-sm">
                            <option value="">@lang('questions.filters.all')</option>
                            @foreach(\App\Models\BankQuestion::STATUSES as $s)
                                <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>@lang('questions.status.'.$s)</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="row g-2 align-items-end mt-1">
                    {{-- Lesson --}}
                    <div class="col-md-3 col-12">
                        <label class="form-label">@lang('questions.filters.lesson')</label>
                        <select name="lesson_id" class="form-select form-select-sm">
                            <option value="">@lang('questions.filters.all')</option>
                            @foreach($lessons as $l)
                                <option value="{{ $l->id }}" {{ (string)request('lesson_id') === (string)$l->id ? 'selected' : '' }}>{{ $l->name_ar }}</option>
                            @endforeach
                        </select>
                    </div>
                    {{-- Source text --}}
                    <div class="col-md-2 col-6">
                        <label class="form-label">@lang('questions.filters.source')</label>
                        <input type="search" name="source" value="{{ request('source') }}" class="form-control form-control-sm" placeholder="@lang('questions.filters.source')">
                    </div>
                    {{-- Checkboxes --}}
                    <div class="col-md-3 col-12 d-flex align-items-end gap-3 pb-1">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="has_image" id="chkHasImage" value="1" {{ request('has_image') ? 'checked' : '' }}>
                            <label class="form-check-label" for="chkHasImage" style="font-size:13px;">@lang('questions.filters.has_image')</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="full_image_only" id="chkFullImg" value="1" {{ request('full_image_only') ? 'checked' : '' }}>
                            <label class="form-check-label" for="chkFullImg" style="font-size:13px;">@lang('questions.filters.full_image_only')</label>
                        </div>
                    </div>
                    {{-- Buttons --}}
                    <div class="col-md-2 col-12 d-flex gap-1">
                        <button class="btn btn-primary btn-sm flex-fill"><i class="la la-filter"></i> @lang('questions.filters.title')</button>
                        <a class="btn btn-outline-secondary btn-sm" href="{{ route('admin.question-banks.questions.index', $bank->id) }}" title="@lang('questions.filters.reset')"><i class="la la-times"></i></a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- ══ Questions Table Card ══ --}}
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="text-muted small">
                {{ $questions->total() }} @lang('questions.index_title')
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 q-table">
                <thead class="thead-light">
                    <tr>
                        <th style="width:40px;"></th>
                        <th style="width:110px;">@lang('questions.columns.code')</th>
                        <th style="width:120px;">@lang('questions.columns.type')</th>
                        <th>@lang('questions.columns.body')</th>
                        <th style="width:90px;">@lang('questions.columns.content_type')</th>
                        <th style="width:75px;">@lang('questions.columns.difficulty')</th>
                        <th style="width:110px;">@lang('questions.columns.status')</th>
                        <th style="width:65px;">@lang('questions.columns.points')</th>
                        <th style="width:130px;">@lang('questions.columns.lesson')</th>
                        <th style="width:110px;">@lang('questions.columns.creator')</th>
                        <th style="width:95px;">@lang('questions.columns.created_at')</th>
                        <th style="width:130px;">@lang('questions.columns.actions')</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($questions as $q)
                        @php
                            $ctype = $q->question_content_type ?? 'text';
                            $isFull = $q->is_full_image_question;
                        @endphp
                        <tr>
                            {{-- Checkbox --}}
                            <td><input type="checkbox" class="form-check-input q-row-check" value="{{ $q->id }}"></td>

                            {{-- Code --}}
                            <td>
                                @if($q->question_code)
                                    <span class="code-pill">{{ $q->question_code }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>

                            {{-- Type --}}
                            <td><span class="badge badge-type">@lang('questions.types.'.$q->type)</span></td>

                            {{-- Question preview (content-type aware) --}}
                            <td>
                                <div class="q-body-preview">
                                    @if($isFull)
                                        {{-- Full-image: show code prominently --}}
                                        @if($q->question_code)
                                            <div><strong class="code-pill" style="font-size:13px;">{{ $q->question_code }}</strong></div>
                                        @endif
                                        @if($q->attachment_path)
                                            <img src="{{ asset('storage/' . $q->attachment_path) }}"
                                                 loading="lazy"
                                                 class="q-thumb mt-1"
                                                 alt=""
                                                 onclick="showImgLightbox(this.src)">
                                        @endif
                                    @elseif($ctype === 'image')
                                        {{-- Image-only question --}}
                                        @if($q->attachment_path)
                                            <img src="{{ asset('storage/' . $q->attachment_path) }}"
                                                 loading="lazy"
                                                 class="q-thumb"
                                                 alt=""
                                                 onclick="showImgLightbox(this.src)">
                                        @else
                                            <span class="text-muted small"><i class="la la-image"></i> @lang('questions.no_image')</span>
                                        @endif
                                    @elseif($ctype === 'mixed')
                                        {{-- Mixed: text + image badge --}}
                                        {{ \Illuminate\Support\Str::limit(strip_tags($q->body_ar), 100) }}
                                        @if($q->attachment_path)
                                            <span class="badge badge-info ms-1" style="font-size:10px;"><i class="la la-image"></i> @lang('questions.has_image')</span>
                                        @endif
                                    @else
                                        {{-- Text: plain excerpt --}}
                                        {{ \Illuminate\Support\Str::limit(strip_tags($q->body_ar), 140) }}
                                    @endif
                                </div>
                            </td>

                            {{-- Content type --}}
                            <td><span class="badge badge-ctype">@lang('questions.content_type.'.$ctype)</span></td>

                            {{-- Difficulty --}}
                            <td>
                                @if($q->difficulty)
                                    <span class="badge badge-diff-{{ $q->difficulty }}">@lang('questions.difficulty.'.$q->difficulty)</span>
                                @else
                                    —
                                @endif
                            </td>

                            {{-- Status --}}
                            <td><span class="badge badge-status-{{ $q->status }}">@lang('questions.status.'.$q->status)</span></td>

                            {{-- Points --}}
                            <td>{{ rtrim(rtrim(number_format((float)$q->points, 2), '0'), '.') }}</td>

                            {{-- Lesson --}}
                            <td>
                                <small class="text-muted">{{ optional($q->lesson)->name_ar ?? '—' }}</small>
                            </td>

                            {{-- Creator --}}
                            <td>
                                <small class="text-muted">{{ optional($q->creator)->name ?? '—' }}</small>
                            </td>

                            {{-- Date --}}
                            <td>
                                <small>{{ $q->created_at?->format('Y-m-d') }}</small>
                            </td>

                            {{-- Actions --}}
                            <td class="q-actions">
                                <button type="button" class="btn btn-sm btn-outline-info preview-btn"
                                        title="@lang('questions.view_actions.preview')"
                                        data-toggle="modal" data-target="#previewModal"
                                        data-bs-toggle="modal" data-bs-target="#previewModal"
                                        data-url="{{ route('admin.question-banks.questions.preview', [$bank->id, $q->id]) }}">
                                    <i class="la la-eye"></i>
                                </button>
                                <a class="btn btn-sm btn-outline-secondary"
                                   href="{{ route('admin.question-banks.questions.edit', [$bank->id, $q->id]) }}"
                                   title="@lang('questions.view_actions.edit')">
                                    <i class="la la-pen"></i>
                                </a>
                                <form action="{{ route('admin.question-banks.questions.duplicate', [$bank->id, $q->id]) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-primary" title="@lang('questions.view_actions.duplicate')">
                                        <i class="la la-copy"></i>
                                    </button>
                                </form>
                                <form action="{{ route('admin.question-banks.questions.destroy', [$bank->id, $q->id]) }}" method="POST" class="d-inline js-delete-form">
                                    @csrf @method('DELETE')
                                    <button type="button" class="btn btn-sm btn-outline-danger js-delete-btn" title="@lang('questions.view_actions.delete')"
                                            data-confirm="@lang('questions.confirm.delete')">
                                        <i class="la la-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="12" class="q-empty">
                            <div class="icon"><i class="la la-question-circle"></i></div>
                            <div>@lang('questions.empty')</div>
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $questions->links() }}</div>
    </div>
</div>

{{-- Full-image lightbox --}}
<div id="imgLightbox" onclick="this.classList.remove('active')">
    <img id="imgLightboxImg" src="" alt="">
</div>

<!-- Preview modal -->
<div class="modal fade modal-preview" id="previewModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">@lang('questions.preview_title')</h5>
                <button type="button" class="close btn-close" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="previewBody">
                <div class="text-center py-4 text-muted"><i class="la la-spinner la-spin la-2x"></i></div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline-secondary btn-sm" data-dismiss="modal" data-bs-dismiss="modal">@lang('questions.preview.close')</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
/* ── lightbox ── */
function showImgLightbox(src) {
    var lb = document.getElementById('imgLightbox');
    var img = document.getElementById('imgLightboxImg');
    if (!lb || !img) return;
    img.src = src;
    lb.classList.add('active');
}

/* ── preview modal ── */
document.addEventListener('DOMContentLoaded', function () {
    var modalEl = document.getElementById('previewModal');
    var body    = document.getElementById('previewBody');
    if (!modalEl || !body) return;

    function showLoader() {
        body.replaceChildren();
        var loader = document.createElement('div');
        loader.className = 'text-center py-4 text-muted';
        loader.innerHTML = '<i class="la la-spinner la-spin la-2x"></i>';
        body.appendChild(loader);
    }

    function showError() {
        body.replaceChildren();
        var err = document.createElement('div');
        err.className = 'text-center text-danger py-4';
        err.textContent = '!';
        body.appendChild(err);
    }

    function loadPreview(url) {
        showLoader();
        fetch(url, { headers: { 'Accept': 'text/html' }, credentials: 'same-origin' })
            .then(function (r) { return r.text(); })
            .then(function (html) {
                var parser = new DOMParser();
                var doc = parser.parseFromString('<div>' + html + '</div>', 'text/html');
                var wrapper = doc.body.firstChild;
                body.replaceChildren();
                if (wrapper) {
                    while (wrapper.firstChild) { body.appendChild(wrapper.firstChild); }
                }
            })
            .catch(showError);
    }

    function openModal() {
        if (window.jQuery && jQuery(modalEl).modal) {
            jQuery(modalEl).modal('show');
        } else if (window.bootstrap && bootstrap.Modal) {
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
        } else {
            modalEl.style.display = 'block';
            modalEl.classList.add('show');
        }
    }

    document.querySelectorAll('.preview-btn').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            var url = btn.getAttribute('data-url');
            if (!url) return;
            loadPreview(url);
            openModal();
        });
    });

    /* ── SweetAlert delete confirms ── */
    document.querySelectorAll('.js-delete-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var msg  = btn.getAttribute('data-confirm') || 'تأكيد الحذف؟';
            var form = btn.closest('.js-delete-form');
            if (!form) return;
            if (window.vcConfirm) {
                window.vcConfirm({ title: msg }).then(function (r) {
                    if (r.isConfirmed) { form.submit(); }
                });
            } else if (confirm(msg)) {
                form.submit();
            }
        });
    });
});
</script>
@endpush
@endsection
