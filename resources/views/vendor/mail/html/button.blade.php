@props([
    'url',
    'color' => 'primary',
    'align' => 'center',
])
@php($brand = \App\Support\MailBrand::get())
@php($bg = match ($color) { 'success', 'green' => '#15803d', 'error', 'red' => '#b91c1c', default => $brand['primary'] })
<table class="action" align="{{ $align }}" width="100%" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td align="{{ $align }}">
<table border="0" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td align="center" bgcolor="{{ $bg }}" style="background-color: {{ $bg }}; border-radius: 8px;">
<a href="{{ $url }}" class="button" target="_blank" rel="noopener" style="display: inline-block; padding: 12px 34px; color: #ffffff; background-color: {{ $bg }}; border: 1px solid {{ $bg }}; border-radius: 8px; font-size: 16px; font-weight: bold; text-decoration: none;">{{ $slot }}</a>
</td>
</tr>
</table>
</td>
</tr>
</table>
