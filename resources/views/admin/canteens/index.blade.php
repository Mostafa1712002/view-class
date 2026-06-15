@extends('layouts.app')
@section('body_class','theme-light')
@section('title', __('canteen.title'))
@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-7 col-12 mb-2">
        <h2 class="content-header-title mb-0">@lang('canteen.title')</h2>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
            <li class="breadcrumb-item active">@lang('canteen.title')</li>
        </ol>
    </div>
    <div class="content-header-right col-md-5 col-12 text-md-right">
        <a href="{{ route('admin.canteen-orders.index') }}" class="btn btn-outline-primary btn-sm"><x-svg-icon name="receipt" /> @lang('canteen.orders.title')</a>
        <a href="{{ route('admin.canteen-balances.index') }}" class="btn btn-outline-primary btn-sm"><x-svg-icon name="wallet2" /> @lang('canteen.balances.title')</a>
        <a href="{{ route('admin.canteens.create') }}" class="btn btn-primary btn-sm"><x-svg-icon name="plus" /> @lang('canteen.add')</a>
    </div>
</div>

<div class="content-body">
    @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

    <div class="card mb-3"><div class="card-body">
        <form method="GET" action="{{ route('admin.canteens.index') }}" class="form-row align-items-end">
            <div class="form-group col-md-5 mb-0"><input type="text" name="q" value="{{ $q ?? '' }}" class="form-control form-control-sm" placeholder="@lang('canteen.search')"></div>
            <div class="form-group col-md-2 mb-0"><button type="submit" class="btn btn-primary btn-sm"><x-svg-icon name="search" /> @lang('canteen.search_btn')</button></div>
        </form>
    </div></div>

    <div class="card"><div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>@lang('canteen.cols.name')</th>
                    <th>@lang('canteen.cols.school')</th>
                    <th>@lang('canteen.cols.manager')</th>
                    <th>@lang('canteen.cols.readiness')</th>
                    <th>@lang('canteen.cols.created_at')</th>
                    <th>@lang('canteen.cols.status')</th>
                    <th class="text-right">@lang('canteen.cols.controls')</th>
                </tr>
            </thead>
            <tbody>
                @forelse($canteens as $c)
                    <tr>
                        <td>{{ $c->name_ar }}@if($c->name_en)<br><small class="text-muted">{{ $c->name_en }}</small>@endif</td>
                        <td>{{ optional($c->school)->name ?? '—' }}</td>
                        <td>{{ optional($c->manager)->name ?? '—' }}</td>
                        <td>
                            <span class="badge {{ $c->categories_count ? 'badge-info' : 'badge-secondary' }}">@lang('canteen.cols.categories'): {{ $c->categories_count }}</span>
                            <span class="badge {{ $c->products_count ? 'badge-info' : 'badge-secondary' }}">@lang('canteen.cols.products'): {{ $c->products_count }}</span>
                        </td>
                        <td><small>{{ optional($c->created_at)->format('Y-m-d') }}</small></td>
                        <td>
                            @if($c->is_active)<span class="badge badge-success">@lang('canteen.status.active')</span>
                            @else<span class="badge badge-secondary">@lang('canteen.status.inactive')</span>@endif
                        </td>
                        <td class="text-right">
                            <a href="{{ route('admin.canteens.edit', $c->id) }}" class="btn btn-sm btn-outline-primary" title="@lang('canteen.actions.edit')"><x-svg-icon name="pencil-square" /></a>
                            <a href="{{ route('admin.canteens.manager', $c->id) }}" class="btn btn-sm btn-outline-info" title="@lang('canteen.actions.assign_manager')"><x-svg-icon name="person-workspace" /></a>
                            <a href="{{ route('admin.canteens.categories.index', $c->id) }}" class="btn btn-sm btn-outline-secondary" title="@lang('canteen.categories.title')"><x-svg-icon name="tags" /></a>
                            <a href="{{ route('admin.canteens.products.index', $c->id) }}" class="btn btn-sm btn-outline-secondary" title="@lang('canteen.products.title')"><x-svg-icon name="box" /></a>
                            @if($c->is_active)
                                <form method="POST" action="{{ route('admin.canteens.deactivate', $c->id) }}" class="d-inline">@csrf
                                    <button type="submit" class="btn btn-sm btn-outline-warning" title="@lang('canteen.actions.deactivate')"><x-svg-icon name="pause-circle" /></button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('admin.canteens.activate', $c->id) }}" class="d-inline">@csrf
                                    <button type="submit" class="btn btn-sm btn-outline-success" title="@lang('canteen.actions.activate')"><x-svg-icon name="play-circle" /></button>
                                </form>
                            @endif
                            <form method="POST" action="{{ route('admin.canteens.destroy', $c->id) }}" class="d-inline" onsubmit="return confirm('@lang('canteen.confirm_delete')');">@csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="@lang('canteen.actions.delete')"><x-svg-icon name="trash" /></button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center py-5 text-muted"><i class="la la-utensils la-3x d-block mb-2"></i>@lang('canteen.empty')</td></tr>
                @endforelse
            </tbody>
        </table>
    </div></div>
    <p class="text-muted small mt-2">@lang('canteen.activation.hint')</p>
</div>
@endsection
