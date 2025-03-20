@props(['url'])
<tr>
    <td class="header">
        <a href="{{ $url }}" style="display: inline-block;">
            <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('landscape-logo.png'))) }}"
                class="logo" alt="Parkroad Fellowship">
        </a>
    </td>
</tr>
