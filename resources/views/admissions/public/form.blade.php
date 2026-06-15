<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $setting->form_title ?: ('التسجيل — '.$school->name) }}</title>
    <style>
        :root { --gold:#cfa046; --navy:#1e293b; }
        * { box-sizing: border-box; }
        body { font-family: 'Cairo', Tahoma, sans-serif; margin: 0; background: #f1f5f9; color: #0f172a; }
        .wrap { max-width: 760px; margin: 0 auto; padding: 24px 16px 48px; }
        .head { text-align: center; padding: 28px 0 18px; }
        .head h1 { margin: 0; font-size: 22px; color: var(--navy); }
        .head p { color: #64748b; margin: 6px 0 0; }
        .card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 22px; margin-bottom: 18px; box-shadow: 0 8px 24px rgba(15,23,42,.05); }
        label { display: block; font-weight: 600; margin-bottom: 6px; font-size: 14px; }
        .req { color: #dc2626; }
        input, textarea { width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 10px; font-family: inherit; margin-bottom: 14px; }
        button { background: linear-gradient(135deg, #e3c178, var(--gold)); color: #fff; border: none; padding: 12px 22px; border-radius: 10px; font-weight: 700; cursor: pointer; width: 100%; font-size: 15px; }
        .err { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; padding: 10px 14px; border-radius: 10px; margin-bottom: 14px; }
        .info-sec h3 { margin: 0 0 8px; font-size: 16px; color: var(--navy); }
        .info-sec { border-bottom: 1px solid #eef2f7; padding-bottom: 12px; margin-bottom: 12px; }
        .info-sec:last-child { border-bottom: none; margin-bottom: 0; }
        .hp { position: absolute; left: -9999px; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="head">
        <h1>{{ $setting->form_title ?: 'استمارة التسجيل' }}</h1>
        <p>{{ $school->name }}</p>
    </div>

    @if($errors->any())
        <div class="err">
            <strong>تعذّر إرسال الطلب:</strong>
            <ul style="margin:6px 0 0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    @if($sections->isNotEmpty())
    <div class="card">
        @foreach($sections as $section)
            <div class="info-sec">
                <h3>{{ $section->title }}</h3>
                <div>{!! nl2br(e($section->content)) !!}</div>
            </div>
        @endforeach
    </div>
    @endif

    <div class="card">
        <form method="POST" action="{{ route('admissions.public.store', $school->id) }}">
            @csrf
            {{-- honeypot --}}
            <input type="text" name="website" class="hp" tabindex="-1" autocomplete="off">

            @php
                $columnFields = ['student_name','guardian_name','phone','email','national_id','hijri_code','birth_date','city','nationality','address','stage','grade'];
            @endphp
            @foreach($fields as $field)
                @php $isColumn = in_array($field->field_key, $columnFields, true); $inputName = $isColumn ? $field->field_key : "data[{$field->field_key}]"; @endphp
                <label>
                    {{ $field->label }}
                    @if($field->is_required)<span class="req">*</span>@endif
                </label>
                @if($field->field_key === 'birth_date')
                    <input type="date" name="{{ $inputName }}" value="{{ old($field->field_key) }}" {{ $field->is_required ? 'required' : '' }}>
                @elseif($field->field_key === 'email')
                    <input type="email" name="{{ $inputName }}" value="{{ old($field->field_key) }}" {{ $field->is_required ? 'required' : '' }}>
                @elseif(in_array($field->field_key, ['address','notes'], true))
                    <textarea name="{{ $inputName }}" rows="2" {{ $field->is_required ? 'required' : '' }}>{{ old($field->field_key) }}</textarea>
                @else
                    <input type="text" name="{{ $inputName }}" value="{{ $isColumn ? old($field->field_key) : old('data.'.$field->field_key) }}" {{ $field->is_required ? 'required' : '' }}>
                @endif
            @endforeach

            <button type="submit">إرسال الطلب</button>
        </form>
    </div>
</div>
</body>
</html>
