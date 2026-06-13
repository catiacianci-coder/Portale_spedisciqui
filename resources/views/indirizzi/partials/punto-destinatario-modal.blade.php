@php
    $rows = $puntiDestRows ?? [];
    $error = $puntiDestError ?? null;
@endphp

<div id="sq-ind-punto-overlay" class="sq-ind-punto-overlay" hidden aria-hidden="true">
    <div class="sq-ind-punto-dialog" role="dialog" aria-labelledby="sq-ind-punto-title" aria-modal="true">
        <div class="sq-ind-punto-dialog-head">
            <h3 id="sq-ind-punto-title" class="sq-ind-punto-title">Scegli il punto di ritiro</h3>
            <button type="button" class="sq-ind-punto-close" id="sq-ind-punto-close" aria-label="Chiudi">×</button>
        </div>
        <p class="sq-ind-punto-sub">Consegna: <strong>{{ e($consegnaMode ?? '') }}</strong> — seleziona dove il destinatario ritirerà il pacco.</p>

        @if ($error)
            <p class="sq-alert sq-alert--info-warm sq-mb-14 sq-ind-punto-pad">{{ $error }}</p>
        @elseif ($rows === [])
            <p class="sq-text-muted sq-ind-punto-pad">Nessun punto disponibile.</p>
        @else
            <p class="sq-mb-14 sq-text-muted sq-ind-punto-pad">
                <strong>{{ count($rows) }}</strong> punti trovati. Clicca un punto, poi <strong>Conferma punto</strong>.
            </p>
            <div class="sq-sc-dest-layout sq-ind-punto-pad">
                <div class="sq-sc-dest-list-wrap">
                    <div class="sq-sc-dest-list-head">Elenco punti</div>
                    <ul class="sq-sc-dest-list" id="ind-dest-points-list" role="listbox" aria-label="Punti consegna">
                        @foreach ($rows as $index => $point)
                            <li>
                                <button type="button"
                                        class="sq-sc-dest-item"
                                        role="option"
                                        data-index="{{ $index }}">
                                    <span class="sq-sc-dest-item-name">{{ $point['name'] }}</span>
                                    <span class="sq-sc-dest-item-addr">
                                        {{ $point['address_line'] }} — {{ $point['postal_code'] }} {{ $point['city'] }}
                                    </span>
                                </button>
                            </li>
                        @endforeach
                    </ul>
                    <div class="sq-sc-dest-list-footer">
                        <p class="sq-sc-dest-list-hint" id="ind-dest-list-hint">Nessun punto scelto.</p>
                        <button type="button" class="sq-btn-primary sq-sc-dest-select-btn" id="ind-dest-confirm-select" disabled>
                            Conferma punto
                        </button>
                    </div>
                </div>
                <div class="sq-sc-dest-map-frame">
                    <div id="ind-dest-points-map" class="sq-sc-dest-map" aria-label="Mappa punti consegna"></div>
                </div>
            </div>
            <script type="application/json" id="ind-dest-points-data">@json($rows)</script>
        @endif
    </div>
</div>
