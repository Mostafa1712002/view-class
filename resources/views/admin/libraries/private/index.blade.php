@extends('layouts.app')

@section('body_class','theme-light')
@section('title', __('libraries.private.title'))

@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-8 col-12 mb-2">
        <h2 class="content-header-title mb-0">@lang('libraries.private.title')</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
                <li class="breadcrumb-item">@lang('libraries.breadcrumb')</li>
                <li class="breadcrumb-item active">@lang('libraries.private.title')</li>
            </ol>
        </div>
    </div>
    <div class="content-header-right col-md-4 col-12 text-md-end">
        <a href="{{ route('admin.libraries.private.create') }}" class="btn btn-primary btn-sm">
            <i class="la la-plus"></i> @lang('libraries.private.add_library')
        </a>
    </div>
</div>

<div class="content-body">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

    <ul class="nav nav-tabs mb-3">
        <li class="nav-item"><a class="nav-link" href="{{ route('admin.libraries.public.index') }}">@lang('libraries.public.title')</a></li>
        <li class="nav-item"><a class="nav-link active" href="{{ route('admin.libraries.private.index') }}">@lang('libraries.private.title')</a></li>
        <li class="nav-item"><a class="nav-link" href="{{ route('admin.libraries.labs.index') }}">@lang('libraries.labs.title')</a></li>
    </ul>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <form method="GET" action="{{ route('admin.libraries.private.index') }}" class="d-flex">
                <input type="search" name="q" value="{{ request('q') }}" class="form-control form-control-sm me-1" placeholder="@lang('libraries.public.search_placeholder')" />
                <button class="btn btn-outline-primary btn-sm" type="submit"><i class="la la-search"></i></button>
            </form>
            <div class="text-muted small">{{ $libraries->total() }}</div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>@lang('libraries.private.columns.title')</th>
                        <th>@lang('libraries.private.columns.items_count')</th>
                        <th>@lang('libraries.private.columns.audiences_count')</th>
                        <th>@lang('libraries.private.columns.is_active')</th>
                        <th>@lang('libraries.private.columns.actions')</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($libraries as $library)
                        <tr>
                            <td><strong>{{ $library->title }}</strong>
                                @if($library->description)<small class="d-block text-muted">{{ \Illuminate\Support\Str::limit($library->description, 80) }}</small>@endif
                            </td>
                            <td><span class="badge bg-info">{{ $library->items_count ?? 0 }}</span></td>
                            <td><span class="badge bg-secondary">{{ $library->audiences_count ?? 0 }}</span></td>
                            <td>
                                @if($library->is_active)
                                    <span class="badge bg-success">@lang('libraries.private.columns.is_active')</span>
                                @else
                                    <span class="badge bg-warning">—</span>
                                @endif
                            </td>
                            <td>
                                <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.libraries.private.edit', $library->id) }}"><i class="la la-pen"></i></a>
                                <a class="btn btn-sm btn-outline-info" href="{{ route('admin.libraries.private.items', $library->id) }}"><i class="la la-list"></i> @lang('libraries.actions.view_items')</a>
                                <form action="{{ route('admin.libraries.private.destroy', $library->id) }}" method="POST" class="d-inline" onsubmit="return confirm('@lang('libraries.confirm_delete')')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="la la-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">@lang('libraries.public.no_results')</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $libraries->links() }}</div>
    </div>
</div>
@endsection
