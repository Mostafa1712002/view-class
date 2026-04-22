@php
    $shellUser = auth()->user();
    $shellSchool = $shellUser?->school;
    $shellCompany = $shellSchool?->educationalCompany;
    $shellLocale = app()->getLocale();
    $shellOtherLocale = $shellLocale === 'ar' ? 'en' : 'ar';
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
            // Scope selector is a progressive enhancement — never break the shell if the repo fails.
        }
    }
@endphp

<nav class="header-navbar navbar-expand-md navbar navbar-with-menu navbar-without-dd-arrow fixed-top navbar-semi-light bg-info navbar-shadow">
    <div class="navbar-wrapper">
        <div class="navbar-header">
            <ul class="nav navbar-nav flex-row">
                <li class="nav-item mobile-menu d-md-none mr-auto">
                    <a class="nav-link nav-menu-main menu-toggle hidden-xs" href="#" aria-label="@lang('shell.menu_toggle')">
                        <i class="ft-menu font-large-1"></i>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="navbar-brand" href="{{ route('dashboard') }}">
                        <img class="brand-logo" alt="@lang('auth.app_name')" src="{{ asset('app-assets/images/logo/logo.png') }}" style="height: 36px;">
                        <h3 class="brand-text">@lang('auth.app_name')</h3>
                    </a>
                </li>
                @if($shellSchoolName)
                    <li class="nav-item d-none d-md-flex align-items-center mx-3 text-white" style="flex-direction: column; justify-content: center; line-height: 1.1; max-width: 220px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                        <small class="text-white-50 text-truncate" style="max-width: 100%;">{{ $shellCompanyName }}</small>
                        <strong class="text-truncate" style="max-width: 100%;">{{ $shellSchoolName }}</strong>
                        @if($shellCurrentYear)
                            <small class="text-white-50 text-truncate" style="max-width: 100%;">@lang('shell.current_semester'): {{ $shellCurrentYear->name }}</small>
                        @endif
                    </li>
                @endif

                <li class="nav-item d-md-none">
                    <a class="nav-link open-navbar-container" data-toggle="collapse" data-target="#navbar-mobile">
                        <i class="la la-ellipsis-v"></i>
                    </a>
                </li>
            </ul>
        </div>
        <div class="navbar-container content">
            <div class="collapse navbar-collapse" id="navbar-mobile">
                <ul class="nav navbar-nav mr-auto float-left">
                    <li class="nav-item d-none d-md-block">
                        <a class="nav-link nav-menu-main menu-toggle hidden-xs" href="#" aria-label="@lang('shell.menu_toggle')">
                            <i class="ft-menu"></i>
                        </a>
                    </li>
                    <li class="nav-item d-none d-md-block">
                        <a class="nav-link nav-link-expand" href="#">
                            <i class="ficon ft-maximize"></i>
                        </a>
                    </li>
                    <li class="nav-item d-none d-md-flex align-items-center text-white px-2" id="shell-clock" style="line-height: 1.1;">
                        <span id="shell-clock-time" class="text-bold-600">—</span>
                    </li>
                    @auth
                        <li class="nav-item d-none d-lg-flex align-items-center">
                            <form id="shell-scope-form" method="POST" action="{{ route('scope.set') }}" class="form-inline m-0 p-0">
                                @csrf
                                <select name="company_id" class="form-control form-control-sm mx-1" style="max-width:160px" onchange="this.form.submit()" aria-label="@lang('shell.scope_company')">
                                    @foreach($shellScopeCompanies as $c)
                                        <option value="{{ $c['id'] }}" @selected(($shellScopeSession['company_id'] ?? null) == $c['id'])>
                                            {{ $shellLocale === 'en' ? ($c['name_en'] ?: $c['name_ar']) : ($c['name_ar'] ?: $c['name_en']) }}
                                        </option>
                                    @endforeach
                                </select>
                                <select name="school_id" class="form-control form-control-sm mx-1" style="max-width:160px" onchange="this.form.submit()" aria-label="@lang('shell.scope_school')">
                                    <option value="all">@lang('shell.scope_all_schools')</option>
                                    @foreach($shellScopeSchools as $s)
                                        <option value="{{ $s['id'] }}" @selected(($shellScopeSession['school_id'] ?? null) == $s['id'])>
                                            {{ $shellLocale === 'en' ? ($s['name_en'] ?: $s['name_ar']) : ($s['name_ar'] ?: $s['name_en']) }}
                                        </option>
                                    @endforeach
                                </select>
                                <select name="academic_year_id" class="form-control form-control-sm mx-1" style="max-width:140px" onchange="this.form.submit()" aria-label="@lang('shell.scope_semester')">
                                    <option value="all">@lang('shell.scope_all_years')</option>
                                    @foreach($shellScopeYears as $y)
                                        <option value="{{ $y['id'] }}" @selected(($shellScopeSession['academic_year_id'] ?? null) == $y['id'])>
                                            {{ $y['name'] }}{{ !empty($y['is_current']) ? ' ★' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </form>
                        </li>
                    @endauth
                </ul>
                <ul class="nav navbar-nav float-right">
                    <li class="nav-item">
                        <a class="nav-link" href="#" title="@lang('shell.search')" aria-label="@lang('shell.search')"
                           onclick="event.preventDefault(); const bar=document.getElementById('shell-search-bar'); if(bar){bar.classList.toggle('d-none'); const i=bar.querySelector('input'); if(i && !bar.classList.contains('d-none')) i.focus();}">
                            <i class="ficon ft-search"></i>
                        </a>
                    </li>

                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-toggle="dropdown" title="@lang('shell.switch_language')">
                            <i class="ficon ft-globe"></i>
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

                    <!-- Messages -->
                    <li class="dropdown dropdown-notification nav-item">
                        <a class="nav-link nav-link-label" href="{{ route('messages.index') }}">
                            <i class="ficon ft-mail"></i>
                        </a>
                    </li>

                    <!-- Notifications -->
                    <li class="dropdown dropdown-notification nav-item">
                        <a class="nav-link nav-link-label" href="#" data-toggle="dropdown">
                            <i class="ficon ft-bell"></i>
                            @if($unreadCount > 0)
                                <span class="badge badge-pill badge-danger badge-up badge-glow">{{ $unreadCount > 9 ? '9+' : $unreadCount }}</span>
                            @endif
                        </a>
                        <ul class="dropdown-menu dropdown-menu-media dropdown-menu-right">
                            <li class="dropdown-menu-header">
                                <h6 class="dropdown-header m-0">
                                    <span class="grey darken-2">الإشعارات</span>
                                </h6>
                                @if($unreadCount > 0)
                                    <span class="notification-tag badge badge-danger float-right m-0">{{ $unreadCount }} جديد</span>
                                @endif
                            </li>
                            <li class="scrollable-container media-list w-100" style="max-height: 300px; overflow-y: auto;">
                                @forelse($unreadNotifications as $notification)
                                    <a href="{{ $notification->action_url ?? route('notifications.index') }}"
                                       onclick="markNotificationRead('{{ $notification->id }}')">
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
                                        <i class="ft-bell-off text-muted"></i>
                                        <p class="text-muted mb-0">لا توجد إشعارات جديدة</p>
                                    </div>
                                @endforelse
                            </li>
                            <li class="dropdown-menu-footer">
                                <a class="dropdown-item text-muted text-center" href="{{ route('notifications.index') }}">عرض جميع الإشعارات</a>
                            </li>
                        </ul>
                    </li>
                    @endauth

                    @auth
                        @php($shellRoles = $shellUser->roles ?? collect())
                        @if($shellRoles->count() > 0)
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" data-toggle="dropdown" title="@lang('shell.select_role')">
                                    <i class="ficon ft-shield"></i>
                                    <span class="d-none d-lg-inline ms-1">{{ session('current_role', $shellRoles->first()->name) }}</span>
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
                    @endauth

                    <!-- User Dropdown -->
                    <li class="dropdown dropdown-user nav-item">
                        <a class="dropdown-toggle nav-link dropdown-user-link" href="#" data-toggle="dropdown">
                            <span class="mr-1">
                                <span class="user-name text-bold-700">{{ auth()->user()->name_ar ?? auth()->user()->name ?? 'المستخدم' }}</span>
                            </span>
                            <span class="avatar avatar-online">
                                <img src="{{ auth()->user()->profile_picture ?? auth()->user()->avatar ?? asset('app-assets/images/portrait/small/avatar-s-1.png') }}" alt="avatar">
                                <i></i>
                            </span>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right">
                            <a class="dropdown-item" href="{{ url('/profile/edit') }}">
                                <i class="ft-user"></i> @lang('shell.profile_edit')
                            </a>
                            <a class="dropdown-item" href="#" data-toggle="modal" data-target="#change-password-modal">
                                <i class="ft-lock"></i> @lang('shell.change_password')
                            </a>
                            <a class="dropdown-item" href="#" data-toggle="modal" data-target="#upload-avatar-modal">
                                <i class="ft-image"></i> @lang('shell.upload_avatar')
                            </a>
                            <a class="dropdown-item" href="#">
                                <i class="ft-book"></i> @lang('shell.user_guide')
                            </a>
                            <div class="dropdown-divider"></div>
                            <h6 class="dropdown-header">@lang('shell.font_size')</h6>
                            <div class="px-2 pb-1 btn-group btn-group-sm shell-font-size-picker" role="group" aria-label="@lang('shell.font_size')">
                                <button type="button" class="btn btn-outline-secondary" data-font-size="small">@lang('shell.font_size_small')</button>
                                <button type="button" class="btn btn-outline-secondary" data-font-size="medium">@lang('shell.font_size_medium')</button>
                                <button type="button" class="btn btn-outline-secondary" data-font-size="large">@lang('shell.font_size_large')</button>
                            </div>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item" href="{{ route('logout') }}"
                               onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                <i class="ft-power"></i> @lang('auth.logout')
                            </a>
                            <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                                @csrf
                            </form>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <div id="shell-search-bar" class="d-none w-100" style="position: absolute; top: 56px; left: 0; background: #fff; box-shadow: 0 4px 10px rgba(0,0,0,0.08); padding: 10px 20px; z-index: 1040;">
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
