@php $v = $input ?? []; @endphp
<div class="sq-sim-row">
    <div class="sq-sim-field">
        <label><strong>Codice servizio</strong></label>
        <input name="codice_servizio" class="sq-sim-input" value="{{ old('codice_servizio', $v['codice_servizio'] ?? 'E') }}">
    </div>
    <div class="sq-sim-field">
        <label><strong>N. colli</strong></label>
        <input name="num_colli" class="sq-sim-input" type="number" min="1" max="10"
               value="{{ old('num_colli', $v['num_colli'] ?? '1') }}">
    </div>
    <div class="sq-sim-field">
        <label><strong>Peso (kg)</strong></label>
        <input name="peso" class="sq-sim-input" value="{{ old('peso', $v['peso'] ?? '1') }}">
    </div>
</div>
<div class="sq-sim-row">
    <div class="sq-sim-field">
        <label><strong>Altezza (cm)</strong></label>
        <input name="altezza" class="sq-sim-input" value="{{ old('altezza', $v['altezza'] ?? '20') }}">
    </div>
    <div class="sq-sim-field">
        <label><strong>Larghezza (cm)</strong></label>
        <input name="larghezza" class="sq-sim-input" value="{{ old('larghezza', $v['larghezza'] ?? '25') }}">
    </div>
    <div class="sq-sim-field">
        <label><strong>Profondità / spessore (cm)</strong></label>
        <input name="spessore" class="sq-sim-input" value="{{ old('spessore', $v['spessore'] ?? '30') }}">
    </div>
    <div class="sq-sim-field">
        <label><strong>Volume (m³, opz.)</strong></label>
        <input name="volume_collo" class="sq-sim-input" placeholder="es. 1.0"
               value="{{ old('volume_collo', $v['volume_collo'] ?? '') }}">
    </div>
</div>
<p class="sq-text-muted sq-mb-14"><strong>Ritiro</strong></p>
<div class="sq-sim-row">
    <div class="sq-sim-field">
        <label><strong>CAP</strong></label>
        <input name="cap_origine" class="sq-sim-input" value="{{ old('cap_origine', $v['cap_origine'] ?? '') }}">
    </div>
    <div class="sq-sim-field">
        <label><strong>Comune</strong></label>
        <input name="citta_origine" class="sq-sim-input" value="{{ old('citta_origine', $v['citta_origine'] ?? '') }}">
    </div>
    <div class="sq-sim-field">
        <label><strong>Prov.</strong></label>
        <input name="pv_origine" class="sq-sim-input" maxlength="2" value="{{ old('pv_origine', $v['pv_origine'] ?? '') }}">
    </div>
</div>
<div class="sq-sim-row">
    <div class="sq-sim-field">
        <label><strong>Via</strong></label>
        <input name="via_origine" class="sq-sim-input" value="{{ old('via_origine', $v['via_origine'] ?? '') }}">
    </div>
    <div class="sq-sim-field">
        <label><strong>Civico</strong></label>
        <input name="civico_origine" class="sq-sim-input" value="{{ old('civico_origine', $v['civico_origine'] ?? '') }}">
    </div>
    <div class="sq-sim-field">
        <label><strong>Ragione sociale mittente</strong></label>
        <input name="mittente_azienda" class="sq-sim-input" value="{{ old('mittente_azienda', $v['mittente_azienda'] ?? '') }}">
    </div>
</div>
<p class="sq-text-muted sq-mb-14"><strong>Consegna</strong></p>
<div class="sq-sim-row">
    <div class="sq-sim-field">
        <label><strong>CAP</strong></label>
        <input name="cap_destino" class="sq-sim-input" value="{{ old('cap_destino', $v['cap_destino'] ?? '') }}">
    </div>
    <div class="sq-sim-field">
        <label><strong>Comune</strong></label>
        <input name="citta_destino" class="sq-sim-input" value="{{ old('citta_destino', $v['citta_destino'] ?? '') }}">
    </div>
    <div class="sq-sim-field">
        <label><strong>Prov.</strong></label>
        <input name="pv_destino" class="sq-sim-input" maxlength="2" value="{{ old('pv_destino', $v['pv_destino'] ?? '') }}">
    </div>
</div>
<div class="sq-sim-row">
    <div class="sq-sim-field">
        <label><strong>Via</strong></label>
        <input name="via_destino" class="sq-sim-input" value="{{ old('via_destino', $v['via_destino'] ?? '') }}">
    </div>
    <div class="sq-sim-field">
        <label><strong>Civico</strong></label>
        <input name="civico_destino" class="sq-sim-input" value="{{ old('civico_destino', $v['civico_destino'] ?? '') }}">
    </div>
    <div class="sq-sim-field">
        <label><strong>Destinatario (referencePerson)</strong></label>
        <input name="destinatario_nome" class="sq-sim-input" value="{{ old('destinatario_nome', $v['destinatario_nome'] ?? '') }}">
    </div>
</div>
