@extends('layouts.app')
@section('body_class','theme-light')
@section('title', __('canteen.parent.orders'))
@section('content')
@php
$badge = ['new'=>'badge-info','confirmed'=>'badge-primary','prepared'=>'badge-warning','delivered'=>'badge-success','cancelled'=>'badge-secondary'];
@endphp
<div class="content-header row">
    <div class="content-header-left col-md-12 col-12 mb-2">
        <h2 class="content-header-title mb-0">@lang('canteen.parent.orders') — {{ $child->name }}</h2>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
            <li class="breadcrumb-item"><a href="{{ route('my.canteen.index') }}">@lang('canteen.parent.title')</a></li>
            <li class="breadcrumb-item active">@lang('canteen.parent.orders')</li>
        </ol>
    </div>
</div>
<div class="content-body"><div class="card"><div class="table-responsive">
    <table class="table table-hover mb-0">
        <thead>
            <tr>
                <th>#</th>
                <th>@lang('canteen.orders.cols.canteen')</th>
                <th>@lang('canteen.parent.items')</th>
                <th>@lang('canteen.orders.cols.total')</th>
                <th>@lang('canteen.orders.cols.status')</th>
                <th>@lang('canteen.orders.cols.date')</th>
            </tr>
        </thead>
        <tbody>
            @forelse($orders as $o)
                <tr>
                    <td>{{ $o->id }}</td>
                    <td>{{ optional($o->canteen)->name_ar ?? '—' }}</td>
                    <td><small>{{ $o->items->map(fn($i) => $i->product_name.' ×'.$i->quantity)->implode('، ') }}</small></td>
                    <td>{{ number_format((float) $o->total, 2) }}</td>
                    <td><span class="badge {{ $badge[$o->status] ?? 'badge-light' }}">@lang('canteen.orders.statuses.'.$o->status)</span></td>
                    <td><small>{{ optional($o->created_at)->format('Y-m-d H:i') }}</small></td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center py-5 text-muted">@lang('canteen.parent.no_orders')</td></tr>
            @endforelse
        </tbody>
    </table>
</div></div></div>
@endsection
