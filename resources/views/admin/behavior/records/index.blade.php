@extends('layouts.app')
@section('body_class','theme-light')
@section('title', __('behavior.records.title'))
@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-7 col-12 mb-2">
        <h2 class="content-header-title mb-0">@lang('behavior.records.title')</h2>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
            <li class="breadcrumb-item active">@lang('behavior.records.title')</li>
        </ol>
    </div>
    <div class="content-header-right col-md-5 col-12 text-md-right">
        <a href="{{ route('admin.behavior.records.create', ['tab' => $tab]) }}" class="btn btn-primary btn-sm">
            <i class="la la-plus"></i> @lang('behavior.records.add')
        </a>
    </div>
</div>

<div class="content-body">
    @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

    <ul class="nav nav-tabs mb-3">
        <li class="nav-item"><a class="nav-link {{ $tab === 'student' ? 'active' : '' }}" href="{{ route('admin.behavior.records.index', ['tab' => 'student']) }}"><i class="la la-user-graduate"></i> @lang('behavior.tabs.students')</a></li>
        <li class="nav-item"><a class="nav-link {{ $tab === 'teacher' ? 'active' : '' }}" href="{{ route('admin.behavior.records.index', ['tab' => 'teacher']) }}"><i class="la la-chalkboard-teacher"></i> @lang('behavior.tabs.teachers')</a></li>
    </ul>

    <div class="card mb-3"><div class="card-body">
        <form method="GET" action="{{ route('admin.behavior.records.index') }}" class="form-row align-items-end">
            <input type="hidden" name="tab" value="{{ $tab }}">
            <div class="form-group col-md-5 mb-0"><input type="text" name="q" value="{{ $q ?? '' }}" class="form-control form-control-sm" placeholder="@lang('behavior.records.search')"></div>
            <div class="form-group col-md-2 mb-0"><button type="submit" class="btn btn-primary btn-sm"><i class="la la-search"></i> @lang('behavior.search_btn')</button></div>
        </form>
    </div></div>

    <div class="card"><div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>@lang('behavior.records.cols.subject')</th>
                    <th>@lang('behavior.records.cols.behavior')</th>
                    <th>@lang('behavior.records.cols.action')</th>
                    <th>@lang('behavior.records.cols.points')</th>
                    <th>@lang('behavior.records.cols.notified')</th>
                    <th>@lang('behavior.records.cols.followup')</th>
                    <th>@lang('behavior.records.cols.recorded_by')</th>
                    <th>@lang('behavior.records.cols.date')</th>
                    <th class="text-right">@lang('behavior.records.cols.controls')</th>
                </tr>
            </thead>
            <tbody>
                @forelse($records as $r)
                    <tr>
                        <td>{{ optional($r->subject)->name ?? '—' }}</td>
                        <td>{{ optional($r->behavior)->name ?? '—' }}</td>
                        <td>{{ $r->action ? \Illuminate\Support\Str::limit($r->action->description, 40) : '—' }}</td>
                        <td>
                            @if($r->points > 0)<span class="badge badge-success">+{{ $r->points }}</span>
                            @elseif($r->points < 0)<span class="badge badge-danger">{{ $r->points }}</span>
                            @else<span class="badge badge-secondary">0</span>@endif
                        </td>
                        <td>
                            @if($r->notified_parent)<span class="badge badge-info">@lang('behavior.yes')</span>
                            @else<span class="badge badge-secondary">@lang('behavior.no')</span>@endif
                        </td>
                        <td>
                            @if($r->needs_followup)<span class="badge badge-warning">@lang('behavior.yes')</span>
                            @else<span class="badge badge-secondary">@lang('behavior.no')</span>@endif
                        </td>
                        <td><small>{{ optional($r->recorder)->name ?? '—' }}</small></td>
                        <td><small>{{ optional($r->created_at)->format('Y-m-d H:i') }}</small></td>
                        <td class="text-right">
                            <form method="POST" action="{{ route('admin.behavior.records.destroy', $r->id) }}" class="d-inline" onsubmit="return confirm('@lang('behavior.confirm_delete')');">@csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="@lang('behavior.actions.delete')"><i class="la la-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="text-center py-5 text-muted"><i class="la la-clipboard-list la-3x d-block mb-2"></i>@lang('behavior.records.empty')</td></tr>
                @endforelse
            </tbody>
        </table>
    </div></div>
</div>
@endsection
