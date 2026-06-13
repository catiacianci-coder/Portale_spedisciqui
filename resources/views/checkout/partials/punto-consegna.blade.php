@php
    $rows = $puntiDestRows ?? [];
    $error = $puntiDestError ?? null;
    $sel = is_array($puntoSelezionato ?? null) ? $puntoSelezionato : [];
@endphp

<section class="sq-checkout-punto sq-mb-18" id="checkout-punto-section" data-requires-punto="1"
         @if (! empty($puntoConsegnaLabel)) data-punto-consegna-label="{{ $puntoConsegnaLabel }}" @endif>

    @if ($error)
        <div class="sq-alert sq-alert--error sq-mb-14">{{ $error }}</div>
    @endif

    <input type="hidden" name="punto_consegna_json" id="checkout-punto-json" value="" class="js-checkout-punto-json"
           @if ($sel !== []) data-initial='@json($sel)' @endif>

    @if ($rows !== [])
        <div class="sq-sc-dest-layout">
            <div class="sq-sc-dest-list-wrap">
                <div class="sq-sc-dest-list-head">{{ $puntoConsegnaLabel ?? 'Punti di consegna' }}</div>
                <ul class="sq-sc-dest-list" id="checkout-dest-points-list" role="listbox" aria-label="Punti consegna">
                    @foreach ($rows as $index => $point)
                        <li>
                            <button type="button"
                                    class="sq-sc-dest-item"
                                    role="option"
                                    data-index="{{ $index }}"
                                    data-point-id="{{ $point['id'] }}">
                                <span class="sq-sc-dest-item-name">{{ $point['name'] }}</span>
                                <span class="sq-sc-dest-item-addr">
                                    {{ $point['address_line'] }} — {{ $point['postal_code'] }} {{ $point['city'] }}
                                </span>
                                <span class="sq-sc-dest-item-type">
                                    {{ $point['general_shop_type'] }}
                                    @if ($point['distance_m'] !== null)
                                        · {{ (int) $point['distance_m'] }} m
                                    @endif
                                </span>
                            </button>
                        </li>
                    @endforeach
                </ul>
                <div class="sq-sc-dest-list-footer">
                    <button type="button" class="sq-btn-primary sq-sc-dest-select-btn" id="checkout-dest-confirm-select" disabled>
                        Conferma punto selezionato
                    </button>
                </div>
            </div>
            <div class="sq-sc-dest-map-frame">
                <div id="checkout-dest-points-map" class="sq-sc-dest-map" aria-label="Mappa punti consegna"></div>
            </div>
        </div>
        <script type="application/json" id="checkout-dest-points-data">@json($rows)</script>
    @elseif (! $error)
        <p class="sq-text-muted">Nessun punto disponibile per questa zona.</p>
    @endif
</section>
