@extends('layouts.app')
@section('title', __('policies.title'))
@section('body_class', 'theme-light')
@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-8 col-12 mb-2">
        <h2 class="content-header-title mb-0">@lang('policies.title')</h2>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
            <li class="breadcrumb-item active">@lang('policies.title')</li>
        </ol>
    </div>
    <div class="content-header-right col-md-4 col-12 text-md-left">
        <a href="{{ route('admin.policies.create') }}" class="btn btn-primary btn-sm"><i class="la la-plus"></i> @lang('policies.add')</a>
    </div>
</div>

<div class="content-body">
    @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif

    <div class="card">
        <div class="card-header">
            <form method="GET" action="{{ route('admin.policies.index') }}" class="d-flex" style="gap:.5rem;max-width:420px;">
                <input type="search" name="q" value="{{ $q }}" class="form-control form-control-sm" placeholder="@lang('policies.search')">
                <button class="btn btn-sm btn-soft"><i class="la la-search"></i></button>
            </form>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>@lang('policies.cols.title')</th>
                            <th>@lang('policies.cols.roles')</th>
                            <th>@lang('policies.cols.beneficiaries')</th>
                            <th>@lang('policies.cols.read')</th>
                            <th>@lang('policies.cols.created_at')</th>
                            <th>@lang('policies.cols.creator')</th>
                            <th class="text-end">@lang('policies.cols.actions')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($policies as $p)
                            <tr>
                                <td>{{ $p->title }}</td>
                                <td>
                                    @foreach(($p->target_roles ?? []) as $r)
                                        <span class="badge badge-info">@lang('policies.roles.'.$r)</span>
                                    @endforeach
                                </td>
                                <td>{{ $p->beneficiaries_count }}</td>
                                <td><span class="text-success">{{ $p->read_count }}</span> / {{ $p->beneficiaries_count }}</td>
                                <td><small>{{ $p->created_at?->format('Y-m-d') }}</small></td>
                                <td><small>{{ optional($p->creator)->name ?? '—' }}</small></td>
                                <td class="text-end">
                                    <div class="dropdown d-inline">
                                        <button class="btn btn-sm btn-soft" data-toggle="dropdown" data-bs-toggle="dropdown"><i class="la la-ellipsis-v"></i></button>
                                        <div class="dropdown-menu dropdown-menu-end">
                                            <a class="dropdown-item" href="{{ route('admin.policies.acknowledgements', $p->id) }}"><i class="la la-clipboard-check"></i> @lang('policies.actions.view_acks')</a>
                                            <a class="dropdown-item" href="{{ route('admin.policies.edit', $p->id) }}"><i class="la la-pen"></i> @lang('policies.actions.edit')</a>
                                            <form method="POST" action="{{ route('admin.policies.destroy', $p->id) }}" onsubmit="return confirm('@lang('policies.confirm_delete')')">
                                                @csrf @method('DELETE')
                                                <button class="dropdown-item text-danger"><i class="la la-trash"></i> @lang('policies.actions.delete')</button>
                                            </form>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-4">@lang('policies.empty')</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($policies->hasPages())<div class="card-footer">{{ $policies->links() }}</div>@endif
    </div>
</div>
@endsection
