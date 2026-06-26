@php
    $m = $mittenza;
    $oldIdComune = old('id_comune', $idComuneCorrente !== null ? (string) $idComuneCorrente : '');
    $mittenteAnagraficaOld = old('mittente_anagrafica');
    if ($mittenteAnagraficaOld === 'azienda' || $mittenteAnagraficaOld === 'privato') {
        $mittenteAnagraficaDefault = $mittenteAnagraficaOld;
    } else {
        $mittenteAnagraficaDefault = trim((string) ($m?->denominazione_ragione_sociale ?? '')) !== '' ? 'azienda' : 'privato';
    }
@endphp

<div class="sq-profilo-page sq-page-preventivi">
    <h1 class="sq-h1-carrello sq-mb-16">{{ $titolo }}</h1>

    @if ($errors->any())
        <div class="sq-alert sq-alert--error sq-mb-14">
            <ul class="sq-m-0 sq-pl-18">
                @foreach ($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ $action }}" id="form-mittenza" autocomplete="off" class="sq-profilo-cards-grid" style="grid-template-columns: 1fr;">
        @csrf
        @if ($method !== 'POST')
            @method($method)
        @endif
        <input type="hidden" name="id_comune" id="id_comune_mittenza" value="{{ $oldIdComune }}">

        <div class="sq-profilo-card sq-profilo-card--stacked">
            <div class="sq-profilo-card-head">
                <h2 class="sq-profilo-card-title">Dati</h2>
            </div>
            <div class="sq-profilo-card-stack">
                <div class="sq-profilo-field sq-mitt-tipo-field">
                    <span class="sq-profilo-label">Tipologia mittente <span class="sq-profilo-req">*</span></span>
                    <div class="sq-mitt-tipo-row" role="radiogroup" aria-label="Tipologia mittente">
                        <label class="sq-mitt-tipo-option">
                            <input type="radio" name="mittente_anagrafica" value="privato" class="sq-mitt-tipo-radio" {{ $mittenteAnagraficaDefault === 'privato' ? 'checked' : '' }}>
                            <span>Privato</span>
                        </label>
                        <label class="sq-mitt-tipo-option">
                            <input type="radio" name="mittente_anagrafica" value="azienda" class="sq-mitt-tipo-radio" {{ $mittenteAnagraficaDefault === 'azienda' ? 'checked' : '' }}>
                            <span>Azienda</span>
                        </label>
                    </div>
                </div>
                <div class="sq-profilo-field sq-mitt-nome-impresa-wrap" id="mitt_nome_impresa_wrap" @if ($mittenteAnagraficaDefault !== 'azienda') hidden @endif>
                    <label for="denominazione_ragione_sociale" class="sq-profilo-label">Nome impresa <span class="sq-profilo-req">*</span></label>
                    <input type="text" name="denominazione_ragione_sociale" id="denominazione_ragione_sociale" class="sq-profilo-input" maxlength="255"
                           placeholder="Ragione sociale o nome commerciale"
                           value="{{ old('denominazione_ragione_sociale', $m?->denominazione_ragione_sociale ?? '') }}"
                           @if ($mittenteAnagraficaDefault !== 'azienda') disabled @endif
                           @if ($mittenteAnagraficaDefault === 'azienda') required @endif>
                </div>
                <div class="sq-profilo-field">
                    <label for="mitt_nome" class="sq-profilo-label">Nome <span class="sq-profilo-req">*</span></label>
                    <input type="text" name="nome" id="mitt_nome" class="sq-profilo-input" required maxlength="255" value="{{ old('nome', $m?->nome ?? '') }}">
                </div>
                <div class="sq-profilo-field">
                    <label for="mitt_cognome" class="sq-profilo-label">Cognome <span class="sq-profilo-req">*</span></label>
                    <input type="text" name="cognome" id="mitt_cognome" class="sq-profilo-input" required maxlength="255" value="{{ old('cognome', $m?->cognome ?? '') }}">
                </div>
                <div class="sq-profilo-field">
                    <label for="mitt_telefono" class="sq-profilo-label">Telefono <span class="sq-profilo-req">*</span></label>
                    <input type="tel" name="telefono" id="mitt_telefono" class="sq-profilo-input" required maxlength="30" value="{{ old('telefono', $m?->telefono ?? '') }}">
                </div>
                <div class="sq-profilo-field">
                    <label for="mitt_email" class="sq-profilo-label">Email <span class="sq-profilo-req">*</span></label>
                    <input type="email" name="email" id="mitt_email" class="sq-profilo-input" required maxlength="255" value="{{ old('email', $m?->email ?? auth()->user()->email) }}">
                </div>
            </div>
        </div>

        <div class="sq-profilo-card sq-profilo-card--stacked">
            <div class="sq-profilo-card-head">
                <h2 class="sq-profilo-card-title">Indirizzo</h2>
            </div>
            <div class="sq-profilo-card-stack">
                <div class="sq-profilo-field sq-profilo-field--suggest sq-profilo-mb-15">
                    <label for="mitt_citta" class="sq-profilo-label">Città <span class="sq-profilo-req">*</span></label>
                    <input type="text" name="citta" id="mitt_citta" class="sq-profilo-input" required maxlength="255" placeholder="Inizia a scrivere il comune…" value="{{ old('citta', $m?->citta ?? '') }}">
                    <div class="sq-profilo-suggest" id="suggest_mitt_citta" hidden></div>
                </div>
                <div class="sq-profilo-cap-pv-row">
                    <div class="sq-profilo-field sq-profilo-field--suggest sq-profilo-mb-0">
                        <label for="mitt_cap" class="sq-profilo-label">CAP <span class="sq-profilo-req">*</span></label>
                        <input type="text" name="cap" id="mitt_cap" class="sq-profilo-input" required maxlength="5" inputmode="numeric" value="{{ old('cap', $m?->cap ?? '') }}">
                        <div class="sq-profilo-suggest" id="suggest_mitt_cap" hidden></div>
                    </div>
                    <div class="sq-profilo-field sq-profilo-mb-0">
                        <label for="mitt_provincia" class="sq-profilo-label">Prov. <span class="sq-profilo-req">*</span></label>
                        <input type="text" name="provincia" id="mitt_provincia" class="sq-profilo-input sq-profilo-input--ro" required maxlength="2" value="{{ old('provincia', $m?->provincia ?? '') }}" readonly tabindex="-1">
                    </div>
                </div>
                <div class="sq-profilo-addr-row">
                    <div class="sq-profilo-field sq-profilo-mb-0">
                        <label for="mitt_indirizzo" class="sq-profilo-label">Via/Piazza <span class="sq-profilo-req">*</span></label>
                        <input type="text" name="indirizzo" id="mitt_indirizzo" class="sq-profilo-input" required maxlength="255" value="{{ old('indirizzo', $m?->indirizzo ?? '') }}">
                    </div>
                    <div class="sq-profilo-field sq-profilo-mb-0">
                        <label for="mitt_civico" class="sq-profilo-label">Civico <span class="sq-profilo-req">*</span></label>
                        <input type="text" name="civico" id="mitt_civico" class="sq-profilo-input" required maxlength="10" value="{{ old('civico', $m?->civico ?? '') }}">
                    </div>
                </div>
                @error('id_comune')<span class="sq-profilo-err sq-profilo-err-block">{{ $message }}</span>@enderror
            </div>
        </div>

        <div class="sq-profilo-global-actions" style="justify-content:flex-start;">
            <button type="submit" class="sq-profilo-azione-btn sq-profilo-btn-sm">Salva</button>
            <a href="{{ route('mittenze.index') }}" class="sq-btn-secondary sq-modal-btn" style="margin-left:10px;">Annulla</a>
        </div>
    </form>
