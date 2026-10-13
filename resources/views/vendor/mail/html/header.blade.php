@props(['url'])
@php($brand = \App\Support\MailBrand::get())
<tr>
<td class="header" align="center">
<table class="header-card" align="center" width="570" cellpadding="0" cellspacing="0" role="presentation" dir="rtl" bgcolor="#ffffff" style="border-top: 6px solid {{ $brand['primary'] }};">
<tr>
<td align="center" style="padding: 24px 24px 18px; text-align: center; border-bottom: 1px solid #e5e7eb;">
<a href="{{ $url }}" target="_blank" rel="noopener" style="display: inline-block; text-decoration: none;">
@if (! empty($brand['logo']))
@php($w = (int) ($brand['logo_width'] ?? 200))
<img src="{{ $brand['logo'] }}" class="logo" width="{{ $w }}" alt="{{ $brand['name'] }}" style="width: {{ $w }}px; max-width: {{ $w }}px; height: auto; border: 0;">
@else
<span style="font-size: 28px; font-weight: bold; color: {{ $brand['primary'] }};">{{ $brand['name'] }}</span>
@endif
</a>
</td>
</tr>
</table>
</td>
</tr>
