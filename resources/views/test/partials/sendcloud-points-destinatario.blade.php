@php
    $mapPrefix = $mapPrefix ?? 'dest';
    $rows = $rows ?? [];
    $searched = $searched ?? false;
    $errorMessage = $errorMessage ?? null;
    $geocoding = $geocoding ?? ['status' => null, 'precision' => null];
    $selectLabel = $selectLabel ?? 'Seleziona punto consegna';
    $listTitle = $listTitle ?? 'Elenco punti consegna';
    $mapAriaLabel = $mapAriaLabel ?? 'Mappa punti consegna';
    $selectedTitle = $selectedTitle ?? 'Punto destinatario selezionato (per to_service_point)';
    $emptyHint = $emptyHint ?? 'Nessun punto da mostrare.';
    $introHint = $introHint ?? 'Scorri l’elenco, clicca un punto per vederlo sulla mappa, poi conferma con Seleziona.';
@endphp

@if ($searched && ! empty($errorMessage))
    <p class="sq-alert sq-alert--info-warm sq-mb-14">{{ $errorMessage }}</p>
@endif

@if ($searched && ! empty($geocoding['status']))
    <p class="sq-mb-14 sq-text-muted">
        Geocoding: <strong>{{ $geocoding['status'] }}</strong>
        @if (! empty($geocoding['precision']))
            — {{ $geocoding['precision'] }}
        @endif
    </p>
@endif

@if ($searched && $rows !== [])
    <p class="sq-mb-14 sq-text-muted">
        <strong>{{ count($rows) }}</strong> punti trovati. {{ $introHint }}
    </p>

    <div class="sq-sc-dest-layout">
        <div class="sq-sc-dest-list-wrap">
            <div class="sq-sc-dest-list-head">{{ $listTitle }}</div>
            <ul class="sq-sc-dest-list" id="{{ $mapPrefix }}-points-list" role="listbox" aria-label="{{ $listTitle }}">
                @foreach ($rows as $index => $point)
                    <li>
                        <button type="button"
                                class="sq-sc-dest-item"
                                role="option"
                                data-map-prefix="{{ $mapPrefix }}"
                                data-index="{{ $index }}"
                                data-point-id="{{ $point['id'] }}">
                            <span class="sq-sc-dest-item-name">{{ $point['name'] }}</span>
                            <span class="sq-sc-dest-item-addr">
                                {{ $point['address_line'] }} — {{ $point['postal_code'] }} {{ $point['city'] }}
                            </span>
                            <span class="sq-sc-dest-item-type">
                                {{ $point['carrier_code'] ?? '—' }}
                                @if (($point['general_shop_type'] ?? '—') !== '—')
                                    · {{ $point['general_shop_type'] }}
                                @endif
                                @if ($point['distance_m'] !== null)
                                    · {{ (int) $point['distance_m'] }} m
                                @endif
                            </span>
                        </button>
                    </li>
                @endforeach
            </ul>
            <div class="sq-sc-dest-list-footer">
                <p class="sq-sc-dest-list-hint" id="{{ $mapPrefix }}-list-hint">Nessun punto scelto nell’elenco.</p>
                <button type="button" class="sq-sim-btn sq-sc-dest-select-btn" id="{{ $mapPrefix }}-confirm-select" disabled>
                    {{ $selectLabel }}
                </button>
            </div>
        </div>
        <div class="sq-sc-dest-map-frame">
            <div id="{{ $mapPrefix }}-points-map" class="sq-sc-dest-map" aria-label="{{ $mapAriaLabel }}"></div>
        </div>
    </div>

    <div class="sq-sc-selected-box" id="{{ $mapPrefix }}-selected-box" hidden>
        <h4>{{ $selectedTitle }}</h4>
        <pre class="sq-pre-json sq-sc-selected-json" id="{{ $mapPrefix }}-selected-json">—</pre>
    </div>

    <script type="application/json" id="{{ $mapPrefix }}-points-data">@json($rows)</script>
@elseif ($searched)
    <p class="sq-text-muted">{{ $emptyHint }}</p>
@endif
