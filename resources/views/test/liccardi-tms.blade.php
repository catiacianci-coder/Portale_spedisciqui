@extends('layouts.app')
@section('content')
@php
    $v = $input ?? [];
    $apiBaseStr = (string) ($apiBase ?? '');
    $isSandbox = str_contains($apiBaseStr, 'tms.test.') || str_contains($apiBaseStr, '.test.');
    $ambienteLabel = $isSandbox ? 'SANDBOX (test)' : 'PRODUZIONE';
@endphp
<style>
    .sq-liccardi-page button.sq-liccardi-btn {
        display: inline-block;
        min-width: 260px;
        margin: 0 10px 10px 0;
        padding: 16px 22px;
        font-size: 1.05rem;
        font-weight: 700;
        color: #fff !important;
        border: 2px solid transparent;
        border-radius: 10px;
        cursor: pointer;
        box-shadow: 0 2px 6px rgba(0,0,0,.15);
        appearance: button;
        -webkit-appearance: button;
    }
    .sq-liccardi-page button.sq-liccardi-btn--quote {
        background: #1565c0 !important;
    }
    .sq-liccardi-page button.sq-liccardi-btn--label {
        background: #2e7d32 !important;
    }
    .sq-liccardi-page button.sq-liccardi-btn--delete {
        background: #c62828 !important;
    }
    .sq-liccardi-page button.sq-liccardi-btn:hover:not(:disabled) {
        filter: brightness(1.08);
    }
    .sq-liccardi-page button.sq-liccardi-btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
    .sq-liccardi-page .sq-liccardi-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        margin: 20px 0 12px;
    }
    .sq-liccardi-page .sq-liccardi-download {
        display: inline-block;
        padding: 14px 22px;
        background: #2e7d32;
        color: #fff !important;
        font-weight: 700;
        border-radius: 8px;
        text-decoration: none;
        box-shadow: 0 2px 6px rgba(0,0,0,.12);
    }
    .sq-liccardi-saldo-help {
        margin: 12px 0 0;
        padding: 14px 16px;
        background: #fff8e1;
        border: 1px solid #ffcc80;
        border-radius: 8px;
        text-align: left;
        font-size: 0.95rem;
        line-height: 1.45;
    }
    .sq-liccardi-saldo-help ul { margin: 8px 0; padding-left: 1.2rem; }
