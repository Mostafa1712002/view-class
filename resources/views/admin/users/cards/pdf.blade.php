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
        @page { margin: 14mm 12mm; }

        /*
         * Do NOT set font-family on body to DejaVu Sans.
         * mPDF's autoLangToFont maps Arabic (und-Arab) → xbriyaz.
         * A global DejaVu override defeats that mapping, causing Arabic
         * to be rendered in DejaVu's sparse Arabic glyphs.
         * Instead: Arabic UI elements use font-family explicitly set to xbriyaz;
         * Latin-only elements (credentials) use dejavusansmono / dejavusans.
         */
        body {
            margin: 0;
            color: #0f172a;
            font-size: 11px;
            direction: {{ $dir }};
            text-align: {{ $align }};
        }

        /* ── Arabic text elements — proper Arabic font ─────────────── */
        .ar-text {
            font-family: xbriyaz, 'XB Riyaz', sans-serif;
        }

        /* ── Page header ─────────────────────────────────────────────── */
        .pdf-header {
            text-align: center;
            margin-bottom: 8mm;
            padding-bottom: 5mm;
            border-bottom: 2px solid #c9a04b;
        }
        .pdf-header .platform-title {
            font-family: xbriyaz, 'XB Riyaz', dejavusans, sans-serif;
            font-size: 20px;
            font-weight: bold;
            color: #0f172a;
            letter-spacing: 0;
        }
        .pdf-header .accent { color: #c9a04b; font-family: dejavusans, sans-serif; }
        .pdf-header .sub-title {
            font-family: xbriyaz, 'XB Riyaz', dejavusans, sans-serif;
            font-size: 12px;
            color: #475569;
            font-weight: normal;
        }
        .pdf-header .datestamp {
            font-family: dejavusans, sans-serif;
            color: #94a3b8;
            font-size: 9px;
            margin-top: 3mm;
        }

        /* ── Card grid ───────────────────────────────────────────────── */
        table.grid {
            width: 100%;
            border-collapse: separate;
            border-spacing: 5mm 6mm;
        }
        table.grid tr { page-break-inside: avoid; }

        table.grid td.card-cell {
            width: 50%;
            border: 1.5px solid #c9a04b;
            border-radius: 4mm;
            padding: 0;
            background: #ffffff;
            vertical-align: top;
            /* subtle drop shadow via border trick — mPDF doesn't support box-shadow */
        }
        table.grid td.empty-cell {
            border: none;
            background: transparent;
            width: 50%;
        }

        /* ── Card header band (rich gold gradient via solid bg) ───────── */
        .band {
            background: #fef3c7;
            border-bottom: 2px solid #c9a04b;
            padding: 4mm 5mm 3.5mm;
            border-radius: 3.5mm 3.5mm 0 0;
        }
        .band-eyebrow {
            font-family: xbriyaz, 'XB Riyaz', dejavusans, sans-serif;
            font-size: 8.5px;
            color: #92400e;
            font-weight: bold;
            margin-bottom: 2mm;
        }
        .band-name {
            font-family: xbriyaz, 'XB Riyaz', dejavusans, sans-serif;
            font-size: 15px;
            font-weight: bold;
            color: #1e293b;
            line-height: 1.35;
            margin-bottom: 3mm;
        }

        /* Role chip — table-based for reliable mPDF pill rendering */
        table.chip-wrap {
            border-collapse: collapse;
            text-align: center;
        }
        table.chip-wrap td {
            font-family: xbriyaz, 'XB Riyaz', dejavusans, sans-serif;
            font-size: 9.5px;
            font-weight: bold;
            padding: 1.2mm 5mm;
            border-radius: 10mm;
            white-space: nowrap;
        }
        .chip-student td { background: #dbeafe; border: 1px solid #60a5fa; color: #1e3a8a; }
        .chip-parent  td { background: #fef9c3; border: 1px solid #facc15; color: #713f12; }
        .chip-teacher td { background: #d1fae5; border: 1px solid #34d399; color: #064e3b; }
        .chip-admin   td { background: #ede9fe; border: 1px solid #a78bfa; color: #3b0764; }

        /* ── Card body ───────────────────────────────────────────────── */
        .body { padding: 3.5mm 5mm 2.5mm; }

        /* Meta table — two-column: label | value */
        table.meta {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 1.5mm;
        }
        table.meta td {
            font-family: xbriyaz, 'XB Riyaz', dejavusans, sans-serif;
            font-size: 10px;
            padding: 1mm 0;
            vertical-align: top;
        }
        table.meta td.lbl {
            color: #94a3b8;
            width: 22mm;
            white-space: nowrap;
        }
        table.meta td.val {
            color: #334155;
            font-weight: bold;
        }

        /* ── Credentials block ───────────────────────────────────────── */
        .creds-header {
            background: #fef3c7;
            border-top: 1px solid #fcd34d;
            border-bottom: 1px solid #fcd34d;
            padding: 2mm 5mm;
            margin-top: 3mm;
            margin-bottom: 0;
        }
        .creds-title {
            font-family: xbriyaz, 'XB Riyaz', dejavusans, sans-serif;
            font-size: 9.5px;
            font-weight: bold;
            color: #92400e;
        }

        .creds-body {
            padding: 3mm 5mm 3mm;
        }

        table.creds {
            width: 100%;
            border-collapse: collapse;
        }
        table.creds td {
            padding: 1.2mm 0;
            vertical-align: middle;
        }
        table.creds td.cred-lbl {
            font-family: xbriyaz, 'XB Riyaz', dejavusans, sans-serif;
            font-size: 9.5px;
            color: #64748b;
            width: 24mm;
            white-space: nowrap;
        }

        /* Credential values: always LTR, monospace, very prominent */
        .cred-box {
            display: inline-block;
            font-family: dejavusansmono, 'DejaVu Sans Mono', dejavusans, monospace;
            font-size: 12px;
            font-weight: bold;
            color: #0f172a;
            background: #f8fafc;
            border: 1.5px solid #c9a04b;
            border-radius: 2mm;
            padding: 1.2mm 3mm;
            direction: ltr;
            unicode-bidi: plaintext;
            letter-spacing: .03em;
        }
        .no-pwd {
            font-family: xbriyaz, 'XB Riyaz', dejavusans, sans-serif;
            font-size: 10px;
            color: #b91c1c;
            font-style: italic;
        }

        /* ── URL footer ──────────────────────────────────────────────── */
        .url-strip {
            border-top: 1.5px solid #e8d5a3;
            background: #fffbeb;
            padding: 2.5mm 5mm;
            border-radius: 0 0 3.5mm 3.5mm;
        }
        .url-strip table {
            width: 100%;
            border-collapse: collapse;
        }
        .url-strip td { padding: 0; vertical-align: middle; }
        .url-strip .ul {
            font-family: xbriyaz, 'XB Riyaz', dejavusans, sans-serif;
            font-size: 9px;
            color: #78350f;
            font-weight: bold;
            white-space: nowrap;
            padding-{{ $isRtl ? 'left' : 'right' }}: 2mm;
        }
        .url-strip .uv {
            font-family: dejavusans, sans-serif;
            font-size: 9.5px;
            font-weight: bold;
            color: #1e293b;
            direction: ltr;
            unicode-bidi: plaintext;
        }
    </style>
</head>
<body>
    {{-- Page header --}}
    <div class="pdf-header">
        <div class="platform-title ar-text">
            {{ $platform }}
            <span class="accent"> ◆ </span>
            <span class="sub-title">{{ __('user_cards.page_title') }}</span>
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

                        {{-- Card body --}}
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
                        </div>

                        {{-- Credentials block — visually separated section --}}
                        <div class="creds-header">
                            <span class="creds-title">{{ __('user_cards.pdf_credentials') }}</span>
                        </div>
                        <div class="creds-body">
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
                            <table><tr>
                                <td class="ul">{{ __('user_cards.pdf_login_at') }}:</td>
                                <td class="uv">{{ $url }}</td>
                            </tr></table>
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
