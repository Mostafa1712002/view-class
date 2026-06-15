@extends('layouts.app')

@section('title', __('evaluation_items.items.page_title'))
@section('body_class','theme-light')

@push('styles')
<style>
    body.theme-light .ev-add-btn { background:linear-gradient(135deg,var(--gold-200),var(--gold-500))!important; color:#fff!important; border:none; padding:.55rem 1rem; border-radius:10px; font-weight:600; box-shadow:0 4px 14px rgba(207,160,70,.25); }
    body.theme-light .ev-add-btn:hover { transform:translateY(-1px); }
    body.theme-light .ev-weight-bar { height:14px; border-radius:999px; background:#f1f5f9; overflow:hidden; }
    body.theme-light .ev-weight-bar > span { display:block; height:100%; background:linear-gradient(90deg,var(--gold-200),var(--gold-500)); transition:width .3s; }
    body.theme-light .ev-weight-bar.over > span { background:linear-gradient(90deg,#f87171,#b91c1c); }
    body.theme-light .ev-meta { font-size:.78rem; color:#64748b; font-weight:600; }
    body.theme-light .ev-meta b { color:var(--gold-500); font-size:1rem; }
    body.theme-light .ev-pill { display:inline-flex; align-items:center; gap:.3rem; padding:.15rem .55rem; border-radius:999px; font-size:.72rem; font-weight:600; }
    body.theme-light .ev-pill.active { background:#ecfdf5; color:#047857; }
    body.theme-light .ev-pill.disabled { background:#f1f5f9; color:#64748b; }
    body.theme-light .ev-drag { cursor:grab; color:#cbd5e1; }
    body.theme-light .ev-empty { padding:3rem 1rem; text-align:center; }
    body.theme-light .ev-empty .icon-wrap { width:72px; height:72px; border-radius:18px; margin:0 auto 1rem; background:linear-gradient(135deg,#fff6dd,#fde8ad); color:var(--gold-500); font-size:1.8rem; display:inline-flex; align-items:center; justify-content:center; }
    body.theme-light tr.row-disabled { opacity:.6; }
</style>
@endpush

@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-8 col-12 mb-2">
        <h2 class="content-header-title mb-0">@lang('evaluation_items.items.page_title') — {{ $form->title }}</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.evaluations.index') }}">@lang('evaluation.forms.page_title')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.evaluations.edit', $form->id) }}">{{ $form->title }}</a></li>
                <li class="breadcrumb-item active">@lang('evaluation_items.items.page_title')</li>
            </ol>
        </div>
    </div>
    <div class="content-header-right col-md-4 col-12 text-end">
        <a href="{{ route('admin.evaluations.edit', $form->id) }}" class="btn btn-outline-secondary"><x-svg-icon name="arrow-right" :size="16" /> @lang('evaluation_items.items.back_to_form')</a>
        @if ($form->isEditable())
            <button type="button" class="btn ev-add-btn" id="ev-add-item"><x-svg-icon name="plus-lg" :size="16" /> @lang('evaluation_items.items.add')</button>
        @endif
    </div>
</div>

<div class="content-body">
    @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    @if ($errors->any())
        <div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif
    @if (!$form->isEditable())
        <div class="alert alert-warning"><x-svg-icon name="lock-fill" :size="16" class="ic-warn me-1" /> @lang('evaluation_items.messages.form_locked')</div>
    @endif

    {{-- Weight total / remaining (weighted types only) --}}
    @if ($isWeighted)
        @php $remaining = round(100 - $weightTotal, 2); $over = $weightTotal > 100; @endphp
        <div class="card p-3 mb-3">
            <div class="d-flex justify-content-between mb-2">
                <span class="ev-meta">@lang('evaluation_items.items.weight_total'): <b id="ev-weight-total">{{ $weightTotal }}</b>% <span class="text-muted">@lang('evaluation_items.items.of_100')</span></span>
                <span class="ev-meta">@lang('evaluation_items.items.weight_remaining'): <b id="ev-weight-remaining">{{ max(0, $remaining) }}</b>%</span>
            </div>
            <div class="ev-weight-bar {{ $over ? 'over' : '' }}"><span style="width: {{ min(100, max(0, $weightTotal)) }}%"></span></div>
        </div>
    @endif

    <div class="card">
        @if ($items->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="width:30px;"></th>
                            <th>@lang('evaluation_items.items.columns.name')</th>
                            <th style="width:130px;">@lang('evaluation_items.items.columns.responsible_role')</th>
                            <th style="width:110px;">@lang('evaluation_items.items.columns.item_type')</th>
                            @if ($isWeighted)<th style="width:90px;">@lang('evaluation_items.items.columns.weight')</th>@endif
                            <th style="width:90px;">@lang('evaluation_items.items.columns.max_score')</th>
                            <th style="width:80px;">@lang('evaluation_items.items.columns.required')</th>
                            <th style="width:90px;">@lang('evaluation_items.items.columns.indicators')</th>
                            <th style="width:90px;">@lang('evaluation_items.items.columns.status')</th>
                            <th class="text-end" style="width:90px;">@lang('evaluation_items.items.columns.actions')</th>
                        </tr>
                    </thead>
                    <tbody id="ev-items-body">
                        @foreach ($items as $item)
                            <tr data-id="{{ $item->id }}" class="{{ $item->status === 'disabled' ? 'row-disabled' : '' }}">
                                <td>
                                    @if ($form->isEditable())
                                        <div class="d-flex flex-column" style="gap:2px;">
                                            <button type="button" class="btn btn-sm p-0 ev-move" data-dir="up" title="↑" style="line-height:1;"><x-svg-icon name="chevron-up" :size="16" class="ic-muted" /></button>
                                            <button type="button" class="btn btn-sm p-0 ev-move" data-dir="down" title="↓" style="line-height:1;"><x-svg-icon name="chevron-down" :size="16" class="ic-muted" /></button>
                                        </div>
                                    @endif
                                </td>
                                <td class="fw-bold">
                                    {{ $item->name }}
                                    @if ($item->description)<div class="text-muted small fw-normal">{{ \Illuminate\Support\Str::limit($item->description, 80) }}</div>@endif
                                </td>
                                <td>
                                    @if ($item->responsible_role)
                                        <span class="badge badge-light-secondary">{{ $item->responsible_role }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($item->item_type && $item->item_type !== 'manual')
                                        <span class="badge badge-light-info">@lang('evaluation_items.item_types.'.$item->item_type)</span>
                                    @else
                                        <span class="text-muted small">@lang('evaluation_items.item_types.manual')</span>
                                    @endif
                                </td>
                                @if ($isWeighted)<td>{{ rtrim(rtrim(number_format((float)$item->weight, 2), '0'), '.') }}%</td>@endif
                                <td>{{ rtrim(rtrim(number_format((float)$item->max_score, 2), '0'), '.') }}</td>
                                <td>@if ($item->is_required)<x-svg-icon name="check-circle-fill" :size="16" class="ic-success" />@else<x-svg-icon name="dash" :size="16" class="ic-muted" />@endif</td>
                                <td>
                                    <a href="{{ route('admin.evaluations.indicators.index', [$form->id, $item->id]) }}" class="btn btn-sm btn-outline-secondary">
                                        <x-svg-icon name="list-ul" :size="16" /> {{ $item->indicators_count }}
                                    </a>
                                </td>
                                <td><span class="ev-pill {{ $item->status }}">@lang('evaluation_items.status.'.$item->status)</span></td>
                                <td class="text-end">
                                    <div class="dropdown">
                                        <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-toggle="dropdown" data-bs-toggle="dropdown" aria-expanded="false"><x-svg-icon name="three-dots-vertical" :size="16" /></button>
                                        <div class="dropdown-menu dropdown-menu-end">
                                            @if ($form->isEditable())
                                                <button type="button" class="dropdown-item ev-edit-item"
                                                    data-id="{{ $item->id }}"
                                                    data-name="{{ $item->name }}"
                                                    data-description="{{ $item->description }}"
                                                    data-weight="{{ (float)$item->weight }}"
                                                    data-max_score="{{ (float)$item->max_score }}"
                                                    data-is_required="{{ $item->is_required ? 1 : 0 }}"
                                                    data-needs_evidence="{{ $item->needs_evidence ? 1 : 0 }}"
                                                    data-evidence_required="{{ $item->evidence_required ? 1 : 0 }}"
                                                    data-allow_note="{{ $item->allow_note ? 1 : 0 }}"
                                                    data-visible_to_evaluator_only="{{ $item->visible_to_evaluator_only ? 1 : 0 }}"
                                                    data-visible_to_subject_after_result="{{ $item->visible_to_subject_after_result ? 1 : 0 }}"
                                                    data-status="{{ $item->status }}"
                                                    data-responsible_role="{{ $item->responsible_role }}"
                                                    data-item_type="{{ $item->item_type ?? 'manual' }}"
                                                    data-calc_method="{{ $item->calc_method ?? 'manual' }}"
                                                    data-evidence_needs_approval="{{ $item->evidence_needs_approval ? 1 : 0 }}"
                                                    data-editable_after_review="{{ $item->editable_after_review ? 1 : 0 }}"
                                                    data-editable_after_approval="{{ $item->editable_after_approval ? 1 : 0 }}"
                                                    data-min_percentage="{{ $item->min_percentage }}"
                                                    data-internal_notes="{{ $item->internal_notes }}">
                                                    <x-svg-icon name="pencil-square" :size="16" class="ic-gold me-1" /> @lang('evaluation_items.actions.edit')
                                                </button>
                                            @endif
                                            <a class="dropdown-item" href="{{ route('admin.evaluations.indicators.index', [$form->id, $item->id]) }}"><x-svg-icon name="list-ul" :size="16" class="ic-navy me-1" /> @lang('evaluation_items.items.indicators')</a>
                                            <form action="{{ route('admin.evaluations.items.toggle', [$form->id, $item->id]) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="dropdown-item">
                                                    <x-svg-icon :name="$item->status === 'active' ? 'eye-slash' : 'eye-fill'" :size="16" class="{{ $item->status === 'active' ? 'ic-muted' : 'ic-info' }} me-1" />
                                                    {{ $item->status === 'active' ? __('evaluation_items.actions.disable') : __('evaluation_items.actions.enable') }}
                                                </button>
                                            </form>
                                            @if ($form->isEditable())
                                                <div class="dropdown-divider"></div>
                                                <form action="{{ route('admin.evaluations.items.destroy', [$form->id, $item->id]) }}" method="POST" onsubmit="return confirm('@lang('evaluation_items.messages.delete_confirm')')">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="dropdown-item text-danger"><x-svg-icon name="trash3-fill" :size="16" class="ic-danger me-1" /> @lang('evaluation_items.actions.delete')</button>
                                                </form>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="ev-empty">
                <span class="icon-wrap"><x-svg-icon name="list-ol" :size="40" class="ic-eval" /></span>
                <h5 class="mb-1">@lang('evaluation_items.items.none')</h5>
                <p class="text-muted">@lang('evaluation_items.items.none_hint')</p>
            </div>
        @endif
    </div>
</div>

{{-- Add / Edit modal --}}
<div class="modal fade" id="ev-item-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form method="POST" id="ev-item-form" action="{{ route('admin.evaluations.items.store', $form->id) }}">
                @csrf
                <input type="hidden" name="_method" value="POST" id="ev-item-method">
                <div class="modal-header">
                    <h5 class="modal-title" id="ev-item-modal-title">@lang('evaluation_items.items.add')</h5>
                    <button type="button" class="close" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-8 col-12 mb-3">
                            <label class="form-label">@lang('evaluation_items.items.fields.name') <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="f-name" class="form-control" required maxlength="255">
                        </div>
                        @if ($isWeighted)
                            <div class="col-md-4 col-12 mb-3">
                                <label class="form-label">@lang('evaluation_items.items.fields.weight')</label>
                                <input type="number" name="weight" id="f-weight" class="form-control" min="0" max="100" step="0.01" value="0">
                            </div>
                        @endif
                        <div class="col-12 mb-3">
                            <label class="form-label">@lang('evaluation_items.items.fields.description')</label>
                            <textarea name="description" id="f-description" rows="2" class="form-control"></textarea>
                        </div>
                        <div class="col-md-4 col-12 mb-3">
                            <label class="form-label">@lang('evaluation_items.items.fields.max_score')</label>
                            <input type="number" name="max_score" id="f-max_score" class="form-control" min="0" step="0.01" value="100">
                        </div>
                        <div class="col-md-4 col-12 mb-3">
                            <label class="form-label">@lang('evaluation_items.items.fields.status')</label>
                            <select name="status" id="f-status" class="form-control">
                                <option value="active">@lang('evaluation_items.status.active')</option>
                                <option value="disabled">@lang('evaluation_items.status.disabled')</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        @foreach (['is_required','needs_evidence','evidence_required','allow_note','visible_to_evaluator_only','visible_to_subject_after_result'] as $flag)
                            <div class="col-md-4 col-6 mb-2">
                                <div class="form-check">
                                    <input type="hidden" name="{{ $flag }}" value="0">
                                    <input type="checkbox" name="{{ $flag }}" value="1" id="f-{{ $flag }}" class="form-check-input">
                                    <label class="form-check-label" for="f-{{ $flag }}">@lang('evaluation_items.items.fields.'.$flag)</label>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Phase A (v2) — Advanced config --}}
                    <hr class="my-3">
                    <p class="text-muted small mb-2"><x-svg-icon name="gear-fill" :size="16" class="ic-muted me-1" /> @lang('evaluation_items.items.advanced_config')</p>
                    <div class="row">
                        <div class="col-md-4 col-12 mb-3">
                            <label class="form-label">@lang('evaluation_items.items.fields.responsible_role')</label>
                            <input type="text" name="responsible_role" id="f-responsible_role" class="form-control" maxlength="40"
                                   placeholder="@lang('evaluation_items.items.fields.responsible_role_placeholder')">
                        </div>
                        <div class="col-md-4 col-12 mb-3">
                            <label class="form-label">@lang('evaluation_items.items.fields.item_type')</label>
                            <select name="item_type" id="f-item_type" class="form-control">
                                <option value="manual">@lang('evaluation_items.item_types.manual')</option>
                                <option value="auto">@lang('evaluation_items.item_types.auto')</option>
                                <option value="evidence_only">@lang('evaluation_items.item_types.evidence_only')</option>
                                <option value="mixed">@lang('evaluation_items.item_types.mixed')</option>
                            </select>
                        </div>
                        <div class="col-md-4 col-12 mb-3">
                            <label class="form-label">@lang('evaluation_items.items.fields.calc_method')</label>
                            <select name="calc_method" id="f-calc_method" class="form-control">
                                <option value="manual">@lang('evaluation_items.calc_methods.manual')</option>
                                <option value="auto_platform">@lang('evaluation_items.calc_methods.auto_platform')</option>
                                <option value="after_evidence">@lang('evaluation_items.calc_methods.after_evidence')</option>
                                <option value="external">@lang('evaluation_items.calc_methods.external')</option>
                            </select>
                        </div>
                        <div class="col-md-4 col-12 mb-3">
                            <label class="form-label">@lang('evaluation_items.items.fields.min_percentage')</label>
                            <input type="number" name="min_percentage" id="f-min_percentage" class="form-control" min="0" max="100" step="0.01"
                                   placeholder="@lang('evaluation_items.items.fields.min_percentage_placeholder')">
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label">@lang('evaluation_items.items.fields.internal_notes')</label>
                            <textarea name="internal_notes" id="f-internal_notes" rows="2" class="form-control"
                                      placeholder="@lang('evaluation_items.items.fields.internal_notes_placeholder')"></textarea>
                        </div>
                    </div>
                    <div class="row">
                        @foreach (['evidence_needs_approval','editable_after_review','editable_after_approval'] as $flag)
                            <div class="col-md-4 col-6 mb-2">
                                <div class="form-check">
                                    <input type="hidden" name="{{ $flag }}" value="0">
                                    <input type="checkbox" name="{{ $flag }}" value="1" id="f-{{ $flag }}" class="form-check-input">
                                    <label class="form-check-label" for="f-{{ $flag }}">@lang('evaluation_items.items.fields.'.$flag)</label>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-dismiss="modal" data-bs-dismiss="modal">@lang('evaluation_items.actions.cancel')</button>
                    <button type="submit" class="btn ev-add-btn"><x-svg-icon name="save" :size="16" /> @lang('evaluation_items.actions.save')</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
jQuery(function ($) {
    var storeUrl = @json(route('admin.evaluations.items.store', $form->id));
    var updateBase = @json(url('admin/evaluations/'.$form->id.'/items'));
    var reorderUrl = @json(route('admin.evaluations.items.reorder', $form->id));
    var csrf = @json(csrf_token());
    var flags = ['is_required','needs_evidence','evidence_required','allow_note','visible_to_evaluator_only','visible_to_subject_after_result',
                 'evidence_needs_approval','editable_after_review','editable_after_approval'];

    function openModal() {
        if (window.bootstrap) { new bootstrap.Modal(document.getElementById('ev-item-modal')).show(); }
        else { $('#ev-item-modal').modal('show'); }
    }

    $('#ev-add-item').on('click', function () {
        $('#ev-item-modal-title').text(@json(__('evaluation_items.items.add')));
        $('#ev-item-form')[0].reset();
        $('#ev-item-form').attr('action', storeUrl);
        $('#ev-item-method').val('POST');
        $('#f-max_score').val(100);
        @if($isWeighted) $('#f-weight').val(0); @endif
        $('#f-item_type').val('manual');
        $('#f-calc_method').val('manual');
        $('#f-min_percentage').val('');
        openModal();
    });

    $('.ev-edit-item').on('click', function () {
        var d = $(this).data();
        $('#ev-item-modal-title').text(@json(__('evaluation_items.items.edit')));
        $('#ev-item-form').attr('action', updateBase + '/' + d.id);
        $('#ev-item-method').val('PUT');
        $('#f-name').val(d.name);
        $('#f-description').val(d.description || '');
        @if($isWeighted) $('#f-weight').val(d.weight); @endif
        $('#f-max_score').val(d.max_score);
        $('#f-status').val(d.status);
        flags.forEach(function (f) { $('#f-' + f).prop('checked', String(d[f]) === '1'); });
        // Phase A (v2) advanced fields
        $('#f-responsible_role').val(d.responsible_role || '');
        $('#f-item_type').val(d.item_type || 'manual');
        $('#f-calc_method').val(d.calc_method || 'manual');
        $('#f-min_percentage').val(d.min_percentage || '');
        $('#f-internal_notes').val(d.internal_notes || '');
        openModal();
    });

    function persistOrder() {
        var ids = $('#ev-items-body tr').map(function () { return $(this).data('id'); }).get();
        $.post(reorderUrl, { _token: csrf, items: ids });
    }

    $('#ev-items-body').on('click', '.ev-move', function () {
        var row = $(this).closest('tr');
        if ($(this).data('dir') === 'up') {
            var prev = row.prev('tr');
            if (prev.length) { row.insertBefore(prev); persistOrder(); }
        } else {
            var next = row.next('tr');
            if (next.length) { row.insertAfter(next); persistOrder(); }
        }
    });
});
</script>
@endpush
@endsection
