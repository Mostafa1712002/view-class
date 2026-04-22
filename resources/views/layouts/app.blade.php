@php
    $appLocale = app()->getLocale();
    $isRtl = $appLocale === 'ar';
    $dir = $isRtl ? 'rtl' : 'ltr';
    $cssRoot = $isRtl ? 'app-assets/css-rtl' : 'app-assets/css';
    $customCss = $isRtl ? 'assets/css/style-rtl.css' : 'assets/css/style.css';
@endphp
<!DOCTYPE html>
<html class="loading" lang="{{ $appLocale }}" dir="{{ $dir }}" data-textdirection="{{ $dir }}">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui">
    <meta name="description" content="@lang('auth.app_name')">
    <meta name="author" content="@lang('auth.app_name')">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __('auth.app_name')) — @lang('auth.app_name')</title>

    <link rel="apple-touch-icon" href="{{ asset('app-assets/images/ico/apple-icon-120.png') }}">
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('app-assets/images/ico/favicon.ico') }}">
    @if($isRtl)
        <link href="https://fonts.googleapis.com/css?family=Cairo:300,400,600,700&display=swap&subset=arabic" rel="stylesheet">
    @else
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    @endif
    <link href="https://cdnjs.cloudflare.com/ajax/libs/line-awesome/1.3.0/line-awesome/css/line-awesome.min.css" rel="stylesheet">

    <!-- BEGIN VENDOR CSS-->
    <link rel="stylesheet" type="text/css" href="{{ asset($cssRoot.'/vendors.css') }}">
    <!-- Select2 (searchable selects) -->
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/@ttskch/select2-bootstrap4-theme@1.5.2/dist/select2-bootstrap4.min.css">
    <!-- END VENDOR CSS-->

    <!-- BEGIN MODERN CSS-->
    <link rel="stylesheet" type="text/css" href="{{ asset($cssRoot.'/app.css') }}">
    @if($isRtl)
        <link rel="stylesheet" type="text/css" href="{{ asset($cssRoot.'/custom-rtl.css') }}">
    @endif
    <!-- END MODERN CSS-->

    <!-- BEGIN Page Level CSS-->
    <link rel="stylesheet" type="text/css" href="{{ asset($cssRoot.'/core/menu/menu-types/vertical-menu.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset($cssRoot.'/core/colors/palette-gradient.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('app-assets/fonts/simple-line-icons/style.css') }}">
    <!-- END Page Level CSS-->

    <!-- BEGIN Custom CSS-->
    <link rel="stylesheet" type="text/css" href="{{ asset($customCss) }}">
    <!-- END Custom CSS-->

    <style>
        body, h1, h2, h3, h4, h5, h6, p, span, a, button, input, select, textarea, label, th, td {
            font-family: {{ $isRtl ? "'Cairo', sans-serif" : "'Inter', system-ui, -apple-system, sans-serif" }} !important;
        }
        .brand-text {
            font-family: {{ $isRtl ? "'Cairo', sans-serif" : "'Inter', system-ui, sans-serif" }} !important;
            font-weight: 700;
        }
        /* ============ Header — single row, professional layout ============ */
        .shell-navbar-row { height: 56px; padding: 0 .75rem; }
        .shell-navbar-row .navbar-wrapper.shell-row {
            display: flex; flex-wrap: nowrap; align-items: center;
            justify-content: space-between; gap: 8px; height: 56px; width: 100%;
        }
        .shell-nav-left, .shell-nav-right { flex: 0 0 auto; min-width: 0; }
        .shell-nav-left { max-width: 30%; }
        .shell-nav-center { flex: 1 1 auto; justify-content: center; min-width: 0; padding: 0 12px; }
        .shell-nav-right { max-width: 55%; overflow: visible; }
        .shell-nav-right .nav-link { padding: .5rem .55rem !important; color: #fff !important; }
        .shell-nav-right .nav-link i { color: #fff; }
        .shell-nav-right .user-name { color: #fff; font-size: .85rem; }
        .shell-nav-right .avatar { width: 32px; height: 32px; }
        .shell-nav-right .avatar img { width: 32px; height: 32px; object-fit: cover; border-radius: 50%; }
        .header-navbar .brand-text { font-size: 1.1rem; font-weight: 700; color: #fff; }
        .header-navbar .brand-logo { height: 32px; }

        /* Select2 tweaks to look at home in a coloured header */
        #shell-scope-form .select2-container { margin: 0 2px; }
        #shell-scope-form .select2-selection--single {
            height: 34px; border-radius: 6px;
            border-color: rgba(255,255,255,.45); background: rgba(255,255,255,.15);
            color: #fff;
        }
        #shell-scope-form .select2-selection__rendered { color: #fff !important; line-height: 32px !important; }
        #shell-scope-form .select2-selection__arrow { height: 32px !important; }
        #shell-scope-form .select2-selection__arrow b { border-top-color: #fff !important; }

        /* ============ Sidebar — section differentiation + icon styling ============ */
        .main-menu.menu-light .navigation > li > a {
            padding: .55rem .85rem; border-radius: 8px; margin: 2px 10px;
            font-size: .92rem;
        }
        .main-menu.menu-light .navigation > li.active > a {
            background: linear-gradient(118deg, #1e9ff2 0%, #60bfff 100%);
            color: #fff !important;
            box-shadow: 0 4px 18px rgba(30,159,242,.35);
        }
        .main-menu.menu-light .navigation > li.active > a i { color: #fff; }
        .main-menu.menu-light .navigation > li > a > i {
            font-size: 1.15rem; width: 22px; text-align: center;
            margin-{{ $isRtl ? 'left' : 'right' }}: 10px;
        }
        /* LA 1.3 renamed the font — the theme's original chevron (\f112 + 'LineAwesome') stopped
           resolving when we upgraded. Re-emit the arrow using LA 1.3's glyph + font name. */
        .main-menu .navigation li.has-sub > a:not(.mm-next)::after {
            font-family: 'Line Awesome Free' !important;
            font-weight: 900 !important;
            content: "{{ $isRtl ? '\f104' : '\f105' }}" !important;
            font-size: .95rem;
            opacity: .55;
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            {{ $isRtl ? 'left' : 'right' }}: 14px;
            transition: transform .2s;
        }
        .main-menu .navigation li.has-sub.open > a:not(.mm-next)::after { transform: translateY(-50%) rotate(-90deg); opacity: .85; }
        .main-menu .navigation li ul.menu-content li a {
            padding: .45rem .75rem .45rem 2.2rem; font-size: .87rem; border-radius: 6px;
        }

        /* 4 distinct sections — tint the header band + add a coloured edge on children */
        .main-menu .navigation-header {
            margin: 14px 10px 6px; padding: 8px 14px;
            border-radius: 8px; background: #f4f6f8;
            font-weight: 700; letter-spacing: .3px; font-size: .78rem;
            text-transform: uppercase; opacity: .95;
        }
        .main-menu .navigation-header > span { font-weight: 700; opacity: 1; }

        /* Section 1 — برامج نوعية (purple) */
        .main-menu .navigation-header.sec-programs { background: linear-gradient(135deg, #e8e0ff, #f3efff); color: #6f42c1; border-{{ $isRtl ? 'right' : 'left' }}: 4px solid #6f42c1; }
        /* Section 2 — عمليات تعليمية (blue) */
        .main-menu .navigation-header.sec-educational { background: linear-gradient(135deg, #d9edff, #eef7ff); color: #1e88e5; border-{{ $isRtl ? 'right' : 'left' }}: 4px solid #1e88e5; }
        /* Section 3 — عمليات التواصل (orange) */
        .main-menu .navigation-header.sec-communication { background: linear-gradient(135deg, #fff3e0, #fff8ed); color: #f57c00; border-{{ $isRtl ? 'right' : 'left' }}: 4px solid #f57c00; }
        /* Section 4 — إعدادات النظام (green) */
        .main-menu .navigation-header.sec-system { background: linear-gradient(135deg, #e1f5e7, #effaf3); color: #2e7d32; border-{{ $isRtl ? 'right' : 'left' }}: 4px solid #2e7d32; }
        /* Section-specific icon tint via data-section attribute on each li */
        .main-menu li[data-section="programs"] > a > i { color: #6f42c1; }
        .main-menu li[data-section="educational"] > a > i { color: #1e88e5; }
        .main-menu li[data-section="communication"] > a > i { color: #f57c00; }
        .main-menu li[data-section="system"] > a > i { color: #2e7d32; }
        .main-menu li.active[data-section] > a > i { color: #fff !important; }
    </style>

    @stack('styles')
</head>
<body class="vertical-layout vertical-menu 2-columns menu-expanded fixed-navbar" data-open="click" data-menu="vertical-menu" data-col="2-columns">

    <!-- BEGIN: Header -->
    @include('components.navbar')
    <!-- END: Header -->

    <!-- BEGIN: Main Menu -->
    @include('components.sidebar')
    <!-- END: Main Menu -->

    <!-- BEGIN: Content -->
    <div class="app-content content">
        <div class="content-wrapper">
            <!-- Breadcrumb -->
            <div class="content-header row">
                <div class="content-header-left col-md-6 col-12 mb-2">
                    <h3 class="content-header-title">@yield('page-title')</h3>
                    <div class="row breadcrumbs-top">
                        <div class="breadcrumb-wrapper col-12">
                            @yield('breadcrumb')
                        </div>
                    </div>
                </div>
            </div>

            <!-- Alerts -->
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            @endif

            <!-- Content Body -->
            <div class="content-body">
                @yield('content')
            </div>
        </div>
    </div>
    <!-- END: Content -->

    <!-- BEGIN: Footer -->
    <footer class="footer footer-static footer-light navbar-border">
        <p class="clearfix blue-grey lighten-2 text-sm-center mb-0 px-2">
            <span class="float-md-{{ $isRtl ? 'right' : 'left' }} d-block d-md-inline-block">
                @lang('shell.footer_rights') &copy; {{ date('Y') }}
                <a class="text-bold-800 grey darken-2" href="#">@lang('auth.app_name')</a>
            </span>
        </p>
    </footer>
    <!-- END: Footer -->

    <!-- BEGIN VENDOR JS-->
    <script src="{{ asset('app-assets/vendors/js/vendors.min.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <!-- END VENDOR JS-->

    <script>
        // Initialise Select2 on the header scope selectors so they are searchable.
        (function () {
            if (!window.jQuery || !jQuery.fn.select2) return;
            jQuery(function ($) {
                $('#shell-scope-form select').select2({
                    theme: 'bootstrap4',
                    width: 'resolve',
                    dir: '{{ $dir }}',
                    minimumResultsForSearch: 0,  // always show search box
                });
            });
        })();
    </script>

    <!-- BEGIN MODERN JS-->
    <script src="{{ asset('app-assets/js/core/app-menu.js') }}"></script>
    <script src="{{ asset('app-assets/js/core/app.js') }}"></script>
    <!-- END MODERN JS-->

    <script>
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        // Mark notification as read
        function markNotificationRead(id) {
            fetch('/notifications/' + id + '/read', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                }
            });
        }

        // Refresh notification count periodically
        function refreshNotificationCount() {
            fetch('/notifications/count', {
                headers: { 'Accept': 'application/json' }
            })
            .then(response => response.json())
            .then(data => {
                const badge = document.querySelector('.ft-bell').nextElementSibling;
                if (data.count > 0) {
                    if (badge && badge.classList.contains('badge')) {
                        badge.textContent = data.count > 9 ? '9+' : data.count;
                    }
                } else {
                    if (badge && badge.classList.contains('badge')) {
                        badge.remove();
                    }
                }
            })
            .catch(err => console.log('Notification refresh error:', err));
        }

        // Refresh every 60 seconds
        setInterval(refreshNotificationCount, 60000);
    </script>

    @stack('scripts')
</body>
</html>
