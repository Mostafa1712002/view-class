@extends('layouts.app')

@section('body_class','theme-light')
@section('title', __('libraries.labs.manage_title'))

@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-8 col-12 mb-2">
        <h2 class="content-header-title mb-0">@lang('libraries.labs.manage_title')</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.libraries.labs.index') }}">@lang('libraries.labs.title')</a></li>
                <li class="breadcrumb-item active">@lang('libraries.labs.manage_title')</li>
            </ol>
        </div>
    </div>
    <div class="content-header-right col-md-4 col-12 text-md-end">
        <a class="btn btn-primary btn-sm" href="{{ route('admin.libraries.labs.create') }}"><i class="la la-plus"></i> @lang('libraries.labs.add')</a>
    </div>
</div>
<div class="content-body">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="thead-light"><tr>
                    <th>@lang('libraries.fields.title')</th>
                    <th>@lang('libraries.labs.fields.category')</th>
                    <th>@lang('libraries.labs.fields.external_url')</th>
                    <th>@lang('libraries.labs.fields.is_active')</th>
                    <th></th>
                </tr></thead>
                <tbody>
                    @forelse($labs as $lab)
                        <tr>
                            <td><strong>{{ $lab->title }}</strong></td>
                            <td>{{ $lab->category?->name_ar ?? '—' }}</td>
                            <td>@if($lab->external_url)<a href="{{ $lab->external_url }}" target="_blank">{{ \Illuminate\Support\Str::limit($lab->external_url, 40) }}</a>@else —@endif</td>
                            <td>{!! $lab->is_active ? '<span class="badge bg-success">✓</span>' : '<span class="badge bg-warning">—</span>' !!}</td>
                            <td>
                                <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.libraries.labs.edit', $lab->id) }}"><i class="la la-pen"></i></a>
                                <form action="{{ route('admin.libraries.labs.destroy', $lab->id) }}" method="POST" class="d-inline" onsubmit="return confirm('@lang('libraries.confirm_delete')')">
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
        <div class="card-footer">{{ $labs->links() }}</div>
    </div>
</div>
@endsection
