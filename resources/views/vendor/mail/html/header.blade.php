@props(['url'])
<tr>
    <td class="header">
        <a href="{{ $url }}" style="display: inline-block;">
            <img src="{{ $tenantSettings->logoURL }}" class="logo" alt="{{ $tenantSettings->organizationName }}">
        </a>
    </td>
</tr>
