@extends('layouts.app')

@section('title', __('evaluation.evaluators.page_title'))
@section('body_class','theme-light')

@push('styles')
<style>
    body.theme-light .ev-add-btn { background:linear-gradient(135deg,var(--gold-200),var(--gold-500))!important; color:#fff!important; border:none; padding:.55rem 1rem; border-radius:10px; font-weight:600; box-shadow:0 4px 14px rgba(207,160,70,.25); }
    body.theme-light .ev-add-btn:hover { transform:translateY(-1px); }
    body.theme-light .ev-empty { padding:2.5rem 1rem; text-align:center; color:#94a3b8; }
    body.theme-light .ev-target-pick { max-height:240px; overflow:auto; border:1px solid #e5e7eb; border-radius:10px; padding:.5rem; }
</style>
@endpush

@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-8 col-12 mb-2">
        <h2 class="content-header-title mb-0">@lang('evaluation.evaluators.page_title') — {{ $form->title }}</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.evaluations.index') }}">@lang('evaluation.forms.page_title')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.evaluations.edit', $form->id) }}">{{ $form->title }}</a></li>
                <li class="breadcrumb-item active">@lang('evaluation.evaluators.page_title')</li>
            </ol>
        </div>
    </div>
    <div class="content-header-right col-md-4 col-12 text-end">
        <a href="{{ route('admin.evaluations.targets.index', $form->id) }}" class="btn btn-outline-secondary"><i class="la la-users"></i> @lang('evaluation.form.actions_menu.targets')</a>
        @if ($form->isEditable())
            <button type="button" class="btn ev-add-btn" id="ev-add-evaluator" @disabled($targets->isEmpty())><i class="la la-plus"></i> @lang('evaluation.evaluators.actions.add')</button>
        @endif
    </div>
</div>

