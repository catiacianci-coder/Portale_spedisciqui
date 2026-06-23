<div class="register-comune-fields">
    <div class="register-comune-field">
        <label for="citta">Città <span>*</span></label>
        <input type="text"
               id="citta"
               name="citta"
               value="{{ old('citta') }}"
               required
               maxlength="255"
               autocomplete="off"
               placeholder="Inizia a scrivere il comune…">
        <div class="sq-profilo-suggest register-comune-suggest" id="suggest_reg_citta" hidden></div>
        @error('citta') <span class="error-validation">{{ $message }}</span> @enderror
    </div>

    <div class="register-row--addr register-comune-cap-row">
        <div class="register-comune-field">
            <label for="cap">CAP <span>*</span></label>
            <input type="text"
                   id="cap"
                   name="cap"
                   value="{{ old('cap') }}"
                   required
                   maxlength="5"
                   inputmode="numeric"
                   autocomplete="off"
                   placeholder="CAP…">
            <div class="sq-profilo-suggest register-comune-suggest" id="suggest_reg_cap" hidden></div>
            @error('cap') <span class="error-validation">{{ $message }}</span> @enderror
        </div>
        <div class="register-comune-field register-comune-field--prov">
            <label for="provincia">Prov. <span>*</span></label>
            <input type="text"
                   id="provincia"
                   name="provincia"
                   value="{{ old('provincia') }}"
                   required
                   maxlength="2"
                   autocomplete="off">
            @error('provincia') <span class="error-validation">{{ $message }}</span> @enderror
        </div>
    </div>

    <p class="sq-register-cf-hint sq-m-0">Seleziona CAP o città dall’elenco suggerito.</p>
</div>

<script>
(() => {
    const suggestUrl = @json(route('api.comuni.suggest'));

    function debounce(fn, ms) {
        let t = null;
        return (...args) => {
            clearTimeout(t);
            t = setTimeout(() => fn(...args), ms);
        };
    }

    function hideSuggest(el) {
        if (!el) {
            return;
        }
        el.hidden = true;
        el.innerHTML = '';
    }

    function showSuggest(el) {
        if (!el) {
            return;
        }
        el.hidden = false;
    }

    function fillComune(it) {
        const cap = document.getElementById('cap');
        const cit = document.getElementById('citta');
        const pv = document.getElementById('provincia');
        if (cap) {
            cap.value = it.cap || '';
        }
        if (cit) {
            cit.value = it.comune || '';
        }
        if (pv) {
            pv.value = (it.provincia || '').toString().substring(0, 2).toUpperCase();
        }
    }

    function wireSuggestCap() {
        const input = document.getElementById('cap');
        const list = document.getElementById('suggest_reg_cap');
        if (!input || !list) {
            return;
        }

        let controller = null;
        const run = debounce(async () => {
            const q = (input.value || '').trim().replace(/\D/g, '').substring(0, 5);
            if (q.length < 1) {
                hideSuggest(list);
                return;
            }

            try {
                if (controller) {
                    controller.abort();
                }
                controller = new AbortController();
                const res = await fetch(`${suggestUrl}?q=${encodeURIComponent(q)}`, {
                    signal: controller.signal,
                    headers: { Accept: 'application/json' },
                });
                if (!res.ok) {
                    hideSuggest(list);
                    return;
                }

                const items = await res.json();
                if (!Array.isArray(items) || !items.length) {
                    hideSuggest(list);
                    return;
                }

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
                        hideSuggest(document.getElementById('suggest_reg_citta'));
                    });
                    list.appendChild(row);
                }
                showSuggest(list);
            } catch (e) {
                if (e.name !== 'AbortError') {
                    hideSuggest(list);
                }
            }
        }, 200);

        input.addEventListener('input', run);
    }

    function wireSuggestCitta() {
        const input = document.getElementById('citta');
        const list = document.getElementById('suggest_reg_citta');
        if (!input || !list) {
            return;
        }

        let controller = null;
        const run = debounce(async () => {
            const q = (input.value || '').trim();
            if (q.length < 2) {
                hideSuggest(list);
                return;
            }
            if (/^\d+$/.test(q)) {
                hideSuggest(list);
                return;
            }

            try {
                if (controller) {
                    controller.abort();
                }
                controller = new AbortController();
                const res = await fetch(`${suggestUrl}?q=${encodeURIComponent(q)}`, {
                    signal: controller.signal,
                    headers: { Accept: 'application/json' },
                });
                if (!res.ok) {
                    hideSuggest(list);
                    return;
                }

                const items = await res.json();
                if (!Array.isArray(items) || !items.length) {
                    hideSuggest(list);
                    return;
                }

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
                        hideSuggest(document.getElementById('suggest_reg_cap'));
                    });
                    list.appendChild(row);
                }
                showSuggest(list);
            } catch (e) {
                if (e.name !== 'AbortError') {
                    hideSuggest(list);
                }
            }
        }, 200);

        input.addEventListener('input', run);
    }

    document.addEventListener('click', (e) => {
        ['suggest_reg_cap', 'suggest_reg_citta'].forEach((id) => {
            const list = document.getElementById(id);
            if (!list || list.hidden) {
                return;
            }
            const inputId = id === 'suggest_reg_cap' ? 'cap' : 'citta';
            const input = document.getElementById(inputId);
            if (e.target === input || list.contains(e.target)) {
                return;
            }
            hideSuggest(list);
        });
    });

    wireSuggestCap();
    wireSuggestCitta();
})();
</script>