</style>
<div class="sq-sim-page sq-liccardi-page">
    <div class="sq-sim-card sq-liccardi-card">
        <h1 class="sq-sim-h1">Liccardi — prova API</h1>
        <p class="sq-liccardi-lead">
            <strong>1 Preventivo</strong> · <strong>2 Crea+PDF</strong> · <strong>3 Solo PDF</strong> · <strong>4 Elimina</strong>.
            Cliente: <code class="sq-code">{{ \App\Services\ParametriApiConfig::liccardiTmsCompanyId() }}</code>
        </p>
        @if ($isSandbox)
            <p class="sq-liccardi-saldo-help sq-mb-18">
                <strong>Ambiente sandbox:</strong> il preventivo può andare anche senza saldo; la creazione etichetta scala il plafond del cliente TMS.
            </p>
        @endif
        @if (! empty($sessionShipmentId))
            <p class="sq-liccardi-ok sq-mb-14">Ultimo <code class="sq-code">spedizioneId</code> in sessione: <strong>{{ $sessionShipmentId }}</strong></p>
        @endif

        @if (! $configured)
            <p class="sq-alert sq-alert--error">
                API non configurata. Imposta <code class="sq-code">liccardi_tms_api_key</code>,
                <code class="sq-code">liccardi_tms_company_id</code> e
                <code class="sq-code">liccardi_tms_api_base</code> in
                <a href="{{ route('backoffice.parametri_globali.edit') }}">Parametri globali</a> (back-office).
            </p>
        @else
            <p class="sq-liccardi-ok">
                API collegata · <strong>{{ $ambienteLabel }}</strong> · {{ $apiBase }}
            </p>
        @endif

        <form method="POST" action="{{ route('test.liccardi-tms') }}" class="sq-liccardi-form">
            @csrf

            <fieldset class="sq-liccardi-fieldset">
                <legend>Mittente (ritiro)</legend>
                <div class="sq-sim-row">
                    <div class="sq-sim-field"><label>CAP</label><input name="cap_origine" class="sq-sim-input" value="{{ $v['cap_origine'] }}"></div>
                    <div class="sq-sim-field"><label>Città</label><input name="citta_origine" class="sq-sim-input" value="{{ $v['citta_origine'] }}"></div>
                    <div class="sq-sim-field"><label>Prov.</label><input name="pv_origine" class="sq-sim-input" maxlength="2" value="{{ $v['pv_origine'] }}"></div>
                </div>
                <div class="sq-sim-row">
                    <div class="sq-sim-field"><label>Via</label><input name="via_origine" class="sq-sim-input" value="{{ $v['via_origine'] }}"></div>
                    <div class="sq-sim-field"><label>Civico</label><input name="civico_origine" class="sq-sim-input" value="{{ $v['civico_origine'] }}"></div>
                </div>
            </fieldset>

            <fieldset class="sq-liccardi-fieldset">
                <legend>Destinatario (consegna)</legend>
                <div class="sq-sim-row">
                    <div class="sq-sim-field"><label>CAP</label><input name="cap_destino" class="sq-sim-input" value="{{ $v['cap_destino'] }}"></div>
                    <div class="sq-sim-field"><label>Città</label><input name="citta_destino" class="sq-sim-input" value="{{ $v['citta_destino'] }}"></div>
                    <div class="sq-sim-field"><label>Prov.</label><input name="pv_destino" class="sq-sim-input" maxlength="2" value="{{ $v['pv_destino'] }}"></div>
                </div>
                <div class="sq-sim-row">
                    <div class="sq-sim-field"><label>Via</label><input name="via_destino" class="sq-sim-input" value="{{ $v['via_destino'] }}"></div>
                    <div class="sq-sim-field"><label>Civico</label><input name="civico_destino" class="sq-sim-input" value="{{ $v['civico_destino'] }}"></div>
                    <div class="sq-sim-field"><label>Nome</label><input name="destinatario_nome" class="sq-sim-input" value="{{ $v['destinatario_nome'] }}"></div>
                </div>
            </fieldset>

            <fieldset class="sq-liccardi-fieldset">
                <legend>Collo</legend>
                <div class="sq-sim-row">
                    <div class="sq-sim-field"><label>Peso (kg)</label><input name="peso" class="sq-sim-input" value="{{ $v['peso'] }}"></div>
                    <div class="sq-sim-field"><label>Altezza cm</label><input name="altezza" class="sq-sim-input" value="{{ $v['altezza'] }}"></div>
                    <div class="sq-sim-field"><label>Larghezza cm</label><input name="larghezza" class="sq-sim-input" value="{{ $v['larghezza'] }}"></div>
                    <div class="sq-sim-field"><label>Profondità cm</label><input name="spessore" class="sq-sim-input" value="{{ $v['spessore'] }}"></div>
                </div>
                <input type="hidden" name="codice_servizio" value="{{ $v['codice_servizio'] }}">
                <input type="hidden" name="mittente_azienda" value="{{ $v['mittente_azienda'] }}">
            </fieldset>

            <div class="sq-liccardi-actions">
                <button type="submit" name="azione" value="preventivo" class="sq-liccardi-btn sq-liccardi-btn--quote" @disabled(! $configured)>
                    1 — Ottieni preventivo
                </button>
                <button type="submit" name="azione" value="etichetta" class="sq-liccardi-btn sq-liccardi-btn--label" @disabled(! $configured)>
                    2 — Crea spedizione e etichetta PDF
                </button>
            </div>
        </form>

        <hr class="sq-sim-hr sq-mt-24">

        <h2 class="sq-sim-h2">3 — Solo etichetta PDF (spedizione esistente)</h2>
        <p class="sq-mb-14 sq-text-muted">
            <code class="sq-code">GET /spedizioni/{spedizioneId}/etichette/pdf</code> — senza creare una nuova spedizione.
        </p>
        <form method="POST" action="{{ route('test.liccardi-tms') }}" class="sq-liccardi-form">
            @csrf
            <div class="sq-sim-row">
                <div class="sq-sim-field" style="max-width:320px">
                    <label><strong>spedizioneId</strong></label>
                    <input name="spedizione_id" class="sq-sim-input" placeholder="es. 15602866"
                           value="{{ old('spedizione_id', $v['spedizione_id'] ?? '') }}">
                </div>
            </div>
            <div class="sq-liccardi-actions">
                <button type="submit" name="azione" value="pdf_solo" class="sq-liccardi-btn sq-liccardi-btn--label" @disabled(! $configured)>
                    3 — Scarica etichetta PDF
                </button>
            </div>
        </form>

        <hr class="sq-sim-hr sq-mt-24">

        <h2 class="sq-sim-h2">4 — Elimina spedizione</h2>
        <p class="sq-mb-14 sq-text-muted">
            <code class="sq-code">DELETE /spedizioni</code> con body <code class="sq-code">{"spedizioneId": …}</code>.
            Serve un ID valido (da una creazione precedente). Puoi testare anche con un ID inventato per vedere la risposta API.
        </p>
        <form method="POST" action="{{ route('test.liccardi-tms') }}" class="sq-liccardi-form">
            @csrf
            <div class="sq-sim-row">
                <div class="sq-sim-field" style="max-width:320px">
                    <label><strong>spedizioneId</strong></label>
                    <input name="spedizione_id" class="sq-sim-input" placeholder="es. 12345"
                           value="{{ old('spedizione_id', $v['spedizione_id'] ?? '') }}">
                </div>
            </div>
            <div class="sq-liccardi-actions">
                <button type="submit" name="azione" value="elimina" class="sq-liccardi-btn sq-liccardi-btn--delete" @disabled(! $configured)>
                    4 — Elimina spedizione su TMS
                </button>
            </div>
        </form>

        @if (($azione ?? '') === 'preventivo' && is_array($preventivo))
            @include('test.partials.liccardi-esito', [
                'titolo' => 'Risultato preventivo',
                'ok' => ($preventivo['ok'] ?? false),
                'httpStatus' => $preventivo['httpStatus'] ?? null,
                'errorMessage' => $preventivo['errorMessage'] ?? null,
                'evidenza' => ! empty($preventivo['prezzoEstratto'])
                    ? 'Prezzo stimato: '.$preventivo['prezzoEstratto']
                    : 'Controlla il JSON in risposta per l\'importo.',
                'extra' => null,
                'probe' => $preventivo,
            ])
        @endif

        @if (($azione ?? '') === 'etichetta' && is_array($etichetta))
            @php
                $cr = $etichetta['create'] ?? [];
                $pdfUrl = $etichetta['pdfUrl'] ?? null;
                $ldv = $cr['hints']['courierLdv'] ?? null;
                $sid = $cr['hints']['spedizioneId'] ?? null;
            @endphp
            @include('test.partials.liccardi-esito', [
                'titolo' => 'Risultato creazione spedizione',
                'ok' => ($cr['ok'] ?? false),
                'httpStatus' => $cr['httpStatus'] ?? null,
                'errorMessage' => $cr['errorMessage'] ?? null,
                'evidenza' => ($sid ? 'ID spedizione: '.$sid : '—').($ldv ? ' · LDV: '.$ldv : ''),
                'extra' => $pdfUrl
                    ? '<a class="sq-liccardi-download" href="'.e($pdfUrl).'" target="_blank" rel="noopener">Scarica etichetta PDF</a>'
                    : null,
                'probe' => $cr,
            ])
            @if (is_array($etichetta['pdf'] ?? null))
                @include('test.partials.liccardi-esito', [
                    'titolo' => 'Download etichetta (PDF)',
                    'ok' => ($etichetta['pdf']['ok'] ?? false),
                    'httpStatus' => $etichetta['pdf']['httpStatus'] ?? null,
                    'errorMessage' => $etichetta['pdf']['errorMessage'] ?? null,
                    'evidenza' => $pdfUrl ? 'PDF pronto.' : 'PDF non ricevuto o non salvato.',
                    'extra' => null,
                    'probe' => $etichetta['pdf'],
                ])
            @endif
        @endif

        @if (($azione ?? '') === 'pdf_solo' && is_array($pdfSolo))
            @php $pdfUrlSolo = $pdfSolo['pdfUrl'] ?? null; @endphp
            @include('test.partials.liccardi-esito', [
                'titolo' => 'Etichetta PDF (spedizione esistente)',
                'ok' => ($pdfSolo['pdf']['ok'] ?? false) && $pdfUrlSolo,
                'httpStatus' => $pdfSolo['pdf']['httpStatus'] ?? null,
                'errorMessage' => $pdfUrlSolo ? null : ($pdfSolo['pdf']['errorMessage'] ?? 'PDF non ricevuto o non salvato.'),
                'evidenza' => 'spedizioneId: '.($pdfSolo['spedizioneId'] ?? '—'),
                'extra' => $pdfUrlSolo
                    ? '<a class="sq-liccardi-download" href="'.e($pdfUrlSolo).'" target="_blank" rel="noopener">Scarica etichetta PDF</a>'
                    : null,
                'probe' => $pdfSolo['pdf'] ?? [],
            ])
        @endif

        @if (($azione ?? '') === 'elimina' && is_array($elimina))
            @include('test.partials.liccardi-esito', [
                'titolo' => 'Risultato eliminazione',
                'ok' => ($elimina['ok'] ?? false),
                'httpStatus' => $elimina['httpStatus'] ?? null,
                'errorMessage' => $elimina['errorMessage'] ?? null,
                'evidenza' => ($elimina['ok'] ?? false)
                    ? 'Spedizione eliminata su TMS Liccardi.'
                    : 'Eliminazione non riuscita (ID inesistente, spedizione già chiusa, ecc.).',
                'extra' => null,
                'probe' => $elimina,
            ])
        @endif
    </div>
</div>
@endsection
