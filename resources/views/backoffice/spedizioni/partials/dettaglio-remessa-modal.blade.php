@php
    /** @var \App\Models\spedizione $s */
    /** @var array<string, mixed> $d */
@endphp
<div class="sq-bo-etq-detalhe-inner">
    @if (($d['context'] ?? '') === 'backoffice' && ! empty($d['etichetta_erro']))
        <section class="sq-bo-etq-erro-box sq-bo-etq-erro-box--top" aria-labelledby="sq-bo-etq-erro-titulo">
            <h3 class="sq-bo-etq-erro-title" id="sq-bo-etq-erro-titulo">{{ $d['etichetta_erro_titulo'] ?? 'Errore nella generazione dell\'etichetta' }}</h3>
            <pre class="sq-bo-etq-erro-pre">{{ $d['etichetta_erro'] }}</pre>
        </section>
    @endif

    <p class="sq-bo-etq-detalhe-pedido-titulo">
        N. ordine: <strong>{{ $d['ordine_id'] ?: '—' }}</strong>
        @if ($d['data_pagamento_fmt'] !== '—')
            — {{ $d['data_pagamento_fmt'] }}
        @endif
        @if ($d['email'] !== '' && $d['email'] !== '—')
            — <span class="sq-bo-etq-detalhe-email">{{ $d['email'] }}</span>
        @endif
        @if (! empty($d['user_id']))
            <span class="sq-bo-etq-detalhe-cliente"> · Cliente #{{ $d['user_id'] }}</span>
        @endif
    </p>

    @if (! empty($d['valore_merce']))
        <section class="sq-bo-etq-detalhe-sec" aria-label="Valore merce">
            <div>
                <span class="sq-bo-etq-detalhe-lab">{{ $d['valore_merce']['label'] }}</span>
                <span class="sq-bo-etq-detalhe-val"><strong>{{ $d['valore_merce']['importo_fmt'] }}</strong></span>
            </div>
        </section>
    @endif

    <div class="sq-bo-etq-detalhe-servico-bar">
        <p class="sq-bo-etq-detalhe-servico-linha"><strong>{{ $d['codice_interno'] }} — {{ $d['servizio'] }}</strong></p>
        @if (! empty($d['editabile_bo']))
            <div class="sq-bo-etq-detalhe-toolbar">
                @if (! empty($d['opcoes_url']))
                    <button type="button" class="sq-btn-secondary sq-btn-sm sq-bo-etq-btn-opcoes js-bo-abrir-opcoes" data-opcoes-url="{{ $d['opcoes_url'] }}">Opzioni</button>
                @endif
                <button type="button" class="sq-btn-secondary sq-btn-sm sq-bo-etq-btn-upload" onclick="document.getElementById('sq-bo-etq-pdf-{{ $s->id }}').click();">Carica etichetta</button>
            </div>
        @endif
    </div>

    <div class="sq-bo-etq-detalhe-pessoas">
        <section class="sq-bo-etq-detalhe-pessoa">
            <h4 class="sq-bo-etq-detalhe-pessoa-title">Mittente</h4>
            <p class="sq-bo-etq-detalhe-nome">{{ $d['mittente']['nome'] }}</p>
            @foreach (explode("\n", $d['mittente']['indirizzo']) as $line)
                @if (trim($line) !== '')
                    <p>{{ trim($line) }}</p>
                @endif
            @endforeach
            @if ($d['mittente']['telefono'] !== '—')
                <p>Tel: {{ $d['mittente']['telefono'] }}</p>
            @endif
        </section>
        <section class="sq-bo-etq-detalhe-pessoa">
            <h4 class="sq-bo-etq-detalhe-pessoa-title">Destinatario</h4>
            <p class="sq-bo-etq-detalhe-nome">{{ $d['destinatario']['nome'] }}</p>
            @foreach (explode("\n", $d['destinatario']['indirizzo']) as $line)
                @if (trim($line) !== '')
                    <p>{{ trim($line) }}</p>
                @endif
            @endforeach
            @if ($d['destinatario']['telefono'] !== '—')
                <p>Tel: {{ $d['destinatario']['telefono'] }}</p>
            @endif
        </section>
    </div>

    <div class="sq-bo-etq-detalhe-metricas">
        <div class="sq-bo-etq-metrica-col">
            <p><span class="sq-bo-etq-detalhe-lab">Costo totale spedizione</span> {{ $d['importo_ivato_fmt'] }}</p>
            <p><span class="sq-bo-etq-detalhe-lab">Status</span> {{ $d['stato_label'] }}</p>
        </div>
        <div class="sq-bo-etq-metrica-col sq-bo-etq-rastreio-col">
            @if (! empty($d['editabile_bo']) && ! empty($d['manual_url']))
                <form method="post" action="{{ $d['manual_url'] }}" class="sq-bo-etq-rastreio-form">
                    @csrf
                    <label class="sq-bo-etq-detalhe-lab" for="sq-bo-etq-rast-{{ $s->id }}">N. tracking</label>
                    <div class="sq-bo-etq-rastreio-row">
                        <input
                            class="sq-bo-etq-inp sq-bo-etq-inp-rast"
                            id="sq-bo-etq-rast-{{ $s->id }}"
                            type="text"
                            name="codigo_rastreio"
                            value="{{ old('codigo_rastreio', $d['tracking'] ?? '') }}"
                            maxlength="80"
                            autocomplete="off"
                            placeholder="Codice tracking"
                        >
                        <button type="submit" class="sq-btn-secondary sq-btn-sm" title="Salva tracking" aria-label="Salva tracking">✎</button>
                    </div>
                </form>
                <form method="post" action="{{ $d['manual_url'] }}" enctype="multipart/form-data" id="sq-bo-etq-pdf-form-{{ $s->id }}" class="sq-bo-etq-pdf-form">
                    @csrf
                    <input type="hidden" name="codigo_rastreio" value="{{ old('codigo_rastreio', $d['tracking'] ?? '') }}" id="sq-bo-etq-pdf-codigo-{{ $s->id }}">
                    <input id="sq-bo-etq-pdf-{{ $s->id }}" type="file" name="arquivo_etiqueta" accept="application/pdf,.pdf" hidden onchange="sqBoSyncRastreioPdfUpload({{ $s->id }});">
                </form>
            @else
                <p><span class="sq-bo-etq-detalhe-lab">N. tracking</span> {{ ($d['tracking'] ?? '') !== '' ? $d['tracking'] : '—' }}</p>
            @endif
            @if (! empty($d['pdf_url']))
                <p class="sq-bo-etq-download-wrap">
                    <a href="{{ $d['pdf_url'] }}" class="sq-bo-etq-btn-pdf" target="_blank" rel="noopener noreferrer">Scarica etichetta</a>
                </p>
            @endif
            @if (! empty($d['rastro_status']))
                <p class="sq-bo-etq-rastro-status"><span class="sq-bo-etq-detalhe-lab">Status tracking</span> {{ $d['rastro_status'] }}</p>
            @endif
        </div>
    </div>

    <p class="sq-bo-etq-detalhe-caixa">{{ $d['colli'] }}</p>

    <footer class="sq-bo-etq-detalhe-footer">
        <span class="sq-bo-etq-detalhe-custo-final">
            Costo: {{ $d['importo_ivato_fmt'] }} ({{ $d['metodo_pagamento'] }})
        </span>
        <button type="button" class="sq-btn-secondary sq-btn-sm sq-bo-etq-btn-fechar js-bo-fechar-modal js-etichetta-dettaglio-close">Chiudi</button>
    </footer>
</div>
