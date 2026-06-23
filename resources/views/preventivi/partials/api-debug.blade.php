@php
    $formatJson = static function (mixed $value): string {
        if ($value === null) {
            return '—';
        }
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '—';
            }

            return $value !== '' ? $value : '—';
        }
        if (is_array($value) || is_object($value)) {
            return json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '—';
        }

        return (string) $value;
    };

    $blocks = [];

    if ($isSendcloud ?? false) {
        $probe = is_array($sendcloudProbe ?? null) ? $sendcloudProbe : [];
        $blocks[] = [
            'titolo' => 'Sendcloud',
            'endpoint' => trim((string) (($probe['api_base'] ?? '').'/'.ltrim((string) ($probe['endpoint'] ?? 'POST /shipping-options'), '/'))),
            'http_status' => $probe['http_status'] ?? null,
            'error' => $probe['error'] ?? null,
            'request' => $probe['payload'] ?? null,
            'response' => $probe['response_json'] ?? ($probe['raw_body'] ?? null),
        ];
    } elseif ($isLiccardiTms ?? false) {
        $probe = is_array($liccardiProbe ?? null) ? $liccardiProbe : [];
        $path = trim((string) ($probe['path'] ?? 'spedizioni/importi/getImporto'));
        $blocks[] = [
            'titolo' => 'Liccardi TMS',
            'endpoint' => trim((string) (($probe['api_base'] ?? '').'/'.ltrim($path, '/'))),
            'http_status' => $probe['http_status'] ?? null,
            'error' => $probe['error'] ?? null,
            'request' => $probe['payload'] ?? null,
            'response' => $probe['response_json'] ?? null,
        ];
    } elseif ($isSpedisciOnline ?? false) {
        $probe = is_array($spedisciProbe ?? null) ? $spedisciProbe : [];
        $blocks[] = [
            'titolo' => 'Spedisci.online',
            'endpoint' => trim((string) (($probe['api_base'] ?? '').'/shipping/rates')),
            'http_status' => $probe['http_status'] ?? null,
            'error' => $probe['error'] ?? null,
            'request' => $probe['payload'] ?? null,
            'response' => $probe['raw_body'] ?? ($probe['rates'] ?? null),
        ];
    } elseif ($usaTariffaInterna ?? false) {
        $blocks[] = [
            'titolo' => 'Tariffa interna (DB)',
            'endpoint' => 'Query locale tariffas',
            'http_status' => null,
            'error' => $riga['motivo_tariffa'] ?? null,
            'request' => [
                'id_corriere' => (int) ($corriere['id'] ?? 0),
                'id_tipo_spediziones' => (int) data_get($preventivo, 'input.id_tipo_spediziones', 0),
                'peso_kg' => (float) data_get($preventivo, 'input.peso', 0),
                'dimensioni_cm' => [
                    'altezza' => (float) data_get($preventivo, 'input.altezza', 0),
                    'larghezza' => (float) data_get($preventivo, 'input.larghezza', 0),
                    'spessore' => (float) data_get($preventivo, 'input.spessore', 0),
                ],
                'misure' => $preventivo['misure'] ?? null,
                'cap_origine' => data_get($preventivo, 'input.cap_origine'),
                'cap_destino' => data_get($preventivo, 'input.cap_destino'),
                'id_comune_origine' => data_get($preventivo, 'input.id_comune_origine'),
                'id_comune_destino' => data_get($preventivo, 'input.id_comune_destino'),
            ],
            'response' => [
                'ok_tratta' => (bool) ($riga['ok_tratta'] ?? false),
                'tariffa' => $riga['tariffa'] ?? null,
                'prezzo_base' => $riga['prezzo_base'] ?? null,
                'prezzo_finale' => $riga['prezzo_finale'] ?? null,
                'prezzo_wallet' => $riga['prezzo_wallet'] ?? null,
                'motivo_tariffa' => $riga['motivo_tariffa'] ?? null,
            ],
        ];
    }

    $spProbe = is_array($spedisciProbe ?? null) ? $spedisciProbe : [];
    if ($spProbe !== [] && ! ($isSpedisciOnline ?? false)) {
        $blocks[] = [
            'titolo' => 'Spedisci.online (verifica rates)',
            'endpoint' => trim((string) (($spProbe['api_base'] ?? '').'/shipping/rates')),
            'http_status' => $spProbe['http_status'] ?? null,
            'error' => $spProbe['error'] ?? null,
            'request' => $spProbe['payload'] ?? null,
            'response' => $spProbe['raw_body'] ?? ($spProbe['rates'] ?? null),
        ];
    }
@endphp

@if (! empty($blocks))
    <details class="sq-prev-api-debug">
        <summary class="sq-prev-api-debug-summary">JSON inviato / ricevuto (debug API)</summary>

        @foreach ($blocks as $block)
            <div class="sq-prev-api-debug-block">
                <div class="sq-prev-api-debug-head">
                    <strong>{{ $block['titolo'] }}</strong>
                    @if (! empty($block['endpoint']))
                        <code class="sq-prev-code-tiny">{{ $block['endpoint'] }}</code>
                    @endif
                    @if ($block['http_status'] !== null)
                        <span class="sq-prev-api-debug-status">HTTP {{ $block['http_status'] }}</span>
                    @endif
                </div>

                @if (! empty($block['error']))
                    <p class="sq-prev-api-debug-error">{{ $block['error'] }}</p>
                @endif

                <h4 class="sq-prev-api-debug-label">Inviato</h4>
                <pre class="sq-pre-json">{{ $formatJson($block['request']) }}</pre>

                <h4 class="sq-prev-api-debug-label">Ricevuto</h4>
                <pre class="sq-pre-json">{{ $formatJson($block['response']) }}</pre>
            </div>
        @endforeach
    </details>
@endif
