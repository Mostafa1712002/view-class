@extends('layouts.app')
@section('body_class','theme-light')
@section('title', __('behavior.actions_page.title'))
@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-7 col-12 mb-2">
        <h2 class="content-header-title mb-0">@lang('behavior.actions_page.title')</h2>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
            <li class="breadcrumb-item active">@lang('behavior.actions_page.title')</li>
        </ol>
    </div>
    <div class="content-header-right col-md-5 col-12 text-md-right">
        <a href="{{ route('admin.behavior.actions.create', ['tab' => $tab]) }}" class="btn btn-primary btn-sm">
            <i class="la la-plus"></i> @lang('behavior.actions_page.add')
        </a>
    </div>
</div>

<div class="content-body">
    @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

    <ul class="nav nav-tabs mb-3">
        <li class="nav-item"><a class="nav-link {{ $tab === 'student' ? 'active' : '' }}" href="{{ route('admin.behavior.actions.index', ['tab' => 'student']) }}"><i class="la la-user-graduate"></i> @lang('behavior.tabs.students')</a></li>
        <li class="nav-item"><a class="nav-link {{ $tab === 'teacher' ? 'active' : '' }}" href="{{ route('admin.behavior.actions.index', ['tab' => 'teacher']) }}"><i class="la la-chalkboard-teacher"></i> @lang('behavior.tabs.teachers')</a></li>
    </ul>

    <div class="card mb-3"><div class="card-body">
        <form method="GET" action="{{ route('admin.behavior.actions.index') }}" class="form-row align-items-end">
            <input type="hidden" name="tab" value="{{ $tab }}">
            <div class="form-group col-md-5 mb-0"><input type="text" name="q" value="{{ $q ?? '' }}" class="form-control form-control-sm" placeholder="@lang('behavior.actions_page.search')"></div>
            <div class="form-group col-md-2 mb-0"><button type="submit" class="btn btn-primary btn-sm"><i class="la la-search"></i> @lang('behavior.search_btn')</button></div>
        </form>
    </div></div>

    <div class="card"><div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>@lang('behavior.actions_page.cols.title')</th>
                    <th>@lang('behavior.actions_page.cols.behavior')</th>
                    <th>@lang('behavior.actions_page.cols.points')</th>
                    <th>@lang('behavior.actions_page.cols.effect')</th>
                    <th>@lang('behavior.actions_page.cols.notify')</th>
                    <th>@lang('behavior.actions_page.cols.followup')</th>
                    <th>@lang('behavior.actions_page.cols.status')</th>
                    <th class="text-right">@lang('behavior.actions_page.cols.controls')</th>
                </tr>
            </thead>
            <tbody>
                @forelse($actions as $a)
                    <tr>
                        <td>{{ \Illuminate\Support\Str::limit($a->description, 60) }}</td>
                        <td>{{ optional($a->behavior)->name ?? '—' }}</td>
                        <td>{{ $a->points }}</td>
                        <td>
                            @if($a->point_type === 'add')<span class="badge badge-success">+ @lang('behavior.point_types.add')</span>
                            @else<span class="badge badge-danger">- @lang('behavior.point_types.deduct')</span>@endif
                        </td>
                        <td>
                            @if($a->notify_parent)<span class="badge badge-info">@lang('behavior.yes')</span>
                            @else<span class="badge badge-secondary">@lang('behavior.no')</span>@endif
                        </td>
                        <td>
                            @if($a->needs_followup)<span class="badge badge-warning">@lang('behavior.yes')</span>
                            @else<span class="badge badge-secondary">@lang('behavior.no')</span>@endif
                        </td>
                        <td>
                            @if($a->is_active)<span class="badge badge-success">@lang('behavior.status.active')</span>
                            @else<span class="badge badge-secondary">@lang('behavior.status.inactive')</span>@endif
                        </td>
                        <td class="text-right">
                            <a href="{{ route('admin.behavior.actions.edit', $a->id) }}" class="btn btn-sm btn-outline-primary" title="@lang('behavior.actions.edit')"><i class="la la-edit"></i></a>
                            <form method="POST" action="{{ route('admin.behavior.actions.toggle', $a->id) }}" class="d-inline">@csrf
                                <button type="submit" class="btn btn-sm btn-outline-secondary" title="{{ $a->is_active ? __('behavior.actions.disable') : __('behavior.actions.enable') }}"><i class="la {{ $a->is_active ? 'la-toggle-on' : 'la-toggle-off' }}"></i></button>
                            </form>
                            <form method="POST" action="{{ route('admin.behavior.actions.destroy', $a->id) }}" class="d-inline" onsubmit="return confirm('@lang('behavior.confirm_delete')');">@csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="@lang('behavior.actions.delete')"><i class="la la-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center py-5 text-muted"><i class="la la-cogs la-3x d-block mb-2"></i>@lang('behavior.actions_page.empty')</td></tr>
                @endforelse
            </tbody>
        </table>
    </div></div>
</div>
@endsection
