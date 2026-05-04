<tr>
    <td class="header">
        <a href="{{ $url }}" style="display: inline-block;">
            @if (trim($slot) === 'Laravel')
            <img src="{{ asset('Images/Fama.png') }}" class="logo" alt="FAMa Logo" style="max-height: 60px;">
            <br>
            <span style="color: #059669; font-weight: bold; font-size: 16px;">FAMa Recrutement</span>
            @else
            {{ $slot }}
            @endif
        </a>
    </td>
</tr>