@php
    $shellUser = auth()->user();
    $shellSchool = $shellUser?->school;
    $shellCompany = $shellSchool?->educationalCompany;
    $shellLocale = app()->getLocale();
    $shellIsRtl = $shellLocale === 'ar';
    $shellSchoolName = $shellSchool ? ($shellLocale === 'en' ? ($shellSchool->name_en ?: $shellSchool->name_ar ?: $shellSchool->name) : ($shellSchool->name_ar ?: $shellSchool->name)) : null;
    $shellCompanyName = $shellCompany ? ($shellLocale === 'en' ? ($shellCompany->name_en ?: $shellCompany->name_ar) : ($shellCompany->name_ar ?: $shellCompany->name_en)) : null;
    $shellCurrentYear = $shellSchool?->academicYears()->where('is_current', true)->first();

    $shellScopeSession = session('scope', [
        'company_id' => optional($shellSchool)->educational_company_id,
        'school_id' => $shellUser?->school_id,
        'academic_year_id' => $shellCurrentYear?->id,
    ]);
    $shellScopeCompanies = [];
    $shellScopeSchools = [];
    $shellScopeYears = [];
    if ($shellUser) {
        try {
            $scopeRepo = app(\App\Modules\Scope\Repositories\Contracts\ScopeRepository::class);
            $shellScopeCompanies = $scopeRepo->companiesFor($shellUser);
            $shellScopeSchools = $scopeRepo->schoolsFor($shellUser, $shellScopeSession['company_id'] ?? null);
            $shellScopeYears = $scopeRepo->yearsFor($shellUser, $shellScopeSession['school_id'] ?? null);
        } catch (\Throwable $e) {
            // progressive enhancement — never break shell
        }
    }
@endphp

<nav class="header-navbar navbar-expand-md navbar navbar-with-menu navbar-without-dd-arrow fixed-top navbar-semi-light bg-info navbar-shadow shell-navbar-row">
    <div class="navbar-wrapper shell-row">
        {{-- Left cluster: brand + mobile toggle --}}
        <div class="shell-nav-left d-flex align-items-center">
            <a class="nav-link nav-menu-main menu-toggle hidden-xs d-md-inline-block p-0 me-2" href="#" aria-label="@lang('shell.menu_toggle')">
                <i class="la la-bars la-2x text-white"></i>
            </a>
            <a class="navbar-brand d-flex align-items-center m-0 p-0" href="{{ route('dashboard') }}">
                <img class="brand-logo" alt="@lang('auth.app_name')" src="{{ asset('app-assets/images/logo/logo.png') }}" style="height: 32px;">
                <span class="brand-text ms-1 d-none d-lg-inline text-white">@lang('auth.app_name')</span>
            </a>
            @if($shellSchoolName)
                <div class="shell-school-meta d-none d-xxl-flex text-white ms-3" style="flex-direction:column; line-height:1.1; max-width:170px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                    <small class="text-white-50 text-truncate" style="font-size:.7rem;">{{ $shellCompanyName }}</small>
                    <strong class="text-truncate" style="font-size:.85rem;">{{ $shellSchoolName }}</strong>
                </div>
            @endif
        </div>

        {{-- Centre: scope selectors (only when authed and wide enough) --}}
        @auth
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
        @endauth

        {{-- Right cluster: utility actions + profile --}}
        <ul class="shell-nav-right nav navbar-nav flex-row align-items-center m-0">
            <li class="nav-item d-none d-md-flex align-items-center text-white px-2" id="shell-clock" style="line-height:1.1;">
                <span id="shell-clock-time" class="text-bold-600">—</span>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="#" title="@lang('shell.search')" aria-label="@lang('shell.search')"
                   onclick="event.preventDefault(); const bar=document.getElementById('shell-search-bar'); if(bar){bar.classList.toggle('d-none'); const i=bar.querySelector('input'); if(i && !bar.classList.contains('d-none')) i.focus();}">
                    <i class="ficon la la-search"></i>
                </a>
            </li>

            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" data-toggle="dropdown" title="@lang('shell.switch_language')">
                    <i class="ficon la la-globe"></i>
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
                        <i class="ficon la la-envelope"></i>
                    </a>
                </li>

                <li class="dropdown dropdown-notification nav-item">
                    <a class="nav-link nav-link-label" href="#" data-toggle="dropdown" title="@lang('shell.notifications_heading')">
                        <i class="ficon la la-bell"></i>
                        @if($unreadCount > 0)
                            <span class="badge badge-pill badge-danger badge-up badge-glow">{{ $unreadCount > 9 ? '9+' : $unreadCount }}</span>
                        @endif
                    </a>
                    <ul class="dropdown-menu dropdown-menu-media dropdown-menu-right">
                        <li class="dropdown-menu-header">
                            <h6 class="dropdown-header m-0">
                                <span class="grey darken-2">@lang('shell.notifications_heading')</span>
                            </h6>
                            @if($unreadCount > 0)
                                <span class="notification-tag badge badge-danger float-{{ $shellIsRtl ? 'right' : 'left' }} m-0">{{ $unreadCount }} @lang('shell.notifications_new')</span>
                            @endif
                        </li>
                        <li class="scrollable-container media-list w-100" style="max-height:300px; overflow-y:auto;">
                            @forelse($unreadNotifications as $notification)
                                <a href="{{ $notification->action_url ?? route('notifications.index') }}" onclick="markNotificationRead('{{ $notification->id }}')">
                                    <div class="media">
                                        <div class="media-left align-self-center">
                                            <i class="{{ $notification->getIcon() }} icon-bg-circle bg-{{ $notification->color }}"></i>
                                        </div>
                                        <div class="media-body">
                                            <h6 class="media-heading">{{ $notification->title }}</h6>
                                            <p class="notification-text font-small-3 text-muted">{{ Str::limit($notification->body, 50) }}</p>
                                            <small><time class="media-meta text-muted">{{ $notification->created_at->diffForHumans() }}</time></small>
                                        </div>
                                    </div>
                                </a>
                            @empty
                                <div class="text-center p-3">
                                    <i class="la la-bell-slash text-muted"></i>
                                    <p class="text-muted mb-0">@lang('shell.notifications_empty')</p>
                                </div>
                            @endforelse
                        </li>
                        <li class="dropdown-menu-footer">
                            <a class="dropdown-item text-muted text-center" href="{{ route('notifications.index') }}">@lang('shell.notifications_view_all')</a>
                        </li>
                    </ul>
                </li>

                @php($shellRoles = $shellUser->roles ?? collect())
                @if($shellRoles->count() > 0)
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-toggle="dropdown" title="@lang('shell.select_role')">
                            <i class="ficon la la-user-shield"></i>
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
                            <i class="la la-user"></i> @lang('shell.profile_edit')
                        </a>
                        <a class="dropdown-item" href="#" data-toggle="modal" data-target="#change-password-modal">
                            <i class="la la-lock"></i> @lang('shell.change_password')
                        </a>
                        <a class="dropdown-item" href="#" data-toggle="modal" data-target="#upload-avatar-modal">
                            <i class="la la-image"></i> @lang('shell.upload_avatar')
                        </a>
                        <a class="dropdown-item" href="#">
                            <i class="la la-book"></i> @lang('shell.user_guide')
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
                            <i class="la la-power-off"></i> @lang('auth.logout')
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
