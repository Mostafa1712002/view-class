@php
    $isRtl = app()->getLocale() === 'ar';
    $dir   = $isRtl ? 'rtl' : 'ltr';
    $align = $isRtl ? 'right' : 'left';
@endphp
<!doctype html>
<html lang="{{ $isRtl ? 'ar' : 'en' }}" dir="{{ $dir }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('user_cards.page_title') }} - {{ $platform }}</title>
    <style>
        @page { margin: 12mm 10mm; }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            margin: 0;
            color: #0f172a;
            font-size: 11px;
            direction: {{ $dir }};
            text-align: {{ $align }};
        }

        /* ── Page header ─────────────────────────────────────────── */
        .pdf-header {
            text-align: center;
            margin-bottom: 7mm;
            padding-bottom: 4mm;
            border-bottom: 2.5px solid #c9a04b;
        }
        .pdf-header .platform-title {
            font-size: 18px;
            font-weight: bold;
            color: #0f172a;
        }
        .pdf-header .accent { color: #c9a04b; }
        .pdf-header .datestamp {
            color: #64748b;
            font-size: 10px;
            margin-top: 2mm;
        }

        /* ── Card grid ───────────────────────────────────────────── */
        table.grid {
            width: 100%;
            border-collapse: separate;
            border-spacing: 4mm 5mm;
        }
        table.grid tr { page-break-inside: avoid; }

        table.grid td.card-cell {
            width: 50%;
            border: 1.5px solid #c9a04b;
            border-radius: 3mm;
            padding: 0;
            background: #ffffff;
            vertical-align: top;
        }
        table.grid td.empty-cell { border: none; background: transparent; width: 50%; }

        /* ── Card header band (gold tint) ────────────────────────── */
        .band {
            background: #fdf0cc;
            border-bottom: 1.5px solid #c9a04b;
            padding: 3.5mm 4.5mm 3mm;
        }
        .band-eyebrow {
            font-size: 9px;
            color: #8a6200;
            font-weight: bold;
            margin-bottom: 1.5mm;
        }
        .band-name {
            font-size: 14px;
            font-weight: bold;
            color: #0f172a;
            line-height: 1.3;
            margin-bottom: 2.5mm;
        }

        /* Role chip — fixed width table for reliable mPDF pill rendering */
        table.chip-wrap {
            border-collapse: collapse;
            width: 26mm;
            text-align: center;
        }
        table.chip-wrap td {
            font-size: 10px;
            font-weight: bold;
            padding: 1.2mm 4mm;
            border-radius: 2mm;
            white-space: nowrap;
        }
        .chip-student td { background: #bfdbfe; border: 1px solid #3b82f6; color: #1e3a8a; }
        .chip-parent  td { background: #fde68a; border: 1px solid #ca8a04; color: #78350f; }
        .chip-teacher td { background: #a7f3d0; border: 1px solid #10b981; color: #064e3b; }
        .chip-admin   td { background: #ddd6fe; border: 1px solid #7c3aed; color: #4c1d95; }

        /* ── Card body ───────────────────────────────────────────── */
        .body { padding: 3mm 4.5mm 2mm; }

        /* Meta table — two-column: label | value */
        table.meta {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 1mm;
        }
        table.meta td {
            font-size: 10px;
            padding: 0.8mm 0;
            vertical-align: top;
        }
        table.meta td.lbl {
            color: #94a3b8;
            width: 20mm;
            white-space: nowrap;
        }
        table.meta td.val {
            color: #1e293b;
            font-weight: bold;
        }

        /* ── Credentials block ───────────────────────────────────── */
        .creds-title {
            font-size: 10px;
            font-weight: bold;
            color: #a37a23;
            border-bottom: 1px solid #e8d5a3;
            padding-bottom: 1mm;
            margin-top: 3mm;
            margin-bottom: 2.5mm;
        }
        table.creds {
            width: 100%;
            border-collapse: collapse;
        }
        table.creds td {
            padding: 1mm 0;
            vertical-align: middle;
        }
        table.creds td.cred-lbl {
            font-size: 10px;
            color: #64748b;
            width: 22mm;
            white-space: nowrap;
        }
        .cred-box {
            display: inline-block;
            font-family: 'DejaVu Sans Mono', 'DejaVu Sans', monospace;
            font-size: 11px;
            font-weight: bold;
            color: #0f172a;
            background: #f1f5f9;
            border: 1px solid #94a3b8;
            border-radius: 1.5mm;
            padding: 0.8mm 2.5mm;
        }
        .no-pwd {
            font-size: 10px;
            color: #b91c1c;
            font-style: italic;
        }

        /* ── URL footer ──────────────────────────────────────────── */
        .url-strip {
            border-top: 1px solid #e2e8f0;
            background: #f8fafc;
            padding: 2mm 4.5mm;
            margin-top: 3mm;
        }
        .url-strip .ul {
            font-size: 10px;
            color: #64748b;
        }
        .url-strip .uv {
            font-size: 10px;
            font-weight: bold;
            color: #0f172a;
        }
    </style>
</head>
<body>
    {{-- Page header --}}
    <div class="pdf-header">
        <div class="platform-title">
            {{ $platform }}
            <span class="accent"> ◆ </span>
            <span style="font-weight:normal;color:#475569;">{{ __('user_cards.page_title') }}</span>
        </div>
        <div class="datestamp">{{ now()->format('Y-m-d H:i') }}</div>
    </div>

    {{-- 2-column card grid --}}
    <table class="grid">
        @foreach($cards->chunk(2) as $row)
            <tr>
                @foreach($row as $c)
                    <td class="card-cell">
                        {{-- Gold band: eyebrow + name + role chip --}}
                        <div class="band">
                            <div class="band-eyebrow">{{ $platform }}</div>
                            <div class="band-name">{{ $c['name'] }}</div>
                            <table class="chip-wrap chip-{{ $c['kind'] }}"><tr><td>{{ __('user_cards.pdf_role_'.$c['kind']) }}</td></tr></table>
                        </div>

                        {{-- Body: meta + credentials --}}
                        <div class="body">
                            {{-- Meta table (only rows that have data) --}}
                            @php
                                $hasMeta = !empty($c['school'])
                                    || ($c['kind'] === 'student' && ($c['grade'] || $c['class']))
                                    || ($c['kind'] === 'admin' && !empty($c['job_title']));
                            @endphp
                            @if($hasMeta)
                                <table class="meta">
                                    @if(!empty($c['school']))
                                        <tr>
                                            <td class="lbl">{{ __('user_cards.pdf_school') }}</td>
                                            <td class="val">{{ $c['school'] }}</td>
                                        </tr>
                                    @endif
                                    @if($c['kind'] === 'student' && $c['grade'])
                                        <tr>
                                            <td class="lbl">{{ __('user_cards.pdf_grade') }}</td>
                                            <td class="val">{{ $c['grade'] }}@if($c['class']) &nbsp;/&nbsp; {{ __('user_cards.pdf_class') }}: {{ $c['class'] }}@endif</td>
                                        </tr>
                                    @endif
                                    @if($c['kind'] === 'admin' && !empty($c['job_title']))
                                        <tr>
                                            <td class="lbl">{{ __('user_cards.pdf_job') }}</td>
                                            <td class="val">{{ $c['job_title'] }}</td>
                                        </tr>
                                    @endif
                                </table>
                            @endif

                            {{-- Credentials --}}
                            <div class="creds-title">{{ __('user_cards.pdf_credentials') }}</div>
                            <table class="creds">
                                <tr>
                                    <td class="cred-lbl">{{ __('user_cards.pdf_username') }}</td>
                                    <td><span class="cred-box">{{ $c['username'] ?? '—' }}</span></td>
                                </tr>
                                <tr>
                                    <td class="cred-lbl">{{ __('user_cards.pdf_password') }}</td>
                                    <td>
                                        @if($c['password'])
                                            <span class="cred-box">{{ $c['password'] }}</span>
                                        @else
                                            <span class="no-pwd">{{ __('user_cards.pdf_no_password') }}</span>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </div>

                        {{-- Login URL strip --}}
                        <div class="url-strip">
                            <span class="ul">{{ __('user_cards.pdf_login_at') }}:</span>
                            <span class="uv"> {{ $url }}</span>
                        </div>
                    </td>
                @endforeach
                @if($row->count() < 2)
                    <td class="empty-cell"></td>
                @endif
            </tr>
        @endforeach
    </table>
</body>
</html>
