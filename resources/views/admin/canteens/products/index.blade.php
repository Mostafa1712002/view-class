@extends('layouts.app')
@section('body_class','theme-light')
@section('title', __('canteen.products.title'))
@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-7 col-12 mb-2">
        <h2 class="content-header-title mb-0">@lang('canteen.products.title') — {{ $canteen->name_ar }}</h2>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.canteens.index') }}">@lang('canteen.title')</a></li>
            <li class="breadcrumb-item active">@lang('canteen.products.title')</li>
        </ol>
    </div>
    <div class="content-header-right col-md-5 col-12 text-md-right">
        <a href="{{ route('admin.canteens.categories.index', $canteen->id) }}" class="btn btn-outline-secondary btn-sm"><i class="la la-tags"></i> @lang('canteen.categories.title')</a>
        <a href="{{ route('admin.canteens.products.create', $canteen->id) }}" class="btn btn-primary btn-sm"><i class="la la-plus"></i> @lang('canteen.products.add')</a>
    </div>
</div>

<div class="content-body">
    @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

    <div class="card"><div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th style="width:64px">@lang('canteen.products.cols.image')</th>
                    <th>@lang('canteen.products.cols.name')</th>
                    <th>@lang('canteen.products.cols.category')</th>
                    <th>@lang('canteen.products.cols.price')</th>
                    <th>@lang('canteen.products.cols.calories')</th>
                    <th>@lang('canteen.cols.status')</th>
                    <th class="text-right">@lang('canteen.cols.controls')</th>
                </tr>
            </thead>
            <tbody>
                @forelse($products as $p)
                    <tr>
                        <td>
                            @if($p->imageUrl())
                                <img src="{{ $p->imageUrl() }}" alt="" style="width:48px;height:48px;object-fit:cover;border-radius:.35rem;">
                            @else
                                <span class="text-muted"><i class="la la-image" style="font-size:1.6rem;"></i></span>
                            @endif
                        </td>
                        <td>{{ $p->name }}</td>
                        <td>{{ optional($p->category)->name ?? '—' }}</td>
                        <td>{{ number_format((float) $p->price, 2) }}</td>
                        <td>{{ $p->calories !== null ? $p->calories : '—' }}</td>
                        <td>
                            @if($p->is_active)<span class="badge badge-success">@lang('canteen.status.active')</span>
                            @else<span class="badge badge-secondary">@lang('canteen.status.inactive')</span>@endif
                        </td>
                        <td class="text-right">
                            <a href="{{ route('admin.canteens.products.edit', [$canteen->id, $p->id]) }}" class="btn btn-sm btn-outline-primary" title="@lang('canteen.actions.edit')"><i class="la la-edit"></i></a>
                            <form method="POST" action="{{ route('admin.canteens.products.toggle', [$canteen->id, $p->id]) }}" class="d-inline">@csrf
                                <button type="submit" class="btn btn-sm btn-outline-secondary" title="{{ $p->is_active ? __('canteen.actions.deactivate') : __('canteen.actions.activate') }}"><i class="la {{ $p->is_active ? 'la-toggle-on' : 'la-toggle-off' }}"></i></button>
                            </form>
                            <form method="POST" action="{{ route('admin.canteens.products.destroy', [$canteen->id, $p->id]) }}" class="d-inline" onsubmit="return confirm('@lang('canteen.confirm_delete')');">@csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="@lang('canteen.actions.delete')"><i class="la la-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center py-5 text-muted"><i class="la la-box la-3x d-block mb-2"></i>@lang('canteen.products.empty')</td></tr>
                @endforelse
            </tbody>
        </table>
    </div></div>
</div>
@endsection
