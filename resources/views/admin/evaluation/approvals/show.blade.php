@extends('layouts.app')

@section('title', __('eval_approval.detail.title'))
@section('body_class','theme-light')

@php
    $items  = collect($payload['items'] ?? [])->filter(fn($i) => ($i['status'] ?? 'active') !== 'disabled')->sortBy('sort_order')->values();
    $levels = collect($payload['levels'] ?? [])->sortBy('sort_order')->values();
    $statusVal = $evaluation->status?->value;
    $canApprove = in_array($statusVal, ['pending_approval','completed','needs_review'], true);
    $canReject  = in_array($statusVal, ['pending_approval','completed','needs_review'], true);
    $canReview  = in_array($statusVal, ['pending_approval','completed','approved'], true);
    $canReopenStatus = in_array($statusVal, ['approved','rejected','needs_review','locked'], true);
@endphp

@push('styles')
<style>
    body.theme-light .ex-item { border:1px solid #e5e7eb; border-radius:12px; margin-bottom:1rem; }
    body.theme-light .ex-item-head { padding:.75rem 1rem; background:#faf6ee; border-bottom:1px solid #f0e6d2; border-radius:12px 12px 0 0; }
    body.theme-light .ex-item-body { padding:1rem; }
    body.theme-light .ex-result-card { background:linear-gradient(135deg,#fff7e6,#fff); border:1px solid #f0e6d2; border-radius:12px; }
    body.theme-light .ev-chip { display:inline-flex; align-items:center; gap:.25rem; font-size:.8rem; background:#eef2f7; border-radius:20px; padding:.15rem .6rem; margin:.15rem; }
    body.theme-light .ap-actions .btn { font-weight:600; }
</style>
@endpush

@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-8 col-12 mb-2">
        <h2 class="content-header-title mb-0">@lang('eval_approval.detail.title') — #{{ $evaluation->id }}</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.evaluations.approvals.index') }}">@lang('eval_approval.breadcrumb')</a></li>
                <li class="breadcrumb-item active">{{ $form?->title }}</li>
            </ol>
        </div>
    </div>
    <div class="content-header-right col-md-4 col-12 text-end">
        <a href="{{ route('admin.evaluations.approvals.index') }}" class="btn btn-outline-secondary"><i class="la la-arrow-right"></i> @lang('eval_approval.actions.back')</a>
    </div>
</div>

<div class="content-body">
    @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
    @if ($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

    {{-- Info row --}}
    <div class="card mb-3"><div class="card-body row">
        <div class="col-md-3"><strong>@lang('eval_approval.columns.subject'):</strong> {{ $evaluation->subject?->name ?? '—' }}</div>
        <div class="col-md-3"><strong>@lang('eval_approval.columns.evaluator'):</strong> {{ $evaluation->evaluator?->name ?? '—' }}</div>
        <div class="col-md-3"><strong>@lang('eval_approval.columns.status'):</strong> <span class="badge bg-info">{{ $evaluation->status?->label() }}</span></div>
        <div class="col-md-3"><strong>@lang('eval_approval.columns.submitted'):</strong> {{ optional($evaluation->submitted_at)->format('Y-m-d H:i') ?? '—' }}</div>
        @if ($evaluation->approver)
            <div class="col-md-6 mt-2 text-success"><i class="la la-check-circle"></i> {{ $evaluation->approver->name }} — {{ optional($evaluation->approved_at)->format('Y-m-d H:i') }}</div>
        @endif
    </div></div>

    {{-- Rejection / review notes if present --}}
    @if ($evaluation->rejection_reason)
        <div class="alert alert-danger"><strong>@lang('eval_approval.detail.rejection_reason'):</strong> {{ $evaluation->rejection_reason }}</div>
    @endif
    @if (!empty($reviewNotes['notes']) && $statusVal === 'needs_review')
        <div class="alert alert-warning"><strong>@lang('eval_approval.detail.review_notes'):</strong> {{ $reviewNotes['notes'] }}</div>
    @endif

    {{-- Result card --}}
    <div class="card ex-result-card mb-3"><div class="card-body">
        <h5 class="mb-3">@lang('eval_approval.detail.result')</h5>
        <div class="row text-center">
            <div class="col"><div class="text-muted small">@lang('eval_approval.detail.total')</div><div class="h4">{{ $evaluation->total_score }}</div></div>
            <div class="col"><div class="text-muted small">@lang('eval_approval.detail.max')</div><div class="h4">{{ $evaluation->max_score }}</div></div>
            <div class="col"><div class="text-muted small">@lang('eval_approval.detail.percentage')</div><div class="h4 text-success">{{ $evaluation->percentage }}%</div></div>
            <div class="col"><div class="text-muted small">@lang('eval_approval.detail.grade')</div><div class="h4">{{ $evaluation->grade_label }}</div></div>
            <div class="col"><div class="text-muted small">@lang('eval_approval.detail.evidence_count')</div><div class="h4">{{ $evaluation->evidences->count() }}</div></div>
        </div>
        @if (!empty($evaluation->score_breakdown['breakdown']))
            <hr><h6>@lang('eval_approval.detail.breakdown')</h6>
            <div class="table-responsive">
            <table class="table table-sm">
                <thead><tr><th>@lang('eval_approval.detail.item')</th><th class="text-center">@lang('eval_approval.detail.earned')</th><th class="text-center">@lang('eval_approval.detail.max')</th></tr></thead>
                <tbody>
                @foreach ($evaluation->score_breakdown['breakdown'] as $b)
                    <tr><td>{{ $b['item_name'] ?? ('#'.($b['item_id'] ?? '')) }}</td><td class="text-center">{{ $b['earned'] ?? '' }}</td><td class="text-center">{{ $b['max'] ?? '' }}</td></tr>
                @endforeach
                </tbody>
            </table>
            </div>
        @endif
        @if ($evaluation->general_notes)
            <hr><strong>@lang('eval_approval.detail.general_notes'):</strong>
            <p class="mb-0">{{ $evaluation->general_notes }}</p>
        @endif
    </div></div>

    {{-- Read-only answers + evidence --}}
    <div class="card mb-3"><div class="card-body">
        <h5 class="mb-3">@lang('eval_approval.detail.answers')</h5>
        @foreach ($items as $item)
            @php $ev = $evidences['item:'.$item['id']] ?? []; @endphp
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
                        @if (!empty($responses['notes'][$item['id']]))<div class="text-muted small mt-1">{{ $responses['notes'][$item['id']] }}</div>@endif
                    @else
                        @foreach (($item['indicators'] ?? []) as $ind)
                            @if (($ind['status'] ?? 'active') === 'disabled') @continue @endif
                            @php $ans = $responses['indicators'][$ind['id']] ?? null; $indEv = $evidences['ind:'.$ind['id']] ?? []; @endphp
                            <div class="mb-1">{{ $ind['text'] }} —
                                @if ($type === 'checklist')
                                    @if($ans===true)<span class="text-success">@lang('eval_approval.detail.met')</span>@elseif($ans===false)<span class="text-danger">@lang('eval_approval.detail.not_met')</span>@else<span class="text-muted">@lang('eval_approval.detail.none')</span>@endif
                                @else
                                    <strong>{{ $levels->firstWhere('id', $ans)['label'] ?? __('eval_approval.detail.none') }}</strong>
                                @endif
                                @foreach ($indEv as $e)
                                    <a class="ev-chip" href="{{ $e->url ?: '#' }}" @if($e->url) target="_blank" @endif><i class="la la-paperclip"></i> {{ $e->original_name ?: ($e->description ?: $e->type) }}</a>
                                @endforeach
                            </div>
                        @endforeach
                    @endif
                    @if (!empty($ev))
                        <div class="mt-2">
                            @foreach ($ev as $e)
                                <a class="ev-chip" href="{{ $e->url ?: '#' }}" @if($e->url) target="_blank" @endif><i class="la la-paperclip"></i> {{ $e->original_name ?: ($e->description ?: $e->type) }}</a>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    </div></div>

    {{-- Approver actions --}}
    <div class="card mb-4"><div class="card-body ap-actions d-flex flex-wrap gap-2">
        @if ($canApprove)
            <form method="POST" action="{{ route('admin.evaluations.approvals.approve', $evaluation->id) }}" onsubmit="return confirm('@lang('eval_approval.approve_confirm')')">
                @csrf
                <button type="submit" class="btn btn-success"><i class="la la-check"></i> @lang('eval_approval.actions.approve')</button>
            </form>
        @endif
        @if ($canReject)
            <button type="button" class="btn btn-danger" data-toggle="modal" data-bs-toggle="modal" data-target="#reject-modal" data-bs-target="#reject-modal"><i class="la la-times"></i> @lang('eval_approval.actions.reject')</button>
        @endif
        @if ($canReview)
            <button type="button" class="btn btn-warning" data-toggle="modal" data-bs-toggle="modal" data-target="#review-modal" data-bs-target="#review-modal"><i class="la la-exclamation-triangle"></i> @lang('eval_approval.actions.review')</button>
        @endif
        @if ($canReopenStatus && $canReopen)
            <form method="POST" action="{{ route('admin.evaluations.approvals.reopen', $evaluation->id) }}" onsubmit="return confirm('@lang('eval_approval.reopen_confirm')')">
                @csrf
                <button type="submit" class="btn btn-outline-primary"><i class="la la-unlock"></i> @lang('eval_approval.actions.reopen')</button>
            </form>
        @endif
    </div></div>
</div>

{{-- Reject modal --}}
@if ($canReject)
<div class="modal fade" id="reject-modal" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document"><div class="modal-content">
    <form method="POST" action="{{ route('admin.evaluations.approvals.reject', $evaluation->id) }}">
      @csrf
      <div class="modal-header">
        <h5 class="modal-title">@lang('eval_approval.reject_title')</h5>
        <button type="button" class="close" data-dismiss="modal" data-bs-dismiss="modal"><span>&times;</span></button>
      </div>
      <div class="modal-body">
        <label class="form-label">@lang('eval_approval.reject_reason')</label>
        <textarea name="rejection_reason" class="form-control" rows="3" required placeholder="@lang('eval_approval.reject_reason_ph')"></textarea>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-dismiss="modal" data-bs-dismiss="modal">@lang('eval_approval.actions.cancel')</button>
        <button type="submit" class="btn btn-danger"><i class="la la-times"></i> @lang('eval_approval.actions.reject')</button>
      </div>
    </form>
  </div></div>
</div>
@endif

{{-- Request-review modal --}}
@if ($canReview)
<div class="modal fade" id="review-modal" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document"><div class="modal-content">
    <form method="POST" action="{{ route('admin.evaluations.approvals.review', $evaluation->id) }}">
      @csrf
      <div class="modal-header">
        <h5 class="modal-title">@lang('eval_approval.review_title')</h5>
        <button type="button" class="close" data-dismiss="modal" data-bs-dismiss="modal"><span>&times;</span></button>
      </div>
      <div class="modal-body">
        <label class="form-label">@lang('eval_approval.review_notes')</label>
        <textarea name="review_notes" class="form-control mb-3" rows="3" required placeholder="@lang('eval_approval.review_notes_ph')"></textarea>
        @if ($type === 'rubric' && $items->count())
            <label class="form-label">@lang('eval_approval.detail.item')</label>
            @foreach ($items as $item)
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="review_items[]" value="{{ $item['id'] }}" id="ri-{{ $item['id'] }}">
                    <label class="form-check-label" for="ri-{{ $item['id'] }}">{{ $item['name'] }}</label>
                </div>
            @endforeach
        @endif
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-dismiss="modal" data-bs-dismiss="modal">@lang('eval_approval.actions.cancel')</button>
        <button type="submit" class="btn btn-warning"><i class="la la-paper-plane"></i> @lang('eval_approval.actions.review')</button>
      </div>
    </form>
  </div></div>
</div>
@endif
@endsection
