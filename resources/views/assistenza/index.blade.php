@extends('layouts.app')
@section('content')
<div class="sq-bleed-layout assistenza-page">
    <x-sq-page-banner title="Assistenza e richieste" icon="fa-headset" class="sq-page-banner--full" />

    <div class="assistenza-page__inner">
        @if (session('status'))
            <div class="sq-alert sq-alert--success sq-mb-16">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="sq-alert sq-alert--error sq-mb-16" role="alert">
                @foreach ($errors->all() as $e)
                    <div>{{ $e }}</div>
                @endforeach
            </div>
        @endif

        @php
            $tiposProblema = $tiposProblema ?? collect();
        @endphp

        <div class="assistenza-layout">
            <div class="assistenza-card assistenza-card--nova" id="nova-richiesta">
                <div class="assistenza-card__head">
                    <div class="assistenza-card__head-text">
                        <h2>Nuova richiesta</h2>
                        <p>Compila il modulo per aprire un ticket al team di assistenza.</p>
                    </div>
                </div>

                @if ($tiposProblema->isEmpty())
                    <p class="sq-alert sq-alert--error" role="alert">Non ci sono tipi di problema configurati. Contatta l'assistenza.</p>
                @else
                    <form method="post" action="{{ route('assistenza.solicitar.store') }}" class="assistenza-form" id="form-assistenza">
                        @csrf
                        <label for="ticket_tipo_problema_id">Tipo di problema</label>
                        <select id="ticket_tipo_problema_id" name="ticket_tipo_problema_id" required>
                            <option value="" @selected(old('ticket_tipo_problema_id', '') === '')>Seleziona…</option>
                            @foreach ($tiposProblema as $tipo)
                                <option value="{{ $tipo->id }}" data-codigo="{{ $tipo->codigo }}" @selected((string) old('ticket_tipo_problema_id') === (string) $tipo->id)>{{ $tipo->nome }}</option>
                            @endforeach
                        </select>

                        <div id="fluxo-entrega" class="assistenza-fluxo-box">
                            <h3>Consegna — scegli ordine ed etichetta</h3>
                            <p class="assistenza-sped-meta">Indica l'<strong>ID ordine</strong> (come in «Ordini spedizioni», es. 27). Verranno elencate le spedizioni <strong>pagate</strong> di quell'ordine. Seleziona <strong>una</strong> etichetta.</p>
                            <div class="assistenza-pick-row">
                                <input type="text" class="assistenza-input-compact" name="ordine_codice_entrega" id="ordine_codice_entrega" value="{{ old('ordine_codice_entrega') }}" placeholder="N° ordine" maxlength="12" autocomplete="off">
                                <button type="button" class="assistenza-btn-sec" id="btn-carregar-entrega">Carica spedizioni</button>
                            </div>
                            <div id="msg-entrega" class="assistenza-api-msg" aria-live="polite"></div>
                            <div id="lista-entrega" class="assistenza-sped-list"></div>
                        </div>

                        <div id="fluxo-etiqueta-ng" class="assistenza-fluxo-box">
                            <h3>Etichetta non generata</h3>
                            <p class="assistenza-sped-meta">Indica l'<strong>ordine</strong> o il <strong>codice spedizione</strong> (come in «Etichette»). Compaiono solo spedizioni <strong>pagate e senza tracking</strong>.</p>
                            <div class="assistenza-radio-inline">
                                <label><input type="radio" name="etiqueta_ng_modo" value="pedido" @checked(old('etiqueta_ng_modo', 'pedido') === 'pedido')> Ho l'ordine</label>
                                <label><input type="radio" name="etiqueta_ng_modo" value="codigo" @checked(old('etiqueta_ng_modo') === 'codigo')> Ho solo il codice spedizione</label>
                            </div>
                            <div id="ng-por-pedido">
                                <div class="assistenza-pick-row">
                                    <input type="text" class="assistenza-input-compact" name="ordine_codice_ng" id="ordine_codice_ng" value="{{ old('ordine_codice_ng') }}" placeholder="N° ordine" maxlength="12" autocomplete="off">
                                    <button type="button" class="assistenza-btn-sec" id="btn-carregar-ng-pedido">Carica spedizioni</button>
                                </div>
                                <div id="msg-ng-pedido" class="assistenza-api-msg" aria-live="polite"></div>
                                <div id="lista-ng-pedido" class="assistenza-sped-list"></div>
                            </div>
                            <div id="ng-por-codigo" style="display:none;">
                                <div class="assistenza-pick-row">
                                    <input type="text" class="assistenza-input-codice" name="codigo_remessa_ng" id="codigo_remessa_ng" value="{{ old('codigo_remessa_ng') }}" placeholder="Codice spedizione" autocomplete="off">
                                    <button type="button" class="assistenza-btn-sec" id="btn-buscar-ng-codigo">Trova spedizione</button>
                                </div>
                                <div id="msg-ng-codigo" class="assistenza-api-msg" aria-live="polite"></div>
                                <div id="res-ng-codigo" class="assistenza-sped-list"></div>
                            </div>
                            <p class="assistenza-sped-meta sq-mt-12">Il messaggio qui sotto verrà inviato al team; puoi modificarlo se necessario.</p>
                        </div>

                        <div id="fluxo-fattura" class="assistenza-fluxo-box">
                            <h3>Non ho ricevuto la fattura</h3>
                            <p class="assistenza-sped-meta">La fattura richiesta si riferisce al seguente periodo:</p>
                            <div class="assistenza-period-row">
                                <label class="assistenza-period-field">
                                    <span class="assistenza-period-label">Mese</span>
                                    <select name="fattura_mese" id="fattura_mese">
                                        <option value="">—</option>
                                        @foreach ([1=>'Gennaio',2=>'Febbraio',3=>'Marzo',4=>'Aprile',5=>'Maggio',6=>'Giugno',7=>'Luglio',8=>'Agosto',9=>'Settembre',10=>'Ottobre',11=>'Novembre',12=>'Dicembre'] as $m => $nomeMese)
                                            <option value="{{ $m }}" @selected((int) old('fattura_mese') === $m)>{{ $nomeMese }}</option>
                                        @endforeach
                                    </select>
                                </label>
                                <label class="assistenza-period-field">
                                    <span class="assistenza-period-label">Anno</span>
                                    <select name="fattura_anno" id="fattura_anno">
                                        <option value="">—</option>
                                        @foreach ($anniFattura ?? [date('Y')] as $anno)
                                            <option value="{{ $anno }}" @selected((int) old('fattura_anno') === (int) $anno)>{{ $anno }}</option>
                                        @endforeach
                                    </select>
                                </label>
                            </div>
                        </div>

                        <div id="fluxo-tracking" class="assistenza-fluxo-box">
                            <h3>Non riesco a fare il tracking</h3>
                            <p class="assistenza-sped-meta">Indica l'ordine (e seleziona le etichette), oppure il codice interno della spedizione, oppure il numero di tracking.</p>
                            <div class="assistenza-radio-inline">
                                <label><input type="radio" name="tracking_modo" value="ordine" @checked(old('tracking_modo', 'ordine') === 'ordine')> Ho l'ordine</label>
                                <label><input type="radio" name="tracking_modo" value="codice_interno" @checked(old('tracking_modo') === 'codice_interno')> Codice interno spedizione</label>
                                <label><input type="radio" name="tracking_modo" value="tracking" @checked(old('tracking_modo') === 'tracking')> Numero tracking</label>
                            </div>
                            <div id="tracking-por-ordine">
                                <div class="assistenza-pick-row">
                                    <input type="text" class="assistenza-input-compact" name="ordine_codice_tracking" id="ordine_codice_tracking" value="{{ old('ordine_codice_tracking') }}" placeholder="N° ordine" maxlength="12" autocomplete="off">
                                    <button type="button" class="assistenza-btn-sec" id="btn-carregar-tracking-ordine">Carica etichette</button>
                                </div>
                                <div id="msg-tracking-ordine" class="assistenza-api-msg" aria-live="polite"></div>
                                <div id="lista-tracking-ordine" class="assistenza-sped-list"></div>
                            </div>
                            <div id="tracking-por-codice" style="display:none;">
                                <div class="assistenza-pick-row">
                                    <input type="text" class="assistenza-input-codice" name="codigo_remessa_tracking" id="codigo_remessa_tracking" value="{{ old('codigo_remessa_tracking') }}" placeholder="Codice spedizione" autocomplete="off">
                                    <button type="button" class="assistenza-btn-sec" id="btn-buscar-tracking-codice">Trova spedizione</button>
                                </div>
                                <div id="msg-tracking-codice" class="assistenza-api-msg" aria-live="polite"></div>
                                <div id="res-tracking-codice" class="assistenza-sped-list"></div>
                            </div>
                            <div id="tracking-por-numero" style="display:none;">
                                <div class="assistenza-pick-row">
                                    <input type="text" class="assistenza-input-codice" name="numero_tracking" id="numero_tracking" value="{{ old('numero_tracking') }}" placeholder="Numero tracking" autocomplete="off">
                                    <button type="button" class="assistenza-btn-sec" id="btn-buscar-tracking-numero">Trova spedizione</button>
                                </div>
                                <div id="msg-tracking-numero" class="assistenza-api-msg" aria-live="polite"></div>
                                <div id="res-tracking-numero" class="assistenza-sped-list"></div>
                            </div>
                        </div>

                        <div id="fluxo-riprenot" class="assistenza-fluxo-box">
                            <h3>Il corriere non è passato — riprenotazione ritiro</h3>
                            <p class="assistenza-sped-meta">Seleziona il corriere, indica l'ordine e scegli le etichette generate per quel corriere.</p>
                            <label for="riprenot_corriere">Corriere</label>
                            <select name="riprenot_corriere" id="riprenot_corriere">
                                <option value="">Carica corrieri…</option>
                            </select>
                            <div class="assistenza-pick-row sq-mt-12">
                                <input type="text" class="assistenza-input-compact" name="ordine_codice_riprenot" id="ordine_codice_riprenot" value="{{ old('ordine_codice_riprenot') }}" placeholder="N° ordine" maxlength="12" autocomplete="off">
                                <button type="button" class="assistenza-btn-sec" id="btn-carregar-riprenot">Carica etichette</button>
                            </div>
                            <div id="msg-riprenot" class="assistenza-api-msg" aria-live="polite"></div>
                            <div id="lista-riprenot" class="assistenza-sped-list"></div>
                        </div>

                        @php
                            $commDef = $commercialeDefaults ?? ['nome_cognome' => '', 'nome_impresa' => '', 'partita_iva' => ''];
                        @endphp
                        <div id="fluxo-commerciale" class="assistenza-fluxo-box">
                            <h3>Contatto commerciale</h3>
                            <p class="assistenza-sped-meta">Indica i tuoi riferimenti; il team commerciale ti ricontatterà.</p>
                            <label for="commerciale_nome_cognome">Nome e cognome</label>
                            <input type="text" id="commerciale_nome_cognome" name="commerciale_nome_cognome" value="{{ old('commerciale_nome_cognome', $commDef['nome_cognome']) }}" maxlength="200" autocomplete="name">
                            <label for="commerciale_nome_impresa">Nome impresa</label>
                            <input type="text" id="commerciale_nome_impresa" name="commerciale_nome_impresa" value="{{ old('commerciale_nome_impresa', $commDef['nome_impresa']) }}" maxlength="200" autocomplete="organization">
                            <label for="commerciale_partita_iva">Partita IVA</label>
                            <input type="text" id="commerciale_partita_iva" name="commerciale_partita_iva" value="{{ old('commerciale_partita_iva', $commDef['partita_iva']) }}" maxlength="20" autocomplete="off" class="assistenza-input-piva">
                        </div>

                        <label for="oggetto">Oggetto</label>
                        <input type="text" id="oggetto" name="oggetto" value="{{ old('oggetto') }}" required maxlength="500" placeholder="Breve riepilogo">

                        <label for="body">Messaggio <span class="assistenza-sped-meta" style="font-weight:400;">(ciò che il team vede nel ticket)</span></label>
                        <textarea id="body" name="body" required placeholder="Descrivi la tua richiesta…">{{ old('body') }}</textarea>

                        <button type="submit" class="assistenza-btn-submit">Invia richiesta</button>
                    </form>
                @endif
            </div>

            <div class="assistenza-card assistenza-card--lista" id="lista-richieste">
                <div class="assistenza-card__head">
                    <div class="assistenza-card__head-text">
                        <h2>Le mie richieste</h2>
                        <p>Segui lo stato delle tue richieste di assistenza. Consulta anche le <a href="{{ route('faq.index') }}" class="assistenza-faq-link">FAQ</a>.</p>
                    </div>
                </div>

                @include('partials.tabella-paginazione', [
                    'paginator' => $tickets,
                    'perPage' => $perPage,
                    'queryParams' => request()->except('page'),
                ])

                <div class="assistenza-list">
                    @forelse ($tickets as $ticket)
                        <a href="{{ route('assistenza.ticket.show', $ticket) }}" class="assistenza-list__item">
                            <strong>#{{ $ticket->id }}</strong> — {{ Str::limit($ticket->oggetto, 72) }}
                            <div class="assistenza-list__sub">
                                <span class="assistenza-pill">{{ $ticket->stato?->nome ?? '—' }}</span>
                                · {{ $ticket->created_at?->timezone(config('app.timezone'))->format('d/m/Y H:i') }}
                            </div>
                        </a>
                    @empty
                        <p class="assistenza-empty">Non hai ancora richieste. Compila il modulo per aprire la prima.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    var tipoSel = document.getElementById('ticket_tipo_problema_id');
    if (!tipoSel) return;

    var idEntrega = @json($tipoEntregaId);
    var idEtiquetaNg = @json($tipoEtiquetaNaoGeradaId);
    var idFattura = @json($tipoFatturaMancanteId);
    var idTracking = @json($tipoTrackingId);
    var idRiprenot = @json($tipoRiprenotazioneId);
    var idCommerciale = @json($tipoCommercialeId);
    var urlPedido = @json(route('assistenza.api.spedizioni_ordine'));
    var urlCodigo = @json(route('assistenza.api.spedizione_codice'));
    var urlTracking = @json(route('assistenza.api.spedizione_tracking'));
    var urlCorrieri = @json(route('assistenza.api.corrieri_cliente'));

    var boxEntrega = document.getElementById('fluxo-entrega');
    var boxNg = document.getElementById('fluxo-etiqueta-ng');
    var boxFattura = document.getElementById('fluxo-fattura');
    var boxTracking = document.getElementById('fluxo-tracking');
    var boxRiprenot = document.getElementById('fluxo-riprenot');
    var boxCommerciale = document.getElementById('fluxo-commerciale');
    var listaEntrega = document.getElementById('lista-entrega');
    var msgEntrega = document.getElementById('msg-entrega');
    var listaNg = document.getElementById('lista-ng-pedido');
    var msgNg = document.getElementById('msg-ng-pedido');
    var inpOggetto = document.getElementById('oggetto');
    var taBody = document.getElementById('body');
    var assuntoEtiquetaNg = 'Etichetta non generata';
    var assuntoFattura = 'Non ho ricevuto la fattura';
    var assuntoTracking = 'Non riesco a fare il tracking';
    var assuntoRiprenot = 'Il corriere non è passato, voglio riprenotare';
    var assuntoCommerciale = 'Voglio parlare con un commerciale';
    var lastNgCodiceInterno = null;
    var lastTrackingCodiceOk = false;
    var lastTrackingNumeroOk = false;
    var corrieriCaricati = false;

    if (window.location.hash === '#nova-richiesta') {
        var painelNova = document.getElementById('nova-richiesta');
        if (painelNova) painelNova.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function mensagemPrecarregadaNg(codigos) {
        if (codigos.length === 1) {
            return 'Ho bisogno dell\'etichetta ' + codigos[0] + ', già pagata e non generata dal sistema.';
        }
        if (codigos.length > 1) {
            return 'Ho bisogno delle etichette ' + codigos.join(', ') + ', già pagate e non generate dal sistema.';
        }
        return '';
    }

    function updateNgPrecarregada() {
        if (!idEtiquetaNg || String(tipoSel.value) !== String(idEtiquetaNg) || !taBody) return;
        var modo = document.querySelector('input[name="etiqueta_ng_modo"]:checked');
        modo = modo ? modo.value : 'pedido';
        var codigos = [];
        if (modo === 'pedido') {
            listaNg.querySelectorAll('input[name="spedizione_ids_ng[]"]:checked').forEach(function (inp) {
                var c = inp.getAttribute('data-codice');
                if (c) codigos.push(c);
            });
            var hasPickList = !!listaNg.querySelector('input[name="spedizione_ids_ng[]"]');
            if (!hasPickList) return;
            if (codigos.length === 0) {
                taBody.value = '';
                return;
            }
        } else {
            if (!lastNgCodiceInterno) return;
            codigos.push(lastNgCodiceInterno);
        }
        taBody.value = mensagemPrecarregadaNg(codigos);
    }

    function toggleFluxos() {
        var v = tipoSel.value;
        var isEnt = idEntrega && String(v) === String(idEntrega);
        var isNg = idEtiquetaNg && String(v) === String(idEtiquetaNg);
        var isFat = idFattura && String(v) === String(idFattura);
        var isTrk = idTracking && String(v) === String(idTracking);
        var isRip = idRiprenot && String(v) === String(idRiprenot);
        var isComm = idCommerciale && String(v) === String(idCommerciale);
        boxEntrega.classList.toggle('is-on', isEnt);
        boxNg.classList.toggle('is-on', isNg);
        if (boxFattura) boxFattura.classList.toggle('is-on', isFat);
        if (boxTracking) boxTracking.classList.toggle('is-on', isTrk);
        if (boxRiprenot) boxRiprenot.classList.toggle('is-on', isRip);
        if (boxCommerciale) boxCommerciale.classList.toggle('is-on', isComm);
        document.querySelectorAll('#fluxo-entrega input[type="text"], #fluxo-entrega button').forEach(function (el) {
            el.disabled = !isEnt;
        });
        document.querySelectorAll('#fluxo-etiqueta-ng input, #fluxo-etiqueta-ng button').forEach(function (el) {
            el.disabled = !isNg;
        });
        if (boxFattura) {
            boxFattura.querySelectorAll('select, input, button').forEach(function (el) {
                el.disabled = !isFat;
            });
        }
        if (boxTracking) {
            boxTracking.querySelectorAll('input, button').forEach(function (el) {
                el.disabled = !isTrk;
            });
        }
        if (boxRiprenot) {
            boxRiprenot.querySelectorAll('select, input, button').forEach(function (el) {
                el.disabled = !isRip;
            });
            if (isRip) carregarCorrieriRiprenot();
        }
        if (boxCommerciale) {
            boxCommerciale.querySelectorAll('input').forEach(function (el) {
                el.disabled = !isComm;
                if (isComm) {
                    el.required = true;
                } else {
                    el.required = false;
                }
            });
        }
    }

    function carregarCorrieriRiprenot() {
        if (corrieriCaricati) return;
        var sel = document.getElementById('riprenot_corriere');
        if (!sel) return;
        fetch(urlCorrieri, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json(); })
            .then(function (j) {
                var old = @json(old('riprenot_corriere', ''));
                sel.innerHTML = '<option value="">Seleziona corriere…</option>';
                (j.corrieri || []).forEach(function (c) {
                    var opt = document.createElement('option');
                    opt.value = c;
                    opt.textContent = c;
                    if (old && old === c) opt.selected = true;
                    sel.appendChild(opt);
                });
                corrieriCaricati = true;
            })
            .catch(function () {
                sel.innerHTML = '<option value="">Errore caricamento corrieri</option>';
            });
    }

    function onTipoProblemaChange(fromUser) {
        toggleFluxos();
        if (fromUser && inpOggetto) {
            if (idEtiquetaNg && String(tipoSel.value) === String(idEtiquetaNg)) inpOggetto.value = assuntoEtiquetaNg;
            else if (idFattura && String(tipoSel.value) === String(idFattura)) inpOggetto.value = assuntoFattura;
            else if (idTracking && String(tipoSel.value) === String(idTracking)) inpOggetto.value = assuntoTracking;
            else if (idRiprenot && String(tipoSel.value) === String(idRiprenot)) inpOggetto.value = assuntoRiprenot;
            else if (idCommerciale && String(tipoSel.value) === String(idCommerciale)) inpOggetto.value = assuntoCommerciale;
        }
        if (idEtiquetaNg && String(tipoSel.value) === String(idEtiquetaNg)) updateNgPrecarregada();
    }

    tipoSel.addEventListener('change', function () { onTipoProblemaChange(true); });
    onTipoProblemaChange(false);

    function esc(s) {
        if (!s) return '';
        var d = document.createElement('div');
        d.textContent = s;
        return d.innerHTML;
    }

    function linhaRastreioSped(s) {
        if (!s || !s.codigo_rastreio) return '';
        return '<span class="assistenza-sped-meta"><br>Tracking: <code>' + esc(s.codigo_rastreio) + '</code></span>';
    }

    function renderSpedizioniRadio(container, spedizioni, nameAttr) {
        container.innerHTML = '';
        if (!spedizioni.length) {
            container.innerHTML = '<p class="assistenza-sped-meta">Nessuna spedizione trovata con questi criteri.</p>';
            return;
        }
        spedizioni.forEach(function (s) {
            var id = 'sp-' + nameAttr + '-' + s.id;
            var lab = document.createElement('label');
            lab.innerHTML = '<input type="radio" name="' + nameAttr + '" value="' + s.id + '" id="' + id + '">' +
                '<span><strong>' + esc(s.codice_interno) + '</strong> — ' + esc(s.carrier) +
                '<span class="assistenza-sped-meta"><br>' + esc(s.service_description) + ' · ' + esc(s.destinatario) + '</span>' +
                linhaRastreioSped(s) + '</span>';
            container.appendChild(lab);
        });
    }

    function renderSpedizioniCheck(container, spedizioni) {
        container.innerHTML = '';
        if (!spedizioni.length) {
            container.innerHTML = '<p class="assistenza-sped-meta">Nessuna spedizione senza tracking per questo ordine.</p>';
            return;
        }
        spedizioni.forEach(function (s) {
            var id = 'chk-ng-' + s.id;
            var lab = document.createElement('label');
            lab.innerHTML = '<input type="checkbox" name="spedizione_ids_ng[]" value="' + s.id + '" id="' + id + '">' +
                '<span><strong>' + esc(s.codice_interno) + '</strong> — ' + esc(s.carrier) +
                '<span class="assistenza-sped-meta"><br>' + esc(s.destinatario) + '</span></span>';
            container.appendChild(lab);
            var inp = lab.querySelector('input');
            if (inp && s.codice_interno) inp.setAttribute('data-codice', s.codice_interno);
        });
    }

    if (listaNg) {
        listaNg.addEventListener('change', function (e) {
            if (e.target && e.target.name === 'spedizione_ids_ng[]') updateNgPrecarregada();
        });
    }

    document.getElementById('btn-carregar-entrega').addEventListener('click', function () {
        var cod = document.getElementById('ordine_codice_entrega').value.trim();
        msgEntrega.textContent = '';
        msgEntrega.className = 'assistenza-api-msg';
        listaEntrega.innerHTML = '';
        if (!cod) { msgEntrega.textContent = 'Indica il numero ordine.'; msgEntrega.classList.add('is-err'); return; }
        fetch(urlPedido + '?modo=entrega&ordine_codice=' + encodeURIComponent(cod), { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, j: j }; }); })
            .then(function (x) {
                if (!x.ok) { msgEntrega.textContent = x.j.error || 'Errore nel caricamento.'; msgEntrega.classList.add('is-err'); return; }
                msgEntrega.textContent = 'Ordine #' + (x.j.ordine && x.j.ordine.id ? x.j.ordine.id : '') + ' — seleziona una spedizione.';
                msgEntrega.classList.add('is-ok');
                renderSpedizioniRadio(listaEntrega, x.j.spedizioni || [], 'spedizione_id_entrega');
            })
            .catch(function () { msgEntrega.textContent = 'Errore di rete.'; msgEntrega.classList.add('is-err'); });
    });

    document.getElementById('btn-carregar-ng-pedido').addEventListener('click', function () {
        var cod = document.getElementById('ordine_codice_ng').value.trim();
        msgNg.textContent = '';
        msgNg.className = 'assistenza-api-msg';
        listaNg.innerHTML = '';
        if (!cod) { msgNg.textContent = 'Indica il numero ordine.'; msgNg.classList.add('is-err'); return; }
        fetch(urlPedido + '?modo=etiqueta_nao_gerada&ordine_codice=' + encodeURIComponent(cod), { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, j: j }; }); })
            .then(function (x) {
                if (!x.ok) { msgNg.textContent = x.j.error || 'Errore nel caricamento.'; msgNg.classList.add('is-err'); return; }
                msgNg.textContent = 'Seleziona una o più spedizioni senza tracking.';
                msgNg.classList.add('is-ok');
                renderSpedizioniCheck(listaNg, x.j.spedizioni || []);
                updateNgPrecarregada();
            })
            .catch(function () { msgNg.textContent = 'Errore di rete.'; msgNg.classList.add('is-err'); });
    });

    var msgCod = document.getElementById('msg-ng-codigo');
    var resCod = document.getElementById('res-ng-codigo');
    var ngModo = document.querySelectorAll('input[name="etiqueta_ng_modo"]');
    var ngPorPedido = document.getElementById('ng-por-pedido');
    var ngPorCodigo = document.getElementById('ng-por-codigo');

    function syncNgModo() {
        var v = document.querySelector('input[name="etiqueta_ng_modo"]:checked');
        v = v ? v.value : 'pedido';
        ngPorPedido.style.display = v === 'pedido' ? 'block' : 'none';
        ngPorCodigo.style.display = v === 'codigo' ? 'block' : 'none';
    }
    ngModo.forEach(function (r) {
        r.addEventListener('change', function () {
            syncNgModo();
            lastNgCodiceInterno = null;
            if (msgCod) { msgCod.textContent = ''; msgCod.className = 'assistenza-api-msg'; }
            if (resCod) resCod.innerHTML = '';
            if (idEtiquetaNg && String(tipoSel.value) === String(idEtiquetaNg) && taBody) taBody.value = '';
            updateNgPrecarregada();
        });
    });
    syncNgModo();

    document.getElementById('btn-buscar-ng-codigo').addEventListener('click', function () {
        var cod = document.getElementById('codigo_remessa_ng').value.trim();
        msgCod.textContent = '';
        msgCod.className = 'assistenza-api-msg';
        resCod.innerHTML = '';
        lastNgCodiceInterno = null;
        if (!cod) { msgCod.textContent = 'Indica il codice spedizione.'; msgCod.classList.add('is-err'); updateNgPrecarregada(); return; }
        fetch(urlCodigo + '?modo=etiqueta_nao_gerada&codigo=' + encodeURIComponent(cod), { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, j: j }; }); })
            .then(function (x) {
                if (!x.ok) {
                    msgCod.textContent = x.j.error || 'Non trovato.';
                    msgCod.classList.add('is-err');
                    updateNgPrecarregada();
                    return;
                }
                msgCod.textContent = 'Spedizione trovata.';
                msgCod.classList.add('is-ok');
                var s = x.j.spedizione;
                lastNgCodiceInterno = s.codice_interno || null;
                resCod.innerHTML = '<p><strong>' + esc(s.codice_interno) + '</strong> — ' + esc(s.carrier) + '</p><p class="assistenza-sped-meta">' + esc(s.destinatario) + '</p>' +
                    (s.codigo_rastreio ? '<p class="assistenza-sped-meta sq-mt-8">Tracking: <code>' + esc(s.codigo_rastreio) + '</code></p>' : '');
                updateNgPrecarregada();
            })
            .catch(function () { msgCod.textContent = 'Errore di rete.'; msgCod.classList.add('is-err'); updateNgPrecarregada(); });
    });

    var listaTrackingOrdine = document.getElementById('lista-tracking-ordine');
    var msgTrackingOrdine = document.getElementById('msg-tracking-ordine');
    document.getElementById('btn-carregar-tracking-ordine').addEventListener('click', function () {
        var cod = document.getElementById('ordine_codice_tracking').value.trim();
        msgTrackingOrdine.textContent = '';
        msgTrackingOrdine.className = 'assistenza-api-msg';
        listaTrackingOrdine.innerHTML = '';
        if (!cod) { msgTrackingOrdine.textContent = 'Indica il numero ordine.'; msgTrackingOrdine.classList.add('is-err'); return; }
        fetch(urlPedido + '?modo=tracking&ordine_codice=' + encodeURIComponent(cod), { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, j: j }; }); })
            .then(function (x) {
                if (!x.ok) { msgTrackingOrdine.textContent = x.j.error || 'Errore nel caricamento.'; msgTrackingOrdine.classList.add('is-err'); return; }
                msgTrackingOrdine.textContent = 'Seleziona una o più etichette (non annullate).';
                msgTrackingOrdine.classList.add('is-ok');
                renderSpedizioniCheckNamed(listaTrackingOrdine, x.j.spedizioni || [], 'spedizione_ids_tracking[]');
            })
            .catch(function () { msgTrackingOrdine.textContent = 'Errore di rete.'; msgTrackingOrdine.classList.add('is-err'); });
    });

    function renderSpedizioniCheckNamed(container, spedizioni, nameAttr) {
        container.innerHTML = '';
        if (!spedizioni.length) {
            container.innerHTML = '<p class="assistenza-sped-meta">Nessuna etichetta trovata.</p>';
            return;
        }
        spedizioni.forEach(function (s) {
            var id = 'chk-' + nameAttr + '-' + s.id;
            var lab = document.createElement('label');
            lab.innerHTML = '<input type="checkbox" name="' + nameAttr + '" value="' + s.id + '" id="' + id + '">' +
                '<span><strong>' + esc(s.codice_interno) + '</strong> — ' + esc(s.carrier) +
                '<span class="assistenza-sped-meta"><br>' + esc(s.destinatario) + '</span>' +
                linhaRastreioSped(s) + '</span>';
            container.appendChild(lab);
        });
    }

    var msgTrkCod = document.getElementById('msg-tracking-codice');
    var resTrkCod = document.getElementById('res-tracking-codigo');
    var msgTrkNum = document.getElementById('msg-tracking-numero');
    var resTrkNum = document.getElementById('res-tracking-numero');
    var trackingModoRadios = document.querySelectorAll('input[name="tracking_modo"]');
    var trackingPorOrdine = document.getElementById('tracking-por-ordine');
    var trackingPorCodice = document.getElementById('tracking-por-codice');
    var trackingPorNumero = document.getElementById('tracking-por-numero');

    function syncTrackingModo() {
        var v = document.querySelector('input[name="tracking_modo"]:checked');
        v = v ? v.value : 'ordine';
        trackingPorOrdine.style.display = v === 'ordine' ? 'block' : 'none';
        trackingPorCodice.style.display = v === 'codice_interno' ? 'block' : 'none';
        trackingPorNumero.style.display = v === 'tracking' ? 'block' : 'none';
    }
    trackingModoRadios.forEach(function (r) {
        r.addEventListener('change', function () {
            syncTrackingModo();
            lastTrackingCodiceOk = false;
            lastTrackingNumeroOk = false;
        });
    });
    syncTrackingModo();

    document.getElementById('btn-buscar-tracking-codice').addEventListener('click', function () {
        var cod = document.getElementById('codigo_remessa_tracking').value.trim();
        msgTrkCod.textContent = '';
        msgTrkCod.className = 'assistenza-api-msg';
        resTrkCod.innerHTML = '';
        lastTrackingCodiceOk = false;
        if (!cod) { msgTrkCod.textContent = 'Indica il codice spedizione.'; msgTrkCod.classList.add('is-err'); return; }
        fetch(urlCodigo + '?modo=tracking&codigo=' + encodeURIComponent(cod), { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, j: j }; }); })
            .then(function (x) {
                if (!x.ok) { msgTrkCod.textContent = x.j.error || 'Non trovato.'; msgTrkCod.classList.add('is-err'); return; }
                msgTrkCod.textContent = 'Spedizione trovata.';
                msgTrkCod.classList.add('is-ok');
                lastTrackingCodiceOk = true;
                var s = x.j.spedizione;
                resTrkCod.innerHTML = '<p><strong>' + esc(s.codice_interno) + '</strong> — ' + esc(s.carrier) + '</p><p class="assistenza-sped-meta">' + esc(s.destinatario) + '</p>' + linhaRastreioSped(s);
            })
            .catch(function () { msgTrkCod.textContent = 'Errore di rete.'; msgTrkCod.classList.add('is-err'); });
    });

    document.getElementById('btn-buscar-tracking-numero').addEventListener('click', function () {
        var trk = document.getElementById('numero_tracking').value.trim();
        msgTrkNum.textContent = '';
        msgTrkNum.className = 'assistenza-api-msg';
        resTrkNum.innerHTML = '';
        lastTrackingNumeroOk = false;
        if (!trk) { msgTrkNum.textContent = 'Indica il numero tracking.'; msgTrkNum.classList.add('is-err'); return; }
        fetch(urlTracking + '?tracking=' + encodeURIComponent(trk), { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, j: j }; }); })
            .then(function (x) {
                if (!x.ok) { msgTrkNum.textContent = x.j.error || 'Non trovato.'; msgTrkNum.classList.add('is-err'); return; }
                msgTrkNum.textContent = 'Spedizione trovata.';
                msgTrkNum.classList.add('is-ok');
                lastTrackingNumeroOk = true;
                var s = x.j.spedizione;
                resTrkNum.innerHTML = '<p><strong>' + esc(s.codice_interno) + '</strong> — ' + esc(s.carrier) + '</p><p class="assistenza-sped-meta">' + esc(s.destinatario) + '</p>' + linhaRastreioSped(s);
            })
            .catch(function () { msgTrkNum.textContent = 'Errore di rete.'; msgTrkNum.classList.add('is-err'); });
    });

    var listaRiprenot = document.getElementById('lista-riprenot');
    var msgRiprenot = document.getElementById('msg-riprenot');
    document.getElementById('btn-carregar-riprenot').addEventListener('click', function () {
        var cod = document.getElementById('ordine_codice_riprenot').value.trim();
        var cor = document.getElementById('riprenot_corriere').value.trim();
        msgRiprenot.textContent = '';
        msgRiprenot.className = 'assistenza-api-msg';
        listaRiprenot.innerHTML = '';
        if (!cor) { msgRiprenot.textContent = 'Seleziona un corriere.'; msgRiprenot.classList.add('is-err'); return; }
        if (!cod) { msgRiprenot.textContent = 'Indica il numero ordine.'; msgRiprenot.classList.add('is-err'); return; }
        fetch(urlPedido + '?modo=riprenotazione_ritiro&ordine_codice=' + encodeURIComponent(cod) + '&corriere=' + encodeURIComponent(cor), { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, j: j }; }); })
            .then(function (x) {
                if (!x.ok) { msgRiprenot.textContent = x.j.error || 'Errore nel caricamento.'; msgRiprenot.classList.add('is-err'); return; }
                msgRiprenot.textContent = 'Seleziona le etichette da riprenotare.';
                msgRiprenot.classList.add('is-ok');
                renderSpedizioniCheckNamed(listaRiprenot, x.j.spedizioni || [], 'spedizione_ids_riprenot[]');
            })
            .catch(function () { msgRiprenot.textContent = 'Errore di rete.'; msgRiprenot.classList.add('is-err'); });
    });

    document.getElementById('form-assistenza').addEventListener('submit', function (e) {
        var v = tipoSel.value;
        if (idEntrega && String(v) === String(idEntrega)) {
            if (!listaEntrega.querySelector('input[name="spedizione_id_entrega"]:checked')) {
                e.preventDefault();
                msgEntrega.textContent = 'Seleziona un\'etichetta.';
                msgEntrega.className = 'assistenza-api-msg is-err';
                return;
            }
        }
        if (idEtiquetaNg && String(v) === String(idEtiquetaNg)) {
            var modo = document.querySelector('input[name="etiqueta_ng_modo"]:checked');
            modo = modo ? modo.value : 'pedido';
            var bodyEl = document.getElementById('body');
            if (!bodyEl || !bodyEl.value.trim()) {
                e.preventDefault();
                alert('Indica il messaggio per il team.');
                return;
            }
            if (modo === 'pedido') {
                if (!listaNg.querySelector('input[name="spedizione_ids_ng[]"]:checked')) {
                    e.preventDefault();
                    msgNg.textContent = 'Seleziona almeno una spedizione.';
                    msgNg.className = 'assistenza-api-msg is-err';
                    return;
                }
            } else {
                if (!lastNgCodiceInterno || !msgCod.classList.contains('is-ok')) {
                    e.preventDefault();
                    msgCod.textContent = 'Trova prima la spedizione con il pulsante sopra.';
                    msgCod.className = 'assistenza-api-msg is-err';
                    return;
                }
            }
        }
        if (idFattura && String(v) === String(idFattura)) {
            var mese = document.getElementById('fattura_mese');
            var anno = document.getElementById('fattura_anno');
            if (!mese || !mese.value || !anno || !anno.value) {
                e.preventDefault();
                alert('Seleziona mese e anno del periodo fattura.');
                return;
            }
        }
        if (idTracking && String(v) === String(idTracking)) {
            var tModo = document.querySelector('input[name="tracking_modo"]:checked');
            tModo = tModo ? tModo.value : 'ordine';
            if (tModo === 'ordine') {
                if (!listaTrackingOrdine.querySelector('input[name="spedizione_ids_tracking[]"]:checked')) {
                    e.preventDefault();
                    msgTrackingOrdine.textContent = 'Seleziona almeno un\'etichetta.';
                    msgTrackingOrdine.className = 'assistenza-api-msg is-err';
                    return;
                }
            } else if (tModo === 'codice_interno') {
                if (!lastTrackingCodiceOk) {
                    e.preventDefault();
                    msgTrkCod.textContent = 'Trova prima la spedizione con il pulsante sopra.';
                    msgTrkCod.className = 'assistenza-api-msg is-err';
                    return;
                }
            } else if (!lastTrackingNumeroOk) {
                e.preventDefault();
                msgTrkNum.textContent = 'Trova prima la spedizione con il pulsante sopra.';
                msgTrkNum.className = 'assistenza-api-msg is-err';
                return;
            }
        }
        if (idRiprenot && String(v) === String(idRiprenot)) {
            if (!document.getElementById('riprenot_corriere').value.trim()) {
                e.preventDefault();
                msgRiprenot.textContent = 'Seleziona un corriere.';
                msgRiprenot.className = 'assistenza-api-msg is-err';
                return;
            }
            if (!listaRiprenot.querySelector('input[name="spedizione_ids_riprenot[]"]:checked')) {
                e.preventDefault();
                msgRiprenot.textContent = 'Seleziona almeno un\'etichetta.';
                msgRiprenot.className = 'assistenza-api-msg is-err';
                return;
            }
        }
    });
})();
</script>
@endsection
