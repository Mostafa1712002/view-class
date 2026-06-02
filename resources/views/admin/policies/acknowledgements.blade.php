@extends('layouts.app')
@section('title', __('policies.acks_title'))
@section('body_class', 'theme-light')
@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-8 col-12 mb-2">
        <h2 class="content-header-title mb-0">@lang('policies.acks_title')</h2>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.policies.index') }}">@lang('policies.title')</a></li>
            <li class="breadcrumb-item active">{{ $policy->title }}</li>
        </ol>
    </div>
    <div class="content-header-right col-md-4 col-12 text-md-left">
        <a href="{{ route('admin.policies.index') }}" class="btn btn-soft btn-sm"><i class="la la-arrow-right"></i> @lang('policies.actions.back')</a>
    </div>
</div>
<div class="content-body">
    @php $read = $rows->whereNotNull('read_at')->count(); @endphp
    <div class="row mb-3">
        <div class="col-md-3 col-6 mb-2"><div class="card text-center"><div class="card-body p-2"><div class="text-muted small">@lang('policies.cols.beneficiaries')</div><h3 class="fw-bold mb-0">{{ $rows->count() }}</h3></div></div></div>
        <div class="col-md-3 col-6 mb-2"><div class="card text-center" style="background:#ecfdf5;"><div class="card-body p-2"><div class="text-muted small">@lang('policies.status.read')</div><h3 class="fw-bold mb-0" style="color:#16a34a;">{{ $read }}</h3></div></div></div>
        <div class="col-md-3 col-6 mb-2"><div class="card text-center" style="background:#fef2f2;"><div class="card-body p-2"><div class="text-muted small">@lang('policies.status.unread')</div><h3 class="fw-bold mb-0" style="color:#dc2626;">{{ $rows->count() - $read }}</h3></div></div></div>
    </div>
    <div class="card"><div class="card-body p-0"><div class="table-responsive">
        <table class="table mb-0">
            <thead><tr>
                <th>@lang('policies.cols.user')</th>
                <th>@lang('policies.cols.status')</th>
                <th>@lang('policies.cols.read_at')</th>
            </tr></thead>
            <tbody>
                @forelse($rows as $row)
                    <tr>
                        <td>{{ optional($row->user)->name ?? '—' }}</td>
                        <td>
                            @if($row->read_at)
                                <span class="badge badge-success">@lang('policies.status.read')</span>
                            @else
                                <span class="badge badge-secondary">@lang('policies.status.unread')</span>
                            @endif
                        </td>
                        <td><small>{{ $row->read_at?->format('Y-m-d H:i') ?? '—' }}</small></td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="text-center text-muted py-4">@lang('policies.empty')</td></tr>
                @endforelse
            </tbody>
        </table>
    </div></div></div>
</div>
@endsection
