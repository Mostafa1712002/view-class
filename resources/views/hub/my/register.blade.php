<!DOCTYPE html>
@php($dir = app()->getLocale() === 'ar' ? 'rtl' : 'ltr')
<html lang="{{ app()->getLocale() }}" dir="{{ $dir }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@lang('hub_my.register_title') — @lang('auth.app_name')</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    @if($dir === 'rtl')
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css">
    @endif
    <style>
        :root { --gold-300: #af6900; --gold-500: #744500; --ink-900: #0f172a; --ink-500: #64748b; --line: #e5e7eb; }
        body { font-family: 'Cairo', 'Segoe UI', sans-serif; background: #f8fafc; color: var(--ink-900); }
        .auth-card { max-width: 520px; margin: 32px auto; border: 1px solid var(--line); border-radius: 16px; background: #fff; box-shadow: 0 12px 32px rgba(15,23,42,.06); }
        .btn-primary { background: linear-gradient(135deg, var(--gold-300), var(--gold-500)) !important; border-color: var(--gold-500) !important; font-weight: 600; border-radius: 10px; }
        .form-control:focus, .form-select:focus { border-color: var(--gold-300); box-shadow: 0 0 0 .18rem rgba(207,160,70,.16); }
    </style>
</head>
<body>
<main class="auth-card p-4">
    <h1 class="h4 text-center">@lang('hub_my.register_title')</h1>
    <p class="text-center" style="color: var(--ink-500)">@lang('hub_my.register_intro')</p>

    @if($errors->has('collision'))
        <div class="alert alert-warning" role="alert">{{ $errors->first('collision') }} <a href="{{ route('login') }}">@lang('hub_my.sign_in')</a></div>
    @endif

    <form method="POST" action="{{ route('hub.register.store') }}" novalidate>
        @csrf
        @foreach([['name', 'text', 'name', 'name'], ['national_id', 'text', 'national_id', 'off'], ['phone', 'tel', 'phone', 'tel'], ['email', 'email', 'email', 'email']] as [$f, $type, $label, $ac])
            <div class="mb-3">
                <label for="{{ $f }}" class="form-label">@lang('hub_my.'.$label)</label>
                <input type="{{ $type }}" id="{{ $f }}" name="{{ $f }}" value="{{ old($f) }}" autocomplete="{{ $ac }}" required
                       @if(in_array($f, ['national_id', 'phone', 'email'])) dir="ltr" @endif
                       @if($f === 'national_id') inputmode="numeric" maxlength="10" @endif
                       class="form-control @error($f) is-invalid @enderror">
                @error($f)<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        @endforeach
        <div class="mb-3">
            <label for="gender" class="form-label">@lang('hub_my.gender')</label>
            <select id="gender" name="gender" required class="form-select @error('gender') is-invalid @enderror">
                <option value="male" @selected(old('gender') === 'male')>@lang('hub_my.male')</option>
                <option value="female" @selected(old('gender') === 'female')>@lang('hub_my.female')</option>
            </select>
            @error('gender')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="mb-3">
            <label for="password" class="form-label">@lang('hub_my.password')</label>
            <input type="password" id="password" name="password" autocomplete="new-password" required minlength="8" class="form-control @error('password') is-invalid @enderror">
            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="mb-3">
            <label for="password_confirmation" class="form-label">@lang('hub_my.password_confirmation')</label>
            <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" required class="form-control">
        </div>
        <button type="submit" class="btn btn-primary w-100">@lang('hub_my.register_btn')</button>
    </form>
    <p class="text-center mt-3 mb-0"><a href="{{ route('login') }}">@lang('hub_my.have_account')</a></p>
</main>
</body>
</html>
