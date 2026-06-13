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

        <div class="assistenza-card" id="lista-richieste">
            <div class="assistenza-card__head">
                <div class="assistenza-card__head-text">
                    <h2>Le mie richieste</h2>
                    <p>Segui lo stato delle tue richieste di assistenza. Consulta anche le <a href="{{ route('faq.index') }}" class="assistenza-faq-link">FAQ</a>.</p>
                </div>
                <button type="button" class="assistenza-btn-nova" id="btn-abrir-nova-richiesta">
                    <i class="fa-solid fa-plus" aria-hidden="true"></i> Nuova richiesta
                </button>
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
                    <p class="assistenza-empty">Non hai ancora richieste. Usa il pulsante a destra per aprire la prima.</p>
                @endforelse
            </div>

            @php
                $tiposProblema = $tiposProblema ?? collect();
            @endphp
            @if ($tiposProblema->isEmpty())
                <p class="sq-alert sq-alert--error sq-mt-16" role="alert">Non ci sono tipi di problema configurati. Contatta l'assistenza.</p>
            @else
                <div id="nova-richiesta" class="assistenza-painel-nova" @if (! $errors->any()) hidden @endif>
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
                            <p class="assistenza-sped-meta">Indica il <strong>numero ordine</strong> (come in «Ordini spedizioni», es. O123). Verranno elencate le spedizioni <strong>pagate</strong> di quell'ordine. Seleziona <strong>una</strong> etichetta.</p>
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

                        <label for="oggetto">Oggetto</label>
                        <input type="text" id="oggetto" name="oggetto" value="{{ old('oggetto') }}" required maxlength="500" placeholder="Breve riepilogo">

                        <label for="body">Messaggio <span class="assistenza-sped-meta" style="font-weight:400;">(ciò che il team vede nel ticket)</span></label>
                        <textarea id="body" name="body" required placeholder="Descrivi la tua richiesta…">{{ old('body') }}</textarea>

                        <button type="submit" class="assistenza-btn-submit">Invia richiesta</button>
                    </form>
                </div>
            @endif
        </div>
    </div>
</div>

<script>
(function () {
    var tipoSel = document.getElementById('ticket_tipo_problema_id');
    if (!tipoSel) return;

    var idEntrega = @json($tipoEntregaId);
    var idEtiquetaNg = @json($tipoEtiquetaNaoGeradaId);
    var urlPedido = @json(route('assistenza.api.spedizioni_ordine'));
    var urlCodigo = @json(route('assistenza.api.spedizione_codice'));

    var boxEntrega = document.getElementById('fluxo-entrega');
    var boxNg = document.getElementById('fluxo-etiqueta-ng');
    var listaEntrega = document.getElementById('lista-entrega');
    var msgEntrega = document.getElementById('msg-entrega');
    var listaNg = document.getElementById('lista-ng-pedido');
    var msgNg = document.getElementById('msg-ng-pedido');
    var painelNova = document.getElementById('nova-richiesta');
    var inpOggetto = document.getElementById('oggetto');
    var taBody = document.getElementById('body');
    var assuntoEtiquetaNg = 'Etichetta non generata';
    var lastNgCodiceInterno = null;

    function abrirPainelNova() {
        if (!painelNova) return;
        painelNova.removeAttribute('hidden');
        painelNova.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        try { history.replaceState(null, '', '#nova-richiesta'); } catch (e) {}
    }

    var btnAbrirNova = document.getElementById('btn-abrir-nova-richiesta');
    if (btnAbrirNova) btnAbrirNova.addEventListener('click', abrirPainelNova);
    if (window.location.hash === '#nova-richiesta') abrirPainelNova();

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
        boxEntrega.classList.toggle('is-on', isEnt);
        boxNg.classList.toggle('is-on', isNg);
        document.querySelectorAll('#fluxo-entrega input[type="text"], #fluxo-entrega button').forEach(function (el) {
            el.disabled = !isEnt;
        });
        document.querySelectorAll('#fluxo-etiqueta-ng input, #fluxo-etiqueta-ng button').forEach(function (el) {
            el.disabled = !isNg;
        });
    }

    function onTipoProblemaChange(fromUser) {
        toggleFluxos();
        if (!idEtiquetaNg || String(tipoSel.value) !== String(idEtiquetaNg)) return;
        if (fromUser && inpOggetto) inpOggetto.value = assuntoEtiquetaNg;
        updateNgPrecarregada();
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
        fetch(urlCodigo + '?codigo=' + encodeURIComponent(cod), { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
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
    });
})();
</script>
@endsection