<div class="content-body">
    @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    @if ($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
    <p class="text-muted">@lang('evaluation.evaluators.subtitle')</p>

    @if ($targets->isEmpty())
        <div class="alert alert-warning"><i class="la la-exclamation-triangle"></i> @lang('evaluation.evaluators.no_targets_warning')</div>
    @endif

    <div class="card">
        <div class="card-header fw-bold">@lang('evaluation.evaluators.current.title')</div>
        @if ($assignments->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead><tr>
                        <th>@lang('evaluation.evaluators.current.columns.name')</th>
                        <th>@lang('evaluation.evaluators.current.columns.targets')</th>
                        <th class="text-end" style="width:120px;">@lang('evaluation.evaluators.current.columns.actions')</th>
                    </tr></thead>
                    <tbody>
                    @foreach ($assignments as $a)
                        <tr>
                            <td class="fw-bold">{{ $a['name'] }} <span class="text-muted small fw-normal">{{ $a['username'] }}</span></td>
                            <td><span class="badge bg-secondary">{{ $a['target_count'] }}</span></td>
                            <td class="text-end">
                                @if ($form->isEditable())
                                    <button type="button" class="btn btn-sm btn-outline-secondary ev-edit-evaluator"
                                        data-id="{{ $a['id'] }}"
                                        data-evaluator="{{ $a['evaluator_id'] }}"
                                        data-name="{{ $a['name'] }}"
                                        data-targets="{{ implode(',', $a['target_ids']) }}">
                                        <i class="la la-pen"></i> @lang('evaluation.evaluators.current.edit')
                                    </button>
                                    <form method="POST" action="{{ route('admin.evaluations.evaluators.destroy', [$form->id, $a['id']]) }}" class="d-inline" onsubmit="return confirm('@lang('evaluation.evaluators.current.remove_confirm')')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger"><i class="la la-trash"></i></button>
                                    </form>
                                @else
                                    <span class="text-muted small"><i class="la la-lock"></i></span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="ev-empty"><i class="la la-user-check la-2x d-block mb-2"></i>@lang('evaluation.evaluators.current.none')</div>
        @endif
    </div>
</div>

{{-- Add / Edit evaluator modal --}}
<div class="modal fade" id="ev-evaluator-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form method="POST" id="ev-evaluator-form" action="{{ route('admin.evaluations.evaluators.store', $form->id) }}">
                @csrf
                <input type="hidden" name="_method" value="POST" id="ev-eval-method">
                <div class="modal-header">
                    <h5 class="modal-title" id="ev-eval-title">@lang('evaluation.evaluators.add_title')</h5>
                    <button type="button" class="close" data-dismiss="modal" data-bs-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3" id="ev-evaluator-select-wrap">
                        <label class="form-label">@lang('evaluation.evaluators.fields.evaluator') <span class="text-danger">*</span></label>
                        <select name="evaluator_id" id="f-evaluator" class="form-control ev-select2">
                            <option value="">@lang('evaluation.evaluators.fields.select_evaluator')</option>
                            @foreach ($evaluators as $e)<option value="{{ $e['id'] }}">{{ $e['label'] }}</option>@endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label">@lang('evaluation.evaluators.fields.targets') <span class="text-danger">*</span></label>
                        <div class="ev-target-pick">
                            @foreach ($targets as $t)
                                <div class="form-check">
                                    <input type="checkbox" name="target_ids[]" value="{{ $t['target_id'] }}" id="ftgt-{{ $t['target_id'] }}" class="form-check-input ev-tgt" data-user="{{ $t['user_id'] }}">
                                    <label class="form-check-label" for="ftgt-{{ $t['target_id'] }}">{{ $t['name'] }}</label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-dismiss="modal" data-bs-dismiss="modal">@lang('evaluation.evaluators.actions.cancel')</button>
                    <button type="submit" class="btn ev-add-btn"><i class="la la-save"></i> @lang('evaluation.evaluators.actions.save')</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
jQuery(function ($) {
    var storeUrl  = @json(route('admin.evaluations.evaluators.store', $form->id));
    var updateBase = @json(url('admin/evaluations/'.$form->id.'/evaluators'));
    var allowSelf = @json($allowSelf);

    function openModal() {
        if (window.bootstrap) { new bootstrap.Modal(document.getElementById('ev-evaluator-modal')).show(); }
        else { $('#ev-evaluator-modal').modal('show'); }
    }
    function initSelect2() {
        if ($.fn.select2) { $('#f-evaluator').select2({ dropdownParent: $('#ev-evaluator-modal'), width: '100%' }); }
    }

    $('#ev-add-evaluator').on('click', function () {
        $('#ev-eval-title').text(@json(__('evaluation.evaluators.add_title')));
        $('#ev-evaluator-form')[0].reset();
        $('#ev-evaluator-form').attr('action', storeUrl);
        $('#ev-eval-method').val('POST');
        $('#ev-evaluator-select-wrap').show();
        $('.ev-tgt').prop('checked', false);
        if ($.fn.select2) { $('#f-evaluator').val('').trigger('change'); }
        openModal();
        initSelect2();
    });

    $('.ev-edit-evaluator').on('click', function () {
        var d = $(this).data();
        $('#ev-eval-title').text(@json(__('evaluation.evaluators.edit_title')) + ' — ' + d.name);
        $('#ev-evaluator-form').attr('action', updateBase + '/' + d.id);
        $('#ev-eval-method').val('PUT');
        // On edit the evaluator is fixed (server reads it from the assignment).
        $('#ev-evaluator-select-wrap').hide();
        var ids = String(d.targets || '').split(',').filter(Boolean);
        $('.ev-tgt').prop('checked', false);
        ids.forEach(function (id) { $('#ftgt-' + id).prop('checked', true); });
        openModal();
    });

    // Self-eval client guard (server is authoritative): hide own-user targets.
    $('#f-evaluator').on('change', function () {
        if (allowSelf) { return; }
        var uid = String($(this).val());
        $('.ev-tgt').each(function () {
            var own = String($(this).data('user')) === uid;
            $(this).prop('disabled', own);
            if (own) { $(this).prop('checked', false); }
        });
    });
});
</script>
@endpush
@endsection
