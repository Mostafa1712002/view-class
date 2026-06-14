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
    {{-- Bootstrap Icons: many Sprint pages (exams, grades, attendance, messages, student/parent) use `bi bi-*`
         which the theme never loaded, so their action icons rendered as empty boxes (card #163). --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    {{-- SweetAlert2: app-wide confirm dialogs + success/error toasts (see the global upgrader before </body>). --}}
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <style>
        /* keep top toasts below the fixed navbar so they're actually visible */
        .swal2-container.swal2-top { top: 4.7rem; }
        .swal2-toast { box-shadow: 0 10px 30px rgba(30, 25, 10, .18) !important; border: 1px solid #efe6cf; }
        .swal2-popup.swal2-toast .swal2-title { font-size: .95rem; }
    </style>
    {{-- Golden Platform brand fonts: Playfair for English serif headings, Cairo already loaded above for Arabic. --}}
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
        /* ============ Golden Platform brand tokens ============ */
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

        /* =====================================================================
           Bootstrap 5 → Bootstrap 4 compatibility shim
           The admin theme ships Bootstrap 4.0 + jQuery 3.2.1. Many views were
           authored with BS5 utility classes that BS4 doesn't define, so they
           silently render unstyled. This block back-fills the BS5 utilities
           we actually use so existing markup behaves correctly. JS data-attrs
           are handled separately (each view also carries data-toggle/-target/
           -dismiss alongside the data-bs-* form).
           ===================================================================== */

        /* form-select → style like BS4 .custom-select / .form-control */
        .form-select {
            display: block;
            width: 100%;
            height: calc(1.5em + .75rem + 2px);
            padding: .375rem 1.75rem .375rem .75rem;
            font-size: .9rem;
            line-height: 1.5;
            color: #495057;
            vertical-align: middle;
            background: #fff url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23343a40' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2 5l6 6 6-6'/%3e%3c/svg%3e") no-repeat right .75rem center/12px 12px;
            border: 1px solid #ced4da;
            border-radius: .375rem;
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
        }
        html[dir="rtl"] .form-select {
            padding: .375rem .75rem .375rem 1.75rem;
            background-position: left .75rem center;
        }
        .form-select:focus {
            border-color: var(--gold-300);
            outline: 0;
            box-shadow: 0 0 0 .15rem rgba(207,160,70,.18);
        }
        .form-select-sm { height: calc(1.5em + .5rem + 2px); padding-top: .25rem; padding-bottom: .25rem; font-size: .8rem; }
        .form-select-lg { height: calc(1.5em + 1rem + 2px); padding-top: .5rem; padding-bottom: .5rem; font-size: 1.05rem; }

        /* btn-close → BS5 close button (BS4 used .close). Render the X glyph. */
        .btn-close {
            box-sizing: content-box;
            width: 1em; height: 1em;
            padding: .25em;
            color: #000;
            background: transparent url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%23000'%3e%3cpath d='M.293.293a1 1 0 011.414 0L8 6.586 14.293.293a1 1 0 111.414 1.414L9.414 8l6.293 6.293a1 1 0 01-1.414 1.414L8 9.414l-6.293 6.293a1 1 0 01-1.414-1.414L6.586 8 .293 1.707a1 1 0 010-1.414z'/%3e%3c/svg%3e") center/1em auto no-repeat;
            border: 0; border-radius: .375rem;
            opacity: .5; cursor: pointer;
        }
        .btn-close:hover { opacity: .85; }

        /* gap-* — flex/grid gap (supported by browsers; BS4 just lacks the class) */
        .gap-0 { gap: 0 !important; }
        .gap-1 { gap: .25rem !important; }
        .gap-2 { gap: .5rem !important; }
        .gap-3 { gap: 1rem !important; }
        .gap-4 { gap: 1.5rem !important; }
        .gap-5 { gap: 3rem !important; }

        /* row gutters g-* (BS5) → emulate with padding on columns + negative row margin */
        .row.g-1 { margin: -.25rem; } .row.g-1 > [class*="col"] { padding: .25rem; }
        .row.g-2 { margin: -.5rem; }  .row.g-2 > [class*="col"] { padding: .5rem; }
        .row.g-3 { margin: -.75rem; } .row.g-3 > [class*="col"] { padding: .75rem; }
        .row.g-4 { margin: -1rem; }   .row.g-4 > [class*="col"] { padding: 1rem; }

        /* Logical-direction margins/paddings (BS5 ms/me/ps/pe). Use inline
           logical props so they flip correctly under RTL automatically. */
        .ms-0 { margin-inline-start: 0 !important; } .me-0 { margin-inline-end: 0 !important; }
        .ms-1 { margin-inline-start: .25rem !important; } .me-1 { margin-inline-end: .25rem !important; }
        .ms-2 { margin-inline-start: .5rem !important; }  .me-2 { margin-inline-end: .5rem !important; }
        .ms-3 { margin-inline-start: 1rem !important; }   .me-3 { margin-inline-end: 1rem !important; }
        .ms-4 { margin-inline-start: 1.5rem !important; } .me-4 { margin-inline-end: 1.5rem !important; }
        .ms-5 { margin-inline-start: 3rem !important; }   .me-5 { margin-inline-end: 3rem !important; }
        .ms-auto { margin-inline-start: auto !important; } .me-auto { margin-inline-end: auto !important; }
        .ps-0 { padding-inline-start: 0 !important; } .pe-0 { padding-inline-end: 0 !important; }
        .ps-1 { padding-inline-start: .25rem !important; } .pe-1 { padding-inline-end: .25rem !important; }
        .ps-2 { padding-inline-start: .5rem !important; }  .pe-2 { padding-inline-end: .5rem !important; }
        .ps-3 { padding-inline-start: 1rem !important; }   .pe-3 { padding-inline-end: 1rem !important; }
        .ps-4 { padding-inline-start: 1.5rem !important; } .pe-4 { padding-inline-end: 1.5rem !important; }
        .ps-5 { padding-inline-start: 3rem !important; }   .pe-5 { padding-inline-end: 3rem !important; }
        /* larger me-* seen in views */
        .me-6 { margin-inline-end: 4rem !important; } .me-7 { margin-inline-end: 5rem !important; }
        .me-8 { margin-inline-end: 6rem !important; } .me-9 { margin-inline-end: 7rem !important; }

        /* font weights (BS5) */
        .fw-bold { font-weight: 700 !important; }
        .fw-bolder { font-weight: 800 !important; }
        .fw-semibold { font-weight: 600 !important; }
        .fw-medium { font-weight: 500 !important; }
        .fw-normal { font-weight: 400 !important; }
        .fw-light { font-weight: 300 !important; }

        /* font sizes (BS5 .fs-*) */
        .fs-1 { font-size: 2.5rem !important; }
        .fs-2 { font-size: 2rem !important; }
        .fs-3 { font-size: 1.75rem !important; }
        .fs-4 { font-size: 1.5rem !important; }
        .fs-5 { font-size: 1.25rem !important; }
        .fs-6 { font-size: 1rem !important; }

        /* d-grid (BS5) */
        .d-grid { display: grid !important; }

        /* rounded-pill (BS5; BS4 had .rounded-pill from 4.2 only) */
        .rounded-pill { border-radius: 50rem !important; }

        /* Background utilities used for badges/labels (BS4 .badge-* exists but
           BS5 markup uses .bg-*; ensure white text + brand gold for primary) */
        .badge.bg-primary, .bg-primary { background-color: var(--gold-400) !important; color: #fff; }
        .badge.bg-secondary, .bg-secondary { background-color: #6c757d !important; color: #fff; }
        .badge.bg-success, .bg-success { background-color: #28a745 !important; color: #fff; }
        .badge.bg-danger, .bg-danger { background-color: #dc3545 !important; color: #fff; }
        .badge.bg-warning, .bg-warning { background-color: #ffc107 !important; color: #212529; }
        .badge.bg-info, .bg-info { background-color: #17a2b8 !important; color: #fff; }
        .badge.bg-dark, .bg-dark { background-color: #343a40 !important; color: #fff; }
        .badge.bg-light, .bg-light { background-color: #f1f5f9 !important; color: #475569; }
        /* text-bg-* (BS5) */
        .text-bg-primary { background-color: var(--gold-400) !important; color: #fff !important; }
        .text-bg-success { background-color: #28a745 !important; color: #fff !important; }
        .text-bg-danger  { background-color: #dc3545 !important; color: #fff !important; }
        .text-bg-warning { background-color: #ffc107 !important; color: #212529 !important; }
        .text-bg-info    { background-color: #17a2b8 !important; color: #fff !important; }
        .text-bg-secondary { background-color: #6c757d !important; color: #fff !important; }
        /* note: header navbar's own .bg-info gradient (defined above) keeps priority via its compound selector */

        /* =====================================================================
           Unified content-header — one professional treatment for every page's
           `class="content-header row"` (card requirement #2). Title + breadcrumb
           on the start side, action buttons on the end side, gold accent bar.
           ===================================================================== */
        .app-content .content-header.row {
            margin: 0 0 1.25rem;
            padding: 1rem 1.25rem;
            background: #ffffff;
            border: 1px solid #e9edf3;
            border-radius: 14px;
            box-shadow: 0 1px 2px rgba(15,23,42,.04), 0 6px 18px rgba(15,23,42,.04);
            position: relative;
            overflow: hidden;
            align-items: center;
            animation: themeLightFadeIn .3s cubic-bezier(.4,0,.2,1) both;
        }
        /* gold accent bar on the leading edge */
        .app-content .content-header.row::before {
            content: "";
            position: absolute;
            inset-block: 0;
            inset-inline-start: 0;
            width: 5px;
            background: linear-gradient(180deg, var(--gold-200), var(--gold-500));
        }
        .app-content .content-header.row .content-header-title {
            font-weight: 800;
            font-size: 1.4rem;
            margin: 0;
            color: #0f172a;
            letter-spacing: -.3px;
        }
        .app-content .content-header.row .breadcrumb {
            background: transparent;
            padding: .3rem 0 0;
            margin: 0;
            font-size: .82rem;
        }
        .app-content .content-header.row .breadcrumb-item a { color: #94a3b8; }
        .app-content .content-header.row .breadcrumb-item a:hover { color: var(--gold-400); }
        .app-content .content-header.row .breadcrumb-item.active { color: var(--gold-500); font-weight: 600; }
        .app-content .content-header.row .content-header-right .d-flex,
        .app-content .content-header.row .content-header-right > div {
            gap: .5rem;
        }
        .app-content .content-header.row .content-header-right {
            display: flex;
            align-items: center;
            justify-content: flex-end;
        }
        @media (max-width: 767.98px) {
            .app-content .content-header.row { padding: .85rem 1rem; }
            .app-content .content-header.row .content-header-title { font-size: 1.2rem; }
            .app-content .content-header.row .content-header-right { justify-content: flex-start; margin-top: .6rem; }
            .app-content .content-header.row .content-header-right .d-flex { flex-wrap: wrap; }
        }
        /* Suppress the layout's empty fallback header (pages render their own) */
        .app-content .content-header.row.shell-empty-header { display: none; }

        /* =====================================================================
           Shared shell consistency (card requirement #1 + #3)
           Tables never overflow the viewport on small screens; consistent
           filter bars / cards / buttons inherit the light theme above.
           ===================================================================== */
        /* Any table inside a card scrolls horizontally instead of breaking layout */
        .card .table-responsive,
        .card > .card-body > .table:not(.table-responsive) { width: 100%; }
        .card .table { width: 100%; }
        @media (max-width: 991.98px) {
            .card .card-body > .table,
            .card .card-body > table.table {
                display: block;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
        }
        /* Consistent filter/search bars: any .card-body holding a search input */
        body.theme-light .form-control,
        body.theme-light .form-select {
            border-color: #e2e8f0;
        }
        /* Consistent secondary/outline buttons in light theme */
        body.theme-light .btn-outline-secondary {
            border-color: #e2e8f0;
            color: #475569;
        }
        body.theme-light .btn-outline-secondary:hover {
            background: #f8fafc;
            border-color: var(--gold-300);
            color: var(--gold-500);
        }
        /* Soft button variant used across list pages */
        body.theme-light .btn-soft {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            color: #475569;
        }
        body.theme-light .btn-soft:hover {
            background: #f1f5f9;
            border-color: var(--gold-300);
            color: var(--gold-500);
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
                @php
                    $__viewingUser = auth()->user();
                    $__viewingRole = $__viewingUser?->isSuperAdmin()  ? __('users.admins')
                        : ($__viewingUser?->isSchoolAdmin()           ? __('users.admins')
                        : ($__viewingUser?->isTeacher()               ? __('users.teachers')
                        : ($__viewingUser?->isParent()                ? __('users.parents')
                        : ($__viewingUser?->isStudent()               ? __('users.students')
                        : ''))));
                @endphp
                <form action="{{ route('admin.users.impersonate.stop') }}" method="POST" class="m-0">
                    @csrf
                    <div class="d-flex align-items-center justify-content-between px-3 py-2 mb-1"
                         style="background:linear-gradient(135deg,#1a2f4e 0%,#2c4a72 100%);border-right:4px solid #c9a227;color:#fff;border-radius:4px;">
                        <span class="d-flex align-items-center gap-2">
                            <i class="la la-eye" style="font-size:1.2rem;color:#c9a227;"></i>
                            <strong style="color:#c9a227;">وضع الإطلاع</strong>
                            <span class="mx-1">|</span>
                            @lang('users.impersonating_banner', [
                                'name' => $__viewingUser?->name,
                                'role' => $__viewingRole,
                            ])
                        </span>
                        <button type="submit" class="btn btn-sm ms-3"
                                style="background:#c9a227;color:#1a2f4e;font-weight:600;border:none;white-space:nowrap;">
                            <i class="la la-sign-out-alt"></i> @lang('users.stop_impersonating')
                        </button>
                    </div>
                </form>
            @endif
            {{-- Fallback breadcrumb header. Pages render their own
                 `content-header row`, so this only shows when a page opts in
                 via @section('page-title'); otherwise it's hidden to avoid an
                 empty band. --}}
            @hasSection('page-title')
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
            @endif

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

    {{-- ── App-wide SweetAlert2: confirm dialogs + success/error toasts ───────────────
         Auto-upgrades existing markup with NO per-view changes:
         • flash banners (.alert-success / .alert-danger, single short message) → toast
         • inline confirm() on forms/links (onsubmit/onclick) → SweetAlert confirm
         Plus window.vcConfirm()/vcToast() helpers for new code. --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
    <script>
    (function () {
        if (!window.Swal) return;
        var RTL = (document.documentElement.getAttribute('dir') || 'rtl') === 'rtl';
        var GOLD = '#c9a04b';
        var T = { yes: @json(__('common.confirm_yes')), no: @json(__('common.confirm_no')), sure: @json(__('common.confirm_sure')) };

        window.vcToast = function (title, icon) {
            Swal.fire({ toast: true, position: 'top', icon: icon || 'success',
                title: title, showConfirmButton: false, timer: 3500, timerProgressBar: true });
        };
        window.vcConfirm = function (opts) {
            return Swal.fire(Object.assign({ icon: 'warning', showCancelButton: true, reverseButtons: true,
                confirmButtonText: T.yes, cancelButtonText: T.no, confirmButtonColor: GOLD }, opts || {}));
        };

        document.addEventListener('DOMContentLoaded', function () {
            // 1) flash banners → toast (skip validation lists / long blocks)
            document.querySelectorAll('.alert-success, .alert-danger').forEach(function (el) {
                if (el.closest('.swal2-container')) return;
                if (el.querySelector('ul, li, form, .close, button')) return;
                var text = (el.textContent || '').trim();
                if (!text || text.length > 220) return;
                window.vcToast(text, el.classList.contains('alert-danger') ? 'error' : 'success');
                el.remove();
            });

            // 2) inline confirm() → SweetAlert
            function upgrade(el, attr) {
                var code = el.getAttribute(attr);
                if (!code || code.indexOf('confirm(') < 0) return;
                var m = code.match(/confirm\(\s*['"]([\s\S]*?)['"]/);
                var msg = m ? m[1] : T.sure;
                el.removeAttribute(attr);
                var isForm = (attr === 'onsubmit');
                el.addEventListener(isForm ? 'submit' : 'click', function (e) {
                    if (el.dataset.vcOk === '1') { el.dataset.vcOk = ''; return; }
                    e.preventDefault(); e.stopPropagation();
                    window.vcConfirm({ title: msg }).then(function (r) {
                        if (!r.isConfirmed) return;
                        if (isForm) { el.submit(); }      // bypasses listeners → no loop
                        else { el.dataset.vcOk = '1'; el.click(); }
                    });
                }, true);
            }
            document.querySelectorAll('[onsubmit]').forEach(function (el) { upgrade(el, 'onsubmit'); });
            document.querySelectorAll('[onclick]').forEach(function (el) {
                if ((el.getAttribute('onclick') || '').indexOf('confirm(') >= 0) upgrade(el, 'onclick');
            });
        });
    })();
    </script>

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

    {{-- Global fix: action/▾ dropdown menus inside scrollable tables get clipped
         by .table-responsive (overflow:auto) and trapped by transformed .card
         ancestors. Relocate the open menu to <body> with fixed positioning so it
         floats above the table and every element, on every page. --}}
    <style>
        .tw-floating-menu { position: fixed !important; z-index: 3000 !important; display: block !important; margin: 0 !important; }
    </style>
    <script>
    (function () {
        function inScroller(el) { return !!(el && el.closest && el.closest('.table-responsive')); }
        function place(menu, toggle) {
            var r = toggle.getBoundingClientRect();
            if (!menu.__twOrigParent) { menu.__twOrigParent = menu.parentNode; menu.__twOrigNext = menu.nextSibling; }
            if (menu.parentNode !== document.body) document.body.appendChild(menu);
            menu.classList.add('tw-floating-menu');
            menu.__twToggle = toggle;
            var mw = menu.offsetWidth || 230;
            var mh = menu.offsetHeight || 0;
            var rtl = (getComputedStyle(document.body).direction === 'rtl');
            var left = rtl ? (r.right - mw) : r.left;
            if (left < 8) left = 8;
            if (left + mw > window.innerWidth - 8) left = window.innerWidth - mw - 8;
            var top = r.bottom + 4;
            if (top + mh > window.innerHeight - 8 && (r.top - mh - 4) > 8) top = r.top - mh - 4; // flip up
            menu.style.left = left + 'px';
            menu.style.top = top + 'px';
            menu.style.right = 'auto';
            menu.style.bottom = 'auto';
            menu.style.transform = 'none';
        }
        function restore(menu) {
            menu.classList.remove('tw-floating-menu');
            ['left', 'top', 'right', 'bottom', 'transform'].forEach(function (p) { menu.style[p] = ''; });
            if (menu.__twOrigParent) {
                if (menu.__twOrigNext && menu.__twOrigNext.parentNode === menu.__twOrigParent) {
                    menu.__twOrigParent.insertBefore(menu, menu.__twOrigNext);
                } else {
                    menu.__twOrigParent.appendChild(menu);
                }
            }
            menu.__twOrigParent = null; menu.__twOrigNext = null; menu.__twToggle = null;
        }
        function syncAll() {
            document.querySelectorAll('.tw-floating-menu').forEach(function (m) {
                if (!m.classList.contains('show')) restore(m);
            });
        }
        if (window.jQuery) {
            window.jQuery(document)
                .on('shown.bs.dropdown', function (e) {
                    var dd = e.target;
                    var toggle = dd.querySelector('[data-toggle="dropdown"],[data-bs-toggle="dropdown"]');
                    var menu = dd.querySelector('.dropdown-menu');
                    if (toggle && menu && inScroller(toggle)) place(menu, toggle);
                })
                .on('hidden.bs.dropdown', function () { setTimeout(syncAll, 0); });
        }
        // Click fallback (covers BS5 / non-jQuery dropdowns).
        document.addEventListener('click', function (e) {
            var toggle = e.target.closest && e.target.closest('[data-toggle="dropdown"],[data-bs-toggle="dropdown"]');
            setTimeout(function () {
                if (toggle && inScroller(toggle)) {
                    var menu = toggle.parentNode.querySelector('.dropdown-menu')
                        || document.querySelector('.tw-floating-menu');
                    if (menu && menu.classList.contains('show')) place(menu, toggle);
                }
                syncAll();
            }, 0);
        }, true);
        function reposition() {
            document.querySelectorAll('.tw-floating-menu').forEach(function (m) {
                if (m.__twToggle && m.classList.contains('show')) place(m, m.__twToggle);
            });
        }
        window.addEventListener('scroll', reposition, true);
        window.addEventListener('resize', reposition);
    })();
    </script>

    @stack('scripts')
</body>
</html>
