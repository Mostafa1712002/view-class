@extends('layouts.app')

@section('body_class','theme-light')
@section('title', __('libraries.private.items_title'))

@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-8 col-12 mb-2">
        <h2 class="content-header-title mb-0">{{ $library->title }} — @lang('libraries.private.items_title')</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.libraries.private.index') }}">@lang('libraries.private.title')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.libraries.private.edit', $library->id) }}">{{ $library->title }}</a></li>
                <li class="breadcrumb-item active">@lang('libraries.private.items_title')</li>
            </ol>
        </div>
    </div>
</div>
<div class="content-body">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

    <div class="card mb-3">
        <div class="card-header"><h5 class="mb-0"><i class="la la-plus"></i> @lang('libraries.public.add_item')</h5></div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.libraries.private.items.store', $library->id) }}" enctype="multipart/form-data">
                @csrf
                <div class="row g-2">
                    <div class="col-md-4"><input type="text" name="title" class="form-control" placeholder="@lang('libraries.fields.title')" required /></div>
                    <div class="col-md-2"><select name="content_type" class="form-select" required>
                        @foreach($types as $t)<option value="{{ $t }}">@lang('libraries.types.'.$t)</option>@endforeach
                    </select></div>
                    <div class="col-md-3"><input type="url" name="external_url" class="form-control" placeholder="@lang('libraries.fields.external_url')" /></div>
                    <div class="col-md-2"><input type="file" name="file" class="form-control" /></div>
                    <div class="col-md-1"><button type="submit" class="btn btn-primary w-100"><i class="la la-plus"></i></button></div>
                </div>
                <div class="row g-2 mt-2"><div class="col-12"><textarea name="description" rows="1" class="form-control" placeholder="@lang('libraries.fields.description')"></textarea></div></div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>@lang('libraries.fields.title')</th>
                        <th>@lang('libraries.fields.content_type')</th>
                        <th>@lang('libraries.fields.file')</th>
                        <th>@lang('libraries.public.columns.actions')</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $item)
                        <tr>
                            <td><strong>{{ $item->title }}</strong></td>
                            <td><span class="badge bg-info">@lang('libraries.types.'.$item->content_type)</span></td>
                            <td>
                                @if($item->file_path)
                                    <a href="{{ asset('storage/' . $item->file_path) }}" target="_blank"><i class="la la-download"></i></a>
                                @elseif($item->external_url)
                                    <a href="{{ $item->external_url }}" target="_blank"><i class="la la-external-link-alt"></i></a>
                                @endif
                            </td>
                            <td>
                                <form action="{{ route('admin.libraries.private.items.destroy', [$library->id, $item->id]) }}" method="POST" onsubmit="return confirm('@lang('libraries.confirm_delete')')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="la la-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted py-4">@lang('libraries.public.no_results')</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $items->links() }}</div>
    </div>
</div>
@endsection
