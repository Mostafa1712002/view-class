{{-- Inline (not @push) on purpose: this component renders in the layout body, after the
     head's @stack('styles') already emitted, so a pushed block would arrive too late. --}}
<style>
/* ── Notification bell + dropdown (gold/light, RTL-aware) ───────────────── */
.vc-notif-bell { position: relative; }
.vc-notif-bell.has-unread .vc-ico { color: var(--gold-500, #c9a04b); }
.vc-notif-count {
    position: absolute; top: 2px; inset-inline-end: 2px;
    min-width: 17px; height: 17px; padding: 0 4px;
    display: inline-flex; align-items: center; justify-content: center;
    font-size: 10px; font-weight: 700; line-height: 1; color: #fff;
    background: linear-gradient(135deg, #f87171, #dc2626);
    border: 2px solid #fff; border-radius: 999px;
    box-shadow: 0 2px 5px rgba(220,38,38,.4);
}
.vc-notif { position: relative; }
.vc-notif-dd {
    width: 360px; max-width: 92vw; padding: 0; overflow: hidden;
    border: 1px solid #efe6cf; border-radius: 16px;
    box-shadow: 0 18px 48px rgba(30,25,10,.18);
    /* Position under the bell, anchored on the physical left so it extends toward the
       screen centre (RTL: the bell sits on the left of the navbar, so this moves the
       box rightward instead of jamming the edge). data-display="static" keeps Popper out. */
    position: absolute; top: 100%; margin-top: .5rem;
    right: auto; left: 0; transform: translateX(34px);
}
.vc-notif-head {
    display: flex; align-items: center; justify-content: space-between;
    gap: .5rem; padding: .8rem 1rem;
    background: linear-gradient(135deg, #fdfaf2, #f7efdc);
    border-bottom: 1px solid #efe6cf;
}
.vc-notif-head-title { font-weight: 700; color: var(--gold-500, #b5832a); font-size: .95rem; display: inline-flex; align-items: center; gap: .4rem; }
.vc-notif-chip {
    display: inline-flex; align-items: center; justify-content: center;
    min-width: 20px; height: 20px; padding: 0 6px; margin-inline-start: .15rem;
    font-size: .72rem; font-weight: 700; color: #fff; border-radius: 999px;
    background: linear-gradient(135deg, var(--gold-200, #e3c170), var(--gold-500, #c9a04b));
}
.vc-notif-markall {
    border: 0; background: transparent; padding: 0; cursor: pointer;
    font-size: .76rem; font-weight: 600; color: var(--gold-500, #b5832a);
    display: inline-flex; align-items: center; gap: .25rem;
}
.vc-notif-markall:hover { color: var(--gold-400, #cfa046); text-decoration: underline; }
.vc-notif-list { max-height: 360px; overflow-y: auto; background: #fff; }
.vc-notif-item {
    display: flex; align-items: flex-start; gap: .7rem;
    padding: .7rem 1rem; text-decoration: none;
    border-bottom: 1px solid #f4f1ea; transition: background .15s;
}
.vc-notif-item:last-child { border-bottom: 0; }
.vc-notif-item:hover { background: #faf7f0; }
.vc-notif-item.unread { background: #fffdf5; }
.vc-notif-ico {
    flex: 0 0 auto; width: 38px; height: 38px; border-radius: 11px;
    display: inline-flex; align-items: center; justify-content: center;
    font-size: 1.05rem;
}
.vc-notif-ico.c-primary { background:#eef2ff; color:#4338ca; }
.vc-notif-ico.c-success { background:#ecfdf5; color:#047857; }
.vc-notif-ico.c-warning { background:#fef3c7; color:#92400e; }
.vc-notif-ico.c-danger  { background:#fee2e2; color:#b91c1c; }
.vc-notif-ico.c-info    { background:#e0f2fe; color:#0369a1; }
.vc-notif-body { display: flex; flex-direction: column; gap: 2px; min-width: 0; flex: 1 1 auto; }
.vc-notif-title { font-size: .85rem; font-weight: 700; color: #2b2b2b; line-height: 1.35; }
.vc-notif-text { font-size: .78rem; color: #7a756c; line-height: 1.4; }
.vc-notif-time { font-size: .7rem; color: #b0a892; display: inline-flex; align-items: center; gap: .25rem; margin-top: 1px; }
.vc-notif-dot { flex: 0 0 auto; width: 8px; height: 8px; margin-top: 6px; border-radius: 999px; background: var(--gold-400, #cfa046); }
.vc-notif-empty { text-align: center; padding: 2.4rem 1rem; }
.vc-notif-empty-ico {
    width: 56px; height: 56px; border-radius: 50%; margin: 0 auto .7rem;
    display: inline-flex; align-items: center; justify-content: center;
    background: linear-gradient(135deg, #fdf6e6, #f6e9c8); color: var(--gold-400, #cfa046); font-size: 1.5rem;
}
.vc-notif-empty-title { font-weight: 700; color: #5d5443; margin: 0; font-size: .9rem; }
.vc-notif-empty-sub { color: #a99e85; font-size: .78rem; margin: .15rem 0 0; }
.vc-notif-foot {
    display: flex; align-items: center; justify-content: center; gap: .35rem;
    padding: .7rem; background: #fcfaf4; border-top: 1px solid #efe6cf;
    font-size: .82rem; font-weight: 600; color: var(--gold-500, #b5832a); text-decoration: none;
}
.vc-notif-foot:hover { background: #f7efdc; color: var(--gold-400, #cfa046); }

/* ══════════════════════════════════════════════════════════════════
   HEADER v3 — clean navy bar matched to the sidebar (QA #221)
   Additive: overrides the muddy navy→gold gradient with a crisp navy
   surface + gold hairline, tidies the utility cluster, and turns the
   plain grey scope selects into translucent rounded controls.
   ══════════════════════════════════════════════════════════════════ */
.header-navbar.bg-info,
.header-navbar.navbar-semi-light.bg-info {
    background: linear-gradient(180deg, #16263f 0%, #0f1c30 100%) !important;
    border-bottom: 1px solid rgba(216,178,74,.45) !important;
    box-shadow: 0 2px 16px rgba(8,15,28,.22) !important;
}

/* Utility icon buttons → consistent square hit-areas with hover pill */
.shell-nav-right .nav-link {
    display: inline-flex !important; align-items: center; gap: .3rem;
    padding: .42rem .55rem !important;
    border-radius: 9px;
    color: #e8edf4 !important;
    transition: background .15s, color .15s;
}
.shell-nav-right .nav-link:hover { background: rgba(255,255,255,.10) !important; color: #fff !important; }
.shell-nav-right .nav-link .vc-ico,
.shell-nav-right .nav-link i { color: inherit !important; }
.shell-nav-right .nav-link:hover .vc-ico { color: var(--gold-200, #e3c170) !important; }

/* Clock chip */
#shell-clock { font-size: .82rem; color: #cdd7e6 !important; font-variant-numeric: tabular-nums; }
#shell-clock .text-bold-600 { color: #fff; }

/* Account name + role chip */
.shell-nav-right .user-name { color: #fff; font-size: .92rem; }
.shell-acct-type {
    background: linear-gradient(135deg, rgba(216,178,74,.95), rgba(199,154,50,.95)) !important;
    color: #14233a !important; font-weight: 800 !important;
    box-shadow: 0 2px 8px rgba(216,178,74,.3);
}
.dropdown-user-link .avatar img { border: 2px solid rgba(216,178,74,.55); }

/* School/company meta block */
.shell-school-meta strong { color: #fff !important; }
.shell-school-meta small { color: rgba(255,255,255,.72) !important; }

/* Desktop scope selectors — readable translucent controls on navy */
.shell-nav-center .form-control,
.shell-nav-center select.form-control {
    height: 36px;
    background: rgba(255,255,255,.10) !important;
    border: 1px solid rgba(216,178,74,.35) !important;
    color: #fff !important;
    border-radius: 9px !important;
    font-size: .86rem;
    font-weight: 600;
    transition: border-color .15s, background .15s;
}
/* Draw our own caret and drop the native one, so a bare (pre-Select2) select
   still looks clean instead of showing the OS dropdown arrow. */
.shell-nav-center select.form-control {
    -webkit-appearance: none;
    -moz-appearance: none;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%23e3c170' stroke-width='3' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E") !important;
    background-repeat: no-repeat !important;
    background-position: left .55rem center !important;
    background-size: 11px !important;
    padding-left: 1.5rem !important;
    padding-right: .6rem !important;
}
.shell-nav-center select.form-control::-ms-expand { display: none; }
.shell-nav-center .form-control:hover,
.shell-nav-center select.form-control:hover { background: rgba(255,255,255,.16) !important; }
.shell-nav-center .form-control:focus,
.shell-nav-center select.form-control:focus {
    background: rgba(255,255,255,.18) !important;
    border-color: var(--gold-200, #e3c170) !important;
    box-shadow: 0 0 0 .15rem rgba(216,178,74,.22) !important;
}
.shell-nav-center select.form-control option { color: #14233a; }

/* Select2 replaces these scope selects with its own pill; fully hide the native
   <select> (our .form-control styling above otherwise leaves its dropdown arrow
   bleeding out under the pill). Value still submits — only `disabled` blocks that. */
.shell-nav-center select.select2-hidden-accessible {
    position: absolute !important;
    height: 1px !important;
    width: 1px !important;
    padding: 0 !important;
    margin: -1px !important;
    border: 0 !important;
    overflow: hidden !important;
    clip: rect(0 0 0 0) !important;
    background: transparent !important;
}

/* Select2's own caret sits at the bottom edge of our taller pill and reads as an
   arrow hanging below it. Span the arrow box to full height and centre the tick. */
.shell-nav-center .select2-container .select2-selection__arrow {
    top: 0 !important;
    height: 100% !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
}
.shell-nav-center .select2-container .select2-selection__arrow b {
    position: static !important;
    margin: 0 !important;
    top: auto !important;
    left: auto !important;
}

/* Notification + mail icons sit slightly larger for clarity */
.shell-nav-right .vc-notif-bell .vc-ico { width: 19px; height: 19px; }

@media (prefers-reduced-motion: reduce) {
    .shell-nav-right .nav-link,
    .shell-nav-center .form-control,
    .shell-nav-center select.form-control { transition: none !important; }
}
</style>

@php
    $shellUser = auth()->user();
    // The header + scope selectors reflect the ACTIVE school (the one picked in
    // the scope switcher), not merely the account's primary school — so a
    // multi-school admin (or a super-admin) sees the school they switched to.
    // session('scope.school_id') is already validated server-side (SetScopeAction
    // → schoolExistsFor), so trusting it here can't leak another tenant's school.
    $shellScopeSchoolId = session('scope.school_id') ?: $shellUser?->school_id;
    $shellSchool = $shellScopeSchoolId ? \App\Models\School::find($shellScopeSchoolId) : null;
    $shellCompany = $shellSchool?->educationalCompany;
    $shellLocale = app()->getLocale();
    $shellIsRtl = $shellLocale === 'ar';
    $shellSchoolName = $shellSchool ? ($shellLocale === 'en' ? ($shellSchool->name_en ?: $shellSchool->name_ar ?: $shellSchool->name) : ($shellSchool->name_ar ?: $shellSchool->name)) : null;
    $shellCompanyName = $shellCompany ? ($shellLocale === 'en' ? ($shellCompany->name_en ?: $shellCompany->name_ar) : ($shellCompany->name_ar ?: $shellCompany->name_en)) : null;
    $shellCurrentYear = $shellSchool?->academicYears()->where('is_current', true)->first();

    $shellScopeSession = session('scope', [
        'company_id' => optional($shellSchool)->educational_company_id,
        'school_id' => $shellScopeSchoolId,
        'academic_year_id' => $shellCurrentYear?->id,
        'academic_term_id' => null,
    ]);
    $shellScopeCompanies = [];
    $shellScopeSchools = [];
    $shellScopeYears = [];
    $shellScopeTerms = [];

    // ── Student shell context (Card #170) ────────────────────────────────────
    $shellIsStudent = $shellUser && $shellUser->isStudent();
    // Whether this student may switch to a previous (non-current) period.
    $shellAllowPrev = $shellIsStudent
        ? (bool) \App\Models\Setting::get('allow_previous_periods', false, $shellUser->school_id)
        : true;
    // Account-type label for the header chip.
    $shellAccountType = null;
    if ($shellUser) {
        $shellAccountType = match (true) {
            $shellUser->isSuperAdmin()  => __('shell.role_super_admin'),
            $shellUser->isSchoolAdmin() => __('shell.role_school_admin'),
            $shellUser->isTeacher()     => __('shell.role_teacher'),
            $shellUser->isStudent()     => __('shell.role_student'),
            $shellUser->isParent()      => __('shell.role_parent'),
            default => null,
        };
    }
    // Resolve the student's grade / class for the header context line.
    $shellStudentClass = null;
    if ($shellIsStudent) {
        $shellStudentClass = $shellUser->classRoom;
    }

    if ($shellUser) {
        try {
            $scopeRepo = app(\App\Modules\Scope\Repositories\Contracts\ScopeRepository::class);
            $shellScopeCompanies = $scopeRepo->companiesFor($shellUser);
            $shellScopeSchools = $scopeRepo->schoolsFor($shellUser, $shellScopeSession['company_id'] ?? null);
            $shellScopeYears = $scopeRepo->yearsFor($shellUser, $shellScopeSession['school_id'] ?? null);
            // Effective year for term-list resolution: scope year (when allowed) else current.
            $shellEffectiveYearId = ($shellIsStudent && ! $shellAllowPrev)
                ? $shellCurrentYear?->id
                : ($shellScopeSession['academic_year_id'] ?? $shellCurrentYear?->id);
            $shellScopeTerms = $scopeRepo->termsFor($shellUser, $shellEffectiveYearId);
        } catch (\Throwable $e) {
            // progressive enhancement — never break shell
        }
    }
@endphp

<nav class="header-navbar navbar-expand-md navbar navbar-with-menu navbar-without-dd-arrow fixed-top navbar-semi-light bg-info navbar-shadow shell-navbar-row">
    <div class="navbar-wrapper shell-row">
        {{-- Left cluster: brand + toggles --}}
        <div class="shell-nav-left d-flex align-items-center gap-1">
            {{-- Desktop sidebar mini-toggle (hidden on mobile — mobile uses its own btn) --}}
            <a class="nav-link nav-menu-main menu-toggle d-none d-md-inline-flex p-0 me-1" href="#" aria-label="@lang('shell.menu_toggle')" style="color:#fff; align-items:center; width:34px; height:34px; justify-content:center; border-radius:7px; transition:background .15s;" onmouseover="this.style.background='rgba(255,255,255,.12)'" onmouseout="this.style.background='transparent'">
                <x-svg-icon name="list" class="text-white" size="22" />
            </a>
            {{-- Mobile hamburger — opens the off-canvas drawer --}}
            <button id="gp-mobile-menu-btn" type="button"
                class="d-flex d-md-none align-items-center justify-content-center p-0 me-1"
                style="width:34px;height:34px;background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.22);border-radius:7px;cursor:pointer;transition:background .15s;"
                onclick="typeof gpOpenMobileDrawer === 'function' && gpOpenMobileDrawer()"
                aria-label="@lang('shell.menu_toggle')">
                <x-svg-icon name="list" class="text-white" size="20" />
            </button>
            <a class="navbar-brand d-flex align-items-center m-0 p-0" href="{{ route('dashboard') }}">
                @if(!empty($brand_logo))
                    <img class="brand-logo" alt="{{ $brand_name_ar ?? 'المنصة الذهبية' }}" src="{{ asset($brand_logo) }}" style="height: 32px;">
                    <span class="brand-text ms-1 d-none d-lg-inline text-white">{{ $brand_name_ar ?? 'المنصة الذهبية' }}</span>
                @else
                    <span class="brand-text-logo d-flex align-items-center gap-1" style="line-height:1; user-select:none;">
                        <span style="display:inline-block; width:8px; height:28px; background:#C9A227; border-radius:3px; flex-shrink:0;"></span>
                        <span class="ms-1 fw-bold text-white d-none d-lg-inline" style="font-size:1.05rem; letter-spacing:.01em;">{{ $brand_name_ar ?? 'المنصة الذهبية' }}</span>
                        <span class="ms-1 fw-bold text-white d-inline d-lg-none text-nowrap" style="font-size:.78rem;">{{ $brand_name_ar ?? 'المنصة الذهبية' }}</span>
                    </span>
                @endif
            </a>
            @if($shellSchoolName)
                <div class="shell-school-meta d-none d-xxl-flex text-white ms-3" style="flex-direction:column; line-height:1.1; max-width:190px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                    <small class="text-white-50 text-truncate" style="font-size:.7rem;">{{ $shellCompanyName }}</small>
                    <strong class="text-truncate" style="font-size:.85rem;">{{ $shellSchoolName }}</strong>
                    @if($shellIsStudent && $shellStudentClass)
                        <small class="text-white-50 text-truncate" style="font-size:.68rem;">{{ $shellStudentClass->grade_level_label ?? $shellStudentClass->name }}@if($shellStudentClass->division) — {{ $shellStudentClass->division }}@endif</small>
                    @endif
                </div>
            @endif
            @if($shellAccountType)
                <span class="shell-acct-type d-none d-lg-inline-flex align-items-center text-white ms-2 px-2 py-1"
                      style="background:rgba(201,162,39,.85); border-radius:999px; font-size:.72rem; font-weight:700; line-height:1;"
                      title="@lang('shell.account_type')">
                    {{ $shellAccountType }}
                </span>
            @endif
        </div>

        {{-- Centre: scope selectors (only when authed and wide enough) --}}
        @auth
            @if($shellIsStudent)
                {{-- Student scope: company/school are STATIC context (a student has exactly
                     one), year + term are selectable only when the school permits viewing
                     previous periods. The disabled state is cosmetic — SetScopeAction
                     enforces the same gate server-side. --}}
                <form id="shell-scope-form" method="POST" action="{{ route('scope.set') }}" class="shell-nav-center d-none d-xl-flex align-items-center m-0">
                    @csrf
                    <span class="d-inline-flex align-items-center text-white mx-1 px-2 py-1" style="background:rgba(255,255,255,.12); border-radius:7px; font-size:.8rem; max-width:200px;" title="{{ $shellSchoolName }}">
                        <x-svg-icon name="building" size="14" class="me-1" />
                        <span class="text-truncate">{{ $shellSchoolName }}</span>
                    </span>
                    <select name="academic_year_id" class="form-control form-control-sm mx-1" style="min-width:120px; max-width:160px" onchange="this.form.submit()" aria-label="@lang('shell.scope_year')" @disabled(! $shellAllowPrev)>
                        @foreach($shellScopeYears as $y)
                            <option value="{{ $y['id'] }}" @selected(($shellScopeSession['academic_year_id'] ?? null) == $y['id'] || (empty($shellScopeSession['academic_year_id']) && !empty($y['is_current'])))>
                                {{ $y['name'] }}{{ !empty($y['is_current']) ? ' ★' : '' }}
                            </option>
                        @endforeach
                    </select>
                    <select name="academic_term_id" class="form-control form-control-sm mx-1" style="min-width:120px; max-width:160px" onchange="this.form.submit()" aria-label="@lang('shell.scope_term')" @disabled(! $shellAllowPrev || empty($shellScopeTerms))>
                        @if(empty($shellScopeTerms))
                            <option value="">@lang('shell.scope_no_terms')</option>
                        @else
                            @foreach($shellScopeTerms as $t)
                                <option value="{{ $t['id'] }}" @selected(($shellScopeSession['academic_term_id'] ?? null) == $t['id'] || (empty($shellScopeSession['academic_term_id']) && !empty($t['is_current'])))>
                                    {{ $t['name'] }}{{ !empty($t['is_current']) ? ' ★' : '' }}
                                </option>
                            @endforeach
                        @endif
                    </select>
                </form>
            @else
                <form id="shell-scope-form" method="POST" action="{{ route('scope.set') }}" class="shell-nav-center d-none d-xl-flex align-items-center m-0">
                    @csrf
                    <select name="company_id" class="form-control form-control-sm mx-1" style="min-width:140px; max-width:180px" onchange="this.form.submit()" aria-label="@lang('shell.scope_company')">
                        @foreach($shellScopeCompanies as $c)
                            <option value="{{ $c['id'] }}" @selected(($shellScopeSession['company_id'] ?? null) == $c['id'])>
                                {{ $shellLocale === 'en' ? ($c['name_en'] ?: $c['name_ar']) : ($c['name_ar'] ?: $c['name_en']) }}
                            </option>
                        @endforeach
                    </select>
                    <select name="school_id" class="form-control form-control-sm mx-1" style="min-width:140px; max-width:180px" onchange="this.form.submit()" aria-label="@lang('shell.scope_school')">
                        <option value="all">@lang('shell.scope_all_schools')</option>
                        @foreach($shellScopeSchools as $s)
                            <option value="{{ $s['id'] }}" @selected(($shellScopeSession['school_id'] ?? null) == $s['id'])>
                                {{ $shellLocale === 'en' ? ($s['name_en'] ?: $s['name_ar']) : ($s['name_ar'] ?: $s['name_en']) }}
                            </option>
                        @endforeach
                    </select>
                    <select name="academic_year_id" class="form-control form-control-sm mx-1" style="min-width:120px; max-width:160px" onchange="this.form.submit()" aria-label="@lang('shell.scope_semester')">
                        <option value="all">@lang('shell.scope_all_years')</option>
                        @foreach($shellScopeYears as $y)
                            <option value="{{ $y['id'] }}" @selected(($shellScopeSession['academic_year_id'] ?? null) == $y['id'])>
                                {{ $y['name'] }}{{ !empty($y['is_current']) ? ' ★' : '' }}
                            </option>
                        @endforeach
                    </select>
                </form>
            @endif
        @endauth

        {{-- Right cluster: utility actions + profile --}}
        <ul class="shell-nav-right nav navbar-nav flex-row align-items-center m-0">
            <li class="nav-item d-none d-md-flex align-items-center text-white px-2" id="shell-clock" style="line-height:1.1;" data-shell-hide-xs>
                <span id="shell-clock-time" class="text-bold-600">—</span>
            </li>

            <li class="nav-item" data-shell-hide-xs>
                <a class="nav-link" href="#" title="@lang('shell.search')" aria-label="@lang('shell.search')"
                   onclick="event.preventDefault(); const bar=document.getElementById('shell-search-bar'); if(bar){bar.classList.toggle('d-none'); const i=bar.querySelector('input'); if(i && !bar.classList.contains('d-none')) i.focus();}">
                    <x-svg-icon name="search" class="ficon" size="18" />
                </a>
            </li>

            <li class="nav-item dropdown" data-shell-hide-xs>
                <a class="nav-link dropdown-toggle" href="#" data-toggle="dropdown" title="@lang('shell.switch_language')">
                    <x-svg-icon name="globe" class="ficon" size="18" />
                    <span class="d-none d-lg-inline ms-1">{{ strtoupper($shellLocale) }}</span>
                </a>
                <div class="dropdown-menu dropdown-menu-right">
                    <a class="dropdown-item {{ $shellLocale === 'ar' ? 'active' : '' }}" href="{{ route('locale.switch', 'ar') }}">العربية</a>
                    <a class="dropdown-item {{ $shellLocale === 'en' ? 'active' : '' }}" href="{{ route('locale.switch', 'en') }}">English</a>
                </div>
            </li>

            @auth
                @php
                    $unreadNotifications = auth()->user()->customNotifications()->unread()->latest()->limit(5)->get();
                    $unreadCount = auth()->user()->customNotifications()->unread()->count();
                @endphp

                <li class="nav-item">
                    <a class="nav-link" href="{{ route('messages.index') }}" title="@lang('shell.nav_mailbox')">
                        <x-svg-icon name="envelope" class="ficon" size="18" />
                    </a>
                </li>

                <li class="dropdown dropdown-notification nav-item vc-notif">
                    <a class="nav-link nav-link-label vc-notif-bell {{ $unreadCount > 0 ? 'has-unread' : '' }}" href="#" data-toggle="dropdown" data-display="static" title="@lang('shell.notifications_heading')">
                        <x-svg-icon name="bell" class="ficon" size="18" />
                        @if($unreadCount > 0)
                            <span class="vc-notif-count">{{ $unreadCount > 9 ? '9+' : $unreadCount }}</span>
                        @endif
                    </a>
                    <div class="dropdown-menu dropdown-menu-{{ $shellIsRtl ? 'left' : 'right' }} vc-notif-dd">
                        <div class="vc-notif-head">
                            <span class="vc-notif-head-title">
                                <x-svg-icon name="bell" size="15" /> @lang('shell.notifications_heading')
                                @if($unreadCount > 0)<span class="vc-notif-chip">{{ $unreadCount }}</span>@endif
                            </span>
                            @if($unreadCount > 0)
                                <form action="{{ route('notifications.mark-all-read') }}" method="POST" class="m-0 p-0">
                                    @csrf
                                    <button type="submit" class="vc-notif-markall"><x-svg-icon name="check2-all" size="14" /> @lang('shell.notifications_mark_all')</button>
                                </form>
                            @endif
                        </div>
                        <div class="vc-notif-list">
                            @forelse($unreadNotifications as $notification)
                                @php($ic = $notification->getIcon())
                                @php($icClass = \Illuminate\Support\Str::startsWith($ic,'bi-') ? 'bi '.$ic : (\Illuminate\Support\Str::startsWith($ic,'la-') ? 'la '.$ic : $ic))
                                <a class="vc-notif-item unread" href="{{ $notification->action_url ?? route('notifications.index') }}" onclick="markNotificationRead('{{ $notification->id }}')">
                                    <span class="vc-notif-ico c-{{ $notification->color ?? 'info' }}"><i class="{{ $icClass }}"></i></span>
                                    <span class="vc-notif-body">
                                        <span class="vc-notif-title">{{ $notification->title }}</span>
                                        <span class="vc-notif-text">{{ Str::limit($notification->body, 70) }}</span>
                                        <span class="vc-notif-time"><x-svg-icon name="clock" size="12" /> {{ $notification->created_at->diffForHumans() }}</span>
                                    </span>
                                    <span class="vc-notif-dot" aria-hidden="true"></span>
                                </a>
                            @empty
                                <div class="vc-notif-empty">
                                    <span class="vc-notif-empty-ico"><x-svg-icon name="bell" size="28" /></span>
                                    <p class="vc-notif-empty-title">@lang('shell.notifications_empty')</p>
                                    <p class="vc-notif-empty-sub">@lang('shell.notifications_empty_sub')</p>
                                </div>
                            @endforelse
                        </div>
                        <a class="vc-notif-foot" href="{{ route('notifications.index') }}">@lang('shell.notifications_view_all') <x-svg-icon name="{{ $shellIsRtl ? 'arrow-left' : 'arrow-right' }}" size="14" /></a>
                    </div>
                </li>

                @php($shellRoles = $shellUser->roles ?? collect())
                @if($shellRoles->count() > 0)
                    <li class="nav-item dropdown" data-shell-hide-xs>
                        <a class="nav-link dropdown-toggle" href="#" data-toggle="dropdown" title="@lang('shell.select_role')">
                            <x-svg-icon name="shield-lock" class="ficon" size="18" />
                            <span class="d-none d-xl-inline ms-1">{{ session('current_role', $shellRoles->first()->name) }}</span>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right">
                            <h6 class="dropdown-header">@lang('shell.select_role')</h6>
                            @foreach($shellRoles as $role)
                                <a class="dropdown-item" href="#"
                                   onclick="event.preventDefault(); document.cookie='current_role={{ $role->slug }}; path=/'; location.reload();">
                                    {{ $role->name }}
                                </a>
                            @endforeach
                        </div>
                    </li>
                @endif

                <li class="dropdown dropdown-user nav-item">
                    <a class="dropdown-toggle nav-link dropdown-user-link" href="#" data-toggle="dropdown">
                        <span class="avatar avatar-online">
                            <img src="{{ auth()->user()->profile_picture ?? auth()->user()->avatar ?? asset('app-assets/images/portrait/small/avatar-s-1.png') }}" alt="avatar">
                            <i></i>
                        </span>
                        <span class="d-none d-xl-inline ms-1 user-name text-bold-700">
                            {{ $shellLocale === 'en'
                                ? (auth()->user()->name_en ?: auth()->user()->name_ar ?: auth()->user()->name ?: __('shell.user_fallback_name'))
                                : (auth()->user()->name_ar ?: auth()->user()->name ?: __('shell.user_fallback_name')) }}
                        </span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-right">
                        <a class="dropdown-item" href="{{ url('/profile/edit') }}">
                            <x-svg-icon name="person" size="16" /> @lang('shell.profile_edit')
                        </a>
                        <a class="dropdown-item" href="#" data-toggle="modal" data-target="#change-password-modal">
                            <x-svg-icon name="lock" size="16" /> @lang('shell.change_password')
                        </a>
                        <a class="dropdown-item" href="#" data-toggle="modal" data-target="#upload-avatar-modal">
                            <x-svg-icon name="image" size="16" /> @lang('shell.upload_avatar')
                        </a>
                        <a class="dropdown-item" href="#">
                            <x-svg-icon name="book" size="16" /> @lang('shell.user_guide')
                        </a>
                        <div class="dropdown-divider"></div>
                        <h6 class="dropdown-header">@lang('shell.font_size')</h6>
                        <div class="px-2 pb-1 btn-group btn-group-sm shell-font-size-picker w-100" role="group" aria-label="@lang('shell.font_size')">
                            <button type="button" class="btn btn-outline-secondary" data-font-size="small">@lang('shell.font_size_small')</button>
                            <button type="button" class="btn btn-outline-secondary" data-font-size="medium">@lang('shell.font_size_medium')</button>
                            <button type="button" class="btn btn-outline-secondary" data-font-size="large">@lang('shell.font_size_large')</button>
                        </div>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="{{ route('logout') }}"
                           onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                            <x-svg-icon name="box-arrow-right" size="16" /> @lang('auth.logout')
                        </a>
                        <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                            @csrf
                        </form>
                    </div>
                </li>
            @endauth
        </ul>
    </div>

    {{-- Mobile scope strip — visible below xl where the centre form is hidden --}}
    @auth
    <div id="shell-scope-mobile" class="d-flex d-xl-none" style="background:rgba(0,0,0,.12); padding:6px 16px; flex-wrap:wrap; gap:6px;">
        @if($shellIsStudent)
            <form method="POST" action="{{ route('scope.set') }}" class="d-flex flex-wrap align-items-center gap-1 m-0 w-100">
                @csrf
                <span class="d-inline-flex align-items-center text-white px-2" style="flex:1 1 130px; max-width:200px; height:30px; background:rgba(255,255,255,.12); border-radius:6px; font-size:.78rem;">
                    <span class="text-truncate">{{ $shellSchoolName }}</span>
                </span>
                <select name="academic_year_id" class="form-control form-control-sm" style="flex:1 1 110px; max-width:180px; height:30px; font-size:.82rem; border:1px solid rgba(255,255,255,.4); background:rgba(255,255,255,.18); color:#fff; border-radius:6px;" onchange="this.form.submit()" aria-label="@lang('shell.scope_year')" @disabled(! $shellAllowPrev)>
                    @foreach($shellScopeYears as $y)
                        <option value="{{ $y['id'] }}" style="color:#333;" @selected(($shellScopeSession['academic_year_id'] ?? null) == $y['id'] || (empty($shellScopeSession['academic_year_id']) && !empty($y['is_current'])))>
                            {{ $y['name'] }}{{ !empty($y['is_current']) ? ' ★' : '' }}
                        </option>
                    @endforeach
                </select>
                <select name="academic_term_id" class="form-control form-control-sm" style="flex:1 1 110px; max-width:180px; height:30px; font-size:.82rem; border:1px solid rgba(255,255,255,.4); background:rgba(255,255,255,.18); color:#fff; border-radius:6px;" onchange="this.form.submit()" aria-label="@lang('shell.scope_term')" @disabled(! $shellAllowPrev || empty($shellScopeTerms))>
                    @if(empty($shellScopeTerms))
                        <option value="" style="color:#333;">@lang('shell.scope_no_terms')</option>
                    @else
                        @foreach($shellScopeTerms as $t)
                            <option value="{{ $t['id'] }}" style="color:#333;" @selected(($shellScopeSession['academic_term_id'] ?? null) == $t['id'] || (empty($shellScopeSession['academic_term_id']) && !empty($t['is_current'])))>
                                {{ $t['name'] }}{{ !empty($t['is_current']) ? ' ★' : '' }}
                            </option>
                        @endforeach
                    @endif
                </select>
            </form>
        @else
            <form method="POST" action="{{ route('scope.set') }}" class="d-flex flex-wrap align-items-center gap-1 m-0 w-100">
                @csrf
                <select name="company_id" class="form-control form-control-sm" style="flex:1 1 130px; max-width:200px; height:30px; font-size:.82rem; border:1px solid rgba(255,255,255,.4); background:rgba(255,255,255,.18); color:#fff; border-radius:6px;" onchange="this.form.submit()" aria-label="@lang('shell.scope_company')">
                    @foreach($shellScopeCompanies as $c)
                        <option value="{{ $c['id'] }}" style="color:#333;" @selected(($shellScopeSession['company_id'] ?? null) == $c['id'])>
                            {{ $shellLocale === 'en' ? ($c['name_en'] ?: $c['name_ar']) : ($c['name_ar'] ?: $c['name_en']) }}
                        </option>
                    @endforeach
                </select>
                <select name="school_id" class="form-control form-control-sm" style="flex:1 1 130px; max-width:200px; height:30px; font-size:.82rem; border:1px solid rgba(255,255,255,.4); background:rgba(255,255,255,.18); color:#fff; border-radius:6px;" onchange="this.form.submit()" aria-label="@lang('shell.scope_school')">
                    <option value="all" style="color:#333;">@lang('shell.scope_all_schools')</option>
                    @foreach($shellScopeSchools as $s)
                        <option value="{{ $s['id'] }}" style="color:#333;" @selected(($shellScopeSession['school_id'] ?? null) == $s['id'])>
                            {{ $shellLocale === 'en' ? ($s['name_en'] ?: $s['name_ar']) : ($s['name_ar'] ?: $s['name_en']) }}
                        </option>
                    @endforeach
                </select>
                <select name="academic_year_id" class="form-control form-control-sm" style="flex:1 1 110px; max-width:180px; height:30px; font-size:.82rem; border:1px solid rgba(255,255,255,.4); background:rgba(255,255,255,.18); color:#fff; border-radius:6px;" onchange="this.form.submit()" aria-label="@lang('shell.scope_semester')">
                    <option value="all" style="color:#333;">@lang('shell.scope_all_years')</option>
                    @foreach($shellScopeYears as $y)
                        <option value="{{ $y['id'] }}" style="color:#333;" @selected(($shellScopeSession['academic_year_id'] ?? null) == $y['id'])>
                            {{ $y['name'] }}{{ !empty($y['is_current']) ? ' ★' : '' }}
                        </option>
                    @endforeach
                </select>
            </form>
        @endif
    </div>
    @endauth

    <div id="shell-search-bar" class="d-none w-100" style="position:absolute; top:56px; {{ $shellIsRtl ? 'left' : 'right' }}:0; background:#fff; box-shadow:0 4px 10px rgba(0,0,0,.08); padding:10px 20px; z-index:1040;">
        <input type="text" class="form-control" placeholder="@lang('shell.search')" />
    </div>

    <script>
        (function () {
            const el = document.getElementById('shell-clock-time');
            if (!el) return;
            function tick() {
                const now = new Date();
                const opts = { hour: '2-digit', minute: '2-digit', day: '2-digit', month: '2-digit', year: 'numeric' };
                el.textContent = new Intl.DateTimeFormat(document.documentElement.lang || 'ar', opts).format(now);
            }
            tick();
            setInterval(tick, 60 * 1000);
        })();

        (function () {
            const KEY = 'shell.fontSize';
            const sizes = { small: '87.5%', medium: '100%', large: '115%' };
            function apply(size) {
                if (!sizes[size]) size = 'medium';
                document.documentElement.style.fontSize = sizes[size];
                document.querySelectorAll('.shell-font-size-picker [data-font-size]').forEach(btn => {
                    btn.classList.toggle('active', btn.getAttribute('data-font-size') === size);
                });
                try { localStorage.setItem(KEY, size); } catch (_) {}
            }
            const saved = (function () { try { return localStorage.getItem(KEY) || 'medium'; } catch (_) { return 'medium'; } })();
            apply(saved);
            document.addEventListener('click', function (ev) {
                const btn = ev.target.closest('.shell-font-size-picker [data-font-size]');
                if (!btn) return;
                ev.preventDefault();
                apply(btn.getAttribute('data-font-size'));
            });
        })();
    </script>
</nav>
