<!DOCTYPE html>
@php($dir = app()->getLocale() === 'ar' ? 'rtl' : 'ltr')
@php($otherLocale = app()->getLocale() === 'ar' ? 'en' : 'ar')
<html lang="{{ app()->getLocale() }}" dir="{{ $dir }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@lang('auth.login_title') — @lang('auth.app_name')</title>

    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    @if($dir === 'ltr')
        <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600;700;800&display=swap" rel="stylesheet">
    @endif
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    @if($dir === 'rtl')
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css">
    @endif

    <style>
        :root {
            --gold-100: #f6d27a; --gold-200: #e3b85c; --gold-300: #cfa046;
            --gold-400: #b7842e; --gold-500: #9c6b1f;
            --black-100: #0b0b0b; --black-200: #121212; --black-300: #1a1a1a;
            --brand-green: #1f6f4a;
        }
        body, html {
            font-family: 'Cairo', 'Segoe UI', sans-serif !important;
            direction: {{ $dir }};
        }
        @if($dir === 'ltr')
        h1, h2, h3, h4, .brand-logo h2 {
            font-family: 'Playfair Display', Georgia, serif !important;
            letter-spacing: .3px;
        }
        @endif
        .auth-wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background:
                radial-gradient(ellipse at 20% 80%, rgba(207,160,70,.18), transparent 60%),
                radial-gradient(ellipse at 80% 20%, rgba(31,111,74,.14), transparent 60%),
                linear-gradient(135deg, var(--black-100) 0%, var(--black-300) 100%);
            padding: 20px;
        }
        .auth-card {
            max-width: 460px;
            width: 100%;
            border-radius: 16px;
            box-shadow: 0 24px 60px rgba(0,0,0,0.55);
            background: rgba(20,20,28,0.55);
            backdrop-filter: blur(18px);
            -webkit-backdrop-filter: blur(18px);
            border: 1px solid rgba(207,160,70,0.22);
            color: var(--white-200);
        }
        .auth-card .card-body { color: var(--white-200); }
        .auth-card h4 { color: var(--white-100); }
        .auth-card .form-label { color: var(--white-200); font-weight: 500; }
        .auth-card p.text-muted, .auth-card small.text-muted { color: #b9b9c2 !important; }
        .auth-card .form-control {
            background: rgba(255,255,255,0.92);
            border: 1px solid rgba(207,160,70,0.25);
        }
        .auth-card .form-check-label { color: var(--white-200); }
        .brand-logo { margin-bottom: 18px; text-align: center; }
        .brand-logo img { max-height: 90px; width: auto; filter: drop-shadow(0 6px 18px rgba(207,160,70,.25)); }
        .brand-logo h2 { color: var(--gold-200); font-weight: 700; margin-top: 10px; margin-bottom: 2px; }
        .brand-logo .version { color: #b9b9c2; font-size: 0.85rem; }
        .btn-primary {
            background: linear-gradient(135deg, var(--gold-200), var(--gold-500)) !important;
            border-color: var(--gold-400) !important;
            color: #fff !important;
            box-shadow: 0 4px 12px rgba(207,160,70,.25);
            transition: .25s ease;
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, var(--gold-300), var(--gold-500)) !important;
            border-color: var(--gold-500) !important;
            transform: translateY(-1px);
            box-shadow: 0 8px 22px rgba(207,160,70,.35);
        }
        .form-control:focus {
            border-color: var(--gold-300);
            box-shadow: 0 0 0 .15rem rgba(207,160,70,.18);
        }
        .lang-switch {
            position: absolute;
            top: 16px;
            {{ $dir === 'rtl' ? 'left' : 'right' }}: 16px;
            color: #fff;
            font-size: 0.85rem;
            background: rgba(255,255,255,0.12);
            padding: 6px 12px;
            border-radius: 20px;
            text-decoration: none;
        }
        .lang-switch:hover { background: rgba(255,255,255,0.22); color: #fff; }
        .recaptcha-mock {
            border: 1px solid #d5d5dc;
            border-radius: 6px;
            padding: 12px 14px;
            display: flex;
            align-items: center;
            gap: 10px;
            background: #f9f9fa;
            margin-bottom: 12px;
        }
        .recaptcha-mock input { margin: 0; }
    </style>
</head>
<body>
    <div class="auth-wrapper">
        <a class="lang-switch" href="{{ route('locale.switch', $otherLocale) }}">
            {{ $otherLocale === 'ar' ? 'العربية' : 'English' }}
        </a>

        <div class="auth-card card">
            <div class="card-body p-4">
                <div class="brand-logo">
                    <img src="{{ asset('img/brand/al-awwal-logo.png') }}" alt="@lang('auth.app_name')">
                    <h2 class="mt-2">@lang('auth.app_name')</h2>
                    <div class="version text-muted">@lang('auth.tagline')</div>
                </div>

                <h4 class="mb-1 text-center">@lang('auth.welcome')</h4>
                <p class="text-muted mb-4 text-center">@lang('auth.please_login')</p>

                <form method="POST" action="{{ route('login') }}">
                    @csrf

                    <div class="mb-3">
                        <label for="email" class="form-label">@lang('auth.username_or_email')</label>
                        <input type="text"
                               class="form-control @error('email') is-invalid @enderror"
                               id="email"
                               name="email"
                               value="{{ old('email') }}"
                               placeholder="admin"
                               autocomplete="username"
                               autofocus
                               required>
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">@lang('auth.password')</label>
                        <input type="password"
                               class="form-control @error('password') is-invalid @enderror"
                               id="password"
                               name="password"
                               autocomplete="current-password"
                               required>
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- reCAPTCHA placeholder per spec (UI only, no backend verification yet) --}}
                    <div class="recaptcha-mock">
                        <input type="checkbox" id="recaptcha" disabled checked aria-disabled="true">
                        <label for="recaptcha" class="m-0">@lang('auth.not_a_robot')</label>
                    </div>

                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="remember" name="remember">
                        <label class="form-check-label" for="remember">@lang('auth.remember_me')</label>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">@lang('auth.sign_in')</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
