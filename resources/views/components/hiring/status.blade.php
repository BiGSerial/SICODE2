@php
    use App\Custom\Viabilitiesstatus;
@endphp
<div>

    <span class="badge {{ Viabilitiesstatus::status($badge)->colorbg }}">
        {{ Viabilitiesstatus::status($badge)->status }}</span>

</div>
