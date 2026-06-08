@extends('layouts.app')

@section('title', __('evaluation.targets.page_title'))
@section('body_class','theme-light')

@push('styles')
<style>
    body.theme-light .ev-add-btn { background:linear-gradient(135deg,var(--gold-200),var(--gold-500))!important; color:#fff!important; border:none; padding:.55rem 1rem; border-radius:10px; font-weight:600; box-shadow:0 4px 14px rgba(207,160,70,.25); }
    body.theme-light .ev-add-btn:hover { transform:translateY(-1px); }
    body.theme-light .filters-card .form-label { font-size:.78rem; color:#64748b; font-weight:600; margin-bottom:.25rem; }
    body.theme-light .ev-meta { font-size:.78rem; color:#64748b; font-weight:600; }
    body.theme-light .ev-pill { display:inline-flex; align-items:center; gap:.3rem; padding:.15rem .55rem; border-radius:999px; font-size:.72rem; font-weight:600; }
    body.theme-light .ev-pill.active { background:#ecfdf5; color:#047857; }
    body.theme-light .ev-pill.inactive { background:#fef2f2; color:#b91c1c; }
    body.theme-light .ev-empty { padding:2.5rem 1rem; text-align:center; color:#94a3b8; }
    body.theme-light .ev-summary-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:.75rem; }
    body.theme-light .ev-summary-grid .box { background:#f8fafc; border-radius:12px; padding:.8rem; text-align:center; }
    body.theme-light .ev-summary-grid .box .n { font-size:1.4rem; font-weight:800; color:var(--gold-500); }
    body.theme-light .ev-summary-grid .box .l { font-size:.74rem; color:#64748b; font-weight:600; }
    body.theme-light .ev-summary-grid .box.warn .n { color:#b91c1c; }
    body.theme-light #ev-candidate-table { max-height:430px; overflow:auto; }
</style>
@endpush

@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-8 col-12 mb-2">
        <h2 class="content-header-title mb-0">@lang('evaluation.targets.page_title') — {{ $form->title }}</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.evaluations.index') }}">@lang('evaluation.forms.page_title')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.evaluations.edit', $form->id) }}">{{ $form->title }}</a></li>
                <li class="breadcrumb-item active">@lang('evaluation.targets.page_title')</li>
            </ol>
        </div>
    </div>
    <div class="content-header-right col-md-4 col-12 text-end">
        <a href="{{ route('admin.evaluations.edit', $form->id) }}" class="btn btn-outline-secondary"><i class="la la-arrow-right"></i> @lang('evaluation.targets.back_to_form')</a>
        <a href="{{ route('admin.evaluations.evaluators.index', $form->id) }}" class="btn btn-outline-secondary"><i class="la la-user-check"></i> @lang('evaluation.form.actions_menu.evaluators')</a>
    </div>
</div>

<div class="content-body">
    @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    @if ($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
    <p class="text-muted">@lang('evaluation.targets.subtitle')</p>

    <form id="ev-targets-form" method="POST" action="{{ route('admin.evaluations.targets.store', $form->id) }}">
        @csrf
        <div class="row">
            {{-- Candidate picker --}}
            <div class="col-lg-7 col-12 mb-3">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span class="fw-bold">@lang('evaluation.targets.candidates.title')</span>
                        <span>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="ev-select-all">@lang('evaluation.targets.candidates.select_all')</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="ev-clear">@lang('evaluation.targets.candidates.clear')</button>
                        </span>
                    </div>
                    {{-- Filters --}}
                    <div class="card-body filters-card border-bottom">
                        <div class="row g-2 align-items-end">
                            @if ($schools->count() > 0)
                            <div class="col-md-4 col-6">
                                <label class="form-label">@lang('evaluation.targets.filters.school')</label>
                                <select name="school_id" class="form-control form-control-sm">
                                    <option value="">@lang('evaluation.targets.filters.all')</option>
                                    @foreach ($schools as $s)<option value="{{ $s->id }}" {{ $filters['school_id'] == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>@endforeach
                                </select>
                            </div>
                            @endif
                            <div class="col-md-4 col-6">
                                <label class="form-label">@lang('evaluation.targets.filters.role')</label>
                                <select name="role" class="form-control form-control-sm">
                                    <option value="">@lang('evaluation.targets.filters.all')</option>
                                    @foreach ($roles as $val => $label)<option value="{{ $val }}" {{ $filters['role'] === $val ? 'selected' : '' }}>{{ $label }}</option>@endforeach
                                </select>
                            </div>
                            <div class="col-md-4 col-6">
                                <label class="form-label">@lang('evaluation.targets.filters.subject')</label>
                                <select name="subject_id" class="form-control form-control-sm">
                                    <option value="">@lang('evaluation.targets.filters.all')</option>
                                    @foreach ($subjects as $sub)<option value="{{ $sub->id }}" {{ $filters['subject_id'] == $sub->id ? 'selected' : '' }}>{{ $sub->name }}</option>@endforeach
                                </select>
                            </div>
                            <div class="col-md-4 col-6">
                                <label class="form-label">@lang('evaluation.targets.filters.section')</label>
                                <select name="section_id" class="form-control form-control-sm">
                                    <option value="">@lang('evaluation.targets.filters.all')</option>
                                    @foreach ($sections as $sec)<option value="{{ $sec->id }}" {{ $filters['section_id'] == $sec->id ? 'selected' : '' }}>{{ $sec->name }}</option>@endforeach
                                </select>
                            </div>
                            <div class="col-md-4 col-6">
                                <label class="form-label">@lang('evaluation.targets.filters.search')</label>
                                <input type="text" name="q" value="{{ $filters['q'] }}" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-4 col-12 d-flex gap-1">
                                <button type="button" class="btn btn-sm ev-add-btn flex-grow-1" id="ev-apply-filters"><i class="la la-filter"></i> @lang('evaluation.targets.filters.show')</button>
                                <a href="{{ route('admin.evaluations.targets.index', $form->id) }}" class="btn btn-sm btn-outline-secondary"><i class="la la-redo"></i></a>
                            </div>
                        </div>
                    </div>
                    <div id="ev-candidate-table">
                        @if ($candidates->count() > 0)
                            <table class="table table-hover table-sm align-middle mb-0">
                                <thead><tr>
                                    <th style="width:34px;"></th>
                                    <th>@lang('evaluation.targets.candidates.columns.name')</th>
                                    <th>@lang('evaluation.targets.candidates.columns.role')</th>
                                    <th>@lang('evaluation.targets.candidates.columns.subjects')</th>
                                    <th>@lang('evaluation.targets.candidates.columns.status')</th>
                                </tr></thead>
                                <tbody>
                                @foreach ($candidates as $c)
                                    <tr>
                                        <td><input type="checkbox" class="form-check-input ev-cand" name="user_ids[]" value="{{ $c['id'] }}"></td>
                                        <td class="fw-bold">{{ $c['name'] }} <span class="text-muted small fw-normal">{{ $c['username'] }}</span></td>
                                        <td><span class="text-muted small">{{ $c['role'] }}</span></td>
                                        <td><span class="text-muted small">{{ $c['subjects'] ?: '—' }}</span></td>
                                        <td><span class="ev-pill {{ $c['inactive'] ? 'inactive' : 'active' }}">{{ $c['inactive'] ? '✗' : '✓' }}</span></td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        @else
                            <div class="ev-empty"><i class="la la-search la-2x d-block mb-2"></i>@lang('evaluation.targets.candidates.none')</div>
                        @endif
                    </div>
                    <div class="card-footer d-flex justify-content-between align-items-center">
                        <span class="ev-meta"><span id="ev-selected-count">0</span> @lang('evaluation.targets.summary.selected')</span>
                        <button type="button" class="btn ev-add-btn" id="ev-review-btn"><i class="la la-clipboard-check"></i> @lang('evaluation.targets.save')</button>
                    </div>
                </div>
            </div>

            {{-- Bulk + current targets --}}
            <div class="col-lg-5 col-12 mb-3">
                <div class="card mb-3">
                    <div class="card-header fw-bold">@lang('evaluation.targets.bulk.title')</div>
                    <div class="card-body">
                        <p class="text-muted small">@lang('evaluation.targets.bulk.hint')</p>
                        <div class="mb-2">
                            <label class="form-label small">@lang('evaluation.targets.bulk.role')</label>
                            <select name="bulk_role" class="form-control form-control-sm">
                                <option value="">—</option>
                                @foreach ($roles as $val => $label)<option value="{{ $val }}">{{ $label }}</option>@endforeach
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small">@lang('evaluation.targets.bulk.subject')</label>
                            <select name="bulk_subject_id" class="form-control form-control-sm">
                                <option value="">—</option>
                                @foreach ($subjects as $sub)<option value="{{ $sub->id }}">{{ $sub->name }}</option>@endforeach
                            </select>
                        </div>
                        @if ($schools->count() > 0)
                        <div class="mb-2">
                            <label class="form-label small">@lang('evaluation.targets.bulk.school')</label>
                            <select name="bulk_school_id" class="form-control form-control-sm">
                                <option value="">—</option>
                                @foreach ($schools as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach
                            </select>
                        </div>
                        @endif
                        <button type="submit" class="btn btn-outline-secondary btn-sm w-100" id="ev-bulk-apply"><i class="la la-layer-group"></i> @lang('evaluation.targets.bulk.apply')</button>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header d-flex justify-content-between fw-bold">
                        <span>@lang('evaluation.targets.current.title')</span>
                        <span class="ev-meta">@lang('evaluation.targets.current.count', ['count' => $targets->count()])</span>
                    </div>
                    @if ($targets->count() > 0)
                        <div class="table-responsive" style="max-height:360px;overflow:auto;">
                            <table class="table table-sm align-middle mb-0">
                                <thead><tr>
                                    <th>@lang('evaluation.targets.current.columns.name')</th>
                                    <th>@lang('evaluation.targets.current.columns.school')</th>
                                    <th class="text-end">@lang('evaluation.targets.current.columns.actions')</th>
                                </tr></thead>
                                <tbody>
                                @foreach ($targets as $t)
                                    <tr>
                                        <td class="fw-bold">{{ $t['name'] }}
                                            @if ($t['after_publish'])<span class="badge bg-warning text-dark">@lang('evaluation.targets.current.added_after')</span>@endif
                                        </td>
                                        <td><span class="text-muted small">{{ $t['school'] ?: '—' }}</span></td>
                                        <td class="text-end">
                                            <form method="POST" action="{{ route('admin.evaluations.targets.destroy', [$form->id, $t['target_id']]) }}" onsubmit="return confirm('@lang('evaluation.targets.current.remove_confirm')')">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="la la-trash"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="ev-empty"><i class="la la-users la-2x d-block mb-2"></i>@lang('evaluation.targets.current.none')</div>
                    @endif
                </div>
            </div>
        </div>
    </form>
</div>

{{-- Pre-save summary modal --}}
<div class="modal fade" id="ev-summary-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">@lang('evaluation.targets.summary.title')</h5>
                <button type="button" class="close" data-dismiss="modal" data-bs-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="ev-summary-grid mb-3">
                    <div class="box"><div class="n" id="sum-selected">0</div><div class="l">@lang('evaluation.targets.summary.selected')</div></div>
                    <div class="box"><div class="n" id="sum-new">0</div><div class="l">@lang('evaluation.targets.summary.new')</div></div>
                    <div class="box warn"><div class="n" id="sum-dups">0</div><div class="l">@lang('evaluation.targets.summary.duplicates')</div></div>
                    <div class="box warn"><div class="n" id="sum-inactive">0</div><div class="l">@lang('evaluation.targets.summary.inactive')</div></div>
                </div>
                <p class="mb-1"><b>@lang('evaluation.targets.summary.schools'):</b> <span id="sum-schools">—</span></p>
                <p class="mb-0"><b>@lang('evaluation.targets.summary.subjects'):</b> <span id="sum-subjects">—</span></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-dismiss="modal" data-bs-dismiss="modal">@lang('evaluation.targets.summary.cancel')</button>
                <button type="button" class="btn ev-add-btn" id="ev-confirm-save"><i class="la la-save"></i> @lang('evaluation.targets.summary.confirm')</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
jQuery(function ($) {
    var summaryUrl = @json(route('admin.evaluations.targets.summary', $form->id));
    var csrf = @json(csrf_token());

    function selectedIds() {
        return $('.ev-cand:checked').map(function () { return parseInt($(this).val(), 10); }).get();
    }
    function refreshCount() { $('#ev-selected-count').text(selectedIds().length); }

    $('.ev-cand').on('change', refreshCount);
    $('#ev-select-all').on('click', function () { $('.ev-cand').prop('checked', true); refreshCount(); });
    $('#ev-clear').on('click', function () { $('.ev-cand').prop('checked', false); refreshCount(); });
    refreshCount();

    // Apply filters = GET reload preserving filter selects only.
    $('#ev-apply-filters').on('click', function () {
        var p = {};
        ['school_id','role','subject_id','section_id','q'].forEach(function (n) {
            var v = $('[name="' + n + '"]').first().val();
            if (v) p[n] = v;
        });
        window.location = @json(route('admin.evaluations.targets.index', $form->id)) + '?' + $.param(p);
    });

    function openModal(id) {
        if (window.bootstrap) { new bootstrap.Modal(document.getElementById(id)).show(); }
        else { $('#' + id).modal('show'); }
    }

    $('#ev-review-btn').on('click', function () {
        var ids = selectedIds();
        if (!ids.length) { alert(@json(__('evaluation.targets.flash.none_selected'))); return; }
        $.post(summaryUrl, { _token: csrf, user_ids: ids }, function (r) {
            if (!r.success) { return; }
            $('#sum-selected').text(r.selected);
            $('#sum-new').text(r.new);
            $('#sum-dups').text(r.duplicates);
            $('#sum-inactive').text(r.inactive);
            $('#sum-schools').text(r.schools.length ? r.schools.join('، ') : '—');
            $('#sum-subjects').text(r.subjects.length ? r.subjects.join('، ') : @json(__('evaluation.targets.summary.none_subjects')));
            openModal('ev-summary-modal');
        }, 'json');
    });

    $('#ev-confirm-save').on('click', function () {
        // Make sure bulk fields don't fire here — submit only the individual selection.
        $('[name="bulk_role"],[name="bulk_subject_id"],[name="bulk_school_id"]').val('');
        $('#ev-targets-form')[0].submit();
    });
});
</script>
@endpush
@endsection
