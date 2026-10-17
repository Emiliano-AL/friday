@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block; text-decoration: none;">
<img src="{{ asset('images/eunox-mail-logo.png') }}" width="32" height="32" alt="{{ config('app.name') }}" style="display: inline-block; vertical-align: middle; border: 0; height: 32px; width: 32px;">
<span style="display: inline-block; vertical-align: middle; margin-left: 8px;">{!! $slot !!}</span>
</a>
</td>
</tr>
