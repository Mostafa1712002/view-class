@extends('layouts.app')
@section('body_class','theme-light')
@section('title', __('canteen.orders.title').' #'.$order->id)
@section('content')
@php
$badge = ['new'=>'badge-info','confirmed'=>'badge-primary','prepared'=>'badge-warning','delivered'=>'badge-success','cancelled'=>'badge-secondary'];
@endphp
<div class="content-header row">
    <div class="content-header-left col-md-12 col-12 mb-2">
        <h2 class="content-header-title mb-0">@lang('canteen.orders.title') #{{ $order->id }}</h2>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.canteen-orders.index') }}">@lang('canteen.orders.title')</a></li>
            <li class="breadcrumb-item active">#{{ $order->id }}</li>
        </ol>
    </div>
</div>
<div class="content-body">
    @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

    <div class="row">
        <div class="col-lg-5 mb-3">
            <div class="card"><div class="card-body">
                <h5 class="card-title">@lang('canteen.orders.details')</h5>
                <ul class="list-unstyled mb-3">
                    <li><strong>@lang('canteen.orders.cols.canteen'):</strong> {{ optional($order->canteen)->name_ar ?? '—' }}</li>
                    <li><strong>@lang('canteen.orders.cols.student'):</strong> {{ optional($order->student)->name ?? '—' }}</li>
                    <li><strong>@lang('canteen.orders.cols.status'):</strong> <span class="badge {{ $badge[$order->status] ?? 'badge-light' }}">@lang('canteen.orders.statuses.'.$order->status)</span></li>
                    <li><strong>@lang('canteen.orders.cols.total'):</strong> {{ number_format((float) $order->total, 2) }}</li>
                    <li><strong>@lang('canteen.orders.charged'):</strong> {{ $order->charged ? __('canteen.actions.activate') : '—' }}</li>
                    @if($order->note)<li><strong>@lang('canteen.orders.fields.note'):</strong> {{ $order->note }}</li>@endif
                    <li><strong>@lang('canteen.orders.cols.date'):</strong> {{ optional($order->created_at)->format('Y-m-d H:i') }}</li>
                </ul>

                @php $next = \App\Models\CanteenOrder::FLOW[$order->status] ?? []; @endphp
                @if(!empty($next))
                    <h6>@lang('canteen.orders.change_status')</h6>
                    <div class="d-flex flex-wrap" style="gap:.4rem;">
                        @foreach($next as $st)
                            <form method="POST" action="{{ route('admin.canteen-orders.status', $order->id) }}"
                                  @if($st==='cancelled') onsubmit="return confirm('@lang('canteen.orders.confirm_cancel')')" @endif>
                                @csrf @method('PUT')
                                <input type="hidden" name="status" value="{{ $st }}">
                                <button type="submit" class="btn btn-sm {{ $st==='cancelled' ? 'btn-outline-danger' : 'btn-outline-primary' }}">
                                    @lang('canteen.orders.statuses.'.$st)
                                </button>
                            </form>
                        @endforeach
                    </div>
                @endif
            </div></div>
        </div>
        <div class="col-lg-7 mb-3">
            <div class="card"><div class="card-header"><h5 class="mb-0">@lang('canteen.orders.products')</h5></div>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead><tr><th>@lang('canteen.products.cols.name')</th><th>@lang('canteen.orders.fields.qty')</th><th>@lang('canteen.products.cols.price')</th><th>@lang('canteen.orders.line_total')</th></tr></thead>
                    <tbody>
                        @foreach($order->items as $it)
                            <tr><td>{{ $it->product_name }}</td><td>{{ $it->quantity }}</td><td>{{ number_format((float) $it->unit_price, 2) }}</td><td>{{ number_format((float) $it->line_total, 2) }}</td></tr>
                        @endforeach
                        <tr><th colspan="3" class="text-right">@lang('canteen.orders.cols.total')</th><th>{{ number_format((float) $order->total, 2) }}</th></tr>
                    </tbody>
                </table>
            </div></div>
        </div>
    </div>
</div>
@endsection
