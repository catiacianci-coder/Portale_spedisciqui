@php
    /** @var \App\Models\spedizione $spedizione */
    $trackingRoute = $trackingRoute ?? 'spedizioni.tracking';
    $btnClass = $btnClass ?? 'sq-ordini-icon-action sq-ordini-icon-action--view';
    $iconClass = $iconClass ?? 'fa-solid fa-location-dot';
@endphp
<button
    type="button"
    class="{{ $btnClass }} js-spedizione-tracking-btn"
    title="Tracking spedizione"
    aria-label="Tracking spedizione {{ $spedizione->codice_interno }}"
    data-tracking-url="{{ route($trackingRoute, $spedizione) }}"
>
    <i class="{{ $iconClass }}" aria-hidden="true"></i>
</button>
