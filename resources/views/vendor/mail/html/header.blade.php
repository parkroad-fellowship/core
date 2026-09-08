@props(['url'])
<tr>
    <td class="header">
        <a href="{{ $url }}" style="display: inline-block;">
        <img src="{{ isset($tenantSettings) ? $tenantSettings->logoURL : '/logo.png' }}" class="logo" alt="{{ isset($tenantSettings) ? $tenantSettings->organizationName : config('app.name') }}">
        </a>
    </td>
</tr>
