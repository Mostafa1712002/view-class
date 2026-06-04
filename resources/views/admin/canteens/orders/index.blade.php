@extends('layouts.app')
@section('body_class','theme-light')
@section('title', __('canteen.orders.title'))
@section('content')
@php
$badge = ['new'=>'badge-info','confirmed'=>'badge-primary','prepared'=>'badge-warning','delivered'=>'badge-success','cancelled'=>'badge-secondary'];
@endphp
<div class="content-header row">
    <div class="content-header-left col-md-7 col-12 mb-2">
        <h2 class="content-header-title mb-0">@lang('canteen.orders.title')</h2>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.canteens.index') }}">@lang('canteen.title')</a></li>
            <li class="breadcrumb-item active">@lang('canteen.orders.title')</li>
        </ol>
    </div>
    <div class="content-header-right col-md-5 col-12 text-md-right">
        <a href="{{ route('admin.canteen-orders.create') }}" class="btn btn-primary btn-sm"><i class="la la-plus"></i> @lang('canteen.orders.add')</a>
    </div>
</div>

<div class="content-body">
    @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

    <div class="card mb-3"><div class="card-body">
        <form method="GET" action="{{ route('admin.canteen-orders.index') }}" class="form-row align-items-end">
            <div class="form-group col-md-4 mb-0">
                <label class="form-label small mb-1">@lang('canteen.orders.cols.status')</label>
                <select name="status" class="custom-select custom-select-sm" onchange="this.form.submit()">
                    <option value="">@lang('canteen.orders.all')</option>
                    @foreach(\App\Models\CanteenOrder::STATUSES as $st)
                        <option value="{{ $st }}" @selected(($status ?? '')===$st)>@lang('canteen.orders.statuses.'.$st)</option>
                    @endforeach
                </select>
            </div>
        </form>
    </div></div>

    <div class="card"><div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>@lang('canteen.orders.cols.canteen')</th>
                    <th>@lang('canteen.orders.cols.student')</th>
                    <th>@lang('canteen.orders.cols.total')</th>
                    <th>@lang('canteen.orders.cols.status')</th>
                    <th>@lang('canteen.orders.cols.date')</th>
                    <th class="text-right">@lang('canteen.cols.controls')</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $o)
                    <tr>
                        <td>{{ $o->id }}</td>
                        <td>{{ optional($o->canteen)->name_ar ?? '—' }}</td>
                        <td>{{ optional($o->student)->name ?? '—' }}</td>
                        <td>{{ number_format((float) $o->total, 2) }}</td>
                        <td><span class="badge {{ $badge[$o->status] ?? 'badge-light' }}">@lang('canteen.orders.statuses.'.$o->status)</span></td>
                        <td><small>{{ optional($o->created_at)->format('Y-m-d H:i') }}</small></td>
                        <td class="text-right">
                            <a href="{{ route('admin.canteen-orders.show', $o->id) }}" class="btn btn-sm btn-outline-primary" title="@lang('canteen.orders.view')"><i class="la la-eye"></i></a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center py-5 text-muted"><i class="la la-receipt la-3x d-block mb-2"></i>@lang('canteen.orders.empty')</td></tr>
                @endforelse
            </tbody>
        </table>
    </div></div>
</div>
@endsection
