@extends('layouts.app')

@section('title', __('evaluation.execute.page_title'))
@section('body_class','theme-light')

@php
    $items   = collect($payload['items'] ?? [])->filter(fn($i) => ($i['status'] ?? 'active') !== 'disabled')->sortBy('sort_order')->values();
    $levels  = collect($payload['levels'] ?? [])->sortBy('sort_order')->values();
    $allowItemNotes = (bool) ($form?->setting('allow_item_notes', false));
    $allowGeneralNotes = (bool) ($form?->setting('allow_general_notes', true));
@endphp

@push('styles')
<style>
    body.theme-light .ex-item { border:1px solid #e5e7eb; border-radius:12px; margin-bottom:1rem; }
    body.theme-light .ex-item-head { padding:.75rem 1rem; background:#faf6ee; border-bottom:1px solid #f0e6d2; border-radius:12px 12px 0 0; }
    body.theme-light .ex-item-body { padding:1rem; }
    body.theme-light .ex-level-pick label { display:block; border:1px solid #e5e7eb; border-radius:8px; padding:.4rem .6rem; margin-bottom:.35rem; cursor:pointer; }
    body.theme-light .ex-level-pick input:checked + span { font-weight:700; color:var(--gold-600,#b8860b); }
    body.theme-light .ex-result-card { background:linear-gradient(135deg,#fff7e6,#fff); border:1px solid #f0e6d2; border-radius:12px; }
    body.theme-light .ev-chip { display:inline-flex; align-items:center; gap:.25rem; font-size:.8rem; background:#eef2f7; border-radius:20px; padding:.15rem .6rem; margin:.15rem; }
</style>
@endpush

@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-8 col-12 mb-2">
        <h2 class="content-header-title mb-0">@lang('evaluation.execute.page_title') — {{ $form->title }}</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.my-evaluations.index') }}">@lang('evaluation.my.page_title')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.evaluations.subjects', $form->id) }}">{{ $form->title }}</a></li>
                <li class="breadcrumb-item active">{{ $evaluation->subject?->name }}</li>
            </ol>
        </div>
    </div>
    <div class="content-header-right col-md-4 col-12 text-end">
        <a href="{{ route('admin.evaluations.subjects', $form->id) }}" class="btn btn-outline-secondary"><i class="la la-arrow-right"></i> @lang('evaluation.execute.back')</a>
    </div>
</div>

<div class="content-body">
    @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    @if ($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

    <div class="row mb-3">
        <div class="col-md-4"><strong>@lang('evaluation.execute.subject'):</strong> {{ $evaluation->subject?->name }}</div>
        <div class="col-md-4"><strong>@lang('evaluation.form.fields.type'):</strong> {{ $form->type?->label() }}</div>
        <div class="col-md-4"><strong>@lang('evaluation.form.fields.status'):</strong> <span class="badge bg-info">{{ $evaluation->status?->label() }}</span></div>
    </div>

    @if ($evaluation->status?->value !== 'draft')
        {{-- Result / read-only view (submitted, completed, approved, locked, ...) --}}
        <div class="card ex-result-card mb-3">
            <div class="card-body">
                <h5 class="mb-3">@lang('evaluation.execute.result.title')</h5>
                <div class="row text-center">
                    <div class="col"><div class="text-muted small">@lang('evaluation.execute.result.total')</div><div class="h4">{{ $evaluation->total_score }}</div></div>
                    <div class="col"><div class="text-muted small">@lang('evaluation.execute.result.max')</div><div class="h4">{{ $evaluation->max_score }}</div></div>
                    <div class="col"><div class="text-muted small">@lang('evaluation.execute.result.percentage')</div><div class="h4 text-success">{{ $evaluation->percentage }}%</div></div>
                    <div class="col"><div class="text-muted small">@lang('evaluation.execute.result.grade')</div><div class="h4">{{ $evaluation->grade_label }}</div></div>
                </div>
                @if (!empty($evaluation->score_breakdown['breakdown']))
                    <hr>
                    <h6>@lang('evaluation.execute.result.breakdown')</h6>
                    <table class="table table-sm">
                        <thead><tr><th>@lang('evaluation.execute.result.item')</th><th class="text-center">@lang('evaluation.execute.result.earned')</th><th class="text-center">@lang('evaluation.execute.result.max')</th></tr></thead>
                        <tbody>
                        @foreach ($evaluation->score_breakdown['breakdown'] as $b)
                            <tr><td>{{ $b['item_name'] ?? ('#'.$b['item_id']) }}</td><td class="text-center">{{ $b['earned'] }}</td><td class="text-center">{{ $b['max'] }}</td></tr>
                        @endforeach
                        </tbody>
                    </table>
                @endif
                @if ($evaluation->general_notes)
                    <hr><strong>@lang('evaluation.execute.fields.general_notes'):</strong>
                    <p class="mb-0">{{ $evaluation->general_notes }}</p>
                @endif
            </div>
        </div>
        {{-- Read-only answers summary --}}
        @foreach ($items as $item)
            <div class="ex-item">
                <div class="ex-item-head fw-bold">{{ $item['name'] }}
                    @if ($type !== 'checklist')<span class="badge bg-secondary">{{ $item['weight'] }}%</span>@endif
                </div>
                <div class="ex-item-body">
                    @if ($type === 'rubric')
                        @php $chosen = $responses['items'][$item['id']] ?? null; @endphp
                        @foreach ($levels as $lvl)
                            <div>@if($chosen===$lvl['id'])<i class="la la-check-circle text-success"></i>@else<i class="la la-circle text-muted"></i>@endif {{ $lvl['label'] }}</div>
                        @endforeach
                    @else
                        @foreach (($item['indicators'] ?? []) as $ind)
                            @if (($ind['status'] ?? 'active') === 'disabled') @continue @endif
                            @php $ans = $responses['indicators'][$ind['id']] ?? null; @endphp
                            <div class="mb-1">{{ $ind['text'] }} —
                                @if ($type === 'checklist')
                                    @if($ans===true)<span class="text-success">@lang('evaluation.execute.fields.met')</span>@elseif($ans===false)<span class="text-danger">@lang('evaluation.execute.fields.not_met')</span>@else<span class="text-muted">—</span>@endif
                                @else
                                    <strong>{{ $levels->firstWhere('id', $ans)['label'] ?? '—' }}</strong>
                                @endif
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        @endforeach
    @else
        {{-- EDITABLE execution form --}}
        <form method="POST" action="{{ route('admin.evaluations.execute.submit', $evaluation->id) }}" id="ex-form">
            @csrf
            @foreach ($items as $item)
                @php $iid = $item['id']; $nodeKey = 'item:'.$iid; @endphp
                <div class="ex-item">
                    <div class="ex-item-head d-flex justify-content-between align-items-center">
                        <span class="fw-bold">{{ $item['name'] }}
                            @if (!empty($item['is_required']))<span class="badge bg-danger">@lang('evaluation.execute.required_badge')</span>@endif
                            @if (!empty($item['evidence_required']))<span class="badge bg-warning text-dark">@lang('evaluation.execute.evidence_badge')</span>@endif
                        </span>
                        @if ($type !== 'checklist')<span class="badge bg-secondary">{{ $item['weight'] }}%</span>@endif
                    </div>
                    <div class="ex-item-body">
                        @if (!empty($item['description']))<p class="text-muted small">{{ $item['description'] }}</p>@endif

                        @if ($type === 'rubric')
                            @php $chosen = $responses['items'][$iid] ?? null; @endphp
                            <div class="ex-level-pick" data-item="{{ $iid }}">
                                @foreach ($levels as $lvl)
                                    <label>
                                        <input type="radio" name="items[{{ $iid }}]" value="{{ $lvl['id'] }}" data-rank="{{ $loop->iteration }}" @checked($chosen===$lvl['id'])>
                                        <span>{{ $lvl['label'] }}</span>
                                    </label>
                                @endforeach
                            </div>
                            {{-- Evidence at item level for rubric --}}
                            @include('admin.evaluation.execute._evidence', ['nodeType'=>'item','nodeId'=>$iid,'nodeKey'=>$nodeKey])
                        @else
                            @foreach (($item['indicators'] ?? []) as $ind)
                                @if (($ind['status'] ?? 'active') === 'disabled') @continue @endif
                                @php $indId = $ind['id']; $indKey='ind:'.$indId; $ans = $responses['indicators'][$indId] ?? null; @endphp
                                <div class="border rounded p-2 mb-2">
                                    <div class="mb-1">
                                        {{ $ind['text'] }}
                                        @if (!empty($ind['is_required']))<span class="badge bg-danger">@lang('evaluation.execute.required_badge')</span>@endif
                                        @if (!empty($ind['evidence_required']))<span class="badge bg-warning text-dark">@lang('evaluation.execute.evidence_badge')</span>@endif
                                    </div>
                                    @if ($type === 'checklist')
                                        <div class="btn-group btn-group-sm" role="group">
                                            <input type="radio" class="btn-check" name="indicators[{{ $indId }}]" id="ind-{{ $indId }}-1" value="1" @checked($ans===true)>
                                            <label class="btn btn-outline-success" for="ind-{{ $indId }}-1">@lang('evaluation.execute.fields.met')</label>
                                            <input type="radio" class="btn-check" name="indicators[{{ $indId }}]" id="ind-{{ $indId }}-0" value="0" @checked($ans===false)>
                                            <label class="btn btn-outline-danger" for="ind-{{ $indId }}-0">@lang('evaluation.execute.fields.not_met')</label>
                                        </div>
                                    @else
                                        <select name="indicators[{{ $indId }}]" class="form-control form-control-sm" style="max-width:280px;">
                                            <option value="">@lang('evaluation.execute.fields.pick_level')</option>
                                            @foreach ($levels as $lvl)
                                                <option value="{{ $lvl['id'] }}" @selected($ans===$lvl['id'])>{{ $lvl['label'] }}</option>
                                            @endforeach
                                        </select>
                                    @endif
                                    @include('admin.evaluation.execute._evidence', ['nodeType'=>'indicator','nodeId'=>$indId,'nodeKey'=>$indKey])
                                </div>
                            @endforeach
                        @endif

                        @if ($allowItemNotes)
                            <div class="mt-2">
                                <label class="form-label small">@lang('evaluation.execute.fields.item_note')</label>
                                <textarea name="item_notes[{{ $iid }}]" class="form-control form-control-sm" rows="1">{{ $responses['notes'][$iid] ?? '' }}</textarea>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach

            @if ($allowGeneralNotes)
                <div class="card mb-3"><div class="card-body">
                    <label class="form-label">@lang('evaluation.execute.fields.general_notes')</label>
                    <textarea name="general_notes" class="form-control" rows="2">{{ $evaluation->general_notes }}</textarea>
                </div></div>
            @endif

            <div class="d-flex gap-2 mb-4">
                <button type="submit" formaction="{{ route('admin.evaluations.execute.draft', $evaluation->id) }}" class="btn btn-outline-secondary"><i class="la la-save"></i> @lang('evaluation.execute.actions.save_draft')</button>
                <button type="submit" class="btn btn-success" onclick="return confirm('@lang('evaluation.execute.submit_confirm')')"><i class="la la-paper-plane"></i> @lang('evaluation.execute.actions.submit')</button>
            </div>
        </form>
    @endif
</div>

@unless ($locked || $evaluation->status?->value !== 'draft')
{{-- Shared Add-Evidence modal (outside #ex-form) --}}
<div class="modal fade" id="ev-modal" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <form method="POST" action="{{ route('admin.evaluations.execute.evidence.store', $evaluation->id) }}" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="item_id" id="ev-item-id">
        <input type="hidden" name="indicator_id" id="ev-indicator-id">
        <div class="modal-header">
          <h5 class="modal-title">@lang('evaluation.evidence.add')</h5>
          <button type="button" class="close" data-dismiss="modal" data-bs-dismiss="modal"><span>&times;</span></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">@lang('evaluation.evidence.fields.type')</label>
            <select name="type" id="ev-type" class="form-control">
              <option value="file">@lang('evaluation.evidence.type.file')</option>
              <option value="link">@lang('evaluation.evidence.type.link')</option>
            </select>
          </div>
          <div class="mb-3" id="ev-file-wrap">
            <label class="form-label">@lang('evaluation.evidence.fields.file')</label>
            <input type="file" name="file" class="form-control">
          </div>
          <div class="mb-3 d-none" id="ev-url-wrap">
            <label class="form-label">@lang('evaluation.evidence.fields.url')</label>
            <input type="url" name="url" class="form-control" placeholder="https://...">
          </div>
          <div class="mb-3">
            <label class="form-label">@lang('evaluation.evidence.fields.description')</label>
            <input type="text" name="description" class="form-control" maxlength="1000">
          </div>
          <div class="form-check">
            <input type="checkbox" name="visible_to_subject" value="1" id="ev-visible" class="form-check-input">
            <label class="form-check-label" for="ev-visible">@lang('evaluation.evidence.fields.visible_to_subject')</label>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-dismiss="modal" data-bs-dismiss="modal">@lang('evaluation.evaluators.actions.cancel')</button>
          <button type="submit" class="btn btn-info"><i class="la la-save"></i> @lang('evaluation.evidence.add')</button>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- Hidden delete form (reused by all delete buttons) --}}
<form method="POST" id="ev-del-form" class="d-none" data-base="{{ url('admin/evaluations/execute/'.$evaluation->id.'/evidence') }}">
  @csrf @method('DELETE')
</form>
@endunless

@push('scripts')
<script>
jQuery(function ($) {
    function openModal(){ if(window.bootstrap){ new bootstrap.Modal(document.getElementById('ev-modal')).show(); } else { $('#ev-modal').modal('show'); } }

    $('.ev-add-btn').on('click', function () {
        var t = $(this).data('nodeType'), id = $(this).data('nodeId');
        $('#ev-item-id').val(t === 'item' ? id : '');
        $('#ev-indicator-id').val(t === 'indicator' ? id : '');
        openModal();
    });

    $('#ev-type').on('change', function () {
        if ($(this).val() === 'link') { $('#ev-url-wrap').removeClass('d-none'); $('#ev-file-wrap').addClass('d-none'); }
        else { $('#ev-file-wrap').removeClass('d-none'); $('#ev-url-wrap').addClass('d-none'); }
    });

    $('.ev-del').on('click', function () {
        if (!confirm(@json(__('evaluation.evidence.remove_confirm')))) { return; }
        var f = $('#ev-del-form');
        f.attr('action', f.data('base') + '/' + $(this).data('id'));
        f.appendTo('body').trigger('submit');
    });
});
</script>
@endpush
@endsection
