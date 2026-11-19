@extends('layouts.app')
@section('title', __('hub_payments.receipt_title'))
@section('page-title', __('hub_payments.receipt_title'))
@section('content')
<div class="card"><div class="card-body">
<h4>{{ __('hub_payments.receipt_title') }} #{{ $payment->id }}</h4>
<table class="table table-borderless w-auto">
<tr><th scope="row">{{ __('hub_payments.buyer') }}</th><td>{{ $payment->buyer?->name }}</td></tr>
<tr><th scope="row">{{ __('hub_payments.beneficiary') }}</th><td>{{ $payment->subscription?->user?->name }}</td></tr>
<tr><th scope="row">{{ __('hub_payments.package') }}</th><td>{{ $payment->subscription?->package?->name_ar }}</td></tr>
<tr><th scope="row">{{ __('hub_payments.amount') }}</th><td>{{ number_format($payment->amount / 100, 2) }} {{ $payment->currency }}</td></tr>
<tr><th scope="row">{{ __('hub_payments.paid_at') }}</th><td>{{ $payment->paid_at?->format('Y-m-d H:i') }}</td></tr>
<tr><th scope="row">{{ __('hub_payments.status') }}</th><td>{{ __('hub_payments.status_'.$payment->status) }}</td></tr>
<tr><th scope="row">{{ __('hub_payments.payment_id') }}</th><td dir="ltr">{{ $payment->moyasar_payment_id }}</td></tr>
</table>
<p class="text-muted mb-1">{{ __('hub_payments.vat_included') }}</p>
<p class="text-muted">{{ __('hub_payments.receipt_not_invoice') }}</p>
<a class="btn btn-outline-secondary" href="{{ route('admin.hub.payments.index') }}">{{ __('hub_payments.back') }}</a>
<button type="button" class="btn btn-primary" onclick="window.print()">{{ __('hub_payments.print') }}</button>
</div></div>
@endsection
