@php
    /** @var \App\Models\spedizione $s */
    /** @var array<string, mixed> $d */
@endphp
<div class="sq-etichetta-det-inner">
    <p class="sq-etichetta-dettaglio-meta sq-m-0">
        N. ordine: <strong>{{ $d['ordine_id'] ?: '—' }}</strong>
        @if ($d['data_pagamento_fmt'] !== '—')
            — {{ $d['data_pagamento_fmt'] }}
        @endif
        @if ($d['email'] !== '' && $d['email'] !== '—')
            — {{ $d['email'] }}
        @endif
    </p>

    @if (! empty($d['valore_merce']))
        <section class="sq-etichetta-det-valore-blocco" aria-label="Valore merce">
            <div>
                <span class="sq-etichetta-dettaglio-col-title">{{ $d['valore_merce']['label'] }}</span>
                <strong>{{ $d['valore_merce']['importo_fmt'] }}</strong>
            </div>
        </section>
    @endif

    <p class="sq-etichetta-dettaglio-servizio-band sq-m-0">
        <strong>{{ $d['codice_interno'] }} — {{ $d['servizio'] }}</strong>
    </p>

    <div class="sq-etichetta-dettaglio-indirizzi">
        <div class="sq-etichetta-dettaglio-col">
            <h3 class="sq-etichetta-dettaglio-col-title">Mittente</h3>
            <p class="sq-etichetta-det-nome">{{ $d['mittente']['nome'] }}</p>
            @foreach (explode("\n", $d['mittente']['indirizzo']) as $line)
                @if (trim($line) !== '')
                    <p class="sq-etichetta-det-indirizzo">{{ trim($line) }}</p>
                @endif
            @endforeach
            @if ($d['mittente']['telefono'] !== '—')
                <p class="sq-etichetta-det-tel">Tel: {{ $d['mittente']['telefono'] }}</p>
            @endif
        </div>
        <div class="sq-etichetta-dettaglio-col">
            <h3 class="sq-etichetta-dettaglio-col-title">Destinatario</h3>
            <p class="sq-etichetta-det-nome">{{ $d['destinatario']['nome'] }}</p>
            @foreach (explode("\n", $d['destinatario']['indirizzo']) as $line)
                @if (trim($line) !== '')
                    <p class="sq-etichetta-det-indirizzo">{{ trim($line) }}</p>
                @endif
            @endforeach
            @if ($d['destinatario']['telefono'] !== '—')
                <p class="sq-etichetta-det-tel">Tel: {{ $d['destinatario']['telefono'] }}</p>
            @endif
        </div>
    </div>

    <dl class="sq-etichetta-dettaglio-dl">
        <div>
            <dt>Costo totale spedizione</dt>
            <dd>{{ $d['importo_ivato_fmt'] }}</dd>
        </div>
        <div>
            <dt>Status</dt>
            <dd>{{ $d['stato_label'] }}</dd>
        </div>
        <div>
            <dt>N. tracking</dt>
            <dd>{{ $d['tracking'] !== '' ? $d['tracking'] : '—' }}</dd>
        </div>
    </dl>

    @if (! empty($d['etichetta_disponibile']) && ! empty($d['etichetta_url']))
        <div class="sq-etichetta-dettaglio-download-wrap">
            <a href="{{ $d['etichetta_url'] }}" class="sq-btn-primary sq-etichetta-det-download" target="_blank" rel="noopener noreferrer">
                Scarica etichetta
            </a>
        </div>
    @endif

    <p class="sq-etichetta-det-colli">{{ $d['colli'] }}</p>

    <div class="sq-etichetta-dettaglio-footer">
        <span class="sq-etichetta-det-footer-costo">
            Costo: {{ $d['importo_ivato_fmt'] }} ({{ $d['metodo_pagamento'] }})
        </span>
        <div class="sq-etichetta-det-footer-actions">
            <button type="button" class="sq-btn-secondary sq-modal-btn js-etichetta-dettaglio-close">Chiudi</button>
        </div>
    </div>
</div>