</div>

<script>
(() => {
    const suggestUrl = @json(route('api.comuni.suggest'));
    const idComuneStart = @json($oldIdComune);

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
        const hid = document.getElementById('id_comune_mittenza');
        if (hid) hid.value = String(it.id);
        const cap = document.getElementById('mitt_cap');
        const cit = document.getElementById('mitt_citta');
        const pv = document.getElementById('mitt_provincia');
        if (cap) cap.value = it.cap;
        if (cit) cit.value = it.comune;
        if (pv) pv.value = (it.provincia || '').toString().substring(0, 2).toUpperCase();
    }

    function wireSuggestCap() {
        const input = document.getElementById('mitt_cap');
        const list = document.getElementById('suggest_mitt_cap');
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
            const hid = document.getElementById('id_comune_mittenza');
            if (hid) hid.value = '';
            run();
        });
        document.addEventListener('click', (e) => {
            if (list.hidden) return;
            if (e.target === input || list.contains(e.target)) return;
            hideSuggest(list);
        });
    }

    function wireSuggestCitta() {
        const input = document.getElementById('mitt_citta');
        const list = document.getElementById('suggest_mitt_citta');
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
            const hid = document.getElementById('id_comune_mittenza');
            if (hid) hid.value = '';
            run();
        });
        document.addEventListener('click', (e) => {
            if (list.hidden) return;
            if (e.target === input || list.contains(e.target)) return;
            hideSuggest(list);
        });
    }

    document.getElementById('form-mittenza')?.addEventListener('submit', (e) => {
        const idc = (document.getElementById('id_comune_mittenza')?.value || '').trim();
        if (!idc) {
            e.preventDefault();
            alert('Seleziona CAP o città dall’elenco suggerito.');
        }
    });

    function wireMittenteTipo() {
        const wrap = document.getElementById('mitt_nome_impresa_wrap');
        const denom = document.getElementById('denominazione_ragione_sociale');
        const radios = document.querySelectorAll('input[name="mittente_anagrafica"]');
        if (!wrap || !denom || !radios.length) return;

        function isAzienda() {
            const c = document.querySelector('input[name="mittente_anagrafica"]:checked');
            return c && c.value === 'azienda';
        }

        function applyState(clearDenom) {
            const az = isAzienda();
            wrap.hidden = !az;
            if (az) {
                denom.disabled = false;
                denom.setAttribute('required', 'required');
            } else {
                denom.removeAttribute('required');
                denom.disabled = true;
                if (clearDenom) denom.value = '';
            }
        }

        radios.forEach((r) => r.addEventListener('change', () => applyState(true)));
        applyState(false);
    }

    wireMittenteTipo();
    wireSuggestCap();
    wireSuggestCitta();

    if (idComuneStart) {
        const hid = document.getElementById('id_comune_mittenza');
        if (hid && !hid.value) hid.value = idComuneStart;
    }
})();
</script>
