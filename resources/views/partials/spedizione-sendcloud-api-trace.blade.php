@php
    use App\Support\PiattaformaCorriere;
    use App\Support\SendcloudIntegrazione;

    $spedizione = $spedizione ?? null;
    if (! $spedizione) {
        return;
    }
    $spedizione->loadMissing('corriereRecord');
    $corriere = $spedizione->corriereRecord;
    if (! $corriere || ! PiattaformaCorriere::corriereUsaAcquistoSendcloud($corriere)) {
        return;
    }
    if (! SendcloudIntegrazione::haTracciaApi($spedizione)) {
        return;
    }
    $traccia = SendcloudIntegrazione::tracciaApiAnnounce($spedizione);
    $fmtJson = static function (mixed $value): string {
        if ($value === null) {
            return '—';
        }
        if (! is_array($value)) {
            return (string) $value;
        }

        return json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '—';
    };
@endphp
<div class="sq-sped-sendcloud-api-trace sq-mt-12">
    <div class="sq-servizio-api-trace-head">
        Sendcloud — invio e risposta
        @if (! empty($traccia['shipment_id']))
            · shipment id <code class="sq-code">{{ $traccia['shipment_id'] }}</code>
        @endif
    </div>
    @if (! empty($traccia['error']))
        <p class="sq-servizio-api-trace-meta sq-servizio-api-trace-meta--err sq-mb-8">{{ $traccia['error'] }}</p>
    @endif
    <details class="sq-servizio-api-trace-call" open>
        <summary>POST /shipments/announce</summary>
        <div class="sq-servizio-api-trace-block">
            <div class="sq-servizio-api-trace-label">Inviamo</div>
            <pre class="sq-pre-json">{{ $fmtJson($traccia['request']) }}</pre>
        </div>
        <div class="sq-servizio-api-trace-block">
            <div class="sq-servizio-api-trace-label">
                Riceviamo
                @if ($traccia['http_status'] !== null)
                    · HTTP {{ $traccia['http_status'] }}
                @endif
            </div>
            <pre class="sq-pre-json">{{ $fmtJson($traccia['response']) }}</pre>
        </div>
    </details>
</div>
