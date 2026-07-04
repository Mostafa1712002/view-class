@php
    use Illuminate\Support\Facades\Storage;
    $textColor = $template?->text_color ?? '#3a3320';
    $nameColor = $template?->name_color ?? '#1a3c6e';
    $bgPath = $template && $template->background_path && Storage::disk('public')->exists($template->background_path)
        ? Storage::disk('public')->path($template->background_path)
        : null;
    $lines = $template?->body['lines'] ?? [];
    $brand = config('app.name', 'المنصة الذهبية');
    $portrait = $template && $template->orientation === 'portrait';

    // Gold accent used for the frame + flourishes.
    $gold = '#c9a227';

    // Design fields (all optional; a plain certificate has none of these).
    $logoUrl      = $logo_url ?? null;
    $signatureUrl = $signature_url ?? null;
    $stampUrl     = $stamp_url ?? null;
    $signerName   = $signer_name ?? null;
    $bodyHtml     = $body_html ?? null;
    $gradeRows    = $grades ?? [];
    $isGeneral    = $certificate->type === 'general';

    $render = function ($line) use ($fields) {
        return strtr($line, [
            '{student_name}' => $fields['student_name'],
            '{school}'       => $fields['school'],
            '{grade}'        => $fields['grade'],
            '{date}'         => $fields['date'],
        ]);
    };
@endphp
<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 0; }
        body { margin: 0; padding: 0; font-family: xbriyaz, sans-serif; }

        /* Full-bleed sheet: uploaded background if present, else an elegant
           cream-to-white base so an empty template never looks bare. */
        .sheet {
            width: 100%;
            box-sizing: border-box;
            padding: 14px;
            @if($bgPath)
            background-image: url('{{ $bgPath }}');
            background-size: cover;
            background-repeat: no-repeat;
            @else
            background-color: #fbf9f2;
            @endif
        }

        /* Double gold frame. When a background image is set we keep the frame
           subtle so it doesn't fight the artwork. */
        .frame-outer {
            border: 3px solid {{ $gold }};
            padding: 5px;
            @if($bgPath) background-color: transparent; @endif
        }
        .frame-inner {
            border: 1px solid {{ $gold }};
            padding: {{ $portrait ? '46px 40px' : '40px 60px' }};
            text-align: center;
            @if(!$bgPath) background-color: #ffffff; @endif
        }

        .brand {
            font-size: 13pt;
            color: {{ $nameColor }};
            letter-spacing: 1px;
            margin-bottom: 2px;
        }
        .flourish {
            font-size: 15pt;
            color: {{ $gold }};
            margin: 4px 0 10px;
            letter-spacing: 6px;
        }
        .title {
            font-size: 27pt;
            font-weight: bold;
            color: {{ $nameColor }};
            margin: 2px 0 4px;
        }
        .title-underline {
            width: 180px;
            border-bottom: 2px solid {{ $gold }};
            margin: 0 auto 20px;
        }
        .presented {
            font-size: 12pt;
            color: {{ $textColor }};
            margin-bottom: 6px;
        }
        .student {
            font-size: 30pt;
            font-weight: bold;
            color: {{ $nameColor }};
            margin: 4px 0 6px;
        }
        .name-rule {
            width: 320px;
            border-bottom: 1px solid {{ $gold }};
            margin: 0 auto 18px;
        }
        .line {
            font-size: 14pt;
            color: {{ $textColor }};
            margin: 5px 0;
            line-height: 1.75;
        }

        /* Bottom band: seal on one side, signature line on the other, meta centered. */
        .footer { margin-top: 30px; width: 100%; }
        .footer td { vertical-align: bottom; font-size: 11pt; color: {{ $textColor }}; }
        .seal {
            width: 88px; height: 88px;
            border: 2px solid {{ $gold }};
            border-radius: 44px;
            color: {{ $nameColor }};
            text-align: center;
            margin: 0 auto;
        }
        .seal .seal-in {
            border: 1px solid {{ $gold }};
            border-radius: 40px;
            margin: 5px;
            padding-top: 22px;
            height: 66px;
            font-size: 10pt;
            font-weight: bold;
        }
        .sig-line { border-top: 1px solid {{ $textColor }}; width: 150px; margin: 0 auto 4px; }
        .meta { font-size: 11pt; color: {{ $textColor }}; }

        .logo { margin-bottom: 8px; }
        .logo img { max-height: 70px; }
        .signer { font-size: 11pt; color: {{ $textColor }}; margin-top: 2px; }
        .body-html { font-size: 14pt; color: {{ $textColor }}; line-height: 1.75; margin: 6px 0; }

        /* Grades table for appreciation / grades certificates. */
        .grades { margin: 12px auto 6px; border-collapse: collapse; width: 70%; }
        .grades th, .grades td {
            border: 1px solid {{ $gold }};
            padding: 5px 8px;
            font-size: 12pt;
            color: {{ $textColor }};
            text-align: center;
        }
        .grades th { color: {{ $nameColor }}; font-weight: bold; }
    </style>
</head>
<body>
    <div class="sheet">
        <div class="frame-outer">
            <div class="frame-inner">
                @if($logoUrl)
                    <div class="logo"><img src="{{ $logoUrl }}" alt=""></div>
                @endif
                <div class="brand">{{ $brand }}</div>
                <div class="flourish">&#10086; &#9670; &#10087;</div>

                <div class="title">{{ $certificate->title }}</div>
                <div class="title-underline"></div>

                <div class="presented">@lang('certificates.pdf.presented_to')</div>
                {{-- Recipient name is real text (extractable, high-res). --}}
                <div class="student">{{ $fields['student_name'] }}</div>
                <div class="name-rule"></div>

                @if($isGeneral && $bodyHtml)
                    {{-- Free-text body replaces the template body lines. --}}
                    <div class="body-html">{!! $bodyHtml !!}</div>
                @else
                    @forelse($lines as $line)
                        <div class="line">{{ $render($line) }}</div>
                    @empty
                        @if($certificate->note)
                            <div class="line">{{ $certificate->note }}</div>
                        @endif
                    @endforelse
                @endif

                @if(! empty($gradeRows))
                    <table class="grades">
                        <tr>
                            <th>@lang('certificates.grades_table.subject')</th>
                            <th>@lang('certificates.grades_table.score')</th>
                            <th>@lang('certificates.grades_table.label')</th>
                        </tr>
                        @foreach($gradeRows as $row)
                            <tr>
                                <td>{{ $row['subject'] }}</td>
                                <td>{{ $row['score'] }}</td>
                                <td>{{ $row['label'] }}</td>
                            </tr>
                        @endforeach
                    </table>
                @endif

                <table class="footer">
                    <tr>
                        <td width="30%" style="text-align:center;">
                            @if($signatureUrl)
                                <img src="{{ $signatureUrl }}" alt="" style="max-height:50px; margin-bottom:4px;">
                            @endif
                            <div class="sig-line"></div>
                            @lang('certificates.pdf.signature')
                            @if($signerName)
                                <div class="signer">{{ $signerName }}</div>
                            @endif
                        </td>
                        <td width="40%" style="text-align:center;">
                            @if($stampUrl)
                                <img src="{{ $stampUrl }}" alt="" style="max-height:80px;">
                            @else
                                <div class="seal"><div class="seal-in">@lang('certificates.pdf.seal')</div></div>
                            @endif
                        </td>
                        <td width="30%" style="text-align:center;">
                            <div class="meta">
                                {{ $fields['school'] }}<br>
                                @if($fields['grade']){{ $fields['grade'] }} &middot; @endif{{ $fields['date'] }}
                            </div>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
