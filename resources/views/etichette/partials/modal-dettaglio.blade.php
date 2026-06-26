<div
    id="sq-etichetta-dettaglio-modal"
    class="sq-modal sq-modal--etichetta-dettaglio"
    hidden
    data-etichetta-dettaglio-modal
>
    <div class="sq-modal-backdrop js-etichetta-dettaglio-close" tabindex="-1" aria-hidden="true"></div>
    <div
        class="sq-modal-panel sq-etichetta-dettaglio-panel"
        role="dialog"
        aria-modal="true"
        aria-labelledby="sq-etichetta-dettaglio-title"
    >
        <div class="sq-etichetta-dettaglio-head">
            <h2 id="sq-etichetta-dettaglio-title" class="sq-etichetta-dettaglio-title">Dettagli remessa</h2>
            <button type="button" class="sq-etichetta-dettaglio-close js-etichetta-dettaglio-close" aria-label="Chiudi">
                <i class="fa-solid fa-xmark" aria-hidden="true"></i>
            </button>
        </div>
        <div id="sq-etichetta-dettaglio-body" class="sq-etichetta-dettaglio-body">
            <p class="sq-m-0 sq-text-muted">Caricamento…</p>
        </div>
    </div>
</div>

@if (config('etichetta.correcao_cliente_abilitata'))
<div
    id="sq-etichetta-correcao-modal"
    class="sq-modal sq-modal--etichetta-correcao"
    hidden
    data-etichetta-correcao-modal
>
    <div class="sq-modal-backdrop js-etichetta-correcao-close" tabindex="-1" aria-hidden="true"></div>
    <div
        class="sq-modal-panel sq-etichetta-correcao-panel"
        role="dialog"
        aria-modal="true"
        aria-labelledby="sq-etichetta-correcao-title"
    >
        <div class="sq-etichetta-dettaglio-head">
            <h2 id="sq-etichetta-correcao-title" class="sq-etichetta-dettaglio-title">Correggi etichetta</h2>
            <button type="button" class="sq-etichetta-dettaglio-close js-etichetta-correcao-close" aria-label="Chiudi">
                <i class="fa-solid fa-xmark" aria-hidden="true"></i>
            </button>
        </div>
        <div id="sq-etichetta-correcao-body" class="sq-etichetta-correcao-body">
            <p class="sq-m-0 sq-text-muted">Caricamento…</p>
        </div>
    </div>
</div>
@endif

