@extends('layouts.app')

@section('title', __('questions.page_title') . ' — ' . $bank->name_ar)
@section('body_class', 'theme-light')

@php
    $isRtl = app()->getLocale() === 'ar';
    $typesList = ['true_false','mcq','essay','matching','fill_blank','short'];
@endphp

@push('styles')
<style>
    .q-filters .form-label { font-size: 12px; font-weight: 600; color: #475569; }
    .q-filters .form-select, .q-filters .form-control { font-size: 14px; }
    .q-table td { vertical-align: middle; }
    .q-body-preview { max-width: 380px; color: #1e293b; line-height: 1.55; }
    .q-body-preview small { color: #64748b; }
    .badge-type {
        background: rgba(212,175,55,.13); color: #7a5d12; font-weight: 600;
        border: 1px solid rgba(212,175,55,.35); padding: 5px 10px; border-radius: 999px;
    }
    .badge-diff-1 { background:#dcfce7; color:#166534; }
    .badge-diff-2 { background:#fef3c7; color:#92400e; }
    .badge-diff-3 { background:#fee2e2; color:#991b1b; }
    .badge-status-draft          { background:#e2e8f0; color:#475569; }
    .badge-status-pending_review { background:#fef3c7; color:#92400e; }
    .badge-status-approved       { background:#dcfce7; color:#166534; }
    .badge-status-rejected       { background:#fee2e2; color:#991b1b; }
    .badge-status-archived       { background:#fde68a; color:#78350f; }
    /* legacy fallback */
    .badge-status-published      { background:#dcfce7; color:#166534; }
    .add-type-menu .dropdown-item { padding: 10px 16px; font-size: 14px; }
    .q-actions .btn { padding: 4px 8px; }
    .q-bank-title-row { display:flex; align-items:center; gap:14px; flex-wrap:wrap; }
    .q-bank-title-row .subj-chip {
        background:#f1f5f9; color:#0f172a; font-size:12px; padding:4px 10px; border-radius:999px;
    }
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

    <div class="card mb-2">
        <div class="card-body py-2">
            <form action="{{ route('admin.question-banks.questions.index', $bank->id) }}" method="GET" class="q-filters row g-2 align-items-end">
                <div class="col-md-3 col-12">
                    <label class="form-label">@lang('questions.filters.search')</label>
                    <input type="search" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="@lang('questions.filters.search')">
                </div>
                <div class="col-md-2 col-6">
                    <label class="form-label">@lang('questions.filters.type')</label>
                    <select name="type" class="form-select form-select-sm">
                        <option value="">@lang('questions.filters.all')</option>
                        @foreach($typesList as $t)
                            <option value="{{ $t }}" {{ request('type') === $t ? 'selected' : '' }}>@lang('questions.types.'.$t)</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 col-6">
                    <label class="form-label">@lang('questions.filters.difficulty')</label>
                    <select name="difficulty" class="form-select form-select-sm">
                        <option value="">@lang('questions.filters.all')</option>
                        @foreach([1,2,3] as $d)
                            <option value="{{ $d }}" {{ (string)request('difficulty') === (string)$d ? 'selected' : '' }}>@lang('questions.difficulty.'.$d)</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 col-6">
                    <label class="form-label">@lang('questions.filters.lesson')</label>
                    <select name="lesson_id" class="form-select form-select-sm">
                        <option value="">@lang('questions.filters.all')</option>
                        @foreach($lessons as $l)
                            <option value="{{ $l->id }}" {{ (string)request('lesson_id') === (string)$l->id ? 'selected' : '' }}>{{ $l->name_ar }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 col-6">
                    <label class="form-label">@lang('questions.filters.status')</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">@lang('questions.filters.all')</option>
                        @foreach(\App\Models\BankQuestion::STATUSES as $s)
                            <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>@lang('questions.status.'.$s)</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-1 col-12 d-flex gap-1">
                    <button class="btn btn-primary btn-sm flex-fill"><i class="la la-filter"></i></button>
                    <a class="btn btn-outline-secondary btn-sm" href="{{ route('admin.question-banks.questions.index', $bank->id) }}" title="@lang('questions.filters.reset')"><i class="la la-times"></i></a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
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
            {{-- Import buttons — gated same as the rest of this controller (role middleware).
                 TODO #217: replace with granular QB import permission. --}}
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <a href="{{ route('admin.question-banks.questions.import.form', $bank->id) }}"
                   class="btn btn-success btn-sm">
                    <i class="la la-file-excel"></i> @lang('question_import.page_title')
                </a>
                <a href="{{ route('admin.question-banks.questions.import.template', $bank->id) }}"
                   class="btn btn-outline-secondary btn-sm">
                    <i class="la la-download"></i> @lang('question_import.download_template')
                </a>
                <div class="text-muted small">{{ $questions->total() }} @lang('questions.index_title')</div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 q-table">
                <thead class="thead-light">
                    <tr>
                        <th>@lang('questions.columns.lesson')</th>
                        <th>@lang('questions.columns.creator')</th>
                        <th>@lang('questions.columns.type')</th>
                        <th>@lang('questions.columns.body')</th>
                        <th>@lang('questions.columns.points')</th>
                        <th>@lang('questions.columns.difficulty')</th>
                        <th>@lang('questions.columns.status')</th>
                        <th>@lang('questions.columns.created_at')</th>
                        <th>@lang('questions.columns.actions')</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($questions as $q)
                        <tr>
                            <td>{{ optional($q->lesson)->name_ar ?? '—' }}</td>
                            <td>{{ optional($q->creator)->name ?? '—' }}</td>
                            <td><span class="badge badge-type">@lang('questions.types.'.$q->type)</span></td>
                            <td>
                                <div class="q-body-preview">
                                    {{ \Illuminate\Support\Str::limit(strip_tags($q->body_ar), 140) }}
                                </div>
                            </td>
                            <td>{{ rtrim(rtrim(number_format((float)$q->points, 2), '0'), '.') }}</td>
                            <td>
                                @if($q->difficulty)
                                    <span class="badge badge-diff-{{ $q->difficulty }}">@lang('questions.difficulty.'.$q->difficulty)</span>
                                @else
                                    —
                                @endif
                            </td>
                            <td><span class="badge badge-status-{{ $q->status }}">@lang('questions.status.'.$q->status)</span></td>
                            <td>{{ $q->created_at?->format('Y-m-d') }}</td>
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
                                <form action="{{ route('admin.question-banks.questions.destroy', [$bank->id, $q->id]) }}" method="POST" class="d-inline"
                                      onsubmit="return confirm('@lang('questions.confirm.delete')')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="@lang('questions.view_actions.delete')">
                                        <i class="la la-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="q-empty">
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
document.addEventListener('DOMContentLoaded', function () {
    var modalEl = document.getElementById('previewModal');
    var body = document.getElementById('previewBody');
    if (!modalEl || !body) return;

    function showLoader() {
        body.replaceChildren();
        var loader = document.createElement('div');
        loader.className = 'text-center py-4 text-muted';
        loader.textContent = '...';
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
                // Server-rendered, auth-gated, blade-escaped fragment.
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

    // Open modal — works on Bootstrap 4 (jQuery) and Bootstrap 5 (native).
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
});
</script>
@endpush
@endsection
