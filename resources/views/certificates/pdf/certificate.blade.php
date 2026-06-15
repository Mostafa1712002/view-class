@php
    use Illuminate\Support\Facades\Storage;
    $textColor = $template?->text_color ?? '#222222';
    $nameColor = $template?->name_color ?? '#1a3c6e';
    $bgPath = $template && $template->background_path && Storage::disk('public')->exists($template->background_path)
        ? Storage::disk('public')->path($template->background_path)
        : null;
    $lines = $template?->body['lines'] ?? [];
    $brand = config('app.name', 'المنصة الذهبية');

    // Replace placeholders in a body line with resolved field values.
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
        .sheet {
            position: relative;
            width: 100%;
            text-align: center;
            @if($bgPath)
            background-image: url('{{ $bgPath }}');
            background-size: cover;
            background-repeat: no-repeat;
            @endif
        }
        .frame { padding: 60px 50px; }
        .brand { font-size: 14pt; color: {{ $nameColor }}; margin-bottom: 8px; }
        .title { font-size: 26pt; font-weight: bold; color: {{ $nameColor }}; margin: 6px 0 18px; }
        .student { font-size: 22pt; font-weight: bold; color: {{ $nameColor }}; margin: 14px 0; }
        .line { font-size: 14pt; color: {{ $textColor }}; margin: 6px 0; line-height: 1.7; }
        .meta { font-size: 12pt; color: {{ $textColor }}; margin-top: 26px; }
    </style>
</head>
<body>
    <div class="sheet">
        <div class="frame">
            <div class="brand">{{ $brand }}</div>
            <div class="title">{{ $certificate->title }}</div>

            {{-- Recipient name is always rendered as real text (extractable). --}}
            <div class="student">{{ $fields['student_name'] }}</div>

            @forelse($lines as $line)
                <div class="line">{{ $render($line) }}</div>
            @empty
                @if($certificate->note)
                    <div class="line">{{ $certificate->note }}</div>
                @endif
            @endforelse

            <div class="meta">
                @lang('certificates.fields.school'): {{ $fields['school'] }}
                @if($fields['grade']) &nbsp;|&nbsp; @lang('certificates.fields.grade'): {{ $fields['grade'] }} @endif
                &nbsp;|&nbsp; @lang('certificates.fields.issue_date'): {{ $fields['date'] }}
            </div>
        </div>
    </div>
</body>
</html>
