@extends('layouts.app')
@section('body_class','theme-light')
@section('title', __('canteen.parent.title'))
@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-12 col-12 mb-2">
        <h2 class="content-header-title mb-0">@lang('canteen.parent.title')</h2>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
            <li class="breadcrumb-item active">@lang('canteen.parent.title')</li>
        </ol>
    </div>
</div>
<div class="content-body">
    @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
    <p class="text-muted">@lang('canteen.parent.intro')</p>

    @forelse($children as $c)
        <div class="card mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start flex-wrap" style="gap:.5rem;">
                    <div>
                        <h5 class="mb-1">{{ $c->name }}</h5>
                        <p class="mb-0">@lang('canteen.parent.balance'): <strong style="font-size:1.2rem;">{{ number_format((float) $c->balance, 2) }}</strong></p>
                    </div>
                    <div class="d-flex flex-wrap" style="gap:.4rem;">
                        <a href="{{ route('my.canteen.products', $c->id) }}" class="btn btn-sm btn-outline-secondary"><i class="la la-ban"></i> @lang('canteen.parent.manage_blocks')</a>
                        <a href="{{ route('my.canteen.orders', $c->id) }}" class="btn btn-sm btn-outline-info"><i class="la la-receipt"></i> @lang('canteen.parent.orders')</a>
                    </div>
                </div>
                <hr>
                <form method="POST" action="{{ route('my.canteen.limit', $c->id) }}" class="form-row align-items-end">
                    @csrf @method('PUT')
                    <div class="form-group col-md-4 mb-0">
                        <label class="form-label small mb-1">@lang('canteen.parent.daily_limit')</label>
                        <input type="number" step="0.01" min="0" name="daily_limit" value="{{ $c->daily_limit }}" class="form-control form-control-sm" placeholder="@lang('canteen.parent.no_limit')">
                    </div>
                    <div class="form-group col-md-3 mb-0">
                        <button type="submit" class="btn btn-primary btn-sm"><i class="la la-save"></i> @lang('canteen.parent.save_limit')</button>
                    </div>
                    <div class="col-md-5"><small class="text-muted">@lang('canteen.parent.daily_limit_hint')</small></div>
                </form>
            </div>
        </div>
    @empty
        <div class="card"><div class="card-body text-center text-muted py-5"><i class="la la-child la-3x d-block mb-2"></i>@lang('canteen.parent.no_children')</div></div>
    @endforelse
</div>
@endsection
