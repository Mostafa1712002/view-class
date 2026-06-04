@extends('layouts.app')
@section('body_class','theme-light')
@section('title', __('canteen.categories.title'))
@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-8 col-12 mb-2">
        <h2 class="content-header-title mb-0">@lang('canteen.categories.title') — {{ $canteen->name_ar }}</h2>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.canteens.index') }}">@lang('canteen.title')</a></li>
            <li class="breadcrumb-item active">@lang('canteen.categories.title')</li>
        </ol>
    </div>
    <div class="content-header-right col-md-4 col-12 text-md-right">
        <a href="{{ route('admin.canteens.products.index', $canteen->id) }}" class="btn btn-outline-primary btn-sm"><i class="la la-box"></i> @lang('canteen.products.title')</a>
    </div>
</div>

<div class="content-body">
    @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

    <div class="card mb-3">
        <div class="card-header"><h5 class="mb-0"><i class="la la-plus"></i> @lang('canteen.categories.add')</h5></div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.canteens.categories.store', $canteen->id) }}" class="form-row align-items-end">
                @csrf
                <div class="form-group col-md-6 mb-0">
                    <label class="form-label small mb-1">@lang('canteen.categories.fields.name')</label>
                    <input type="text" name="name" class="form-control form-control-sm" required maxlength="255">
                </div>
                <div class="form-group col-md-3 mb-0">
                    <label class="form-label small mb-1">@lang('canteen.categories.fields.sort_order')</label>
                    <input type="number" name="sort_order" value="0" class="form-control form-control-sm" min="0">
                </div>
                <div class="form-group col-md-3 mb-0">
                    <button type="submit" class="btn btn-primary btn-sm btn-block"><i class="la la-save"></i> @lang('canteen.actions.save')</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card"><div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>@lang('canteen.categories.fields.name')</th>
                    <th style="width:120px">@lang('canteen.categories.fields.sort_order')</th>
                    <th style="width:140px">@lang('canteen.cols.status')</th>
                    <th class="text-right" style="width:160px">@lang('canteen.cols.controls')</th>
                </tr>
            </thead>
            <tbody>
                @forelse($categories as $cat)
                    @php $fid = 'catform'.$cat->id; @endphp
                    <tr>
                        <td>
                            <form id="{{ $fid }}" method="POST" action="{{ route('admin.canteens.categories.update', [$canteen->id, $cat->id]) }}">@csrf @method('PUT')</form>
                            <input type="hidden" name="is_active" value="0" form="{{ $fid }}">
                            <input type="text" name="name" value="{{ $cat->name }}" class="form-control form-control-sm" required form="{{ $fid }}">
                        </td>
                        <td><input type="number" name="sort_order" value="{{ $cat->sort_order }}" class="form-control form-control-sm" min="0" form="{{ $fid }}"></td>
                        <td>
                            <div class="form-check">
                                <input type="checkbox" name="is_active" value="1" class="form-check-input" id="cat-{{ $cat->id }}" form="{{ $fid }}" @checked($cat->is_active)>
                                <label class="form-check-label" for="cat-{{ $cat->id }}">@lang('canteen.status.active')</label>
                            </div>
                        </td>
                        <td class="text-right">
                            <button type="submit" form="{{ $fid }}" class="btn btn-sm btn-outline-primary" title="@lang('canteen.actions.save')"><i class="la la-save"></i></button>
                            <form method="POST" action="{{ route('admin.canteens.categories.destroy', [$canteen->id, $cat->id]) }}" class="d-inline" onsubmit="return confirm('@lang('canteen.confirm_delete')');">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="@lang('canteen.actions.delete')"><i class="la la-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center py-4 text-muted">@lang('canteen.categories.empty')</td></tr>
                @endforelse
            </tbody>
        </table>
    </div></div>
</div>
@endsection
