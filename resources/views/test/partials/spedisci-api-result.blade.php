@if ($searched ?? false)
    <hr class="sq-sim-hr">
    <h2 class="sq-sim-h2">Risultato — {{ $endpoint ?? '—' }}</h2>
    <p><strong>HTTP:</strong> {{ $httpStatus ?? '—' }}</p>

    @if (! empty($infoMessage))
        <p class="sq-text-muted sq-mb-14">{{ $infoMessage }}</p>
    @endif

    @if (! empty($errorMessage))
        <p class="sq-alert sq-alert--info-warm">{{ $errorMessage }}</p>
    @endif

    @if (! empty($ratesPreviewBody))
        <h3 class="sq-sim-h2 sq-mt-16">Preventivo intermedio — POST /shipping/rates</h3>
        <p><strong>HTTP:</strong> {{ $ratesPreviewStatus ?? '—' }}</p>
        @php
            $ratesDecoded = json_decode($ratesPreviewBody, true);
            $ratesDisplay = json_last_error() === JSON_ERROR_NONE
                ? json_encode($ratesDecoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
                : $ratesPreviewBody;
        @endphp
        <pre class="sq-pre-json">{{ $ratesDisplay }}</pre>
    @endif

    <h3 class="sq-sim-h2 sq-mt-16">Chiamata A — solo trasporto</h3>
    <p class="sq-text-muted sq-mb-14">Prezzo base e contrassegno (<code class="sq-code">cash_on_delivery</code>).</p>
    <h4 class="sq-sim-h2">Richiesta inviata</h4>
    <pre class="sq-pre-json">{{ ! empty($payload) ? json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : '—' }}</pre>

    <h4 class="sq-sim-h2 sq-mt-16">Risposta API</h4>
    @if (! empty($rawBody))
        @php
            $decoded = json_decode($rawBody, true);
            $displayResponse = json_last_error() === JSON_ERROR_NONE
                ? json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
                : $rawBody;
        @endphp
        <pre class="sq-pre-json">{{ $displayResponse }}</pre>
    @else
        <pre class="sq-pre-json">—</pre>
    @endif

    @if (! empty($insurancePayload))
        <h3 class="sq-sim-h2 sq-mt-24">Chiamata B — trasporto + assicurazione test
            @if (($valoreAssicurazioneTest ?? 0) > 0)
                ({{ \App\Support\ImportoEuro::format((float) $valoreAssicurazioneTest) }})
            @endif
        </h3>
        <p class="sq-text-muted sq-mb-14">Costo supplemento in <code class="sq-code">quotes[].price.breakdown</code> → <code class="sq-code">insurance_price</code>.</p>
        <p><strong>HTTP:</strong> {{ $insuranceHttpStatus ?? '—' }}</p>
        @if (! empty($insuranceError))
            <p class="sq-alert sq-alert--info-warm">{{ $insuranceError }}</p>
        @endif
        <h4 class="sq-sim-h2">Richiesta inviata</h4>
        <pre class="sq-pre-json">{{ json_encode($insurancePayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
        <h4 class="sq-sim-h2 sq-mt-16">Risposta API</h4>
        @if (! empty($insuranceRawBody))
            @php
                $insuranceDecoded = json_decode($insuranceRawBody, true);
                $insuranceDisplay = json_last_error() === JSON_ERROR_NONE
                    ? json_encode($insuranceDecoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
                    : $insuranceRawBody;
            @endphp
            <pre class="sq-pre-json">{{ $insuranceDisplay }}</pre>
        @else
            <pre class="sq-pre-json">—</pre>
        @endif
    @endif
@endif
