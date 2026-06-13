@php
    $testo = trim((string) ($testo ?? ''));
@endphp

@if ($testo !== '')
    <p class="sq-homepage-avviso" role="status">
        <i class="fa-solid fa-bullhorn sq-homepage-avviso__icon" aria-hidden="true"></i>
        <span class="sq-homepage-avviso__text">{{ $testo }}</span>
    </p>
@endif
