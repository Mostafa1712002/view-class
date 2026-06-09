@extends('layouts.app')

@section('title', __('eval_audit.title'))
@section('body_class','theme-light')

@push('styles')
    @include('admin.evaluation.reports._styles')
@endpush

@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-8 col-12 mb-2">
        <h2 class="content-header-title mb-0">@lang('eval_audit.title')</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
                <li class="breadcrumb-item">@lang('eval_audit.breadcrumb_eval')</li>
                <li class="breadcrumb-item active">@lang('eval_audit.title')</li>
            </ol>
        </div>
        <p class="text-muted small mb-0">@lang('eval_audit.subtitle')</p>
    </div>
    <div class="content-header-right col-md-4 col-12 text-end no-print">
        <span class="ev-pill completed">{{ __('eval_audit.count', ['count' => $logs->total()]) }}</span>
    </div>
</div>

<div class="content-body">
    {{-- Filters --}}
    <form action="{{ route('admin.eval-audit.index') }}" method="GET" class="card filters-card p-3 mb-3 no-print">
        <div class="row g-2 align-items-end">
            <div class="col-md-3 col-6">
                <label class="form-label">@lang('eval_audit.filters.user')</label>
                <select name="user" class="form-control eval-select2">
                    <option value="">@lang('eval_audit.all')</option>
                    @foreach ($userOptions as $u)
                        <option value="{{ $u->id }}" {{ (int) ($filters['user'] ?? 0) === $u->id ? 'selected' : '' }}>{{ $u->name_ar ?: $u->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-3 col-6">
                <label class="form-label">@lang('eval_audit.filters.action')</label>
                <select name="action" class="form-control eval-select2">
                    <option value="">@lang('eval_audit.all')</option>
                    @foreach ($actionOptions as $value => $label)
                        <option value="{{ $value }}" {{ ($filters['action'] ?? null) === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-2 col-6">
                <label class="form-label">@lang('eval_audit.filters.date_from')</label>
                <input type="date" name="date_from" value="{{ $filters['date_from'] }}" class="form-control">
            </div>
            <div class="col-md-2 col-6">
                <label class="form-label">@lang('eval_audit.filters.date_to')</label>
                <input type="date" name="date_to" value="{{ $filters['date_to'] }}" class="form-control">
            </div>

            <div class="col-md-2 col-12">
                <label class="form-label">@lang('eval_audit.filters.search')</label>
                <input type="text" name="search" value="{{ $filters['search'] }}" class="form-control" placeholder="@lang('eval_audit.filters.search_ph')">
            </div>

            <div class="col-md-4 col-12 d-flex gap-1 align-items-end mt-2">
                <button type="submit" class="btn ev-add-btn flex-grow-1"><i class="la la-search"></i> @lang('eval_audit.show')</button>
                <a href="{{ route('admin.eval-audit.index') }}" class="btn btn-outline-secondary" title="@lang('eval_audit.reset')"><i class="la la-redo"></i></a>
            </div>
        </div>
    </form>

    {{-- Table --}}
    <div class="card">
        @if ($logs->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 report-table">
                    <thead>
                        <tr>
                            <th>@lang('eval_audit.cols.datetime')</th>
                            <th>@lang('eval_audit.cols.user')</th>
                            <th>@lang('eval_audit.cols.role')</th>
                            <th>@lang('eval_audit.cols.action')</th>
                            <th>@lang('eval_audit.cols.model')</th>
                            <th style="white-space:normal; min-width:260px;">@lang('eval_audit.cols.description')</th>
                            <th>@lang('eval_audit.cols.ip')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($logs as $log)
                            <tr>
                                <td>{{ $log->created_at?->format('Y-m-d H:i') }}</td>
                                <td class="fw-bold">{{ $log->user?->name_ar ?: $log->user?->name ?: __('eval_audit.system') }}</td>
                                <td>
                                    @php($roles = $log->user?->roles ?? collect())
                                    @if ($roles->isNotEmpty())
                                        @foreach ($roles as $role)
                                            <span class="ev-pill draft">{{ $role->name }}</span>
                                        @endforeach
                                    @else
                                        <span class="bool-no">@lang('eval_audit.no_role')</span>
                                    @endif
                                </td>
                                <td><span class="ev-pill approved">{{ ucwords(str_replace(['evaluation.', '.', '_'], ['', ' ', ' '], $log->action)) }}</span></td>
                                <td>
                                    @if ($log->model_type)
                                        <span class="text-muted">{{ class_basename($log->model_type) }} #{{ $log->model_id }}</span>
                                    @else
                                        <span class="bool-no">—</span>
                                    @endif
                                </td>
                                <td style="white-space:normal;">{{ $log->description }}</td>
                                <td class="text-muted small">{{ $log->ip_address }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="p-3 no-print">
                {{ $logs->links() }}
            </div>
        @else
            <div class="ev-empty">
                <span class="icon-wrap"><i class="la la-history"></i></span>
                <p class="mb-0 text-muted">@lang('eval_audit.empty')</p>
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
    (function () {
        if (window.jQuery && jQuery.fn.select2) {
            jQuery('.eval-select2').select2({ width: '100%' });
        }
    })();
</script>
@endpush
