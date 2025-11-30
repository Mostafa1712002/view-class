<!DOCTYPE html>
<html class="loading" lang="ar" data-textdirection="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>تسجيل الدخول - المنصة الذهبية</title>

    <!-- Google Fonts - Cairo -->
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- BEGIN: Vendor CSS-->
    <link rel="stylesheet" type="text/css" href="{{ asset('app-assets/vendors/css/vendors-rtl.min.css') }}">
    <!-- END: Vendor CSS-->

    <!-- BEGIN: Theme CSS-->
    <link rel="stylesheet" type="text/css" href="{{ asset('app-assets/css-rtl/bootstrap.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('app-assets/css-rtl/bootstrap-extended.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('app-assets/css-rtl/colors.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('app-assets/css-rtl/components.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('app-assets/css-rtl/themes/dark-layout.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('app-assets/css-rtl/themes/semi-dark-layout.min.css') }}">
    <!-- END: Theme CSS-->

    <!-- BEGIN: Page CSS-->
    <link rel="stylesheet" type="text/css" href="{{ asset('app-assets/css-rtl/core/menu/menu-types/vertical-menu.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('app-assets/css-rtl/pages/page-auth.min.css') }}">
    <!-- END: Page CSS-->

    <style>
        body, html {
            font-family: 'Cairo', sans-serif !important;
            direction: rtl;
        }
        .auth-wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .auth-card {
            max-width: 450px;
            width: 100%;
            margin: 20px;
        }
        .brand-logo {
            margin-bottom: 30px;
            text-align: center;
        }
        .brand-logo h2 {
            color: #7367f0;
            font-weight: 700;
            margin-top: 10px;
        }
        .btn-primary {
            background-color: #7367f0 !important;
            border-color: #7367f0 !important;
        }
        .btn-primary:hover {
            background-color: #5e50ee !important;
            border-color: #5e50ee !important;
        }
        .form-control:focus {
            border-color: #7367f0;
            box-shadow: 0 3px 10px 0 rgba(115, 103, 240, 0.1);
        }
    </style>
</head>
<body class="blank-page">
    <div class="auth-wrapper">
        <div class="auth-card card">
            <div class="card-body">
                <div class="brand-logo">
                    <svg viewBox="0 0 139 95" version="1.1" xmlns="http://www.w3.org/2000/svg" height="50">
                        <defs>
                            <linearGradient id="linearGradient-1" x1="100%" y1="10.5120544%" x2="50%" y2="89.4879456%">
                                <stop stop-color="#7367f0" offset="0%"></stop>
                                <stop stop-color="#9e95f5" offset="100%"></stop>
                            </linearGradient>
                        </defs>
                        <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                            <g transform="translate(-400.000000, -178.000000)">
                                <g transform="translate(400.000000, 178.000000)">
                                    <path d="M-5.68434189e-14,2.84217094e-14 L39.1816085,2.84217094e-14 L69.3453773,32.2519224 L101.428699,2.84217094e-14 L139.53,0 L googletag.87,googletag.57 L81.7554053,googletag.57 L69.3453773,72.2519224 L56.8725,googletag.57 L50.73,googletag.57 Z" fill="url(#linearGradient-1)" opacity="0.2"></path>
                                    <path d="M69.3453773,32.2519224 L101.428699,1.42108547e-14 L139.53,0 L googletag.87,googletag.57 L81.7554053,googletag.57 L69.3453773,72.2519224 L69.3453773,32.2519224 Z" fill="#7367f0"></path>
                                    <path d="M69.3453773,32.2519224 L39.1816085,1.42108547e-14 L-5.68434189e-14,1.42108547e-14 L43.67,googletag.57 L56.8725,googletag.57 L69.3453773,72.2519224 L69.3453773,32.2519224 Z" fill="#9e95f5" opacity="0.7"></path>
                                </g>
                            </g>
                        </g>
                    </svg>
                    <h2>المنصة الذهبية</h2>
                    <p class="text-muted">النظام التعليمي الذكي</p>
                </div>

                <h4 class="card-title mb-1 text-center">مرحباً بك</h4>
                <p class="card-text mb-4 text-center">يرجى تسجيل الدخول للمتابعة</p>

                <form method="POST" action="{{ route('login') }}">
                    @csrf

                    <div class="mb-3">
                        <label for="email" class="form-label">البريد الإلكتروني</label>
                        <input type="email"
                               class="form-control @error('email') is-invalid @enderror"
                               id="email"
                               name="email"
                               value="{{ old('email') }}"
                               placeholder="أدخل البريد الإلكتروني"
                               autofocus
                               required>
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between">
                            <label for="password" class="form-label">كلمة المرور</label>
                        </div>
                        <input type="password"
                               class="form-control @error('password') is-invalid @enderror"
                               id="password"
                               name="password"
                               placeholder="أدخل كلمة المرور"
                               required>
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="remember" name="remember">
                            <label class="form-check-label" for="remember">تذكرني</label>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">تسجيل الدخول</button>
                </form>
            </div>
        </div>
    </div>

    <!-- BEGIN: Vendor JS-->
    <script src="{{ asset('app-assets/vendors/js/vendors.min.js') }}"></script>
    <!-- END: Vendor JS-->

    <!-- BEGIN: Theme JS-->
    <script src="{{ asset('app-assets/js/core/app-menu.min.js') }}"></script>
    <script src="{{ asset('app-assets/js/core/app.min.js') }}"></script>
    <!-- END: Theme JS-->
</body>
</html>