<script>
(() => {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    const detModal = document.querySelector('[data-etichetta-dettaglio-modal]');
    const detBody = document.getElementById('sq-etichetta-dettaglio-body');
    const detTitle = document.getElementById('sq-etichetta-dettaglio-title');
    const detPanel = detModal?.querySelector('.sq-etichetta-dettaglio-panel');

    window.sqBoSyncRastreioPdfUpload = function (spedizioneId) {
        const rast = document.getElementById('sq-bo-etq-rast-' + spedizioneId);
        const codHidden = document.getElementById('sq-bo-etq-pdf-codigo-' + spedizioneId);
        const file = document.getElementById('sq-bo-etq-pdf-' + spedizioneId);
        if (!file || !file.files || !file.files.length) return;
        const cod = rast ? (rast.value || '').trim() : (codHidden ? codHidden.value.trim() : '');
        if (cod === '') {
            alert('Indica il numero di tracking prima di caricare il PDF.');
            file.value = '';
            return;
        }
        if (codHidden) codHidden.value = cod;
        const form = document.getElementById('sq-bo-etq-pdf-form-' + spedizioneId);
        form?.submit();
    };

    const setDetDialogMode = (mode) => {
        detPanel?.classList.toggle('sq-modal-panel--opcoes', mode === 'opcoes');
    };

@if (config('etichetta.correcao_cliente_abilitata'))
    const corModal = document.querySelector('[data-etichetta-correcao-modal]');
    const corBody = document.getElementById('sq-etichetta-correcao-body');
    const corTitle = document.getElementById('sq-etichetta-correcao-title');

    let correcaoUrl = null;
@endif

    const esc = (v) => {
        const d = document.createElement('div');
        d.textContent = v == null ? '' : String(v);
        return d.innerHTML;
    };

    const closeDet = () => {
        if (!detModal) return;
        detModal.hidden = true;
        detModal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('sq-modal-open');
        setDetDialogMode('dettaglio');
    };

    const openDet = () => {
        if (!detModal) return;
        detModal.hidden = false;
        detModal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('sq-modal-open');
    };

@if (config('etichetta.correcao_cliente_abilitata'))
    const closeCor = () => {
        if (!corModal) return;
        corModal.hidden = true;
        corModal.setAttribute('aria-hidden', 'true');
        correcaoUrl = null;
        if (detModal?.hidden !== false) {
            document.body.classList.remove('sq-modal-open');
        }
    };

    const openCor = () => {
        if (!corModal) return;
        corModal.hidden = false;
        corModal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('sq-modal-open');
    };
@endif

    const wireDettaglioActions = () => {
@if (config('etichetta.correcao_cliente_abilitata'))
        detBody?.querySelectorAll('.js-etichetta-correcao-open').forEach((btn) => {
            btn.addEventListener('click', () => openCorrecaoFromBtn(btn));
        });
@endif
        detBody?.querySelectorAll('.js-etichetta-dettaglio-close, .js-bo-fechar-modal').forEach((btn) => {
            btn.addEventListener('click', closeDet);
        });
        detBody?.querySelectorAll('.js-bo-abrir-opcoes').forEach((btn) => {
            btn.addEventListener('click', () => {
                const url = btn.getAttribute('data-opcoes-url');
                loadDettaglioHtml(url, 'opcoes', 'Opzioni spedizione');
            });
        });
        detBody?.querySelectorAll('.js-bo-voltar-detalhe').forEach((btn) => {
            btn.addEventListener('click', () => {
                const url = btn.getAttribute('data-detalhe-url');
                loadDettaglioHtml(url, 'dettaglio', 'Dettagli remessa');
            });
        });
    };

    const loadDettaglioHtml = (url, mode, title) => {
        if (!url || !detBody) return;
        if (detTitle) detTitle.textContent = title;
        setDetDialogMode(mode);
        detBody.innerHTML = '<p class="sq-m-0 sq-text-muted">Caricamento…</p>';
        openDet();
        fetch(url, { headers: { Accept: 'text/html', 'X-Requested-With': 'XMLHttpRequest' } })
            .then((r) => {
                if (!r.ok) throw new Error('HTTP');
                return r.text();
            })
            .then((html) => {
                detBody.innerHTML = html;
                wireDettaglioActions();
            })
            .catch(() => {
                detBody.innerHTML = '<div class="sq-alert sq-alert--error">Impossibile caricare i dettagli.</div>';
            });
    };

@if (config('etichetta.correcao_cliente_abilitata'))
    const openCorrecaoFromBtn = (btn) => {
        const url = btn.getAttribute('data-correcao-url');
        const codice = btn.getAttribute('data-codice') || '';
        if (!url || !corBody) return;
        correcaoUrl = url;
        if (corTitle) corTitle.textContent = codice ? `Correggi etichetta — ${codice}` : 'Correggi etichetta';
        corBody.innerHTML = '<p class="sq-m-0 sq-text-muted">Caricamento…</p>';
        openCor();
        fetch(url, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
            .then((r) => r.json())
            .then((payload) => {
                if (!payload?.ok) {
                    corBody.innerHTML = `<div class="sq-alert sq-alert--error">${esc(payload?.message || 'Errore')}</div>`;
                    return;
                }
                renderCorrecaoForm(payload.data || {});
            })
            .catch(() => {
                corBody.innerHTML = '<div class="sq-alert sq-alert--error">Errore di rete.</div>';
            });
    };

    const renderCorrecaoForm = (data) => {
        if (!corBody) return;
        corBody.innerHTML = `
            <p class="sq-etichetta-correcao-hint">Modifica solo i campi consentiti. Salvando, l'etichetta attuale non sarà più valida e verrà generata una <strong>nuova etichetta</strong> con un nuovo codice di tracking (quando il corriere risponde).</p>
            <div id="sq-correcao-error" class="sq-alert sq-alert--error sq-mb-12" hidden></div>
            <form id="sq-correcao-form" class="sq-etichetta-correcao-form">
                ${data.ragione_sociale_d ? `
                <p class="sq-etichetta-correcao-ragione sq-m-0 sq-mb-12">
                    <span class="sq-etichetta-correcao-ragione-label">Ragione sociale destinatario</span>
                    <strong>${esc(data.ragione_sociale_d)}</strong>
                    <span class="sq-text-muted sq-text-13"> — nome e cognome sotto sono facoltativi per destinatari azienda.</span>
                </p>` : ''}
                <div class="sq-etichetta-correcao-row">
                    <div class="sq-etichetta-correcao-field">
                        <label for="ccf-nome">Nome destinatario</label>
                        <input id="ccf-nome" name="nome_d" type="text" maxlength="120" ${data.ragione_sociale_d ? '' : 'required'} value="${esc(data.nome_d)}">
                    </div>
                    <div class="sq-etichetta-correcao-field">
                        <label for="ccf-cognome">Cognome destinatario</label>
                        <input id="ccf-cognome" name="sobrenome_d" type="text" maxlength="120" ${data.ragione_sociale_d ? '' : 'required'} value="${esc(data.sobrenome_d)}">
                    </div>
                </div>
                <div class="sq-etichetta-correcao-field">
                    <label for="ccf-indirizzo">Indirizzo destinatario</label>
                    <input id="ccf-indirizzo" name="indirizzo_d" type="text" maxlength="255" required value="${esc(data.indirizzo_d)}">
                </div>
                <div class="sq-etichetta-correcao-field">
                    <label for="ccf-numero">Numero civico</label>
                    <input id="ccf-numero" name="numero_d" type="text" maxlength="32" required value="${esc(data.numero_d)}">
                </div>
                <div class="sq-etichetta-correcao-field">
                    <label for="ccf-frazione">Interno / frazione</label>
                    <input id="ccf-frazione" name="frazione_d" type="text" maxlength="120" value="${esc(data.frazione_d)}">
                </div>
                <div class="sq-etichetta-correcao-field">
                    <label for="ccf-tel">Telefono destinatario</label>
                    <input id="ccf-tel" name="tel_d" type="tel" maxlength="64" required value="${esc(data.tel_d)}">
                </div>
                <div class="sq-etichetta-correcao-field">
                    <label for="ccf-note">Note destinatario</label>
                    <textarea id="ccf-note" name="note_d" maxlength="500" rows="3">${esc(data.note_d)}</textarea>
                </div>
                <p class="sq-text-muted sq-text-13 sq-m-0">CAP, città e provincia restano quelli dell'ordine originale (tariffa calcolata su tali dati).</p>
                <div class="sq-etichetta-correcao-actions">
                    <button type="button" class="sq-btn-secondary js-etichetta-correcao-close">Annulla</button>
                    <button type="submit" class="sq-btn-primary" id="sq-correcao-submit">Salva e genera nuova etichetta</button>
                </div>
            </form>`;

        corBody.querySelectorAll('.js-etichetta-correcao-close').forEach((el) => el.addEventListener('click', closeCor));

        const form = document.getElementById('sq-correcao-form');
        const errBox = document.getElementById('sq-correcao-error');
        form?.addEventListener('submit', async (ev) => {
            ev.preventDefault();
            if (!correcaoUrl) return;
            const submitBtn = document.getElementById('sq-correcao-submit');
            if (submitBtn) submitBtn.disabled = true;
            if (errBox) errBox.hidden = true;

            const fd = new FormData(form);
            try {
                const res = await fetch(correcaoUrl, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrf,
                    },
                    body: fd,
                });
                const payload = await res.json();
                if (!payload?.ok) {
                    if (errBox) {
                        errBox.textContent = payload?.message || 'Operazione non riuscita.';
                        errBox.hidden = false;
                    }
                    if (submitBtn) submitBtn.disabled = false;
                    return;
                }
                window.location.href = payload.redirect || window.location.href;
            } catch {
                if (errBox) {
                    errBox.textContent = 'Errore di rete.';
                    errBox.hidden = false;
                }
                if (submitBtn) submitBtn.disabled = false;
            }
        });
    };

@endif

    document.querySelectorAll('.js-etichetta-dettaglio-open').forEach((btn) => {
        btn.addEventListener('click', () => {
            const url = btn.getAttribute('data-dettaglio-url');
            loadDettaglioHtml(url, 'dettaglio', 'Dettagli remessa');
        });
    });

@if (config('etichetta.correcao_cliente_abilitata'))
    document.querySelectorAll('.js-etichetta-correcao-open').forEach((btn) => {
        btn.addEventListener('click', () => openCorrecaoFromBtn(btn));
    });

    corModal?.querySelectorAll('.js-etichetta-correcao-close').forEach((el) => el.addEventListener('click', closeCor));
@endif

    detModal?.querySelectorAll('.js-etichetta-dettaglio-close').forEach((el) => el.addEventListener('click', closeDet));

    document.addEventListener('keydown', (e) => {
        if (e.key !== 'Escape') return;
@if (config('etichetta.correcao_cliente_abilitata'))
        if (corModal && !corModal.hidden) closeCor();
        else if (detModal && !detModal.hidden) closeDet();
@else
        if (detModal && !detModal.hidden) closeDet();
@endif
    });
})();
</script>
