@extends('layouts.app')

@section('title', __('users.team_title'))
@section('body_class', 'theme-light')

@php
    $isRtl = app()->getLocale() === 'ar';
    $authUser = auth()->user();
    $isSuperUser = $authUser->isSuperAdmin();
    $tabLabels = [
        'accounts'   => __('users.team_tab_accounts'),
        'job-titles' => __('users.team_tab_job_titles'),
        'roles'      => __('users.team_tab_roles'),
    ];
@endphp

@include('admin.users.admins._styles')
@if($tab === 'job-titles')
    @include('admin.users.job_titles._styles')
@endif
@push('styles')
<style>
    .ad-toolbar .ad-add-menu { min-width: 300px; top: 100%; }

    .team-tabs { display: flex; gap: .35rem; border-bottom: 1px solid #e5e7eb; margin-bottom: 1.25rem; flex-wrap: wrap; }
    .team-tabs a {
        padding: .6rem 1.1rem; font-weight: 600; font-size: .92rem; color: #64748b;
        border-bottom: 2px solid transparent; margin-bottom: -1px; text-decoration: none;
    }
    .team-tabs a:hover { color: #0f172a; }
    .team-tabs a.active { color: var(--gold-500); border-bottom-color: var(--gold-400); }
    .ad-pill.type-teacher { background: #eff6ff; color: #1d4ed8; border-color: #bfdbfe; }
    .team-hint { color: #64748b; font-size: .85rem; margin-bottom: .85rem; }
</style>
@endpush

@section('content')
<div class="content-header ad-header">
    <h2>@lang('users.team_title')</h2>
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
        <li class="breadcrumb-item active">@lang('users.team_title')</li>
    </ol>
</div>

<div class="content-body">
    @if(session('status') && $tab !== 'job-titles')
        <div class="ad-alert"><x-svg-icon name="check-circle-fill" :size="16" class="ic-success" /><span>{{ session('status') }}</span></div>
    @endif
    @if(session('error'))
        <div class="ad-alert err"><x-svg-icon name="exclamation-triangle-fill" :size="16" class="ic-danger" /><span>{{ session('error') }}</span></div>
    @endif

    <nav class="team-tabs">
        @foreach($tabs as $t)
            <a href="{{ route('admin.users.team.index', ['tab' => $t]) }}" class="{{ $tab === $t ? 'active' : '' }}">{{ $tabLabels[$t] }}</a>
        @endforeach
    </nav>

    @if($tab === 'accounts')
        @php $hasFilter = ($filters['q'] ?? '') !== '' || $filters['type'] || $filters['job_title_id'] || $filters['status']; @endphp

        <div class="ad-kpis">
            <div class="ad-kpi">
                <div class="ico"><x-svg-icon name="people-fill" :size="20" /></div>
                <div><div class="num">{{ $stats['total'] }}</div><div class="lbl">@lang('users.team_kpi_total')</div></div>
            </div>
            <div class="ad-kpi">
                <div class="ico ico-blue"><x-svg-icon name="person-badge-fill" :size="20" /></div>
                <div><div class="num muted">{{ $stats['teachers'] }}</div><div class="lbl">@lang('users.team_kpi_teachers')</div></div>
            </div>
            <div class="ad-kpi">
                <div class="ico ico-violet"><x-svg-icon name="shield-lock-fill" :size="20" /></div>
                <div><div class="num muted">{{ $stats['admins'] }}</div><div class="lbl">@lang('users.team_kpi_admins')</div></div>
            </div>
            <div class="ad-kpi">
                <div class="ico ico-green"><x-svg-icon name="check-circle-fill" :size="20" /></div>
                <div><div class="num muted">{{ $stats['active'] }}</div><div class="lbl">@lang('users.team_kpi_active')</div></div>
            </div>
        </div>

        <div class="ad-search-card">
            <div class="se-title">
                <x-svg-icon name="search" :size="16" class="ic-gold" />
                <span>@lang('users.team_filter_title')</span>
            </div>
            <form action="{{ route('admin.users.team.index') }}" method="GET" class="ad-search-row">
                <input type="hidden" name="tab" value="accounts" />
                <input type="search" name="q" value="{{ $filters['q'] }}" class="form-control"
                       placeholder="@lang('users.team_search_placeholder')" />
                <select name="type" class="form-control">
                    <option value="">@lang('users.team_type') — @lang('users.all')</option>
                    <option value="teacher" @selected($filters['type'] === 'teacher')>@lang('users.team_type_teacher')</option>
                    <option value="admin" @selected($filters['type'] === 'admin')>@lang('users.team_type_admin')</option>
                </select>
                <select name="job_title_id" class="form-control">
                    <option value="">@lang('users.filter_job_title') — @lang('users.all')</option>
                    @foreach($jobTitles as $jt)
                        <option value="{{ $jt->id }}" @selected($filters['job_title_id'] == $jt->id)>{{ $jt->localized_name }}</option>
                    @endforeach
                </select>
                <select name="status" class="form-control">
                    <option value="">@lang('users.status') — @lang('users.all')</option>
                    <option value="active" @selected($filters['status'] === 'active')>@lang('users.admin_status_active')</option>
                    <option value="inactive" @selected($filters['status'] === 'inactive')>@lang('users.admin_status_inactive')</option>
                </select>
                <button class="btn-gold" type="submit">
                    <x-svg-icon name="search" :size="16" /> @lang('users.search')
                </button>
                @if($hasFilter)
                    <a href="{{ route('admin.users.team.index', ['tab' => 'accounts']) }}" class="btn-reset">
                        <x-svg-icon name="x-circle-fill" :size="16" class="ic-muted" /> @lang('users.cancel')
                    </a>
                @endif
            </form>
        </div>

        <div class="ad-toolbar">
            <div class="left">
                <div class="dropdown">
                    <button class="btn-gold dropdown-toggle" type="button"
                            data-toggle="dropdown" data-bs-toggle="dropdown" data-display="static" aria-expanded="false">
                        <x-svg-icon name="plus-lg" :size="16" /> @lang('users.team_add')
                    </button>
                    <div class="dropdown-menu ad-add-menu">
                        <a class="ad-blank" href="{{ route('admin.users.teachers.create') }}">
                            <x-svg-icon name="person-plus-fill" :size="16" class="ic-gold" /> @lang('users.team_add_teacher')
                        </a>
                        <div class="dropdown-divider"></div>
                        <div class="dropdown-header">@lang('users.team_add_admin')</div>
                        @foreach($jobTitles as $jt)
                            <a class="ad-job-item" href="{{ route('admin.users.admins.create', ['job_title_id' => $jt->id]) }}">
                                <span class="jt-ico"><x-svg-icon name="tag-fill" :size="16" /></span>
                                <span class="jt-meta">
                                    <span>{{ $jt->localized_name }}</span>
                                    <span class="jt-slug">{{ $jt->slug }}</span>
                                </span>
                            </a>
                        @endforeach
                        <a class="ad-blank" href="{{ route('admin.users.admins.create') }}">
                            <x-svg-icon name="person-plus-fill" :size="16" class="ic-gold" /> @lang('users.team_add_admin_blank')
                        </a>
                    </div>
                </div>
                <a class="btn-ghost" href="{{ route('admin.users.teachers.import') }}">
                    <x-svg-icon name="upload" :size="16" class="ic-gold" /> @lang('users.team_import_teachers')
                </a>
            </div>
            <span class="count-pill">{{ $members->total() }} / {{ $stats['total'] }}</span>
        </div>

        <div class="ad-surface">
            <div class="table-responsive">
                <table class="table ad-table">
                    <thead>
                        <tr>
                            <th>@lang('users.name')</th>
                            <th>@lang('users.team_type')</th>
                            <th>@lang('users.job_title')</th>
                            @if($isSuperUser)<th>@lang('users.school')</th>@endif
                            <th>@lang('users.status')</th>
                            <th>@lang('users.last_activity')</th>
                            <th class="text-{{ $isRtl ? 'start' : 'end' }}">@lang('users.actions')</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($members as $u)
                        @php
                            $initials = collect(preg_split('/\s+/u', trim($u->name ?: $u->username ?: '')))
                                ->filter()->take(2)->map(fn($p) => mb_substr($p, 0, 1))->implode('');
                            $isTeacher = $u->roles->contains('slug', 'teacher');
                            $isSuper = $u->roles->contains(fn($r) => $r->slug === 'super-admin');
                            $isCurrentlyActive = ($u->is_active ?? true) && ($u->status ?? 'active') !== 'inactive';
                            $editUrl = $isTeacher ? route('admin.users.teachers.edit', $u->id) : route('admin.users.admins.edit', $u->id);
                            $showUrl = $isTeacher ? route('admin.users.teachers.show', $u->id) : route('admin.users.admins.show', $u->id);
                            $destroyUrl = $isTeacher ? route('admin.users.teachers.destroy', $u->id) : route('admin.users.admins.destroy', $u->id);
                        @endphp
                        <tr>
                            <td data-label="@lang('users.name')">
                                <div class="ad-user">
                                    <div class="ad-avatar">{{ $initials ?: '?' }}</div>
                                    <div>
                                        <div class="ad-name">{{ $u->name }}</div>
                                        @if($u->email)<div class="ad-secondary">{{ $u->email }}</div>@endif
                                    </div>
                                </div>
                            </td>
                            <td data-label="@lang('users.team_type')">
                                @if($isTeacher)
                                    <span class="ad-pill type-teacher">@lang('users.team_type_teacher')</span>
                                @elseif($isSuper)
                                    <span class="ad-pill role-super">@lang('users.team_type_super')</span>
                                @else
                                    <span class="ad-pill role">@lang('users.team_type_admin')</span>
                                @endif
                            </td>
                            <td data-label="@lang('users.job_title')">
                                @if($u->jobTitle)
                                    <span class="ad-pill role">{{ $u->jobTitle->localized_name }}</span>
                                @else
                                    <span class="ad-pill role-muted">—</span>
                                @endif
                            </td>
                            @if($isSuperUser)<td data-label="@lang('users.school')"><span class="ad-secondary">{{ optional($u->school)->name_ar ?? '—' }}</span></td>@endif
                            <td data-label="@lang('users.status')">
                                @if($isCurrentlyActive)
                                    <span class="ad-pill active"><span class="dot"></span>@lang('users.admin_status_active')</span>
                                @else
                                    <span class="ad-pill inactive"><span class="dot"></span>@lang('users.admin_status_inactive')</span>
                                @endif
                            </td>
                            <td data-label="@lang('users.last_activity')">
                                <span class="ad-secondary">{{ $u->last_login_at ? $u->last_login_at->diffForHumans() : '—' }}</span>
                            </td>
                            <td data-label="@lang('users.actions')" class="actions-cell text-{{ $isRtl ? 'start' : 'end' }}">
                                <div class="ad-actions">
                                    <a href="{{ $editUrl }}" class="ad-action-btn edit" title="@lang('users.edit')">
                                        <x-svg-icon name="pencil-square" :size="16" class="ic-gold" />
                                    </a>
                                    <div class="dropdown">
                                        <button type="button" class="ad-action-btn more dropdown-toggle"
                                                data-toggle="dropdown" data-bs-toggle="dropdown"
                                                aria-expanded="false" title="@lang('users.admin_more_actions')">
                                            <x-svg-icon name="three-dots" :size="16" class="ic-muted" />
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-{{ $isRtl ? 'start' : 'end' }}">
                                            <a class="dropdown-item" href="{{ $showUrl }}">
                                                <x-svg-icon name="eye-fill" :size="16" class="ic-info" /> @lang('users.admin_view')
                                            </a>
                                            @if($isTeacher)
                                                <a class="dropdown-item" href="{{ route('admin.users.teachers.permissions', $u->id) }}">
                                                    <x-svg-icon name="shield-lock-fill" :size="16" class="ic-gold" /> @lang('users.team_permissions')
                                                </a>
                                            @elseif($u->jobTitle && $authUser->canDo('job_titles.manage_permissions'))
                                                <a class="dropdown-item" href="{{ route('admin.users.job-titles.permissions.index', $u->jobTitle->id) }}">
                                                    <x-svg-icon name="shield-lock-fill" :size="16" class="ic-gold" /> @lang('users.team_job_title_permissions')
                                                </a>
                                            @endif
                                            @if($isSuperUser)
                                                <div class="dropdown-divider"></div>
                                                <form action="{{ route('admin.users.impersonate.start', $u->id) }}" method="POST">
                                                    @csrf
                                                    <button class="dropdown-item" type="submit">
                                                        <x-svg-icon name="box-arrow-in-right" :size="16" class="ic-warn" /> @lang('users.login_as')
                                                    </button>
                                                </form>
                                            @endif
                                            <div class="dropdown-divider"></div>
                                            <button type="button" class="dropdown-item danger js-user-delete"
                                                    data-url="{{ $destroyUrl }}" data-name="{{ $u->name }}">
                                                <x-svg-icon name="trash3-fill" :size="16" class="ic-danger" /> @lang('users.delete')
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $isSuperUser ? 7 : 6 }}">
                                <div class="ad-empty">
                                    <x-svg-icon name="people-fill" :size="40" class="ic-muted" />
                                    <div class="lbl">{{ $hasFilter ? __('users.admin_no_filter_results') : __('users.no_results') }}</div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            @if($members->hasPages())
                <div class="ad-footer">
                    <div class="ad-secondary">{{ $members->total() }} @lang('users.team_title')</div>
                    <div>{{ $members->withQueryString()->links() }}</div>
                </div>
            @endif
        </div>
    @elseif($tab === 'job-titles')
        @include('admin.users.job_titles._panel')
    @else
        <div class="team-hint">@lang('users.team_roles_hint')</div>
        <div class="ad-surface">
            <div class="table-responsive">
                <table class="table ad-table">
                    <thead>
                        <tr>
                            <th>@lang('users.name')</th>
                            <th>@lang('users.team_role_description')</th>
                            <th class="text-center">@lang('users.team_role_users')</th>
                            <th>@lang('users.status')</th>
                            <th class="text-{{ $isRtl ? 'start' : 'end' }}">@lang('users.actions')</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($roleRows as $role)
                        <tr>
                            <td data-label="@lang('users.name')">
                                <div class="ad-name">{{ $role->name }}</div>
                                <div class="ad-secondary">{{ $role->slug }}</div>
                            </td>
                            <td data-label="@lang('users.team_role_description')"><span class="ad-secondary">{{ $role->description ?: '—' }}</span></td>
                            <td data-label="@lang('users.team_role_users')" class="text-center">{{ $role->users_count }}</td>
                            <td data-label="@lang('users.status')">
                                @if($role->is_active ?? true)
                                    <span class="ad-pill active"><span class="dot"></span>@lang('users.admin_status_active')</span>
                                @else
                                    <span class="ad-pill inactive"><span class="dot"></span>@lang('users.admin_status_inactive')</span>
                                @endif
                            </td>
                            <td data-label="@lang('users.actions')" class="actions-cell text-{{ $isRtl ? 'start' : 'end' }}">
                                <a class="btn-ghost" href="{{ route('admin.roles.permissions.edit', $role) }}">
                                    <x-svg-icon name="shield-lock-fill" :size="16" class="ic-gold" /> @lang('users.team_manage_permissions')
                                </a>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>

@if($tab === 'accounts')
@push('scripts')
<script>
// Float row action dropdowns above the table using position:fixed (avoid clipping).
document.addEventListener('DOMContentLoaded', function () {
    if (!window.jQuery) { return; }
    jQuery(document)
        .on('shown.bs.dropdown', '.ad-table .dropdown', function () {
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
        .on('hidden.bs.dropdown', '.ad-table .dropdown', function () {
            jQuery(this).find('.dropdown-menu').removeClass('is-floating').css({ top: '', left: '', right: '' });
        });
});
</script>
@endpush

@include('admin.users.partials.delete-modal')
@endif
@endsection
