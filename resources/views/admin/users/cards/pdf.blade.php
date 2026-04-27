<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <title>{{ __('users.cards') }} - {{ $platform }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; margin: 12mm; font-size: 11px; }
        .grid { width: 100%; border-collapse: separate; border-spacing: 6px; }
        .card { border: 1px solid #444; border-radius: 4px; padding: 10px; width: 48%; height: 110px; }
        .name { font-weight: bold; font-size: 13px; margin-bottom: 4px; }
        .role { color: #666; font-size: 10px; margin-bottom: 6px; }
        .row { margin: 1px 0; }
        .label { display: inline-block; min-width: 70px; color: #555; }
        .value { font-weight: bold; }
        .platform { background: #f4f4f4; padding: 6px; margin-bottom: 8px; border: 1px solid #ddd; }
    </style>
</head>
<body>
    <div class="platform">
        <strong>{{ $platform }}</strong> — {{ $url }}
    </div>

    <table class="grid">
        @foreach($users->chunk(2) as $row)
            <tr>
                @foreach($row as $u)
                    <td class="card">
                        <div class="name">{{ $u['name'] }}</div>
                        <div class="role">{{ __('users.card_role_'.$u['kind']) }}
                            @if($u['kind'] === 'student' && $u['grade'])
                                — {{ $u['grade'] }}{{ $u['class'] ? ' / '.$u['class'] : '' }}
                            @endif
                            @if($u['kind'] === 'admin' && $u['job_title']) — {{ $u['job_title'] }} @endif
                        </div>
                        <div class="row"><span class="label">{{ __('users.card_username') }}:</span> <span class="value">{{ $u['username'] }}</span></div>
                        <div class="row"><span class="label">{{ __('users.card_password') }}:</span> <span class="value">{{ $u['password'] }}</span></div>
                        <div class="row"><span class="label">{{ __('users.card_url') }}:</span> {{ $url }}</div>
                    </td>
                @endforeach
                @if($row->count() < 2)<td class="card" style="border:none;"></td>@endif
            </tr>
        @endforeach
    </table>
</body>
</html>
