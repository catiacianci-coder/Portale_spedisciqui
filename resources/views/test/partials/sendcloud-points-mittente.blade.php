@php
    $rows = $mittRows ?? [];
    $searched = $mittSearched ?? false;
@endphp

@if ($searched && ! empty($mittErrorMessage))
    <p class="sq-alert sq-alert--info-warm sq-mb-14">{{ $mittErrorMessage }}</p>
@endif

@if ($searched && ! empty($mittGeocoding['status']))
    <p class="sq-mb-14 sq-text-muted">
        Geocoding: <strong>{{ $mittGeocoding['status'] }}</strong>
        @if (! empty($mittGeocoding['precision']))
            — {{ $mittGeocoding['precision'] }}
        @endif
    </p>
@endif

@if ($searched && $rows !== [])
    <p class="sq-mb-14 sq-text-muted">
        <strong>{{ count($rows) }}</strong> punti vicino al mittente. Apri una riga per gli <strong>orari</strong>, poi seleziona il punto di deposito.
    </p>

    <ul class="sq-sc-mitt-list" id="mitt-points-list">
        @foreach ($rows as $point)
            <li>
                <details class="sq-sc-mitt-item">
                    <summary>
                        <strong>{{ $point['name'] }}</strong>
                        <span class="sq-sc-mitt-meta">
                            {{ $point['address_line'] }} — {{ $point['postal_code'] }} {{ $point['city'] }}
                            @if ($point['distance_m'] !== null)
                                · {{ (int) $point['distance_m'] }} m
                            @endif
                        </span>
                        <span class="sq-sc-mitt-meta">
                            <code class="sq-code">{{ $point['general_shop_type'] }}</code>
                            @if ($point['carrier_shop_type'] !== '—')
                                / {{ $point['carrier_shop_type'] }}
                            @endif
                        </span>
                    </summary>

                    @if (! empty($point['opening_hours']))
                        <div class="sq-sc-mitt-hours">
                            <table class="sq-sc-hours-table">
                                <tbody>
                                    @foreach ($point['opening_hours'] as $oh)
                                        <tr>
                                            <td>{{ $oh['day'] }}</td>
                                            <td>{{ $oh['hours'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            @if ($point['is_open_tomorrow'])
                                <p class="sq-text-muted" style="font-size:12px; margin:8px 0 0;">Aperto domani (stima API).</p>
                            @endif
                        </div>
                    @else
                        <div class="sq-sc-mitt-hours">
                            <p class="sq-text-muted" style="margin:8px 0 0; font-size:13px;">Orari non disponibili in risposta API.</p>
                        </div>
                    @endif

                    <div class="sq-sc-mitt-actions">
                        <button type="button"
                                class="sq-sim-btn sq-sc-select-mitt"
                                data-point='@json($point, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)'>
                            Seleziona per deposito mittente
                        </button>
                    </div>
                </details>
            </li>
        @endforeach
    </ul>

    <div class="sq-sc-selected-box" id="mitt-selected-box" hidden>
        <h4>Punto mittente selezionato</h4>
        <pre class="sq-pre-json sq-sc-selected-json" id="mitt-selected-json">—</pre>
    </div>
@elseif ($searched)
    <p class="sq-text-muted">Nessun punto da mostrare.</p>
@endif
