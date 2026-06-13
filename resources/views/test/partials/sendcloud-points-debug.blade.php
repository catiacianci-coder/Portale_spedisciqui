@if ($searched ?? false)
    <details class="sq-sc-debug">
        <summary>Risposta API grezza (debug)</summary>
        <p><strong>HTTP:</strong> {{ $httpStatus ?? '—' }}</p>
        <h4 class="sq-sim-h2 sq-mt-16">Query</h4>
        <pre class="sq-pre-json">{{ ! empty($query) ? json_encode($query, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : '—' }}</pre>
        <h4 class="sq-sim-h2 sq-mt-16">Body</h4>
        @if (! empty($rawBody))
            @php
                $decoded = json_decode($rawBody, true);
                $display = json_last_error() === JSON_ERROR_NONE
                    ? json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
                    : $rawBody;
            @endphp
            <pre class="sq-pre-json">{{ $display }}</pre>
        @else
            <pre class="sq-pre-json">—</pre>
        @endif
    </details>
@endif
