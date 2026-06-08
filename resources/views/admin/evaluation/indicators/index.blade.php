@extends('layouts.app')

@section('title', __('evaluation_items.indicators.page_title'))
@section('body_class','theme-light')

@push('styles')
<style>
    body.theme-light .ev-add-btn { background:linear-gradient(135deg,var(--gold-200),var(--gold-500))!important; color:#fff!important; border:none; padding:.55rem 1rem; border-radius:10px; font-weight:600; box-shadow:0 4px 14px rgba(207,160,70,.25); }
    body.theme-light .ev-add-btn:hover { transform:translateY(-1px); }
    body.theme-light .ev-pill { display:inline-flex; align-items:center; gap:.3rem; padding:.15rem .55rem; border-radius:999px; font-size:.72rem; font-weight:600; }
    body.theme-light .ev-pill.active { background:#ecfdf5; color:#047857; }
    body.theme-light .ev-pill.disabled { background:#f1f5f9; color:#64748b; }
    body.theme-light .ev-lvl-pill { background:#fff6dd; color:var(--gold-500); padding:.15rem .55rem; border-radius:999px; font-size:.72rem; font-weight:600; }
    body.theme-light .ev-empty { padding:3rem 1rem; text-align:center; }
    body.theme-light .ev-empty .icon-wrap { width:72px; height:72px; border-radius:18px; margin:0 auto 1rem; background:linear-gradient(135deg,#fff6dd,#fde8ad); color:var(--gold-500); font-size:1.8rem; display:inline-flex; align-items:center; justify-content:center; }
    body.theme-light tr.row-disabled { opacity:.6; }
</style>
@endpush

@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-8 col-12 mb-2">
        <h2 class="content-header-title mb-0">@lang('evaluation_items.indicators.page_title') — {{ $item->name }}</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.evaluations.index') }}">@lang('evaluation.forms.page_title')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.evaluations.items.index', $form->id) }}">{{ $form->title }}</a></li>
                <li class="breadcrumb-item active">@lang('evaluation_items.indicators.page_title')</li>
            </ol>
        </div>
    </div>
    <div class="content-header-right col-md-4 col-12 text-end">
        <a href="{{ route('admin.evaluations.items.index', $form->id) }}" class="btn btn-outline-secondary"><i class="la la-arrow-right"></i> @lang('evaluation_items.indicators.back_to_items')</a>
        @if ($form->isEditable())
            <button type="button" class="btn ev-add-btn" id="ev-add-ind"><i class="la la-plus"></i> @lang('evaluation_items.indicators.add')</button>
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
        <div class="alert alert-warning"><i class="la la-lock"></i> @lang('evaluation_items.messages.form_locked')</div>
    @endif

    <div class="card">
        @if ($indicators->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="width:30px;"></th>
                            <th>@lang('evaluation_items.indicators.columns.text')</th>
                            @if ($isRubric)<th style="width:110px;">@lang('evaluation_items.indicators.columns.level')</th>@endif
                            <th style="width:80px;">@lang('evaluation_items.indicators.columns.required')</th>
                            <th style="width:80px;">@lang('evaluation_items.indicators.columns.evidence')</th>
                            <th style="width:90px;">@lang('evaluation_items.indicators.columns.status')</th>
                            <th class="text-end" style="width:90px;">@lang('evaluation_items.indicators.columns.actions')</th>
                        </tr>
                    </thead>
                    <tbody id="ev-ind-body">
                        @foreach ($indicators as $ind)
                            <tr data-id="{{ $ind->id }}" class="{{ $ind->status === 'disabled' ? 'row-disabled' : '' }}">
                                <td>
                                    @if ($form->isEditable())
                                        <div class="d-flex flex-column" style="gap:2px;">
                                            <button type="button" class="btn btn-sm p-0 ev-move" data-dir="up" style="line-height:1;"><i class="la la-angle-up"></i></button>
                                            <button type="button" class="btn btn-sm p-0 ev-move" data-dir="down" style="line-height:1;"><i class="la la-angle-down"></i></button>
                                        </div>
                                    @endif
                                </td>
                                <td class="fw-bold">
                                    {{ $ind->text }}
                                    @if ($ind->description)<div class="text-muted small fw-normal">{{ \Illuminate\Support\Str::limit($ind->description, 80) }}</div>@endif
                                </td>
                                @if ($isRubric)<td>@if ($ind->level)<span class="ev-lvl-pill">{{ $ind->level->label }}</span>@else<span class="text-muted small">—</span>@endif</td>@endif
                                <td>@if ($ind->is_required)<i class="la la-check-circle text-success"></i>@else<i class="la la-minus text-muted"></i>@endif</td>
                                <td>@if ($ind->needs_evidence)<i class="la la-paperclip text-success"></i>@else<i class="la la-minus text-muted"></i>@endif</td>
                                <td><span class="ev-pill {{ $ind->status }}">@lang('evaluation_items.status.'.$ind->status)</span></td>
                                <td class="text-end">
                                    <div class="dropdown">
                                        <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-toggle="dropdown" data-bs-toggle="dropdown" aria-expanded="false"><i class="la la-ellipsis-h"></i></button>
                                        <div class="dropdown-menu dropdown-menu-end">
                                            @if ($form->isEditable())
                                                <button type="button" class="dropdown-item ev-edit-ind"
                                                    data-id="{{ $ind->id }}"
                                                    data-text="{{ $ind->text }}"
                                                    data-description="{{ $ind->description }}"
                                                    data-level_id="{{ $ind->level_id }}"
                                                    data-is_required="{{ $ind->is_required ? 1 : 0 }}"
                                                    data-needs_note="{{ $ind->needs_note ? 1 : 0 }}"
                                                    data-needs_evidence="{{ $ind->needs_evidence ? 1 : 0 }}"
                                                    data-evidence_required="{{ $ind->evidence_required ? 1 : 0 }}"
                                                    data-status="{{ $ind->status }}">
                                                    <i class="la la-pen"></i> @lang('evaluation_items.actions.edit')
                                                </button>
                                            @endif
                                            <form action="{{ route('admin.evaluations.indicators.toggle', [$form->id, $item->id, $ind->id]) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="dropdown-item">
                                                    <i class="la {{ $ind->status === 'active' ? 'la-eye-slash' : 'la-eye' }}"></i>
                                                    {{ $ind->status === 'active' ? __('evaluation_items.actions.disable') : __('evaluation_items.actions.enable') }}
                                                </button>
                                            </form>
                                            @if ($form->isEditable())
                                                <div class="dropdown-divider"></div>
                                                <form action="{{ route('admin.evaluations.indicators.destroy', [$form->id, $item->id, $ind->id]) }}" method="POST" onsubmit="return confirm('@lang('evaluation_items.messages.delete_confirm')')">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="dropdown-item text-danger"><i class="la la-trash"></i> @lang('evaluation_items.actions.delete')</button>
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
                <span class="icon-wrap"><i class="la la-list"></i></span>
                <h5 class="mb-1">@lang('evaluation_items.indicators.none')</h5>
                <p class="text-muted">@lang('evaluation_items.indicators.none_hint')</p>
            </div>
        @endif
    </div>
</div>

{{-- Add / Edit modal --}}
<div class="modal fade" id="ev-ind-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form method="POST" id="ev-ind-form" action="{{ route('admin.evaluations.indicators.store', [$form->id, $item->id]) }}">
                @csrf
                <input type="hidden" name="_method" value="POST" id="ev-ind-method">
                <div class="modal-header">
                    <h5 class="modal-title" id="ev-ind-modal-title">@lang('evaluation_items.indicators.add')</h5>
                    <button type="button" class="close" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-12 mb-3">
                            <label class="form-label">@lang('evaluation_items.indicators.fields.text') <span class="text-danger">*</span></label>
                            <input type="text" name="text" id="i-text" class="form-control" required maxlength="1000">
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label">@lang('evaluation_items.indicators.fields.description')</label>
                            <textarea name="description" id="i-description" rows="2" class="form-control"></textarea>
                        </div>
                        @if ($isRubric)
                            <div class="col-md-6 col-12 mb-3">
                                <label class="form-label">@lang('evaluation_items.indicators.fields.level_id') <span class="text-danger">*</span></label>
                                <select name="level_id" id="i-level_id" class="form-control select2">
                                    <option value="">@lang('evaluation_items.indicators.fields.level_placeholder')</option>
                                    @foreach ($levels as $lvl)
                                        <option value="{{ $lvl->id }}">{{ $lvl->label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @else
                            <div class="col-12 mb-2"><small class="text-muted"><i class="la la-info-circle"></i> @lang('evaluation_items.indicators.level_only_rubric')</small></div>
                        @endif
                        <div class="col-md-6 col-12 mb-3">
                            <label class="form-label">@lang('evaluation_items.indicators.fields.status')</label>
                            <select name="status" id="i-status" class="form-control">
                                <option value="active">@lang('evaluation_items.status.active')</option>
                                <option value="disabled">@lang('evaluation_items.status.disabled')</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        @foreach (['is_required','needs_note','needs_evidence','evidence_required'] as $flag)
                            <div class="col-md-3 col-6 mb-2">
                                <div class="form-check">
                                    <input type="hidden" name="{{ $flag }}" value="0">
                                    <input type="checkbox" name="{{ $flag }}" value="1" id="i-{{ $flag }}" class="form-check-input">
                                    <label class="form-check-label" for="i-{{ $flag }}">@lang('evaluation_items.indicators.fields.'.$flag)</label>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-dismiss="modal" data-bs-dismiss="modal">@lang('evaluation_items.actions.cancel')</button>
                    <button type="submit" class="btn ev-add-btn"><i class="la la-save"></i> @lang('evaluation_items.actions.save')</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
jQuery(function ($) {
    var storeUrl = @json(route('admin.evaluations.indicators.store', [$form->id, $item->id]));
    var updateBase = @json(url('admin/evaluations/'.$form->id.'/items/'.$item->id.'/indicators'));
    var reorderUrl = @json(route('admin.evaluations.indicators.reorder', [$form->id, $item->id]));
    var csrf = @json(csrf_token());
    var flags = ['is_required','needs_note','needs_evidence','evidence_required'];
    var isRubric = @json($isRubric);

    function openModal() {
        if (window.bootstrap) { new bootstrap.Modal(document.getElementById('ev-ind-modal')).show(); }
        else { $('#ev-ind-modal').modal('show'); }
    }

    $('#ev-add-ind').on('click', function () {
        $('#ev-ind-modal-title').text(@json(__('evaluation_items.indicators.add')));
        $('#ev-ind-form')[0].reset();
        $('#ev-ind-form').attr('action', storeUrl);
        $('#ev-ind-method').val('POST');
        if (isRubric) { $('#i-level_id').val('').trigger('change'); }
        openModal();
    });

    $('.ev-edit-ind').on('click', function () {
        var d = $(this).data();
        $('#ev-ind-modal-title').text(@json(__('evaluation_items.indicators.edit')));
        $('#ev-ind-form').attr('action', updateBase + '/' + d.id);
        $('#ev-ind-method').val('PUT');
        $('#i-text').val(d.text);
        $('#i-description').val(d.description || '');
        $('#i-status').val(d.status);
        if (isRubric) { $('#i-level_id').val(d.level_id ? String(d.level_id) : '').trigger('change'); }
        flags.forEach(function (f) { $('#i-' + f).prop('checked', String(d[f]) === '1'); });
        openModal();
    });

    function persistOrder() {
        var ids = $('#ev-ind-body tr').map(function () { return $(this).data('id'); }).get();
        $.post(reorderUrl, { _token: csrf, indicators: ids });
    }

    $('#ev-ind-body').on('click', '.ev-move', function () {
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
