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
    {{-- Al-Awwal brand fonts: Playfair for English serif headings, Cairo already loaded above for Arabic. --}}
    @if(!$isRtl)
        <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600;700;800&display=swap" rel="stylesheet">
    @endif

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
        /* ============ Al-Awwal brand tokens (Sprint 5 — الهوية) ============ */
        :root {
            --gold-100: #f6d27a;
            --gold-200: #e3b85c;
            --gold-300: #cfa046;
            --gold-400: #b7842e;
            --gold-500: #9c6b1f;

            --black-100: #0b0b0b;
            --black-200: #121212;
            --black-300: #1a1a1a;

            --white-100: #ffffff;
            --white-200: #f5f5f5;
            --white-300: #dcdcdc;

            --brand-green: #1f6f4a;

            --text-primary: var(--white-100);
            --text-secondary: #a1a1a1;

            --radius-md: 10px;
        }

        body, h1, h2, h3, h4, h5, h6, p, span, a, button, input, select, textarea, label, th, td {
            font-family: {{ $isRtl ? "'Cairo', sans-serif" : "'Inter', system-ui, -apple-system, sans-serif" }} !important;
        }
        {{-- English luxury serif on headings + brand text --}}
        @if(!$isRtl)
        h1, h2, h3, h4, .brand-text, .auth-card .brand-logo h2 {
            font-family: 'Playfair Display', 'Cinzel', Georgia, serif !important;
            letter-spacing: .2px;
        }
        @endif
        .brand-text {
            font-family: {{ $isRtl ? "'Cairo', sans-serif" : "'Playfair Display', 'Inter', serif" }} !important;
            font-weight: 700;
        }

        /* ============ Brand colour overrides (replace blue/purple primary) ============ */
        .bg-info, .header-navbar.bg-info {
            background: linear-gradient(135deg, var(--black-200) 0%, var(--black-300) 60%, var(--gold-500) 130%) !important;
            border-bottom: 1px solid var(--gold-400);
        }
        .header-navbar .brand-text { color: var(--gold-200) !important; }

        .btn-primary, .btn-primary:focus {
            background: linear-gradient(135deg, var(--gold-200), var(--gold-500)) !important;
            border-color: var(--gold-400) !important;
            color: var(--white-100) !important;
            box-shadow: 0 2px 8px rgba(207,160,70,.25);
        }
        .btn-primary:hover, .btn-primary:active {
            background: linear-gradient(135deg, var(--gold-300), var(--gold-500)) !important;
            border-color: var(--gold-500) !important;
            transform: translateY(-1px);
            box-shadow: 0 6px 18px rgba(207,160,70,.35);
        }
        .btn-gold {
            background: linear-gradient(135deg, var(--gold-200), var(--gold-500));
            color: #fff;
            border: none;
            border-radius: var(--radius-md);
            transition: .25s ease;
            box-shadow: 0 2px 8px rgba(207,160,70,.25);
        }
        .btn-gold:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(207,160,70,.3);
            color: #fff;
        }

        /* Form focus glow in gold instead of purple/blue */
        .form-control:focus, .form-select:focus {
            border-color: var(--gold-300) !important;
            box-shadow: 0 0 0 .15rem rgba(207,160,70,.18) !important;
        }
        a { color: var(--gold-500); }
        a:hover { color: var(--gold-400); }
        /* ============ Header — single row on xl+, stacked on smaller ============ */
        .shell-navbar-row { min-height: 56px; height: auto; padding: 0 .75rem; }
        .shell-navbar-row .navbar-wrapper.shell-row {
            display: flex; flex-wrap: nowrap; align-items: center;
            justify-content: space-between; gap: 8px; min-height: 56px; width: 100%;
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

        /* ============ Mobile/tablet header adjustments ============ */
        /* When mobile scope strip is visible (below xl), push content further down */
        @media (max-width: 1199.98px) {
            html body.fixed-navbar { padding-top: 7.5rem; }
        }
        /* Phones: tighten icon padding and hide non-critical chrome */
        @media (max-width: 575.98px) {
            html body.fixed-navbar { padding-top: 8rem; }
            .shell-navbar-row { padding: 0 .35rem; }
            .shell-nav-left { max-width: 38%; }
            .shell-nav-right { max-width: 62%; gap: 0; }
            .shell-nav-right .nav-link { padding: .35rem .3rem !important; }
            .shell-nav-right .nav-link i { font-size: 1.05rem; }
            .shell-nav-right .badge.badge-up { transform: scale(.8); }
            .header-navbar .brand-logo { height: 28px; }
            /* Drop low-value items on phones (search, language, role-switch live in avatar dropdown anyway) */
            [data-shell-hide-xs] { display: none !important; }
        }
        /* Scope strip: stack cleanly on phones, single row from sm up */
        #shell-scope-mobile { padding: 6px 12px; gap: 6px; }
        #shell-scope-mobile form { width: 100%; gap: 6px !important; }
        #shell-scope-mobile select { min-width: 0; }
        @media (max-width: 575.98px) {
            #shell-scope-mobile select { flex: 1 1 100% !important; max-width: 100% !important; }
        }

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
            background: linear-gradient(118deg, var(--gold-300) 0%, var(--gold-200) 100%);
            color: var(--white-100) !important;
            box-shadow: 0 4px 18px rgba(207,160,70,.35);
        }
        .main-menu.menu-light .navigation > li.active > a i { color: #fff; }
        .main-menu.menu-light .navigation > li > a > i {
            font-size: 1.15rem; width: 22px; text-align: center;
            margin-{{ $isRtl ? 'left' : 'right' }}: 10px;
        }
        /* ============ Sidebar — prevent menu-title truncation ============ */
        .main-menu .navigation > li > a .menu-title,
        .main-menu .navigation li ul.menu-content li a .menu-item {
            white-space: normal !important;
            overflow: visible !important;
            text-overflow: unset !important;
        }

        /* ============ Table improvements for sub-pages ============ */
        .table thead th {
            background: #f4f6fb;
            font-weight: 600;
            font-size: .85rem;
            letter-spacing: .3px;
            white-space: nowrap;
        }
        .table tbody tr:hover { background: #f8fbff; }
        .table td, .table th { vertical-align: middle; }
        .btn-group .btn { border-radius: 6px !important; margin: 0 1px; }

        /* ============ Mobile scope strip (visible on lg, hidden on xl+ where it's in header) ============ */
        #shell-scope-mobile {
            background: rgba(0,0,0,.12);
            padding: 6px 16px;
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            align-items: center;
        }
        #shell-scope-mobile select {
            flex: 1 1 140px;
            min-width: 120px;
            max-width: 200px;
            height: 30px;
            font-size: .82rem;
            border: 1px solid rgba(255,255,255,.4);
            background: rgba(255,255,255,.18);
            color: #fff;
            border-radius: 6px;
        }
        #shell-scope-mobile select option { color: #333; background: #fff; }

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

        /* ============ Light theme — opt-in via body.theme-light (Slice 1 reworked) ============
           Clean white background, soft hairline cards, gold used only as accent.
           No backdrop-filter, no heavy gradients — keeps mobile FCP snappy. */
        body.theme-light {
            background: #f8fafc !important;
            color: #0f172a;
        }
        body.theme-light .app-content { background: transparent; }
        body.theme-light .content-header-title,
        body.theme-light h1, body.theme-light h2, body.theme-light h3,
        body.theme-light h4, body.theme-light h5, body.theme-light h6 {
            color: #0f172a;
        }
        body.theme-light .text-muted,
        body.theme-light small.text-muted { color: #64748b !important; }
        body.theme-light p, body.theme-light .card-text { color: #475569; }
        body.theme-light .breadcrumb { background: transparent; }
        body.theme-light .breadcrumb-item,
        body.theme-light .breadcrumb-item a { color: #64748b; }
        body.theme-light .breadcrumb-item.active { color: var(--gold-400); font-weight: 600; }

        /* Cards — white surface, hairline border, very soft elevation */
        body.theme-light .card {
            background: #ffffff !important;
            border: 1px solid #e5e7eb !important;
            border-radius: 14px !important;
            box-shadow: 0 1px 2px rgba(15,23,42,.04), 0 4px 12px rgba(15,23,42,.04);
            color: #0f172a;
            transition: transform .25s cubic-bezier(.4,0,.2,1), box-shadow .25s cubic-bezier(.4,0,.2,1);
            animation: themeLightFadeIn .32s cubic-bezier(.4,0,.2,1) both;
        }
        body.theme-light .card .card-body { color: #0f172a; }
        body.theme-light .card-header {
            background: transparent;
            border-bottom: 1px solid #f1f5f9;
            padding: .9rem 1rem;
        }
        body.theme-light .card-title,
        body.theme-light .card .card-title { color: #0f172a; font-weight: 700; }

        /* Hover lift — but only on stat tiles to keep tables/charts stable */
        body.theme-light .card.text-center:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 14px rgba(15,23,42,.06), 0 12px 28px rgba(15,23,42,.05);
        }

        /* KPI numbers — gold accent, sparingly */
        body.theme-light .card h2.fw-bolder,
        body.theme-light .card h2.fw-bold,
        body.theme-light .card h3.fw-bolder,
        body.theme-light .luxury-stat {
            color: var(--gold-400);
            font-weight: 800;
            letter-spacing: -.5px;
        }

        /* Tables — clean, lots of whitespace */
        body.theme-light .table { color: #0f172a; }
        body.theme-light .table thead th {
            background: #f8fafc !important;
            color: #475569;
            border-bottom: 1px solid #e5e7eb;
            font-weight: 600;
        }
        body.theme-light .table tbody tr { border-color: #f1f5f9; }
        body.theme-light .table tbody tr:hover { background: #f8fafc; }
        body.theme-light .table td,
        body.theme-light .table th { border-color: #f1f5f9; }

        /* Avatars — subtle ring */
        body.theme-light .avatar.bg-light-primary,
        body.theme-light .avatar.bg-light-success,
        body.theme-light .avatar.bg-light-warning,
        body.theme-light .avatar.bg-light-info,
        body.theme-light .avatar.bg-light-danger,
        body.theme-light .avatar.bg-light-secondary {
            border: 1px solid rgba(15,23,42,.06);
        }

        /* Welcome banner override — keep readable on white shell */
        body.theme-light .card[style*="#f8fbff"] {
            background: #ffffff !important;
            border-{{ $isRtl ? 'right' : 'left' }}: 4px solid var(--gold-300) !important;
        }
        body.theme-light .card[style*="#f8fbff"] h4 { color: #0f172a; }

        /* Progress bars — soft track */
        body.theme-light .progress { background: #f1f5f9; }

        /* Buttons that aren't btn-primary still benefit from a subtle interaction */
        body.theme-light .btn { transition: transform .15s ease, box-shadow .2s ease; }
        body.theme-light .btn:active { transform: translateY(1px); }

        /* Stagger card fade-in — first row appears immediately, later rows trail in */
        body.theme-light .row:nth-of-type(1) .card { animation-delay: 0ms; }
        body.theme-light .row:nth-of-type(2) .card { animation-delay: 40ms; }
        body.theme-light .row:nth-of-type(3) .card { animation-delay: 80ms; }
        body.theme-light .row:nth-of-type(4) .card { animation-delay: 120ms; }

        @keyframes themeLightFadeIn {
            from { opacity: 0; transform: translateY(6px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        @media (prefers-reduced-motion: reduce) {
            body.theme-light .card { animation: none !important; transition: none !important; }
            body.theme-light .card.text-center:hover { transform: none; }
        }
    </style>

    @stack('styles')
</head>
<body class="vertical-layout vertical-menu 2-columns menu-expanded fixed-navbar @yield('body_class')" data-open="click" data-menu="vertical-menu" data-col="2-columns">

    <!-- BEGIN: Header -->
    @include('components.navbar')
    <!-- END: Header -->

    <!-- BEGIN: Main Menu -->
    @include('components.sidebar')
    <!-- END: Main Menu -->

    <!-- BEGIN: Content -->
    <div class="app-content content">
        <div class="content-wrapper">
            @if(session('impersonator_id'))
                <form action="{{ route('admin.users.impersonate.stop') }}" method="POST" class="m-0">
                    @csrf
                    <div class="alert alert-warning d-flex justify-content-between align-items-center mb-1" style="border:2px solid #f0ad4e;">
                        <span>
                            <i class="la la-user-secret"></i>
                            @lang('users.impersonating_banner', ['name' => auth()->user()?->name])
                        </span>
                        <button class="btn btn-sm btn-outline-danger">
                            @lang('users.stop_impersonating')
                        </button>
                    </div>
                </form>
            @endif
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
                // Generic init for any form that opts in via `.select2` class.
                $('select.select2').not('#shell-scope-form select').select2({
                    theme: 'bootstrap4',
                    width: '100%',
                    dir: '{{ $dir }}',
                    minimumResultsForSearch: 6,  // hide search for tiny lists
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
