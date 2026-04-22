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
    <link href="https://maxcdn.icons8.com/fonts/line-awesome/1.1/css/line-awesome.min.css" rel="stylesheet">

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
        /* Shell polish — tighter header, cleaner sidebar, consistent spacing */
        .header-navbar { padding: 0 1rem; }
        .header-navbar .navbar-wrapper { max-width: 100%; }
        .header-navbar .brand-text { font-size: 1.1rem; margin-{{ $isRtl ? 'right' : 'left' }}: .5rem; }
        #shell-scope-form .select2-container { margin: 0 .25rem; }
        #shell-scope-form .select2-selection--single {
            height: 32px; line-height: 32px; border-radius: 6px;
            border-color: rgba(255,255,255,.4); background: rgba(255,255,255,.15);
            color: #fff;
        }
        #shell-scope-form .select2-selection__rendered { color: #fff !important; line-height: 30px !important; }
        #shell-scope-form .select2-selection__arrow { height: 30px !important; }
        .main-menu.menu-light .navigation > li > a { padding: .65rem 1rem; border-radius: 6px; margin: 2px 8px; }
        .main-menu.menu-light .navigation > li.active > a { background: linear-gradient(118deg, rgba(0,170,255,.2), rgba(0,170,255,.08)); }
        .main-menu.menu-light .navigation > li > a > i { font-size: 1.1rem; margin-{{ $isRtl ? 'left' : 'right' }}: 10px; }
        .navigation-header > span { font-weight: 700; letter-spacing: .5px; opacity: .85; }
        .main-menu .navigation .has-sub > a::after { opacity: .5; }
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
                    minimumResultsForSearch: 5,
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
