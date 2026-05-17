<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <title>{{ __('user_cards.page_title') }} - {{ $platform }}</title>
    <style>
        @page { margin: 10mm; }
        body { font-family: 'DejaVu Sans', sans-serif; margin: 0; color:#0f172a; font-size: 11px; }

        .pdf-header {
            text-align:center;
            border-bottom: 2px solid #c9a04b;
            padding: 4mm 0 3mm; margin-bottom: 6mm;
        }
        .pdf-header .platform { font-size: 16px; font-weight: bold; color:#0f172a; }
        .pdf-header .platform .accent { color:#c9a04b; }
        .pdf-header .sub { color:#475569; font-size:10px; margin-top:2px; }

        table.grid { width:100%; border-collapse: separate; border-spacing: 4mm 4mm; }
        table.grid td.cell {
            width: 50%;
            border: 1.5px solid #c9a04b;
            border-radius: 4mm;
            padding: 5mm;
            background: #fff;
            vertical-align: top;
            height: 50mm;
        }
        table.grid td.empty { border: none; background: transparent; }

        .card-top {
            border-bottom: 1px dashed #c9a04b;
            padding-bottom: 2mm;
            margin-bottom: 3mm;
        }
        .card-platform { font-size: 9px; color:#a37a23; font-weight:bold; letter-spacing:.5px; }
        .card-name { font-size: 13px; font-weight: bold; color:#0f172a; margin-top:1mm; }
        .card-role {
            display: inline-block;
            padding: 0.6mm 2mm;
            border-radius: 2mm;
            font-size: 8.5px;
            font-weight: bold;
            margin-top: 1.5mm;
        }
        .role-student { background:#eff6ff; color:#1d4ed8; }
        .role-parent  { background:#fef3c7; color:#92400e; }
        .role-teacher { background:#ecfeff; color:#0e7490; }
        .role-admin   { background:#f3e8ff; color:#7e22ce; }

        .meta-line { font-size: 10px; color:#475569; margin-top:1.5mm; }
        .meta-line strong { color:#0f172a; }

        .creds { margin-top: 3mm; }
        .creds .row { margin: 1.5mm 0; font-size: 10.5px; }
        .creds .label { color:#64748b; display:inline-block; min-width: 26mm; }
        .creds .value {
            color:#0f172a;
            font-weight: bold;
            font-family: 'DejaVu Sans Mono', 'DejaVu Sans', monospace;
            background:#f8fafc;
            border: 1px dashed #cbd5e1;
            padding: 0.5mm 2mm;
            border-radius: 1.5mm;
        }
        .url-line { font-size: 9px; color:#475569; margin-top: 2.5mm; border-top: 1px dotted #cbd5e1; padding-top: 1.5mm; }
        .url-line .v { color:#0f172a; font-weight:bold; }

        .no-pwd { color:#991b1b; font-style: italic; }
    </style>
</head>
<body>
    <div class="pdf-header">
        <div class="platform">
            {{ $platform }}
            <span class="accent">•</span>
            <span style="font-weight:normal;color:#64748b;">{{ __('user_cards.page_title') }}</span>
        </div>
        <div class="sub">{{ now()->format('Y-m-d H:i') }}</div>
    </div>

    <table class="grid">
        @foreach($cards->chunk(2) as $row)
            <tr>
                @foreach($row as $c)
                    <td class="cell">
                        <div class="card-top">
                            <div class="card-platform">{{ $platform }}</div>
                            <div class="card-name">{{ $c['name'] }}</div>
                            <span class="card-role role-{{ $c['kind'] }}">{{ __('user_cards.pdf_role_'.$c['kind']) }}</span>
                            @if($c['kind'] === 'student' && ($c['grade'] || $c['class']))
                                <div class="meta-line">
                                    @if($c['grade'])<strong>{{ __('user_cards.pdf_grade') }}:</strong> {{ $c['grade'] }}@endif
                                    @if($c['class']) — <strong>{{ __('user_cards.pdf_class') }}:</strong> {{ $c['class'] }}@endif
                                </div>
                            @endif
                            @if($c['kind'] === 'admin' && $c['job_title'])
                                <div class="meta-line"><strong>{{ __('user_cards.pdf_job') }}:</strong> {{ $c['job_title'] }}</div>
                            @endif
                            @if(!empty($c['school']))
                                <div class="meta-line"><strong>{{ __('user_cards.pdf_school') }}:</strong> {{ $c['school'] }}</div>
                            @endif
                        </div>

                        <div class="creds">
                            <div class="row">
                                <span class="label">{{ __('user_cards.pdf_username') }}:</span>
                                <span class="value">{{ $c['username'] ?? '—' }}</span>
                            </div>
                            <div class="row">
                                <span class="label">{{ __('user_cards.pdf_password') }}:</span>
                                @if($c['password'])
                                    <span class="value">{{ $c['password'] }}</span>
                                @else
                                    <span class="no-pwd">{{ __('user_cards.pdf_no_password') }}</span>
                                @endif
                            </div>
                        </div>

                        <div class="url-line">
                            {{ __('user_cards.pdf_login_at') }}: <span class="v">{{ $url }}</span>
                        </div>
                    </td>
                @endforeach
                @if($row->count() < 2)
                    <td class="cell empty"></td>
                @endif
            </tr>
        @endforeach
    </table>
</body>
</html>
