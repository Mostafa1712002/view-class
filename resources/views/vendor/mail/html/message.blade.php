@php($brand = \App\Support\MailBrand::get())
<x-mail::layout>
{{-- Header --}}
<x-slot:header>
<x-mail::header :url="$brand['url']">
{{ $brand['name'] }}
</x-mail::header>
</x-slot:header>

{{-- Body --}}
{{ $slot }}

{{-- Subcopy --}}
@isset($subcopy)
<x-slot:subcopy>
<x-mail::subcopy>
{{ $subcopy }}
</x-mail::subcopy>
</x-slot:subcopy>
@endisset

{{-- Footer --}}
<x-slot:footer>
<x-mail::footer>
**{{ $brand['name'] }}**<br>
[{{ preg_replace('#^https?://#', '', rtrim($brand['url'], '/')) }}]({{ $brand['url'] }})

هذه رسالة آلية من {{ $brand['name'] }}، يُرجى عدم الرد عليها.<br>
© {{ date('Y') }} {{ $brand['name'] }}. جميع الحقوق محفوظة.
</x-mail::footer>
</x-slot:footer>
</x-mail::layout>
