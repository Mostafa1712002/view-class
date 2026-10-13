@php($brand = \App\Support\MailBrand::get())
<table class="panel" width="100%" cellpadding="0" cellspacing="0" role="presentation" dir="rtl">
<tr>
<td class="panel-content" bgcolor="#f8fafc" style="background-color: #f8fafc; border-right: 4px solid {{ $brand['accent'] }};">
<table width="100%" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td class="panel-item">
{{ Illuminate\Mail\Markdown::parse($slot) }}
</td>
</tr>
</table>
</td>
</tr>
</table>
