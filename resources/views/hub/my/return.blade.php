@extends('layouts.app')
@section('title', __('hub_my.return_title'))
@section('page-title', __('hub_my.return_title'))
@section('content')
<div class="card"><div class="card-body">
<p id="hub-state" role="status" aria-live="polite">
@if($state['payment'] === 'failed'){{ __('hub_my.payment_failed') }}
@elseif($state['payment'] === 'refunded'){{ __('hub_my.payment_refunded') }}
@elseif($state['payment'] !== 'paid'){{ __('hub_my.payment_pending') }}
@elseif($state['done']){{ __('hub_my.all_ready') }}
@else{{ __('hub_my.creating_accounts') }}@endif
</p>
@if($state['payment'] === 'paid')@include('hub.my._accounts', ['accounts' => $accounts, 'canSee' => $canSee])@endif
<a class="btn btn-light" href="{{ route('my.packages.index') }}">{{ __('hub_my.back_to_packages') }}</a>
</div></div>
@unless($state['done'])
@push('scripts')
<script>
(function () {
  var url = @json(route('my.packages.return.status', $payment->id)), tries = 0, box = document.getElementById('hub-state');
  var timer = setInterval(function () {
    if (++tries > 40) { clearInterval(timer); box.textContent = @json(__('hub_my.slow')); return; }
    fetch(url, {headers: {'Accept': 'application/json'}, credentials: 'same-origin'})
      .then(function (r) { return r.ok ? r.json() : null; })
      .then(function (s) { if (s && s.done) { clearInterval(timer); location.reload(); } });
  }, 3000);
})();
</script>
@endpush
@endunless
@endsection
