@extends('layouts.app')

@section('content')
@php
    $a = $anagrafica;
    $u = auth()->user();
    $tipo = $u->tipo_utente ?? 'privato';
    $isImpresa = $tipo !== 'privato';
    $titoloDati = $isImpresa ? 'Dati azienda e referente' : 'Dati personali';
@endphp

<div class="sq-profilo-page">
    <h1 class="sq-h1-carrello sq-text-heading sq-mb-8">La mia anagrafica</h1>

    @if (session('ok'))
        <div class="sq-alert sq-alert--success sq-mb-14">{{ session('ok') }}</div>
    @endif
    @if (session('info'))
        <div class="sq-alert sq-alert--info-warm sq-mb-14">{{ session('info') }}</div>
    @endif
    @if ($errors->has('profilo'))
        <div class="sq-alert sq-alert--error sq-mb-14">{{ $errors->first('profilo') }}</div>
    @endif

    @if (! $a)
        <div class="sq-alert sq-alert--error sq-mb-14">
            Non risulta un’anagrafica associata. <a href="{{ route('register.complete') }}">Completa la registrazione</a>.
        </div>
    @else
        <form method="POST" action="{{ route('profilo.anagrafica.update') }}" id="form-profilo-anagrafica" autocomplete="off">
            @csrf
            <input type="hidden" name="id_comune" id="id_comune_profilo" value="{{ old('id_comune', $idComuneCorrente !== null ? (string) $idComuneCorrente : '') }}">

            <div class="sq-profilo-cards-grid">
                {{-- Card sinistra: dati personali / azienda --}}
                <div class="sq-profilo-card sq-profilo-card--stacked" id="card-dati">
                    <div class="sq-profilo-card-head">
                        <h2 class="sq-profilo-card-title">{{ $titoloDati }}</h2>
                    </div>
                    <div class="sq-profilo-card-stack">
                        <div class="sq-profilo-card-view" id="view-dati" aria-hidden="false">
                            <div class="sq-profilo-kv"><span class="sq-profilo-k">Email</span><span class="sq-profilo-v">{{ e($u->email) }}</span></div>
                            @if ($isImpresa)
                                <div class="sq-profilo-kv"><span class="sq-profilo-k">Ragione sociale</span><span class="sq-profilo-v" id="disp-denominazione">{{ e($a->denominazione_ragione_sociale ?? '—') }}</span></div>
                                <div class="sq-profilo-kv"><span class="sq-profilo-k">P. IVA</span><span class="sq-profilo-v" id="disp-partita_iva">{{ e($a->partita_iva ?? '—') }}</span></div>
                                <div class="sq-profilo-kv"><span class="sq-profilo-k">PEC</span><span class="sq-profilo-v" id="disp-pec">{{ e($a->pec ?? '—') }}</span></div>
                                <div class="sq-profilo-kv"><span class="sq-profilo-k">Codice SDI</span><span class="sq-profilo-v" id="disp-codice_sdi">{{ e($a->codice_sdi ?? '—') }}</span></div>
                            @endif
                            <div class="sq-profilo-kv"><span class="sq-profilo-k">Codice fiscale</span><span class="sq-profilo-v">{{ e($a->codice_fiscale ?? '—') }}</span></div>
                            <div class="sq-profilo-kv"><span class="sq-profilo-k">Nome</span><span class="sq-profilo-v" id="disp-nome">{{ e($a->nome ?? '—') }}</span></div>
                            <div class="sq-profilo-kv"><span class="sq-profilo-k">Cognome</span><span class="sq-profilo-v" id="disp-cognome">{{ e($a->cognome ?? '—') }}</span></div>
                            <div class="sq-profilo-kv"><span class="sq-profilo-k">Telefono</span><span class="sq-profilo-v" id="disp-telefono">{{ e($a->telefono ?? '—') }}</span></div>
                        </div>

                        <div class="sq-profilo-card-form sq-profilo-hidden" id="edit-dati" aria-hidden="true">
                            <div class="sq-profilo-field">
                                <label for="profilo_email_display" class="sq-profilo-label">Email</label>
                                <input type="email" id="profilo_email_display" class="sq-profilo-input sq-profilo-input--ro" value="{{ e($u->email) }}" readonly disabled tabindex="-1">
                                <span class="sq-profilo-hint sq-text-muted sq-text-12">L’email di accesso non si modifica da qui.</span>
                            </div>

                            @if ($isImpresa)
                                <div class="sq-profilo-field">
                                    <label for="denominazione_ragione_sociale" class="sq-profilo-label">Ragione sociale / denominazione <span class="sq-profilo-req">*</span></label>
                                    <input type="text" name="denominazione_ragione_sociale" id="denominazione_ragione_sociale" class="sq-profilo-input" required maxlength="255"
                                           value="{{ old('denominazione_ragione_sociale', $a->denominazione_ragione_sociale) }}">
                                    @error('denominazione_ragione_sociale')<span class="sq-profilo-err">{{ $message }}</span>@enderror
                                </div>
                                <div class="sq-profilo-field">
                                    <label for="partita_iva" class="sq-profilo-label">Partita IVA <span class="sq-profilo-req">*</span></label>
                                    <input type="text" name="partita_iva" id="partita_iva" class="sq-profilo-input" required maxlength="11" inputmode="numeric"
                                           value="{{ old('partita_iva', $a->partita_iva) }}">
                                    @error('partita_iva')<span class="sq-profilo-err">{{ $message }}</span>@enderror
                                </div>
                                <div class="sq-profilo-field">
                                    <label for="pec" class="sq-profilo-label">PEC</label>
                                    <input type="email" name="pec" id="pec" class="sq-profilo-input" maxlength="255"
                                           value="{{ old('pec', $a->pec) }}">
                                    @error('pec')<span class="sq-profilo-err">{{ $message }}</span>@enderror
                                </div>
                                <div class="sq-profilo-field">
                                    <label for="codice_sdi" class="sq-profilo-label">Codice SDI</label>
                                    <input type="text" name="codice_sdi" id="codice_sdi" class="sq-profilo-input" maxlength="7"
                                           value="{{ old('codice_sdi', $a->codice_sdi) }}">
                                    @error('codice_sdi')<span class="sq-profilo-err">{{ $message }}</span>@enderror
                                </div>
                            @endif

                            <div class="sq-profilo-field">
                                <label for="nome" class="sq-profilo-label">Nome <span class="sq-profilo-req">*</span></label>
                                <input type="text" name="nome" id="nome" class="sq-profilo-input" required maxlength="255" value="{{ old('nome', $a->nome) }}">
                                @error('nome')<span class="sq-profilo-err">{{ $message }}</span>@enderror
                            </div>
                            <div class="sq-profilo-field">
                                <label for="cognome" class="sq-profilo-label">Cognome <span class="sq-profilo-req">*</span></label>
                                <input type="text" name="cognome" id="cognome" class="sq-profilo-input" required maxlength="255" value="{{ old('cognome', $a->cognome) }}">
                                @error('cognome')<span class="sq-profilo-err">{{ $message }}</span>@enderror
                            </div>
                            <div class="sq-profilo-field">
                                <label for="telefono" class="sq-profilo-label">Telefono <span class="sq-profilo-req">*</span></label>
                                <input type="tel" name="telefono" id="telefono" class="sq-profilo-input" required maxlength="20" value="{{ old('telefono', $a->telefono) }}">
                                @error('telefono')<span class="sq-profilo-err">{{ $message }}</span>@enderror
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Card destra: indirizzo --}}
                <div class="sq-profilo-card sq-profilo-card--stacked" id="card-indirizzo">
                    <div class="sq-profilo-card-head">
                        <h2 class="sq-profilo-card-title">Indirizzo</h2>
                    </div>
                    <div class="sq-profilo-card-stack">
                        <div class="sq-profilo-card-view" id="view-indirizzo" aria-hidden="false">
                            <div class="sq-profilo-kv"><span class="sq-profilo-k">Città</span><span class="sq-profilo-v" id="disp-citta">{{ e($a->citta ?? '—') }}</span></div>
                            <div class="sq-profilo-kv"><span class="sq-profilo-k">CAP</span><span class="sq-profilo-v" id="disp-cap">{{ e($a->cap ?? '—') }}</span></div>
                            <div class="sq-profilo-kv"><span class="sq-profilo-k">Provincia</span><span class="sq-profilo-v" id="disp-provincia">{{ e($a->provincia ?? '—') }}</span></div>
                            <div class="sq-profilo-kv"><span class="sq-profilo-k">Strada</span><span class="sq-profilo-v" id="disp-indirizzo">{{ e($a->indirizzo ?? '—') }}</span></div>
                            <div class="sq-profilo-kv"><span class="sq-profilo-k">Numero</span><span class="sq-profilo-v" id="disp-civico">{{ e($a->civico ?? '—') }}</span></div>
                        </div>

                        <div class="sq-profilo-card-form sq-profilo-hidden" id="edit-indirizzo" aria-hidden="true">
                            <div class="sq-profilo-field sq-profilo-field--suggest sq-profilo-mb-15">
                                <label for="profilo_citta" class="sq-profilo-label">Città <span class="sq-profilo-req">*</span></label>
                                <input type="text" name="citta" id="profilo_citta" class="sq-profilo-input" required maxlength="255"
                                       placeholder="Inizia a scrivere il comune…" value="{{ old('citta', $a->citta) }}">
                                <div class="sq-profilo-suggest" id="suggest_profilo_citta" hidden></div>
                                @error('citta')<span class="sq-profilo-err">{{ $message }}</span>@enderror
                            </div>
                            <div class="sq-profilo-cap-pv-row">
                                <div class="sq-profilo-field sq-profilo-field--suggest sq-profilo-mb-0">
                                    <label for="profilo_cap" class="sq-profilo-label">CAP <span class="sq-profilo-req">*</span></label>
                                    <input type="text" name="cap" id="profilo_cap" class="sq-profilo-input" required maxlength="5" inputmode="numeric" placeholder="CAP…"
                                           value="{{ old('cap', $a->cap) }}">
                                    <div class="sq-profilo-suggest" id="suggest_profilo_cap" hidden></div>
                                    @error('cap')<span class="sq-profilo-err">{{ $message }}</span>@enderror
                                </div>
                                <div class="sq-profilo-field sq-profilo-mb-0">
                                    <label for="profilo_provincia" class="sq-profilo-label">Prov. <span class="sq-profilo-req">*</span></label>
                                    <input type="text" name="provincia" id="profilo_provincia" class="sq-profilo-input sq-profilo-input--ro" required maxlength="2" placeholder="PV"
                                           value="{{ old('provincia', $a->provincia) }}" readonly tabindex="-1" title="Provincia impostata automaticamente da Città o CAP">
                                    <span class="sq-profilo-hint sq-text-muted sq-text-12">Dall’elenco Città o CAP.</span>
                                    @error('provincia')<span class="sq-profilo-err">{{ $message }}</span>@enderror
                                </div>
                            </div>
                            <div class="sq-profilo-addr-row">
                                <div class="sq-profilo-field sq-profilo-mb-0">
                                    <label for="profilo_strada" class="sq-profilo-label">Strada <span class="sq-profilo-req">*</span></label>
                                    <input type="text" name="indirizzo" id="profilo_strada" class="sq-profilo-input" required maxlength="255" value="{{ old('indirizzo', $a->indirizzo) }}">
                                    @error('indirizzo')<span class="sq-profilo-err">{{ $message }}</span>@enderror
                                </div>
                                <div class="sq-profilo-field sq-profilo-mb-0">
                                    <label for="profilo_civico" class="sq-profilo-label">Civico <span class="sq-profilo-req">*</span></label>
                                    <input type="text" name="civico" id="profilo_civico" class="sq-profilo-input" required maxlength="10" value="{{ old('civico', $a->civico) }}">
                                    @error('civico')<span class="sq-profilo-err">{{ $message }}</span>@enderror
                                </div>
                            </div>
                            @error('id_comune')<span class="sq-profilo-err sq-profilo-err-block">{{ $message }}</span>@enderror
                            <p class="sq-profilo-suggest-hint sq-text-muted sq-text-14 sq-m-0 sq-mt-10">
                                Scrivi in <strong>Città</strong> o in <strong>CAP</strong> e scegli una riga dall’elenco per allineare CAP, comune e provincia.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="sq-profilo-global-actions" id="sq-profilo-anagrafica-actions" data-profilo-actions="idle">
                <button type="button" class="sq-profilo-azione-btn sq-profilo-btn-sm" id="btn-modifica-profilo">Modifica</button>
                <button type="submit" class="sq-profilo-azione-btn sq-profilo-btn-sm" id="btn-conferma-profilo" disabled>Conferma</button>
                <button type="button" class="sq-profilo-azione-btn sq-profilo-btn-sm" id="btn-annulla-profilo" disabled>Annulla</button>
            </div>
        </form>
    @endif
