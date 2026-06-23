@php
    /** @var \App\Models\spedizione $s */
    /** @var array<string, string> $fieldLabels */
    $remKeys = ['nome_o', 'cognome_o', 'ragione_sociale_o', 'cap_o', 'citta_o', 'indirizzo_o', 'numero_o', 'frazione_o', 'stato_o', 'tel_o', 'email_o', 'note_o'];
    $destKeys = ['nome_d', 'sobrenome_d', 'ragione_sociale_d', 'cap_d', 'citta_d', 'indirizzo_d', 'numero_d', 'frazione_d', 'stato_d', 'tel_d', 'email_d', 'note_d'];
    $svcKeys = ['tipo_id', 'codice_servizio', 'service_description', 'corriere', 'id_shipment'];
    $pkgKeys = ['altezza', 'larghezza', 'spessore', 'peso'];
    $dadosFormId = 'sq-bo-dados-'.$s->id;
    $detalheUrl = $detalheUrl ?? route('backoffice.spedizioni.dettaglio', $s);
@endphp
<div class="sq-bo-ng-inner sq-bo-ng-opcoes-inner">
    <p class="sq-bo-ng-opcoes-intro sq-text-muted">
        Modifica indirizzi e dati della spedizione. Dopo il salvataggio usa <strong>Rigenera etichetta</strong> per rilanciare la generazione automatica.
        Per registrare solo tracking o PDF esterno torna ai
        <button type="button" class="sq-link-btn js-bo-voltar-detalhe" data-detalhe-url="{{ $detalheUrl }}">Dettagli</button>.
    </p>

    <div class="sq-bo-ng-readonly-bar">
        <span><strong>{{ $s->codice_interno }}</strong></span>
        <span>Ordine <strong>#{{ $s->ordine_id }}</strong></span>
        <span>Cliente #{{ $s->user_id }} @if($s->user) — {{ $s->user->email }} @endif</span>
        @if($servicoNome !== '')
            <span>Servizio: <strong>{{ $servicoNome }}</strong></span>
        @endif
    </div>

    <section class="sq-bo-ng-block sq-bo-ng-opcao" aria-labelledby="sq-bo-tit-dados">
        <h4 class="sq-bo-ng-h4" id="sq-bo-tit-dados">Modifica indirizzi e dati</h4>
        <form id="{{ $dadosFormId }}" method="post" action="{{ route('backoffice.spedizioni.update', $s) }}" class="sq-bo-ng-form-dados">
            @csrf
            @method('PUT')
            <fieldset class="sq-bo-ng-fieldset">
                <legend>Mittente</legend>
                <div class="sq-bo-ng-grid">
                    @foreach ($remKeys as $key)
                        <label class="sq-bo-ng-lab" for="sq-bo-{{ $key }}-{{ $s->id }}">{{ $fieldLabels[$key] ?? $key }}</label>
                        <input class="sq-bo-ng-inp" id="sq-bo-{{ $key }}-{{ $s->id }}" type="text" name="{{ $key }}" value="{{ old($key, $s->{$key} !== null && $s->{$key} !== '' ? (string) $s->{$key} : '') }}">
                    @endforeach
                </div>
            </fieldset>
            <fieldset class="sq-bo-ng-fieldset">
                <legend>Destinatario</legend>
                <div class="sq-bo-ng-grid">
                    @foreach ($destKeys as $key)
                        <label class="sq-bo-ng-lab" for="sq-bo-{{ $key }}-{{ $s->id }}">{{ $fieldLabels[$key] ?? $key }}</label>
                        <input class="sq-bo-ng-inp" id="sq-bo-{{ $key }}-{{ $s->id }}" type="text" name="{{ $key }}" value="{{ old($key, $s->{$key} !== null && $s->{$key} !== '' ? (string) $s->{$key} : '') }}">
                    @endforeach
                </div>
            </fieldset>
            <fieldset class="sq-bo-ng-fieldset">
                <legend>Servizio</legend>
                <div class="sq-bo-ng-grid">
                    @foreach ($svcKeys as $key)
                        <label class="sq-bo-ng-lab" for="sq-bo-{{ $key }}-{{ $s->id }}">{{ $fieldLabels[$key] ?? $key }}</label>
                        <input class="sq-bo-ng-inp" id="sq-bo-{{ $key }}-{{ $s->id }}" type="text" name="{{ $key }}" value="{{ old($key, $s->{$key} !== null && $s->{$key} !== '' ? (string) $s->{$key} : '') }}">
                    @endforeach
                </div>
            </fieldset>
            <fieldset class="sq-bo-ng-fieldset">
                <legend>Collo (dimensioni / peso)</legend>
                <div class="sq-bo-ng-grid">
                    @foreach ($pkgKeys as $key)
                        <label class="sq-bo-ng-lab" for="sq-bo-{{ $key }}-{{ $s->id }}">{{ $fieldLabels[$key] ?? $key }}</label>
                        <input class="sq-bo-ng-inp" id="sq-bo-{{ $key }}-{{ $s->id }}" type="text" name="{{ $key }}" value="{{ old($key, $s->{$key} !== null && $s->{$key} !== '' ? (string) $s->{$key} : '') }}">
                    @endforeach
                </div>
            </fieldset>
        </form>
        <div class="sq-bo-ng-actions sq-bo-ng-actions-dual">
            <div class="sq-bo-ng-actions-dual-row">
                <button type="button" class="sq-btn-secondary sq-btn-sm js-bo-voltar-detalhe" data-detalhe-url="{{ $detalheUrl }}">← Dettagli</button>
                <button type="submit" class="sq-btn-primary sq-btn-sm" form="{{ $dadosFormId }}">Salva modifiche</button>
                @if (! empty($retryUrl))
                    <form method="post" action="{{ $retryUrl }}" class="sq-bo-ng-inline-form" onsubmit="return confirm('Rigenerare l\'etichetta automatica per questa spedizione?');">
                        @csrf
                        <button type="submit" class="sq-btn-primary sq-btn-sm sq-bo-etq-btn-reserva" formnovalidate>Rigenera etichetta</button>
                    </form>
                @endif
            </div>
        </div>
    </section>
</div>
