@php($brand = \App\Support\MailBrand::get())
<x-mail::message>
{{-- Greeting --}}
@if (! empty($greeting))
# {{ $greeting }}
@else
@if ($level === 'error')
# عذرًا!
@else
# مرحبًا،
@endif
@endif

{{-- Intro Lines --}}
@foreach ($introLines as $line)
{{ $line }}

@endforeach

{{-- Action Button --}}
@isset($actionText)
<?php
    $color = match ($level) {
        'success', 'error' => $level,
        default => 'primary',
    };
?>
<x-mail::button :url="$actionUrl" :color="$color">
{{ $actionText }}
</x-mail::button>
@endisset

{{-- Outro Lines --}}
@foreach ($outroLines as $line)
{{ $line }}

@endforeach

{{-- Salutation --}}
@if (! empty($salutation))
{{ $salutation }}
@else
مع خالص التحيات،<br>
فريق {{ $brand['name'] }}
@endif

{{-- Subcopy --}}
@isset($actionText)
<x-slot:subcopy>
إذا لم يعمل زر «{{ $actionText }}»، انسخ الرابط التالي والصقه في متصفحك:
<span class="break-all" dir="ltr">[{{ $displayableActionUrl }}]({{ $actionUrl }})</span>
</x-slot:subcopy>
@endisset
</x-mail::message>
