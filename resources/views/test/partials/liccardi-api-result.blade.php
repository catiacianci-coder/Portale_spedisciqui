@if ($searched ?? false)
    <div class="sq-liccardi-api-result sq-mt-16">
        <h3 class="sq-sim-h2">Richiesta e risposta API</h3>

        @if (! empty($errorMessage))
            <p class="sq-alert sq-alert--info-warm">{{ $errorMessage }}</p>
        @endif

        @if (! empty($hints) && is_array($hints))
            <p class="sq-text-muted sq-mb-14">
                @if (! empty($hints['spedizioneId']))
                    <strong>spedizioneId:</strong> {{ $hints['spedizioneId'] }}
                @endif
                @if (! empty($hints['courierLdv']))
                    · <strong>courierLdv / LDV:</strong> <code class="sq-code">{{ $hints['courierLdv'] }}</code>
                @endif
                @if (! empty($hints['packageIds']) && is_array($hints['packageIds']))
                    · <strong>packageId:</strong> {{ implode(', ', $hints['packageIds']) }}
                @endif
                <span class="sq-text-muted"> (salvati in sessione per i passaggi successivi)</span>
            </p>
        @endif

        <p class="sq-mb-8">
            <strong>{{ $method ?? '—' }}</strong>
            <code class="sq-code sq-liccardi-url">{{ $url ?? '—' }}</code>
        </p>
        <p class="sq-mb-14"><strong>HTTP:</strong> {{ $httpStatus ?? '—' }}
            @if (! empty($contentType))
                · <strong>Content-Type:</strong> {{ $contentType }}
            @endif
        </p>

        @if (! empty($bodyNote))
            <p class="sq-text-muted sq-mb-14">{{ $bodyNote }}</p>
        @endif

        <h4 class="sq-sim-h2 sq-mt-16">Header inviati</h4>
        <pre class="sq-pre-json">@if (! empty($requestHeaders) && is_array($requestHeaders)){{ json_encode($requestHeaders, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}@else—@endif</pre>

        @if (! empty($query) && is_array($query) && count($query) > 0)
            <h4 class="sq-sim-h2 sq-mt-16">Query string</h4>
            <pre class="sq-pre-json">{{ json_encode($query, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
        @endif

        <h4 class="sq-sim-h2 sq-mt-16">Body inviato</h4>
        <pre class="sq-pre-json">@if (! empty($payload) && is_array($payload)){{ json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}@else—@endif</pre>

        <h4 class="sq-sim-h2 sq-mt-16">Risposta ricevuta</h4>
        <pre class="sq-pre-json sq-liccardi-response-body">{{ $rawBody ?? '—' }}</pre>
    </div>
@endif
