@extends('layouts.app')
@section('title', __('users.teachers'))
@section('body_class', 'theme-light')
@push('styles')
<style>
    /* Let row action dropdowns float above the table instead of being clipped. */
    body.theme-light .table-responsive { overflow: visible; }
    body.theme-light .dropdown-menu.is-floating {
        position: fixed; z-index: 1080;
        box-shadow: 0 8px 24px rgba(15,23,42,.12), 0 2px 6px rgba(15,23,42,.06);
    }
    /* On phones there's no room to let the table overflow visibly (body clips
       horizontal overflow) — restore scrolling so every column stays reachable.
       The dropdown still floats via position:fixed from the JS below. */
    @media (max-width: 575.98px) {
        body.theme-light .table-responsive { overflow-x: auto; }
    }
</style>
@endpush
@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-8 col-12 mb-2">
        <h2 class="content-header-title mb-0">@lang('users.teachers')</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
                <li class="breadcrumb-item active">@lang('users.teachers')</li>
            </ol>
        </div>
    </div>
    <div class="content-header-right col-md-4 col-12">
        <form action="{{ route('admin.users.teachers.index') }}" method="GET" class="d-flex">
            <input type="search" name="q" value="{{ $q }}" class="form-control form-control-sm me-1" placeholder="@lang('users.search_placeholder')" />
            <button class="btn btn-outline-primary btn-sm" type="submit"><x-svg-icon name="search" /></button>
        </form>
    </div>
</div>

<div class="content-body">
    @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

    <div class="card">
        <div class="card-header d-flex flex-wrap gap-2 justify-content-between align-items-center">
            <div class="btn-group btn-group-sm" role="group">
                <a href="{{ route('admin.users.teachers.create') }}" class="btn btn-primary">
                    <x-svg-icon name="person-plus" /> @lang('users.add_teacher')
                </a>
                <a href="{{ route('admin.users.teachers.import') }}" class="btn btn-outline-success">
                    <x-svg-icon name="file-earmark-excel" /> @lang('users.import_excel')
                </a>
                <a href="{{ route('admin.users.teachers.workloads') }}" class="btn btn-outline-info">
                    <x-svg-icon name="bar-chart" /> @lang('users.workloads_btn')
                </a>
            </div>
            <div class="text-muted small">
                <x-svg-icon name="people" />
                {{ $teachers->total() }} @lang('users.teachers')
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="thead-light">
                    <tr>
                        <th style="width:32px"><input type="checkbox" id="js-check-all" /></th>
                        <th>@lang('users.name')</th>
                        <th>@lang('users.username')</th>
                        <th>@lang('users.national_id')</th>
                        <th>@lang('users.employee_id')</th>
                        <th>@lang('users.specialization')</th>
                        <th>@lang('users.status')</th>
                        <th>@lang('users.last_activity')</th>
                        <th class="text-end">@lang('users.actions')</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($teachers as $u)
                    <tr>
                        <td><input type="checkbox" class="js-row-check" value="{{ $u->id }}" /></td>
                        <td>
                            <strong>{{ $u->name }}</strong>
                            @if($u->email)<br><small class="text-muted">{{ $u->email }}</small>@endif
                        </td>
                        <td><code>{{ $u->username ?? '—' }}</code></td>
                        <td>{{ $u->national_id ?? '—' }}</td>
                        <td>{{ $u->employee_id ?? '—' }}</td>
                        <td>{{ $u->specialization ?? '—' }}</td>
                        <td>
                            @if($u->is_active)
                                <span class="badge bg-success">@lang('users.teacher_status_active')</span>
                            @else
                                <span class="badge bg-secondary">@lang('users.teacher_status_inactive')</span>
                            @endif
                        </td>
                        <td>{{ $u->last_login_at ? $u->last_login_at->diffForHumans() : '—' }}</td>
                        <td class="text-end">
                            <a href="{{ route('admin.users.teachers.show', $u->id) }}" class="btn btn-sm btn-outline-info" title="@lang('users.view')">
                                <x-svg-icon name="eye" />
                            </a>
                            <a href="{{ route('admin.users.teachers.edit', $u->id) }}" class="btn btn-sm btn-outline-primary" title="@lang('users.edit')">
                                <x-svg-icon name="pencil-square" />
                            </a>
                            <button type="button" class="btn btn-sm btn-outline-danger js-user-delete" title="@lang('users.delete')"
                                    data-url="{{ route('admin.users.teachers.destroy', $u->id) }}" data-name="{{ $u->name }}"><x-svg-icon name="trash" /></button>
                            <div class="dropdown d-inline">
                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-toggle="dropdown" data-bs-toggle="dropdown">
                                    <x-svg-icon name="three-dots" />
                                </button>
                                <div class="dropdown-menu dropdown-menu-end">
                                    @if(auth()->user()->isSuperAdmin())
                                    <form action="{{ route('admin.users.impersonate.start', $u->id) }}" method="POST">
                                        @csrf
                                        <button class="dropdown-item" type="submit"><x-svg-icon name="person-bounding-box" /> @lang('users.login_as')</button>
                                    </form>
                                    @endif
                                    <a class="dropdown-item" href="{{ route('admin.users.teachers.workloads') }}"><x-svg-icon name="calendar" /> @lang('users.schedule_link')</a>
                                    <a class="dropdown-item" href="{{ route('admin.users.teachers.permissions', $u->id) }}"><x-svg-icon name="shield-shaded" /> @lang('users.permissions_link')</a>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="text-center text-muted py-4">@lang('users.no_results')</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $teachers->links() }}</div>
    </div>
</div>

@include('admin.users.partials.delete-modal')
@endsection
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var checkAll = document.getElementById('js-check-all');
    var rows = document.querySelectorAll('.js-row-check');
    if (checkAll) {
        checkAll.addEventListener('change', function () {
            rows.forEach(function (r) { r.checked = checkAll.checked; });
        });
    }

    // Float row action dropdowns above the table using position:fixed.
    if (window.jQuery) {
        jQuery(document)
            .on('shown.bs.dropdown', '.table .dropdown', function (e) {
                var $toggle = jQuery(this).find('[data-toggle="dropdown"],[data-bs-toggle="dropdown"]').first();
                var $menu = jQuery(this).find('.dropdown-menu').first();
                if (!$toggle.length || !$menu.length) { return; }
                var r = $toggle[0].getBoundingClientRect();
                var mw = $menu.outerWidth();
                $menu.addClass('is-floating').css({
                    top: (r.bottom + 4) + 'px',
                    left: Math.max(8, r.right - mw) + 'px',
                    right: 'auto'
                });
            })
            .on('hidden.bs.dropdown', '.table .dropdown', function () {
                jQuery(this).find('.dropdown-menu').removeClass('is-floating').css({ top: '', left: '', right: '' });
            });
    }
});
</script>
@endpush
