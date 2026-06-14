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
            --ink-900: #0f172a; --ink-700: #334155; --ink-500: #64748b;
            --line: #e5e7eb; --surface: #ffffff; --shell: #f8fafc;
        }
        body, html {
            font-family: 'Cairo', 'Segoe UI', sans-serif !important;
            direction: {{ $dir }};
            background: var(--shell);
            color: var(--ink-900);
        }
        @if($dir === 'ltr')
        h1, h2, h3, h4, .brand-logo h2 {
            font-family: 'Playfair Display', Georgia, serif !important;
            letter-spacing: .2px;
        }
        @endif
        .auth-wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--shell);
            padding: 20px;
            position: relative;
        }
        /* Soft, performant accent — single radial, no blur */
        .auth-wrapper::before {
            content: "";
            position: absolute; inset: 0;
            background:
                radial-gradient(ellipse 60% 50% at 50% 0%, rgba(207,160,70,.07), transparent 70%);
            pointer-events: none;
        }
        .auth-card {
            max-width: 440px;
            width: 100%;
            border-radius: 16px;
            background: var(--surface);
            border: 1px solid var(--line);
            box-shadow:
                0 1px 2px rgba(15,23,42,.04),
                0 12px 32px rgba(15,23,42,.06);
            color: var(--ink-900);
            position: relative;
            animation: authFadeIn .35s cubic-bezier(.4,0,.2,1) both;
        }
        .auth-card .card-body { color: var(--ink-900); }
        .auth-card h4 { color: var(--ink-900); font-weight: 700; }
        .auth-card .form-label { color: var(--ink-700); font-weight: 500; }
        .auth-card p.text-muted,
        .auth-card small.text-muted { color: var(--ink-500) !important; }
        .auth-card .form-control {
            background: #fff;
            border: 1px solid var(--line);
            color: var(--ink-900);
            transition: border-color .2s, box-shadow .2s;
        }
        .auth-card .form-check-label { color: var(--ink-700); }
        .brand-logo { margin-bottom: 18px; text-align: center; }
        .brand-logo img {
            max-height: 84px; width: auto;
            filter: drop-shadow(0 4px 12px rgba(15,23,42,.08));
        }
        .brand-logo h2 {
            color: var(--ink-900);
            font-weight: 700;
            margin-top: 10px;
            margin-bottom: 2px;
        }
        .brand-logo h2::after {
            content: "";
            display: block;
            width: 40px;
            height: 3px;
            margin: 8px auto 0;
            border-radius: 3px;
            background: linear-gradient(90deg, var(--gold-200), var(--gold-400));
        }
        .brand-logo .version { color: var(--ink-500); font-size: 0.85rem; }
        .btn-primary {
            background: linear-gradient(135deg, var(--gold-300), var(--gold-500)) !important;
            border-color: var(--gold-400) !important;
            color: #fff !important;
            font-weight: 600;
            border-radius: 10px;
            padding: .65rem 1rem;
            box-shadow: 0 1px 2px rgba(207,160,70,.18);
            transition: transform .15s ease, box-shadow .2s ease, background .2s ease;
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, var(--gold-400), var(--gold-500)) !important;
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(207,160,70,.22);
        }
        .btn-primary:active { transform: translateY(0); }
        .form-control:focus {
            border-color: var(--gold-300);
            box-shadow: 0 0 0 .18rem rgba(207,160,70,.16);
        }
        .lang-switch {
            position: absolute;
            top: 16px;
            {{ $dir === 'rtl' ? 'left' : 'right' }}: 16px;
            color: var(--ink-700);
            font-size: 0.85rem;
            background: var(--surface);
            border: 1px solid var(--line);
            padding: 6px 12px;
            border-radius: 20px;
            text-decoration: none;
            transition: border-color .15s, color .15s, background .15s;
        }
        .lang-switch:hover {
            background: #fff;
            border-color: var(--gold-300);
            color: var(--gold-500);
        }
        .recaptcha-mock {
            border: 1px solid var(--line);
            border-radius: 8px;
            padding: 12px 14px;
            display: flex;
            align-items: center;
            gap: 10px;
            background: #fafafa;
            margin-bottom: 12px;
            color: var(--ink-700);
        }
        .recaptcha-mock input { margin: 0; }
        @keyframes authFadeIn {
            from { opacity: 0; transform: translateY(8px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        @media (prefers-reduced-motion: reduce) {
            .auth-card { animation: none; }
            .btn-primary:hover { transform: none; }
        }
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
                    <img src="{{ !empty($brand_logo) ? asset('storage/' . $brand_logo) : asset('img/brand/golden-platform-logo.png') }}" alt="@lang('auth.app_name')">
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
