@extends('layouts.app')
@section('title', __('hub_payments.payments'))
@section('page-title', __('hub_payments.payments'))
@section('content')
<div class="card"><div class="card-body">
<form method="GET" class="mb-3"><div class="form-row align-items-end">
<div class="col-md-3 col-6 mb-2"><label for="status">{{ __('hub_payments.status') }}</label><select class="form-control" id="status" name="status"><option value="">{{ __('hub_payments.all') }}</option>
@foreach(['pending', 'paid', 'failed', 'refunded'] as $s)<option value="{{ $s }}" @selected(request('status') === $s)>{{ __('hub_payments.status_'.$s) }}</option>@endforeach</select></div>
<div class="col-md-3 col-6 mb-2"><label for="flag">{{ __('hub_payments.flag') }}</label><select class="form-control" id="flag" name="flag"><option value="">{{ __('hub_payments.all') }}</option>
<option value="paid_without_active" @selected(request('flag') === 'paid_without_active')>{{ __('hub_payments.flag_paid_without_active') }}</option></select></div>
<div class="col-md-2 col-6 mb-2"><button class="btn btn-primary">{{ __('hub_payments.filter') }}</button></div>
</div></form>
<div class="table-responsive"><table class="table"><thead><tr>
@foreach(['buyer', 'package', 'amount', 'status', 'invoice_id', 'payment_id', 'paid_at'] as $h)<th scope="col">{{ __('hub_payments.'.$h) }}</th>@endforeach<th scope="col"></th>
</tr></thead><tbody>
@forelse($payments as $p)
@php($sub = $p->subscription)
<tr><td>{{ $p->buyer?->name }}</td><td>{{ $sub?->package?->name_ar }}</td><td>{{ number_format($p->amount / 100, 2) }}</td>
<td>{{ __('hub_payments.status_'.$p->status) }}@if($p->status === 'paid' && $sub && $sub->status !== 'active')<br><span class="badge badge-warning">{{ __('hub_payments.flag_paid_without_active') }}</span>@endif</td>
<td dir="ltr">{{ $p->moyasar_invoice_id ?? '—' }}</td><td dir="ltr">{{ $p->moyasar_payment_id ?? '—' }}</td><td>{{ $p->paid_at?->format('Y-m-d H:i') ?? '—' }}</td>
<td>@if($sub)<a href="{{ route('admin.hub.subscriptions.index', ['package_id' => $sub->package_id]) }}">{{ __('hub_payments.subscription') }} #{{ $sub->id }}</a><br>@endif
@if(in_array($p->status, ['paid', 'refunded']))<a href="{{ route('admin.hub.payments.receipt', $p->id) }}">{{ __('hub_payments.receipt') }}</a>@endif
@if(in_array($p->status, ['pending', 'failed']) && $p->moyasar_invoice_id)
<form method="POST" action="{{ route('admin.hub.payments.check', $p->id) }}" class="mt-1">@csrf<button class="btn btn-sm btn-outline-primary">{{ __('hub_payments.check') }}</button></form>
@endif
@if($p->status === 'paid')
<form method="POST" action="{{ route('admin.hub.payments.refund', $p->id) }}" class="mt-1" onsubmit="return confirm('{{ __('hub_payments.refund_confirm') }}')">@csrf
<label class="sr-only" for="note{{ $p->id }}">{{ __('hub_payments.refund_note') }}</label>
<input class="form-control form-control-sm mb-1" id="note{{ $p->id }}" name="note" required maxlength="500" placeholder="{{ __('hub_payments.refund_note') }}">
<button class="btn btn-sm btn-outline-danger">{{ __('hub_payments.record_refund') }}</button></form>
@elseif($p->status === 'refunded')<br><small>{{ $p->refunded_at?->format('Y-m-d') }} — {{ $p->refund_note }}</small>@endif</td></tr>
@empty<tr><td colspan="8">{{ __('hub_payments.empty') }}</td></tr>@endforelse
</tbody></table></div>{{ $payments->links() }}
@if($unmatched->isNotEmpty())
<h5 class="mt-4">{{ __('hub_payments.unmatched_events') }}</h5>
<div class="table-responsive"><table class="table"><thead><tr><th scope="col">{{ __('hub_payments.event') }}</th><th scope="col">{{ __('hub_payments.payment_id') }}</th><th scope="col">{{ __('hub_payments.date') }}</th></tr></thead><tbody>
@foreach($unmatched as $e)<tr><td dir="ltr">{{ $e->event_type }}</td><td dir="ltr">{{ $e->moyasar_payment_id ?? '—' }}</td><td>{{ $e->created_at }}</td></tr>@endforeach
</tbody></table></div>
@endif
</div></div>
@endsection
