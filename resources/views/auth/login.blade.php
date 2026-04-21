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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    @if($dir === 'rtl')
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css">
    @endif

    <style>
        body, html {
            font-family: 'Cairo', 'Segoe UI', sans-serif !important;
            direction: {{ $dir }};
        }
        .auth-wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
        }
        .auth-card {
            max-width: 460px;
            width: 100%;
            border-radius: 14px;
            box-shadow: 0 18px 40px rgba(0,0,0,0.18);
        }
        .brand-logo { margin-bottom: 18px; text-align: center; }
        .brand-logo h2 { color: #7367f0; font-weight: 700; margin-top: 10px; margin-bottom: 2px; }
        .brand-logo .version { color: #8c8ca1; font-size: 0.85rem; }
        .btn-primary { background-color: #7367f0 !important; border-color: #7367f0 !important; }
        .btn-primary:hover { background-color: #5e50ee !important; border-color: #5e50ee !important; }
        .form-control:focus { border-color: #7367f0; box-shadow: 0 3px 10px 0 rgba(115, 103, 240, 0.1); }
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
                    <svg viewBox="0 0 139 95" height="48" xmlns="http://www.w3.org/2000/svg">
                        <defs>
                            <linearGradient id="lg1" x1="100%" y1="10.5%" x2="50%" y2="89.5%">
                                <stop stop-color="#7367f0" offset="0%"/>
                                <stop stop-color="#9e95f5" offset="100%"/>
                            </linearGradient>
                        </defs>
                        <g fill-rule="evenodd">
                            <path d="M0,0 L39.18,0 L69.35,32.25 L101.43,0 L139.53,0 L139.53,94.57 L81.76,94.57 L69.35,72.25 L56.87,94.57 L0,94.57 Z" fill="url(#lg1)" opacity="0.2"/>
                            <path d="M69.35,32.25 L101.43,0 L139.53,0 L139.53,94.57 L81.76,94.57 L69.35,72.25 Z" fill="#7367f0"/>
                            <path d="M69.35,32.25 L39.18,0 L0,0 L43.67,94.57 L56.87,94.57 L69.35,72.25 Z" fill="#9e95f5" opacity="0.7"/>
                        </g>
                    </svg>
                    <h2>@lang('auth.app_name')</h2>
                    <div class="version">@lang('auth.version_label') 5.3</div>
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
