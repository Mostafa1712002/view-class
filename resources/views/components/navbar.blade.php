<nav class="header-navbar navbar-expand-md navbar navbar-with-menu navbar-without-dd-arrow fixed-top navbar-semi-light bg-info navbar-shadow">
    <div class="navbar-wrapper">
        <div class="navbar-header">
            <ul class="nav navbar-nav flex-row">
                <li class="nav-item mobile-menu d-md-none mr-auto">
                    <a class="nav-link nav-menu-main menu-toggle hidden-xs" href="#">
                        <i class="ft-menu font-large-1"></i>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="navbar-brand" href="{{ route('dashboard') }}">
                        <img class="brand-logo" alt="المنصة الذهبية" src="{{ asset('app-assets/images/logo/logo.png') }}" style="height: 36px;">
                        <h3 class="brand-text">المنصة الذهبية</h3>
                    </a>
                </li>
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
                        <a class="nav-link nav-menu-main menu-toggle hidden-xs" href="#">
                            <i class="ft-menu"></i>
                        </a>
                    </li>
                    <li class="nav-item d-none d-md-block">
                        <a class="nav-link nav-link-expand" href="#">
                            <i class="ficon ft-maximize"></i>
                        </a>
                    </li>
                </ul>
                <ul class="nav navbar-nav float-right">
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

                    <!-- User Dropdown -->
                    <li class="dropdown dropdown-user nav-item">
                        <a class="dropdown-toggle nav-link dropdown-user-link" href="#" data-toggle="dropdown">
                            <span class="mr-1">
                                <span class="user-name text-bold-700">{{ auth()->user()->name ?? 'المستخدم' }}</span>
                            </span>
                            <span class="avatar avatar-online">
                                <img src="{{ asset('app-assets/images/portrait/small/avatar-s-1.png') }}" alt="avatar">
                                <i></i>
                            </span>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right">
                            <a class="dropdown-item" href="#">
                                <i class="ft-user"></i> الملف الشخصي
                            </a>
                            <a class="dropdown-item" href="#">
                                <i class="ft-settings"></i> الإعدادات
                            </a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item" href="{{ route('logout') }}"
                               onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                <i class="ft-power"></i> تسجيل الخروج
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
</nav>
