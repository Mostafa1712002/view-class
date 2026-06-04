@extends('layouts.app')
@section('body_class','theme-light')
@section('title', __('canteen.balances.title'))
@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-7 col-12 mb-2">
        <h2 class="content-header-title mb-0">@lang('canteen.balances.title')</h2>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.canteens.index') }}">@lang('canteen.title')</a></li>
            <li class="breadcrumb-item active">@lang('canteen.balances.title')</li>
        </ol>
    </div>
</div>

<div class="content-body">
    @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

    <div class="card mb-3"><div class="card-body">
        <form method="GET" action="{{ route('admin.canteen-balances.index') }}" class="form-row align-items-end">
            <div class="form-group col-md-5 mb-0"><input type="text" name="q" value="{{ $q ?? '' }}" class="form-control form-control-sm" placeholder="@lang('canteen.balances.search')"></div>
            <div class="form-group col-md-2 mb-0"><button type="submit" class="btn btn-primary btn-sm"><i class="la la-search"></i> @lang('canteen.search_btn')</button></div>
        </form>
    </div></div>

    <div class="card"><div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>@lang('canteen.balances.cols.student')</th>
                    <th>@lang('canteen.balances.cols.grade')</th>
                    <th>@lang('canteen.balances.cols.class')</th>
                    <th>@lang('canteen.balances.cols.balance')</th>
                    <th>@lang('canteen.balances.cols.daily_limit')</th>
                    <th>@lang('canteen.balances.cols.last_tx')</th>
                    <th class="text-right">@lang('canteen.cols.controls')</th>
                </tr>
            </thead>
            <tbody>
                @forelse($students as $s)
                    <tr>
                        <td>{{ $s->name }}</td>
                        <td>{{ $s->grade_name ?? '—' }}</td>
                        <td>{{ $s->class_name ?? '—' }}</td>
                        <td><strong>{{ number_format((float) $s->balance, 2) }}</strong></td>
                        <td>{{ $s->daily_limit !== null ? number_format((float) $s->daily_limit, 2) : '—' }}</td>
                        <td><small>{{ $s->last_tx ? \Illuminate\Support\Carbon::parse($s->last_tx)->format('Y-m-d H:i') : '—' }}</small></td>
                        <td class="text-right">
                            <a href="{{ route('admin.canteen-balances.edit', $s->id) }}" class="btn btn-sm btn-outline-primary" title="@lang('canteen.balances.edit')"><i class="la la-wallet"></i></a>
                            <a href="{{ route('admin.canteen-balances.history', $s->id) }}" class="btn btn-sm btn-outline-secondary" title="@lang('canteen.balances.history')"><i class="la la-history"></i></a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center py-5 text-muted"><i class="la la-wallet la-3x d-block mb-2"></i>@lang('canteen.balances.empty')</td></tr>
                @endforelse
            </tbody>
        </table>
    </div></div>
</div>
@endsection
