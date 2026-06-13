@extends('layouts.app')
@section('content')
<div class="sq-sim-page">
    <div class="sq-sim-card">
        <h1 class="sq-sim-h1">Prova API Spedisci.online</h1>
        <p class="sq-mb-14 sq-text-muted">
            Base <code class="sq-code">{{ $apiBase }}</code>
            — piattaforma <strong>{{ $piattaforma }}</strong>.
            Nessun controllo sulle tariffe interne del portale.
        </p>

        @if (! $configured)
            <p class="sq-alert sq-alert--info-warm">API key non configurata in parametri globali (<code class="sq-code">spedisci_online_quick_api_key</code>).</p>
        @endif

        <h2 class="sq-sim-h2">1. Preventivi — POST /shipping/rates</h2>
        <form method="POST" action="{{ route('test.spedisci-online') }}" class="sq-sim-form sq-mb-24">
            @csrf
            <input type="hidden" name="azione" value="rates">
            <div class="sq-sim-row">
                <div class="sq-sim-field">
                    <label for="rates_cap_origine"><strong>CAP origine</strong></label>
                    <input id="rates_cap_origine" name="cap_origine" class="sq-sim-input"
                           value="{{ old('cap_origine', $input['cap_origine']) }}">
                </div>
                <div class="sq-sim-field">
                    <label for="rates_cap_destino"><strong>CAP destino</strong></label>
                    <input id="rates_cap_destino" name="cap_destino" class="sq-sim-input"
                           value="{{ old('cap_destino', $input['cap_destino']) }}">
                </div>
            </div>
            <div class="sq-sim-row">
                <div class="sq-sim-field">
                    <label for="rates_spessore"><strong>Spessore (cm)</strong></label>
                    <input id="rates_spessore" name="spessore" class="sq-sim-input"
                           value="{{ old('spessore', $input['spessore']) }}">
                </div>
                <div class="sq-sim-field">
                    <label for="rates_larghezza"><strong>Larghezza (cm)</strong></label>
                    <input id="rates_larghezza" name="larghezza" class="sq-sim-input"
                           value="{{ old('larghezza', $input['larghezza']) }}">
                </div>
                <div class="sq-sim-field">
                    <label for="rates_altezza"><strong>Altezza (cm)</strong></label>
                    <input id="rates_altezza" name="altezza" class="sq-sim-input"
                           value="{{ old('altezza', $input['altezza']) }}">
                </div>
                <div class="sq-sim-field">
                    <label for="rates_peso"><strong>Peso (kg)</strong></label>
                    <input id="rates_peso" name="peso" class="sq-sim-input"
                           value="{{ old('peso', $input['peso']) }}">
                </div>
            </div>
            <button type="submit" class="sq-sim-btn">Chiama /shipping/rates</button>
        </form>

        <h2 class="sq-sim-h2">2. Ritiro corriere — POST /pickup/create</h2>
        @if ($corriereRitiro)
            <p class="sq-mb-14">
                Corriere portale <strong>#{{ $corriereRitiro->id }}</strong>
                — {{ $corriereRitiro->nome_visualizzato }}
                @if (trim((string) $corriereRitiro->carrier_code) === '' || trim((string) $corriereRitiro->contract_code) === '')
                    <span class="sq-alert sq-alert--info-warm">Codici API mancanti in tabella <code class="sq-code">corrieres</code>.</span>
                @endif
            </p>
        @else
            <p class="sq-alert sq-alert--info-warm sq-mb-14">Corriere id=4 non trovato in <code class="sq-code">corrieres</code>.</p>
        @endif
        <p class="sq-text-muted sq-mb-14">
            <code class="sq-code">carrierCode</code> e <code class="sq-code">contractCode</code> da tabella corrieres.
            <code class="sq-code">shipmentId</code> obbligatorio (numero spedizione / LDV).
        </p>
        <form method="POST" action="{{ route('test.spedisci-online') }}" class="sq-sim-form">
            @csrf
            <input type="hidden" name="azione" value="pickup">
            <div class="sq-sim-row">
                <div class="sq-sim-field">
                    <label for="pickup_cap_origine"><strong>CAP ritiro (mittente)</strong></label>
                    <input id="pickup_cap_origine" name="cap_origine" class="sq-sim-input"
                           value="{{ old('cap_origine', $input['cap_origine']) }}">
                </div>
                <div class="sq-sim-field">
                    <label for="pickup_tracking"><strong>shipmentId</strong> (obbl.)</label>
                    <input id="pickup_tracking" name="tracking" class="sq-sim-input"
                           placeholder="es. 3UW1SS5717556"
                           value="{{ old('tracking', $input['tracking']) }}">
                </div>
            </div>
            <div class="sq-sim-row">
                <div class="sq-sim-field">
                    <label for="pickup_carrier_code"><strong>carrierCode</strong></label>
                    <input id="pickup_carrier_code" name="pickup_carrier_code" class="sq-sim-input"
                           value="{{ old('pickup_carrier_code', $input['pickup_carrier_code']) }}">
                </div>
                <div class="sq-sim-field">
                    <label for="pickup_contract_code"><strong>contractCode</strong></label>
                    <input id="pickup_contract_code" name="pickup_contract_code" class="sq-sim-input"
                           value="{{ old('pickup_contract_code', $input['pickup_contract_code']) }}">
                </div>
            </div>
            <div class="sq-sim-row">
                <div class="sq-sim-field">
                    <label for="data_ritiro"><strong>Data ritiro</strong></label>
                    <input id="data_ritiro" name="data_ritiro" type="date" class="sq-sim-input"
                           value="{{ old('data_ritiro', $input['data_ritiro']) }}">
                </div>
                <div class="sq-sim-field">
                    <label for="ora_inizio"><strong>pickupTime</strong> (HH:MM)</label>
                    <input id="ora_inizio" name="ora_inizio" class="sq-sim-input"
                           value="{{ old('ora_inizio', $input['ora_inizio']) }}">
                </div>
                <div class="sq-sim-field">
                    <label for="colli"><strong>Colli</strong></label>
                    <input id="colli" name="colli" class="sq-sim-input"
                           value="{{ old('colli', $input['colli']) }}">
                </div>
                <div class="sq-sim-field">
                    <label for="pickup_peso"><strong>Peso totale (kg)</strong></label>
                    <input id="pickup_peso" name="peso" class="sq-sim-input"
                           value="{{ old('peso', $input['peso']) }}">
                </div>
            </div>
            <div class="sq-sim-row">
                <div class="sq-sim-field">
                    <label for="pickup_spessore"><strong>Spessore (cm)</strong></label>
                    <input id="pickup_spessore" name="spessore" class="sq-sim-input"
                           value="{{ old('spessore', $input['spessore']) }}">
                </div>
                <div class="sq-sim-field">
                    <label for="pickup_larghezza"><strong>Larghezza (cm)</strong></label>
                    <input id="pickup_larghezza" name="larghezza" class="sq-sim-input"
                           value="{{ old('larghezza', $input['larghezza']) }}">
                </div>
                <div class="sq-sim-field">
                    <label for="pickup_altezza"><strong>Altezza (cm)</strong></label>
                    <input id="pickup_altezza" name="altezza" class="sq-sim-input"
                           value="{{ old('altezza', $input['altezza']) }}">
                </div>
            </div>
            <div class="sq-sim-field sq-mb-14">
                <label for="note_ritiro"><strong>Note ritiro</strong></label>
                <input id="note_ritiro" name="note_ritiro" class="sq-sim-input"
                       value="{{ old('note_ritiro', $input['note_ritiro']) }}">
            </div>
            <details class="sq-mb-14">
                <summary><strong>Payload JSON personalizzato</strong> (opzionale, sostituisce il body generato)</summary>
                <textarea id="pickup_payload_json" name="pickup_payload_json" class="sq-sim-input" rows="8"
                          placeholder='Incolla qui il JSON da apidocs.spedisci.online se il body automatico non basta'>{{ old('pickup_payload_json', $input['pickup_payload_json']) }}</textarea>
            </details>
            <button type="submit" class="sq-sim-btn">Chiama /pickup/create</button>
        </form>

        <h2 class="sq-sim-h2 sq-mt-24">3. Crea etichetta — POST /shipping/create</h2>
        @if ($corriereRitiro)
            <p class="sq-mb-14">
                Corriere portale <strong>#{{ $corriereRitiro->id }}</strong>
                — {{ $corriereRitiro->nome_visualizzato }}
            </p>
        @endif
        <p class="sq-text-muted sq-mb-14">
            <a href="https://apidocs.spedisci.online/api/shipping/create" target="_blank" rel="noopener">Create Shipping Label</a>:
            body con <code class="sq-code">carrierCode</code>, <code class="sq-code">contractCode</code>,
            <code class="sq-code">label_format</code>, <code class="sq-code">packages</code>,
            <code class="sq-code">shipFrom</code>, <code class="sq-code">shipTo</code>, ecc. (struttura piatta come in doc).
        </p>
        <form method="POST" action="{{ route('test.spedisci-online') }}" class="sq-sim-form sq-mb-24">
            @csrf
            <input type="hidden" name="azione" value="create">
            <div class="sq-sim-row">
                <div class="sq-sim-field">
                    <label for="create_cap_origine"><strong>CAP origine</strong></label>
                    <input id="create_cap_origine" name="cap_origine" class="sq-sim-input"
                           value="{{ old('cap_origine', $input['cap_origine']) }}">
                </div>
                <div class="sq-sim-field">
                    <label for="create_cap_destino"><strong>CAP destino</strong></label>
                    <input id="create_cap_destino" name="cap_destino" class="sq-sim-input"
                           value="{{ old('cap_destino', $input['cap_destino']) }}">
                </div>
            </div>
            <div class="sq-sim-row">
                <div class="sq-sim-field">
                    <label for="create_spessore"><strong>Spessore (cm)</strong></label>
                    <input id="create_spessore" name="spessore" class="sq-sim-input"
                           value="{{ old('spessore', $input['spessore']) }}">
                </div>
                <div class="sq-sim-field">
                    <label for="create_larghezza"><strong>Larghezza (cm)</strong></label>
                    <input id="create_larghezza" name="larghezza" class="sq-sim-input"
                           value="{{ old('larghezza', $input['larghezza']) }}">
                </div>
                <div class="sq-sim-field">
                    <label for="create_altezza"><strong>Altezza (cm)</strong></label>
                    <input id="create_altezza" name="altezza" class="sq-sim-input"
                           value="{{ old('altezza', $input['altezza']) }}">
                </div>
                <div class="sq-sim-field">
                    <label for="create_peso"><strong>Peso (kg)</strong></label>
                    <input id="create_peso" name="peso" class="sq-sim-input"
                           value="{{ old('peso', $input['peso']) }}">
                </div>
            </div>
            <h3 class="sq-sim-h2">Mittente</h3>
            <div class="sq-sim-row">
                <div class="sq-sim-field">
                    <label for="mittente_nome"><strong>Nome</strong></label>
                    <input id="mittente_nome" name="mittente_nome" class="sq-sim-input"
                           value="{{ old('mittente_nome', $input['mittente_nome']) }}">
                </div>
                <div class="sq-sim-field">
                    <label for="mittente_azienda"><strong>Azienda</strong></label>
                    <input id="mittente_azienda" name="mittente_azienda" class="sq-sim-input"
                           value="{{ old('mittente_azienda', $input['mittente_azienda']) }}">
                </div>
                <div class="sq-sim-field">
                    <label for="mittente_indirizzo"><strong>Indirizzo</strong></label>
                    <input id="mittente_indirizzo" name="mittente_indirizzo" class="sq-sim-input"
                           value="{{ old('mittente_indirizzo', $input['mittente_indirizzo']) }}">
                </div>
            </div>
            <h3 class="sq-sim-h2">Destinatario</h3>
            <div class="sq-sim-row">
                <div class="sq-sim-field">
                    <label for="destinatario_nome"><strong>Nome</strong></label>
                    <input id="destinatario_nome" name="destinatario_nome" class="sq-sim-input"
                           value="{{ old('destinatario_nome', $input['destinatario_nome']) }}">
                </div>
                <div class="sq-sim-field">
                    <label for="destinatario_indirizzo"><strong>Indirizzo</strong></label>
                    <input id="destinatario_indirizzo" name="destinatario_indirizzo" class="sq-sim-input"
                           value="{{ old('destinatario_indirizzo', $input['destinatario_indirizzo']) }}">
                </div>
            </div>
            <div class="sq-sim-row">
                <div class="sq-sim-field">
                    <label for="create_carrier_code"><strong>carrierCode</strong></label>
                    <input id="create_carrier_code" name="create_carrier_code" class="sq-sim-input"
                           value="{{ old('create_carrier_code', $input['create_carrier_code']) }}">
                </div>
                <div class="sq-sim-field">
                    <label for="create_contract_code"><strong>contractCode</strong></label>
                    <input id="create_contract_code" name="create_contract_code" class="sq-sim-input"
                           value="{{ old('create_contract_code', $input['create_contract_code']) }}">
                </div>
                <div class="sq-sim-field">
                    <label for="label_format"><strong>label_format</strong></label>
                    <input id="label_format" name="label_format" class="sq-sim-input"
                           value="{{ old('label_format', $input['label_format']) }}">
                </div>
            </div>
            <div class="sq-sim-field sq-mb-14">
                <label class="sq-sim-check">
                    <input type="checkbox" name="create_auto_from_rates" value="1"
                           @checked(old('create_auto_from_rates', $input['create_auto_from_rates']) === '1')>
                    Mostra anche anteprima <code class="sq-code">POST /shipping/rates</code> (stesso body senza label_format)
                </label>
            </div>
            <details class="sq-mb-14">
                <summary><strong>Payload JSON completo</strong> (opzionale)</summary>
                <textarea id="create_payload_json" name="create_payload_json" class="sq-sim-input" rows="8"
                          placeholder='Body completo per /shipping/create'>{{ old('create_payload_json', $input['create_payload_json']) }}</textarea>
            </details>
            <button type="submit" class="sq-sim-btn">Chiama /shipping/create</button>
        </form>

        <h2 class="sq-sim-h2 sq-mt-24">4. Elimina etichetta — POST /shipping/delete</h2>
        <p class="sq-text-muted sq-mb-14">
            <a href="https://apidocs.spedisci.online/api/delete" target="_blank" rel="noopener">Delete Shipping Label</a>:
            serve almeno <code class="sq-code">trackingNumber</code> (LDV / shipment-id, es. dalla risposta create)
            oppure <code class="sq-code">increment_id</code> (id numerico interno).
        </p>
        <form method="POST" action="{{ route('test.spedisci-online') }}" class="sq-sim-form sq-mb-24">
            @csrf
            <input type="hidden" name="azione" value="delete">
            <div class="sq-sim-row">
                <div class="sq-sim-field">
                    <label for="delete_shipment_id"><strong>shipment-id</strong> (trackingNumber)</label>
                    <input id="delete_shipment_id" name="delete_shipment_id" class="sq-sim-input"
                           placeholder="es. 3UW1SS5717556"
                           value="{{ old('delete_shipment_id', $input['delete_shipment_id']) }}">
                </div>
                <div class="sq-sim-field">
                    <label for="delete_increment_id"><strong>increment_id</strong> (opz.)</label>
                    <input id="delete_increment_id" name="delete_increment_id" class="sq-sim-input"
                           placeholder="id numerico da risposta create"
                           value="{{ old('delete_increment_id', $input['delete_increment_id']) }}">
                </div>
            </div>
            <details class="sq-mb-14">
                <summary><strong>Payload JSON personalizzato</strong> (opzionale)</summary>
                <textarea id="delete_payload_json" name="delete_payload_json" class="sq-sim-input" rows="6"
                          placeholder='{"trackingNumber":"3UW1SS5717556"}'>{{ old('delete_payload_json', $input['delete_payload_json']) }}</textarea>
            </details>
            <button type="submit" class="sq-sim-btn">Chiama /shipping/delete</button>
        </form>

        @include('test.partials.spedisci-api-result')
    </div>
</div>
@endsection