</div>

@if ($a)
<script>
(() => {
    const suggestUrl = @json(route('api.comuni.suggest'));
    const idComuneStart = @json((string) old('id_comune', $idComuneCorrente !== null ? (string) $idComuneCorrente : ''));
    const isImpresa = @json($isImpresa);

    const MSG_CONFERMA = 'Stai aggiornando i dati dell’anagrafica.\n\n'
        + 'Le tue spedizioni e i pagamenti già registrati non cambiano: restano con i dati di quando sono stati effettuati.\n\n'
        + 'Vuoi confermare?';

    function debounce(fn, ms) {
        let t = null;
        return (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), ms); };
    }

    function hideSuggest(el) {
        if (!el) return;
        el.hidden = true;
        el.innerHTML = '';
    }

    function showSuggest(el) {
        if (!el) return;
        el.hidden = false;
    }

    function fillComune(it) {
        const hid = document.getElementById('id_comune_profilo');
        if (hid) hid.value = String(it.id);
        const cap = document.getElementById('profilo_cap');
        const cit = document.getElementById('profilo_citta');
        const pv = document.getElementById('profilo_provincia');
        if (cap) cap.value = it.cap;
        if (cit) cit.value = it.comune;
        if (pv) pv.value = (it.provincia || '').toString().substring(0, 2).toUpperCase();
        markDirty();
    }

    function wireSuggestCap() {
        const input = document.getElementById('profilo_cap');
        const list = document.getElementById('suggest_profilo_cap');
        if (!input || !list) return;
        let controller = null;
        const run = debounce(async () => {
            const q = (input.value || '').trim().replace(/\D/g, '').substring(0, 5);
            if (q.length < 1) { hideSuggest(list); return; }
            try {
                if (controller) controller.abort();
                controller = new AbortController();
                const res = await fetch(`${suggestUrl}?q=${encodeURIComponent(q)}`, {
                    signal: controller.signal,
                    headers: { 'Accept': 'application/json' },
                });
                if (!res.ok) { hideSuggest(list); return; }
                const items = await res.json();
                if (!Array.isArray(items) || !items.length) { hideSuggest(list); return; }
                list.innerHTML = '';
                for (const it of items) {
                    const row = document.createElement('button');
                    row.type = 'button';
                    row.className = 'sq-profilo-suggest-item';
                    row.textContent = it.label || `${it.cap} — ${it.comune} (${it.provincia})`;
                    row.addEventListener('mousedown', (e) => {
                        e.preventDefault();
                        fillComune(it);
                        hideSuggest(list);
                    });
                    list.appendChild(row);
                }
                showSuggest(list);
            } catch (e) {
                if (e.name !== 'AbortError') hideSuggest(list);
            }
        }, 200);
        input.addEventListener('input', () => {
            const hid = document.getElementById('id_comune_profilo');
            if (hid) hid.value = '';
            markDirty();
            run();
        });
        document.addEventListener('click', (e) => {
            if (list.hidden) return;
            if (e.target === input || list.contains(e.target)) return;
            hideSuggest(list);
        });
    }

    function wireSuggestCitta() {
        const input = document.getElementById('profilo_citta');
        const list = document.getElementById('suggest_profilo_citta');
        if (!input || !list) return;
        let controller = null;
        const run = debounce(async () => {
            const q = (input.value || '').trim();
            if (q.length < 2) { hideSuggest(list); return; }
            if (/^\d+$/.test(q)) { hideSuggest(list); return; }
            try {
                if (controller) controller.abort();
                controller = new AbortController();
                const res = await fetch(`${suggestUrl}?q=${encodeURIComponent(q)}`, {
                    signal: controller.signal,
                    headers: { 'Accept': 'application/json' },
                });
                if (!res.ok) { hideSuggest(list); return; }
                const items = await res.json();
                if (!Array.isArray(items) || !items.length) { hideSuggest(list); return; }
                list.innerHTML = '';
                for (const it of items) {
                    const row = document.createElement('button');
                    row.type = 'button';
                    row.className = 'sq-profilo-suggest-item';
                    row.textContent = it.label || `${it.cap} — ${it.comune} (${it.provincia})`;
                    row.addEventListener('mousedown', (e) => {
                        e.preventDefault();
                        fillComune(it);
                        hideSuggest(list);
                    });
                    list.appendChild(row);
                }
                showSuggest(list);
            } catch (e) {
                if (e.name !== 'AbortError') hideSuggest(list);
            }
        }, 200);
        input.addEventListener('input', () => {
            const hid = document.getElementById('id_comune_profilo');
            if (hid) hid.value = '';
            markDirty();
            run();
        });
        document.addEventListener('click', (e) => {
            if (list.hidden) return;
            if (e.target === input || list.contains(e.target)) return;
            hideSuggest(list);
        });
    }

    function val(id) {
        const el = document.getElementById(id);
        return el ? (el.value || '').trim() : '';
    }

    function snapshotAll() {
        const o = {
            nome: val('nome'),
            cognome: val('cognome'),
            telefono: val('telefono'),
            cap: val('profilo_cap'),
            citta: val('profilo_citta'),
            provincia: val('profilo_provincia'),
            indirizzo: val('profilo_strada'),
            civico: val('profilo_civico'),
            id_comune: (document.getElementById('id_comune_profilo')?.value || '').trim(),
        };
        if (isImpresa) {
            o.denominazione_ragione_sociale = val('denominazione_ragione_sociale');
            o.partita_iva = val('partita_iva');
            o.pec = val('pec');
            o.codice_sdi = val('codice_sdi');
        }
        return JSON.stringify(o);
    }

    let editing = false;
    let baseSnapshot = '';
    const form = document.getElementById('form-profilo-anagrafica');
    const actionsBar = document.getElementById('sq-profilo-anagrafica-actions');
    const btnMod = document.getElementById('btn-modifica-profilo');
    const btnConf = document.getElementById('btn-conferma-profilo');
    const btnAnn = document.getElementById('btn-annulla-profilo');
    const viewDati = document.getElementById('view-dati');
    const editDati = document.getElementById('edit-dati');
    const viewInd = document.getElementById('view-indirizzo');
    const editInd = document.getElementById('edit-indirizzo');

    function setPanels(editMode) {
        editing = editMode;
        [viewDati, viewInd].forEach((v) => {
            if (!v) return;
            v.classList.toggle('sq-profilo-hidden', editMode);
            v.setAttribute('aria-hidden', editMode ? 'true' : 'false');
        });
        [editDati, editInd].forEach((f) => {
            if (!f) return;
            f.classList.toggle('sq-profilo-hidden', !editMode);
            f.setAttribute('aria-hidden', editMode ? 'false' : 'true');
        });
        syncActionButtons();
    }

    /** Fuori modifica: solo Modifica abilitata. In modifica: Modifica disabilitata, Conferma e Annulla abilitate (anche senza cambiamenti ancora). */
    function syncActionButtons() {
        if (!btnMod || !btnConf || !btnAnn) return;
        if (!editing) {
            btnMod.disabled = false;
            btnConf.disabled = true;
            btnAnn.disabled = true;
            if (actionsBar) actionsBar.dataset.profiloActions = 'idle';
            return;
        }
        btnMod.disabled = true;
        btnConf.disabled = false;
        btnAnn.disabled = false;
        if (actionsBar) actionsBar.dataset.profiloActions = 'edit';
    }

    function markDirty() {
        syncActionButtons();
    }

    function enterEdit() {
        if (editing) {
            return;
        }
        baseSnapshot = snapshotAll();
        setPanels(true);
        document.getElementById('nome')?.focus();
    }

    function resetFromBase() {
        try {
            const d = JSON.parse(baseSnapshot);
            const set = (id, v) => { const el = document.getElementById(id); if (el) el.value = v ?? ''; };
            set('nome', d.nome);
            set('cognome', d.cognome);
            set('telefono', d.telefono);
            set('profilo_cap', d.cap);
            set('profilo_citta', d.citta);
            set('profilo_provincia', d.provincia);
            set('profilo_strada', d.indirizzo);
            set('profilo_civico', d.civico);
            const hid = document.getElementById('id_comune_profilo');
            if (hid) hid.value = d.id_comune || '';
            if (isImpresa) {
                set('denominazione_ragione_sociale', d.denominazione_ragione_sociale);
                set('partita_iva', d.partita_iva);
                set('pec', d.pec);
                set('codice_sdi', d.codice_sdi);
            }
        } catch (e) { /* ignore */ }
        hideSuggest(document.getElementById('suggest_profilo_cap'));
        hideSuggest(document.getElementById('suggest_profilo_citta'));
        markDirty();
    }

    function exitEdit() {
        resetFromBase();
        setPanels(false);
    }

    btnMod?.addEventListener('click', enterEdit);
    btnAnn?.addEventListener('click', () => {
        if (!editing) return;
        exitEdit();
    });

    const watchIds = ['nome', 'cognome', 'telefono', 'profilo_cap', 'profilo_citta', 'profilo_strada', 'profilo_civico'];
    if (isImpresa) {
        watchIds.push('denominazione_ragione_sociale', 'partita_iva', 'pec', 'codice_sdi');
    }
    watchIds.forEach((id) => document.getElementById(id)?.addEventListener('input', markDirty));

    form?.addEventListener('submit', (e) => {
        if (form.dataset.confirmed === '1') {
            form.dataset.confirmed = '';
            return;
        }
        e.preventDefault();
        const idc = (document.getElementById('id_comune_profilo')?.value || '').trim();
        if (!idc) {
            alert('Seleziona CAP o città dall’elenco suggerito in modo che CAP, città e provincia coincidano con un comune valido.');
            return;
        }
        if (confirm(MSG_CONFERMA)) {
            form.dataset.confirmed = '1';
            /* Dopo “Sì” l’utente ha confermato: subito UI come post-salvataggio (pannelli lettura + solo Modifica attiva). I valori del form restano per il POST. */
            setPanels(false);
            requestAnimationFrame(() => form.requestSubmit());
        }
    });

    wireSuggestCap();
    wireSuggestCitta();

    if (idComuneStart) {
        const hid = document.getElementById('id_comune_profilo');
        if (hid && !hid.value) hid.value = idComuneStart;
    }

    @if ($errors->any())
        enterEdit();
    @endif

    syncActionButtons();
})();
</script>
@endif
@endsection
