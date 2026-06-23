<script>
(() => {
    const suggestUrl = @json(route('api.comuni.suggest'));
    const idComuneStart = @json((string) old('id_comune', $idComuneCorrente !== null ? (string) $idComuneCorrente : ''));
    const isImpresa = @json($isImpresa);

    const MSG_CONFERMA = 'Stai aggiornando l’anagrafica dell’utente dal back office.\n\n'
        + 'Spedizioni e pagamenti già registrati non cambiano.\n\n'
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
        const hid = document.getElementById('id_comune_bo_anag');
        if (hid) hid.value = String(it.id);
        const cap = document.getElementById('bo_cap');
        const cit = document.getElementById('bo_citta');
        const pv = document.getElementById('bo_provincia');
        if (cap) cap.value = it.cap;
        if (cit) cit.value = it.comune;
        if (pv) pv.value = (it.provincia || '').toString().substring(0, 2).toUpperCase();
        markDirty();
    }

    function wireSuggestCap() {
        const input = document.getElementById('bo_cap');
        const list = document.getElementById('suggest_bo_cap');
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
            const hid = document.getElementById('id_comune_bo_anag');
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
        const input = document.getElementById('bo_citta');
        const list = document.getElementById('suggest_bo_citta');
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
            const hid = document.getElementById('id_comune_bo_anag');
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
            nome: val('bo_nome'),
            cognome: val('bo_cognome'),
            telefono: val('bo_telefono'),
            cap: val('bo_cap'),
            citta: val('bo_citta'),
            provincia: val('bo_provincia'),
            indirizzo: val('bo_strada'),
            civico: val('bo_civico'),
            id_comune: (document.getElementById('id_comune_bo_anag')?.value || '').trim(),
            sede_liccardi: document.getElementById('bo_sede_liccardi')?.checked ? '1' : '0',
        };
        if (isImpresa) {
            o.denominazione_ragione_sociale = val('bo_denominazione_ragione_sociale');
            o.partita_iva = val('bo_partita_iva');
            o.pec = val('bo_pec');
            o.codice_sdi = val('bo_codice_sdi');
        }
        return JSON.stringify(o);
    }

    let editing = false;
    let baseSnapshot = '';
    const form = document.getElementById('form-bo-anagrafica');
    const actionsBar = document.getElementById('sq-bo-anagrafica-actions');
    const btnMod = document.getElementById('btn-bo-modifica-anag');
    const btnConf = document.getElementById('btn-bo-conferma-anag');
    const btnAnn = document.getElementById('btn-bo-annulla-anag');
    const viewDati = document.getElementById('bo-view-dati');
    const editDati = document.getElementById('bo-edit-dati');
    const viewInd = document.getElementById('bo-view-indirizzo');
    const editInd = document.getElementById('bo-edit-indirizzo');

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
        if (editing) return;
        baseSnapshot = snapshotAll();
        setPanels(true);
        document.getElementById('bo_nome')?.focus();
    }

    function resetFromBase() {
        try {
            const d = JSON.parse(baseSnapshot);
            const set = (id, v) => { const el = document.getElementById(id); if (el) el.value = v ?? ''; };
            set('bo_nome', d.nome);
            set('bo_cognome', d.cognome);
            set('bo_telefono', d.telefono);
            set('bo_cap', d.cap);
            set('bo_citta', d.citta);
            set('bo_provincia', d.provincia);
            set('bo_strada', d.indirizzo);
            set('bo_civico', d.civico);
            const hid = document.getElementById('id_comune_bo_anag');
            if (hid) hid.value = d.id_comune || '';
            if (isImpresa) {
                set('bo_denominazione_ragione_sociale', d.denominazione_ragione_sociale);
                set('bo_partita_iva', d.partita_iva);
                set('bo_pec', d.pec);
                set('bo_codice_sdi', d.codice_sdi);
            }
            const sede = document.getElementById('bo_sede_liccardi');
            if (sede) sede.checked = d.sede_liccardi === '1';
        } catch (e) { /* ignore */ }
        hideSuggest(document.getElementById('suggest_bo_cap'));
        hideSuggest(document.getElementById('suggest_bo_citta'));
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

    const watchIds = ['bo_nome', 'bo_cognome', 'bo_telefono', 'bo_cap', 'bo_citta', 'bo_strada', 'bo_civico'];
    if (isImpresa) {
        watchIds.push('bo_denominazione_ragione_sociale', 'bo_partita_iva', 'bo_pec', 'bo_codice_sdi');
    }
    watchIds.forEach((id) => document.getElementById(id)?.addEventListener('input', markDirty));
    document.getElementById('bo_sede_liccardi')?.addEventListener('change', markDirty);

    form?.addEventListener('submit', (e) => {
        if (form.dataset.confirmed === '1') {
            form.dataset.confirmed = '';
            return;
        }
        e.preventDefault();
        const idc = (document.getElementById('id_comune_bo_anag')?.value || '').trim();
        if (!idc) {
            alert('Seleziona CAP o città dall’elenco suggerito in modo che CAP, città e provincia coincidano con un comune valido.');
            return;
        }
        if (confirm(MSG_CONFERMA)) {
            form.dataset.confirmed = '1';
            setPanels(false);
            requestAnimationFrame(() => form.requestSubmit());
        }
    });

    wireSuggestCap();
    wireSuggestCitta();

    if (idComuneStart) {
        const hid = document.getElementById('id_comune_bo_anag');
        if (hid && !hid.value) hid.value = idComuneStart;
    }

    @if ($errors->any())
        enterEdit();
    @endif

    syncActionButtons();
})();
</script>
