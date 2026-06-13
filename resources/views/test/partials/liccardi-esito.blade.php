@php
    $ok = $ok ?? false;
    $probe = is_array($probe ?? null) ? $probe : [];
@endphp
<section class="sq-liccardi-esito {{ $ok ? 'is-ok' : 'is-ko' }}">
    <h2 class="sq-liccardi-esito-title">{{ $titolo ?? 'Risultato' }}</h2>

    <p class="sq-liccardi-esito-badge">
        @if ($ok)
            OK — HTTP {{ $httpStatus ?? '200' }}
        @else
            Errore @if (! empty($httpStatus)) — HTTP {{ $httpStatus }} @endif
        @endif
    </p>

    @if (! empty($errorMessage))
        <p class="sq-liccardi-esito-msg sq-liccardi-esito-msg--err">{{ $errorMessage }}</p>
        @if (stripos((string) $errorMessage, 'saldo') !== false)
            <div class="sq-liccardi-saldo-help">
                <p><strong>Non è un errore del portale.</strong> La sandbox Liccardi addebita il conto cliente TMS (<code class="sq-code">{{ \App\Services\ParametriApiConfig::liccardiTmsCompanyId() }}</code>) quando crei la spedizione.</p>
                <p>Il preventivo (<code class="sq-code">getImporto</code>) può funzionare anche senza saldo; l’etichetta no.</p>
                <p>Chiedi a Liccardi di:</p>
                <ul>
                    <li>accreditare plafond/saldo sul cliente sandbox <strong>K91DEMO</strong>, oppure</li>
                    <li>indicarti come caricare credito nel pannello TMS / procedura test.</li>
                </ul>
                <p class="sq-m-0">Nel codice non c’è un’API per ricaricare il saldo: va gestito lato Liccardi.</p>
            </div>
        @endif
    @endif

    @if (! empty($evidenza))
        <p class="sq-liccardi-esito-evidenza">{{ $evidenza }}</p>
    @endif

    @if (! empty($extra))
        <p class="sq-liccardi-esito-extra">{!! $extra !!}</p>
    @endif

    <details class="sq-liccardi-details">
        <summary>Mostra cosa abbiamo inviato e ricevuto (tecnico)</summary>

        <p class="sq-liccardi-tech-line">
            <strong>{{ $probe['method'] ?? '—' }}</strong>
            <code class="sq-code">{{ $probe['url'] ?? '—' }}</code>
        </p>

        <h4>Inviato</h4>
        <pre class="sq-pre-json">@if (! empty($probe['payload']) && is_array($probe['payload'])){{ json_encode($probe['payload'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}@else—@endif</pre>

        <h4>Ricevuto</h4>
        <pre class="sq-pre-json sq-liccardi-response-body">{{ $probe['rawBody'] ?? '—' }}</pre>
    </details>
</section>
