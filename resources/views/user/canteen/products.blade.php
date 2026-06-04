@extends('layouts.app')
@section('body_class','theme-light')
@section('title', __('canteen.parent.manage_blocks'))
@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-12 col-12 mb-2">
        <h2 class="content-header-title mb-0">@lang('canteen.parent.manage_blocks') — {{ $child->name }}</h2>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
            <li class="breadcrumb-item"><a href="{{ route('my.canteen.index') }}">@lang('canteen.parent.title')</a></li>
            <li class="breadcrumb-item active">@lang('canteen.parent.manage_blocks')</li>
        </ol>
    </div>
</div>
<div class="content-body">
    @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
    <p class="text-muted">@lang('canteen.parent.blocks_hint')</p>

    <div class="card"><div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>@lang('canteen.products.cols.name')</th>
                    <th>@lang('canteen.products.cols.category')</th>
                    <th>@lang('canteen.products.cols.price')</th>
                    <th>@lang('canteen.products.cols.calories')</th>
                    <th class="text-right">@lang('canteen.parent.block_status')</th>
                </tr>
            </thead>
            <tbody>
                @forelse($products as $p)
                    <tr>
                        <td>{{ $p->name }}</td>
                        <td>{{ optional($p->category)->name ?? '—' }}</td>
                        <td>{{ number_format((float) $p->price, 2) }}</td>
                        <td>{{ $p->calories !== null ? $p->calories : '—' }}</td>
                        <td class="text-right">
                            <form method="POST" action="{{ route('my.canteen.products.toggle', [$child->id, $p->id]) }}" class="d-inline">
                                @csrf
                                @if(isset($blocked[$p->id]))
                                    <span class="badge badge-danger mr-1">@lang('canteen.parent.blocked')</span>
                                    <button type="submit" class="btn btn-sm btn-outline-success"><i class="la la-unlock"></i> @lang('canteen.parent.unblock')</button>
                                @else
                                    <span class="badge badge-success mr-1">@lang('canteen.parent.allowed')</span>
                                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="la la-ban"></i> @lang('canteen.parent.block')</button>
                                @endif
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center py-5 text-muted">@lang('canteen.parent.no_products')</td></tr>
                @endforelse
            </tbody>
        </table>
    </div></div>
</div>
@endsection
