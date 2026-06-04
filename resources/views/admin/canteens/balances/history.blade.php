@extends('layouts.app')
@section('body_class','theme-light')
@section('title', __('canteen.balances.history'))
@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-12 col-12 mb-2">
        <h2 class="content-header-title mb-0">@lang('canteen.balances.history') — {{ $student->name }}</h2>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.canteen-balances.index') }}">@lang('canteen.balances.title')</a></li>
            <li class="breadcrumb-item active">@lang('canteen.balances.history')</li>
        </ol>
    </div>
</div>
<div class="content-body"><div class="card"><div class="table-responsive">
    <table class="table table-hover mb-0">
        <thead>
            <tr>
                <th>@lang('canteen.balances.cols.type')</th>
                <th>@lang('canteen.balances.cols.amount')</th>
                <th>@lang('canteen.balances.cols.balance_after')</th>
                <th>@lang('canteen.balances.cols.source')</th>
                <th>@lang('canteen.balances.cols.note')</th>
                <th>@lang('canteen.balances.cols.by')</th>
                <th>@lang('canteen.balances.cols.last_tx')</th>
            </tr>
        </thead>
        <tbody>
            @forelse($transactions as $t)
                <tr>
                    <td>
                        @if($t->type === 'add')<span class="badge badge-success">@lang('canteen.balances.types.add')</span>
                        @elseif($t->type === 'deduct')<span class="badge badge-danger">@lang('canteen.balances.types.deduct')</span>
                        @else<span class="badge badge-info">@lang('canteen.balances.types.set')</span>@endif
                    </td>
                    <td>{{ number_format((float) $t->amount, 2) }}</td>
                    <td><strong>{{ number_format((float) $t->balance_after, 2) }}</strong></td>
                    <td><span class="badge badge-light">{{ $t->source }}</span></td>
                    <td><small>{{ $t->note ?? '—' }}</small></td>
                    <td><small>{{ optional($t->performer)->name ?? '—' }}</small></td>
                    <td><small>{{ optional($t->created_at)->format('Y-m-d H:i') }}</small></td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center py-5 text-muted">@lang('canteen.balances.no_history')</td></tr>
            @endforelse
        </tbody>
    </table>
</div></div></div>
@endsection
