<x-mail::message>
@isset($heading)
# {{ $heading }}

@endisset
{!! nl2br(e($body)) !!}

@if (! empty($actionUrl))
<x-mail::button :url="$actionUrl">
{{ $actionText }}
</x-mail::button>
@endif

مع خالص التحيات،<br>
فريق {{ \App\Support\MailBrand::get()['name'] }}
</x-mail::message>
