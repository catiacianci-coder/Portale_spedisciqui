@extends('layouts.app')
@section('content')
@php
    $v = $input ?? [];
@endphp
<style>
    .sq-sc-etichetta button.sq-sc-btn {
        display: inline-block;
        min-width: 280px;
        margin: 0 10px 10px 0;
        padding: 16px 22px;
        font-size: 1.05rem;
        font-weight: 700;
        color: #fff !important;
        border: none;
        border-radius: 10px;
        cursor: pointer;
        box-shadow: 0 2px 6px rgba(0,0,0,.15);
    }
    .sq-sc-etichetta .sq-sc-btn--quote { background: #1565c0 !important; }
    .sq-sc-etichetta .sq-sc-btn--label { background: #2e7d32 !important; }
    .sq-sc-etichetta .sq-sc-btn--cancel { background: #c62828 !important; }
    .sq-sc-etichetta .sq-sc-btn:disabled { opacity: .5; cursor: not-allowed; }
    .sq-sc-etichetta .sq-sc-actions { display: flex; flex-wrap: wrap; gap: 12px; margin: 20px 0; }
    .sq-sc-etichetta .sq-sc-quote-table { width: 100%; border-collapse: collapse; margin: 16px 0; font-size: .92rem; }
    .sq-sc-etichetta .sq-sc-quote-table th,
    .sq-sc-etichetta .sq-sc-quote-table td { border: 1px solid #ddd; padding: 8px 10px; text-align: left; vertical-align: top; }
    .sq-sc-etichetta .sq-sc-quote-table tr.is-selected { background: #e8f5e9; }
    .sq-sc-etichetta .sq-sc-download {
        display: inline-block; padding: 14px 22px; background: #2e7d32; color: #fff !important;
        font-weight: 700; border-radius: 8px; text-decoration: none;
    }
    .sq-sc-etichetta fieldset { margin: 0 0 18px; padding: 14px 16px; border: 1px solid #ddd; border-radius: 8px; }
    .sq-sc-etichetta legend { font-weight: 700; padding: 0 6px; }
</style>
<div class="sq-sim-page sq-sc-etichetta">
    <div class="sq-sim-card">
        <h1 class="sq-sim-h1">Sendcloud — prova etichetta</h1>
        <p class="sq-mb-14">
            <strong>1 Preventivo</strong> · <strong>2 Seleziona servizio</strong> · <strong>3 Crea etichetta</strong> · <strong>4 Cancella</strong>
            (POST <code class="sq-code">/shipping-options</code> → POST <code class="sq-code">/shipments/announce</code> → POST <code class="sq-code">/shipments/{id}/cancel</code>).
            Tratta precompilata: <strong>Caserta 81100 → Napoli 80143</strong>, corriere Poste Delivery Express.
        </p>

        @if (! empty($sessionShipmentId))
            <p class="sq-liccardi-ok sq-mb-14">Ultimo <code class="sq-code">shipment id</code> in sessione: <strong>{{ $sessionShipmentId }}</strong></p>
        @endif

        @if (! $configured)
            <p class="sq-alert sq-alert--error">
                Chiavi Sendcloud mancanti. Imposta <code class="sq-code">sendcloud_public_key</code> e
                <code class="sq-code">sendcloud_secret_key</code> in
                <a href="{{ route('backoffice.parametri_globali.edit') }}">Parametri globali</a>.
            </p>
        @else
            <p class="sq-liccardi-ok">API collegata · {{ $apiBase }}</p>
        @endif

        <form method="POST" action="{{ route('test.sendcloud-etichetta') }}" class="sq-liccardi-form">
            @csrf

            <fieldset>
                <legend>Tratta (preventivo)</legend>
                <div class="sq-sim-row">
                    <div class="sq-sim-field"><label>CAP origine</label><input name="cap_origine" class="sq-sim-input" value="{{ $v['cap_origine'] }}"></div>
                    <div class="sq-sim-field"><label>Città origine</label><input name="citta_origine" class="sq-sim-input" value="{{ $v['citta_origine'] }}"></div>
                    <div class="sq-sim-field"><label>CAP destino</label><input name="cap_destino" class="sq-sim-input" value="{{ $v['cap_destino'] }}"></div>
                    <div class="sq-sim-field"><label>Città destino</label><input name="citta_destino" class="sq-sim-input" value="{{ $v['citta_destino'] }}"></div>
                </div>
                <div class="sq-sim-row">
                    <div class="sq-sim-field"><label>Carrier code</label><input name="carrier_code" class="sq-sim-input" value="{{ $v['carrier_code'] }}"></div>
                    <div class="sq-sim-field"><label>Contract ID (opz.)</label><input name="contract_id" class="sq-sim-input" value="{{ $v['contract_id'] }}" placeholder="auto da /contracts"></div>
                </div>
            </fieldset>

            <fieldset>
                <legend>Mittente (announce)</legend>
                <div class="sq-sim-row">
                    <div class="sq-sim-field"><label>CAP</label><input name="mitt_cap" class="sq-sim-input" value="{{ $v['mitt_cap'] }}"></div>
                    <div class="sq-sim-field"><label>Città</label><input name="mitt_citta" class="sq-sim-input" value="{{ $v['mitt_citta'] }}"></div>
                    <div class="sq-sim-field"><label>Prov.</label><input name="mitt_provincia" class="sq-sim-input" maxlength="2" value="{{ $v['mitt_provincia'] }}"></div>
                </div>
                <div class="sq-sim-row">
                    <div class="sq-sim-field"><label>Via</label><input name="mitt_via" class="sq-sim-input" value="{{ $v['mitt_via'] }}"></div>
                    <div class="sq-sim-field"><label>Civico</label><input name="mitt_civico" class="sq-sim-input" value="{{ $v['mitt_civico'] }}"></div>
                    <div class="sq-sim-field"><label>Nome</label><input name="mitt_nome" class="sq-sim-input" value="{{ $v['mitt_nome'] }}"></div>
                    <div class="sq-sim-field"><label>Cognome</label><input name="mitt_cognome" class="sq-sim-input" value="{{ $v['mitt_cognome'] }}"></div>
                </div>
                <div class="sq-sim-row">
                    <div class="sq-sim-field"><label>Azienda</label><input name="mitt_azienda" class="sq-sim-input" value="{{ $v['mitt_azienda'] }}"></div>
                    <div class="sq-sim-field"><label>Telefono</label><input name="mitt_telefono" class="sq-sim-input" value="{{ $v['mitt_telefono'] }}"></div>
                    <div class="sq-sim-field"><label>Email</label><input name="mitt_email" class="sq-sim-input" value="{{ $v['mitt_email'] }}"></div>
                </div>
            </fieldset>

            <fieldset>
                <legend>Destinatario (announce)</legend>
                <div class="sq-sim-row">
                    <div class="sq-sim-field"><label>CAP</label><input name="dest_cap" class="sq-sim-input" value="{{ $v['dest_cap'] }}"></div>
                    <div class="sq-sim-field"><label>Città</label><input name="dest_citta" class="sq-sim-input" value="{{ $v['dest_citta'] }}"></div>
                    <div class="sq-sim-field"><label>Prov.</label><input name="dest_provincia" class="sq-sim-input" maxlength="2" value="{{ $v['dest_provincia'] }}"></div>
                </div>
                <div class="sq-sim-row">
                    <div class="sq-sim-field"><label>Via</label><input name="dest_via" class="sq-sim-input" value="{{ $v['dest_via'] }}"></div>
                    <div class="sq-sim-field"><label>Civico</label><input name="dest_civico" class="sq-sim-input" value="{{ $v['dest_civico'] }}"></div>
                    <div class="sq-sim-field"><label>Nome</label><input name="dest_nome" class="sq-sim-input" value="{{ $v['dest_nome'] }}"></div>
                    <div class="sq-sim-field"><label>Cognome</label><input name="dest_cognome" class="sq-sim-input" value="{{ $v['dest_cognome'] }}"></div>
                </div>
                <div class="sq-sim-row">
                    <div class="sq-sim-field"><label>Telefono</label><input name="dest_telefono" class="sq-sim-input" value="{{ $v['dest_telefono'] }}"></div>
                    <div class="sq-sim-field"><label>Email</label><input name="dest_email" class="sq-sim-input" value="{{ $v['dest_email'] }}"></div>
                </div>
            </fieldset>

            <fieldset>
                <legend>Collo</legend>
                <div class="sq-sim-row">
                    <div class="sq-sim-field"><label>Peso (kg)</label><input name="peso" class="sq-sim-input" value="{{ $v['peso'] }}"></div>
                    <div class="sq-sim-field"><label>Altezza cm</label><input name="altezza" class="sq-sim-input" value="{{ $v['altezza'] }}"></div>
                    <div class="sq-sim-field"><label>Larghezza cm</label><input name="larghezza" class="sq-sim-input" value="{{ $v['larghezza'] }}"></div>
                    <div class="sq-sim-field"><label>Profondità cm</label><input name="spessore" class="sq-sim-input" value="{{ $v['spessore'] }}"></div>
                    <div class="sq-sim-field"><label>Assicurazione €</label><input name="valore_assicurazione" class="sq-sim-input" value="{{ $v['valore_assicurazione'] }}"></div>
                </div>
            </fieldset>

            <fieldset>
                <legend>Servizio selezionato</legend>
                <p class="sq-text-muted sq-mb-14">Esegui sempre il preventivo prima: il codice va copiato dalla risposta API (non dal listino CSV).</p>
                <input name="shipping_option_code" class="sq-sim-input" style="max-width:100%"
                       value="{{ $v['shipping_option_code'] }}" placeholder="(compilato dal preventivo)">

                @if (! empty($quoteRows))
                    <table class="sq-sc-quote-table">
                        <thead>
                            <tr>
                                <th></th>
                                <th>Codice</th>
                                <th>Nome</th>
                                <th>Prezzo</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($quoteRows as $row)
                                @php
                                    $code = (string) ($row['code'] ?? '');
                                    $selected = $code === ($v['shipping_option_code'] ?? '');
                                @endphp
                                <tr class="{{ $selected ? 'is-selected' : '' }}">
                                    <td>
                                        <input type="radio" name="shipping_option_pick" value="{{ $code }}"
                                               @checked($selected)
                                               onclick="document.querySelector('[name=shipping_option_code]').value=this.value">
                                    </td>
                                    <td><code class="sq-code">{{ $code }}</code></td>
                                    <td>{{ $row['name'] ?? '—' }}</td>
                                    <td>{{ $row['price'] ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </fieldset>

            <fieldset>
                <legend>Cancellazione</legend>
                <p class="sq-text-muted sq-mb-14">Usa l’ultimo shipment id in sessione oppure incollane uno manualmente.</p>
                <input name="shipment_id" class="sq-sim-input" style="max-width:100%"
                       value="{{ old('shipment_id', $sessionShipmentId ?? '') }}"
                       placeholder="shipment id Sendcloud">
            </fieldset>

            <div class="sq-sc-actions">
                <button type="submit" name="azione" value="preventivo" class="sq-sc-btn sq-sc-btn--quote" @disabled(! $configured)>
                    1 — Ottieni preventivo
                </button>
                <button type="submit" name="azione" value="etichetta" class="sq-sc-btn sq-sc-btn--label" @disabled(! $configured)>
                    2 — Crea etichetta (announce)
                </button>
                <button type="submit" name="azione" value="cancella" class="sq-sc-btn sq-sc-btn--cancel" @disabled(! $configured)>
                    3 — Cancella spedizione
                </button>
            </div>
        </form>

        @if (($azione ?? '') === 'preventivo' && is_array($preventivo))
            @include('test.partials.liccardi-esito', [
                'titolo' => 'Risultato preventivo Sendcloud',
                'ok' => ($preventivo['ok'] ?? false),
                'httpStatus' => $preventivo['httpStatus'] ?? null,
                'errorMessage' => $preventivo['errorMessage'] ?? null,
                'evidenza' => count($quoteRows ?? []).' servizi Poste Express in tabella (se presenti).',
                'extra' => null,
                'probe' => $preventivo,
            ])
        @endif

        @if (($azione ?? '') === 'cancella' && is_array($cancellazione))
            @php
                $cancelHints = $cancellazione['hints'] ?? [];
                $cancelSid = $cancelHints['shipmentId'] ?? null;
            @endphp
            @include('test.partials.liccardi-esito', [
                'titolo' => 'Risultato cancellazione (cancel)',
                'ok' => ($cancellazione['ok'] ?? false),
                'httpStatus' => $cancellazione['httpStatus'] ?? null,
                'errorMessage' => $cancellazione['errorMessage'] ?? null,
                'evidenza' => $cancelSid ? 'Shipment ID: '.$cancelSid : '—',
                'extra' => ($cancellazione['ok'] ?? false)
                    ? '<p class="sq-liccardi-ok sq-m-0">Spedizione annullata su Sendcloud. PDF di test rimosso se presente.</p>'
                    : null,
                'probe' => $cancellazione,
            ])
        @endif

        @if (($azione ?? '') === 'etichetta' && is_array($etichetta))
            @php
                $hints = $etichetta['hints'] ?? [];
                $sid = $hints['shipmentId'] ?? null;
                $trk = $hints['tracking'] ?? null;
                $codeUsed = $hints['shipping_option_code'] ?? null;
                $codeSwap = $hints['code_sostituito'] ?? null;
            @endphp
            @include('test.partials.liccardi-esito', [
                'titolo' => 'Risultato creazione etichetta (announce)',
                'ok' => ($etichetta['ok'] ?? false),
                'httpStatus' => $etichetta['httpStatus'] ?? null,
                'errorMessage' => $etichetta['errorMessage'] ?? null,
                'evidenza' => ($codeUsed ? 'Codice: '.$codeUsed : '—')
                    .($codeSwap ? ' · Sostituito: '.$codeSwap : '')
                    .($sid ? ' · Shipment ID: '.$sid : '')
                    .($trk ? ' · Tracking: '.$trk : ''),
                'extra' => ! empty($pdfUrl)
                    ? '<a class="sq-sc-download" href="'.e($pdfUrl).'" target="_blank" rel="noopener">Apri etichetta PDF</a>'
                    : null,
                'probe' => $etichetta,
            ])
        @endif
    </div>
</div>
@endsection
