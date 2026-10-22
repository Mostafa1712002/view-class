@extends('layouts.app')

@section('title', __('users.admins'))
@section('body_class', 'theme-light')

@php
    $isRtl = app()->getLocale() === 'ar';
    $total = $stats['total'] ?? 0;
    $active = $stats['active'] ?? 0;
    $withJob = $stats['withJob'] ?? 0;
    $super = $stats['super'] ?? 0;
@endphp

@include('admin.users.admins._styles')

@section('content')
<div class="content-header ad-header">
    <h2>@lang('users.admins')</h2>
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
        <li class="breadcrumb-item active">@lang('users.admins')</li>
    </ol>
</div>

<div class="content-body">
    @if(session('status'))
        <div class="ad-alert"><x-svg-icon name="check-circle-fill" :size="16" class="ic-success" /><span>{{ session('status') }}</span></div>
    @endif
    @if(session('error'))
        <div class="ad-alert err"><x-svg-icon name="exclamation-triangle-fill" :size="16" class="ic-danger" /><span>{{ session('error') }}</span></div>
    @endif

    {{-- KPI strip --}}
    <div class="ad-kpis">
        <div class="ad-kpi">
            <div class="ico"><x-svg-icon name="shield-lock-fill" :size="20" /></div>
            <div>
                <div class="num">{{ $total }}</div>
                <div class="lbl">@lang('users.admin_total')</div>
            </div>
        </div>
        <div class="ad-kpi">
            <div class="ico ico-green"><x-svg-icon name="check-circle-fill" :size="20" /></div>
            <div>
                <div class="num muted">{{ $active }}</div>
                <div class="lbl">@lang('users.admin_active')</div>
            </div>
        </div>
        <div class="ad-kpi">
            <div class="ico ico-blue"><x-svg-icon name="person-badge-fill" :size="20" /></div>
            <div>
                <div class="num muted">{{ $withJob }}</div>
                <div class="lbl">@lang('users.admin_with_role')</div>
            </div>
        </div>
        <div class="ad-kpi">
            <div class="ico ico-violet"><x-svg-icon name="gem" :size="20" /></div>
            <div>
                <div class="num muted">{{ $super }}</div>
                <div class="lbl">@lang('users.admin_super')</div>
            </div>
        </div>
    </div>

    {{-- Search engine --}}
    <div class="ad-search-card">
        <div class="se-title">
            <x-svg-icon name="search" :size="16" class="ic-gold" />
            <span>@lang('users.admin_search_engine')</span>
            <span class="se-hint">@lang('users.admin_search_hint')</span>
        </div>
        <form action="{{ route('admin.users.admins.index') }}" method="GET" class="ad-search-row">
            <input type="search" name="q" value="{{ $q }}"
                   class="form-control"
                   placeholder="@lang('users.admin_search_hint')" />
            <select name="job_title_id" class="form-control" onchange="this.form.submit()">
                <option value="">@lang('users.filter_job_title') — @lang('users.all')</option>
                @foreach($jobTitles as $jt)
                    <option value="{{ $jt->id }}" @selected(request('job_title_id') == $jt->id)>{{ $jt->localized_name }}</option>
                @endforeach
            </select>
            <button class="btn-gold" type="submit">
                <x-svg-icon name="search" :size="16" /> @lang('users.search')
            </button>
            @if($q || request('job_title_id'))
                <a href="{{ route('admin.users.admins.index') }}" class="btn-reset">
                    <x-svg-icon name="x-circle-fill" :size="16" class="ic-muted" /> @lang('users.cancel')
                </a>
            @endif
        </form>
    </div>

    {{-- Toolbar --}}
    <div class="ad-toolbar">
        <div class="left">
            <div class="dropdown">
                <button class="btn-gold dropdown-toggle" type="button"
                        data-toggle="dropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <x-svg-icon name="plus-lg" :size="16" /> @lang('users.add_admin')
                </button>
                <div class="dropdown-menu ad-add-menu">
                    <div class="dropdown-header">@lang('users.admin_picker_title')</div>
                    @forelse($jobTitles as $jt)
                        <a class="ad-job-item" href="{{ route('admin.users.admins.create', ['job_title_id' => $jt->id]) }}">
                            <span class="jt-ico"><x-svg-icon name="tag-fill" :size="16" /></span>
                            <span class="jt-meta">
                                <span>{{ $jt->localized_name }}</span>
                                <span class="jt-slug">{{ $jt->slug }}</span>
                            </span>
                        </a>
                    @empty
                        <div class="ad-empty" style="padding:1rem .5rem;">
                            <x-svg-icon name="tag-fill" :size="40" class="ic-muted" />
                            <div class="lbl">@lang('users.no_results')</div>
                        </div>
                    @endforelse
                    <div class="dropdown-divider"></div>
                    <a class="ad-blank" href="{{ route('admin.users.admins.create') }}">
                        <x-svg-icon name="person-plus-fill" :size="16" class="ic-gold" /> @lang('users.admin_picker_blank')
                    </a>
                </div>
            </div>
            <a class="btn-ghost" href="{{ route('admin.users.job-titles.index') }}">
                <x-svg-icon name="gear-fill" :size="16" class="ic-gold" /> @lang('users.admin_manage_job_titles')
            </a>
        </div>
        <span class="count-pill">{{ $admins->total() }} / {{ $total }}</span>
    </div>

    {{-- Table --}}
    <div class="ad-surface">
        <div class="table-responsive">
            <table class="table ad-table">
                <thead>
                    <tr>
                        <th>@lang('users.name')</th>
                        @if(auth()->user()->isSuperAdmin())<th>@lang('users.school')</th>@endif
                        <th>@lang('users.username')</th>
                        <th>@lang('users.job_title')</th>
                        <th>@lang('users.status')</th>
                        <th>@lang('users.last_activity')</th>
                        <th class="text-{{ $isRtl ? 'start' : 'end' }}">@lang('users.actions')</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($admins as $u)
                    @php
                        $initials = collect(preg_split('/\s+/u', trim($u->name ?: $u->username ?: '')))
                            ->filter()
                            ->take(2)
                            ->map(fn($p) => mb_substr($p, 0, 1))
                            ->implode('');
                        $isSuper = $u->roles->contains(fn($r) => $r->slug === 'super-admin');
                        $isCurrentlyActive = ($u->is_active ?? true) && ($u->status ?? 'active') !== 'inactive';
                    @endphp
                    <tr>
                        <td data-label="@lang('users.name')">
                            <div class="ad-user">
                                <div class="ad-avatar">{{ $initials ?: '?' }}</div>
                                <div>
                                    <div class="ad-name">{{ $u->name }}</div>
                                    @if($u->email)
                                        <div class="ad-secondary">{{ $u->email }}</div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        @if(auth()->user()->isSuperAdmin())<td data-label="@lang('users.school')"><span class="ad-secondary">{{ optional($u->school)->name_ar ?? '—' }}</span></td>@endif
                        <td data-label="@lang('users.username')">
                            <span class="ad-secondary">{{ $u->username ?? '—' }}</span>
                        </td>
                        <td data-label="@lang('users.job_title')">
                            @if($u->jobTitle)
                                <span class="ad-pill role">{{ $u->jobTitle->localized_name }}</span>
                            @else
                                <span class="ad-pill role-muted">—</span>
                            @endif
                            @if($isSuper)
                                <span class="ad-pill role-super">@lang('users.card_role_admin')</span>
                            @endif
                        </td>
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
                                <a href="{{ route('admin.users.admins.edit', $u->id) }}"
                                   class="ad-action-btn edit"
                                   title="@lang('users.edit')">
                                    <x-svg-icon name="pencil-square" :size="16" class="ic-gold" />
                                </a>
                                <div class="dropdown">
                                    <button type="button"
                                            class="ad-action-btn more dropdown-toggle"
                                            data-toggle="dropdown" data-bs-toggle="dropdown"
                                            aria-expanded="false" title="@lang('users.admin_more_actions')">
                                        <x-svg-icon name="three-dots" :size="16" class="ic-muted" />
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-{{ $isRtl ? 'start' : 'end' }}">
                                        <a class="dropdown-item" href="{{ route('admin.users.admins.show', $u->id) }}">
                                            <x-svg-icon name="eye-fill" :size="16" class="ic-info" /> @lang('users.admin_view')
                                        </a>
                                        <a class="dropdown-item" href="{{ route('admin.users.admins.edit', $u->id) }}">
                                            <x-svg-icon name="pencil-square" :size="16" class="ic-gold" /> @lang('users.edit')
                                        </a>
                                        @if($u->jobTitle && in_array($u->jobTitle->slug, ['supervisor', 'counselor']))
                                            <a class="dropdown-item" href="{{ route('admin.users.admins.supervisees', $u->id) }}">
                                                <x-svg-icon name="people-fill" :size="16" class="ic-info" /> @lang('users.admin_supervisees')
                                            </a>
                                        @endif
                                        <a class="dropdown-item" href="{{ route('admin.users.job-titles.index') }}">
                                            <x-svg-icon name="gear-fill" :size="16" class="ic-muted" /> @lang('users.admin_manage_job_titles')
                                        </a>
                                        @if(auth()->user()->isSuperAdmin())
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
                                                data-url="{{ route('admin.users.admins.destroy', $u->id) }}"
                                                data-name="{{ $u->name }}">
                                            <x-svg-icon name="trash3-fill" :size="16" class="ic-danger" /> @lang('users.delete')
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ auth()->user()->isSuperAdmin() ? 7 : 6 }}">
                            <div class="ad-empty">
                                <x-svg-icon name="shield-lock-fill" :size="40" class="ic-muted" />
                                <div class="lbl">
                                    @if($q || request('job_title_id'))
                                        @lang('users.admin_no_filter_results')
                                    @else
                                        @lang('users.no_results')
                                    @endif
                                </div>
                                @if($q || request('job_title_id'))
                                    <div class="sub">
                                        <a href="{{ route('admin.users.admins.index') }}" style="color:var(--gold-500);">
                                            @lang('users.cancel')
                                        </a>
                                    </div>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if($admins->hasPages())
            <div class="ad-footer">
                <div class="ad-secondary">{{ $admins->total() }} @lang('users.admins')</div>
                <div>{{ $admins->withQueryString()->links() }}</div>
            </div>
        @endif
    </div>
</div>

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
@endsection
