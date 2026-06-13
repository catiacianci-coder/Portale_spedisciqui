@once
    <div
        id="sq-spedizione-tracking-modal"
        class="sq-modal sq-modal--tracking"
        hidden
        data-spedizione-tracking-modal
    >
        <div class="sq-modal-backdrop js-spedizione-tracking-modal-close" tabindex="-1" aria-hidden="true"></div>
        <div
            class="sq-modal-panel"
            role="dialog"
            aria-modal="true"
            aria-labelledby="sq-spedizione-tracking-modal-title"
        >
            <h2 id="sq-spedizione-tracking-modal-title" class="sq-modal-title">Tracking spedizione</h2>
            <div id="sq-spedizione-tracking-modal-body" class="sq-modal-text"></div>
            <div class="sq-modal-actions">
                <button type="button" class="sq-btn-secondary sq-modal-btn js-spedizione-tracking-modal-close">Chiudi</button>
            </div>
        </div>
    </div>
    <script>
    (() => {
        const modal = document.querySelector('[data-spedizione-tracking-modal]');
        if (!modal || modal.dataset.bound === '1') {
            return;
        }
        modal.dataset.bound = '1';

        const bodyEl = document.getElementById('sq-spedizione-tracking-modal-body');
        const titleEl = document.getElementById('sq-spedizione-tracking-modal-title');

        const closeModal = () => {
            modal.hidden = true;
            modal.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('sq-modal-open');
        };

        const openModal = () => {
            modal.hidden = false;
            modal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('sq-modal-open');
        };

        const setLoading = () => {
            if (titleEl) {
                titleEl.textContent = 'Tracking spedizione';
            }
            if (!bodyEl) {
                return;
            }
            bodyEl.replaceChildren();
            const p = document.createElement('p');
            p.className = 'sq-m-0';
            p.textContent = 'Consultazione in corso…';
            bodyEl.appendChild(p);
            openModal();
        };

        const renderResponse = (data) => {
            if (!bodyEl) {
                return;
            }
            bodyEl.replaceChildren();

            const tipo = data && data.tipo ? String(data.tipo) : 'errore';

            if (tipo === 'api') {
                if (titleEl) {
                    titleEl.textContent = 'Stato spedizione';
                }
                const stato = document.createElement('p');
                stato.className = 'sq-m-0 sq-fw-700';
                stato.textContent = data.stato ? String(data.stato) : '—';
                bodyEl.appendChild(stato);

                if (data.data_evento) {
                    const dataEv = document.createElement('p');
                    dataEv.className = 'sq-m-0 sq-mt-8 sq-text-muted';
                    dataEv.textContent = 'Aggiornato il ' + String(data.data_evento);
                    bodyEl.appendChild(dataEv);
                }

                if (data.tracking) {
                    const tn = document.createElement('p');
                    tn.className = 'sq-m-0 sq-mt-8';
                    tn.textContent = 'Tracking: ' + String(data.tracking);
                    bodyEl.appendChild(tn);
                }

                return;
            }

            if (tipo === 'manuale') {
                if (titleEl) {
                    titleEl.textContent = 'Tracking manuale';
                }
                const msg = document.createElement('p');
                msg.className = 'sq-m-0';
                msg.textContent = 'Questo corriere non permette il tracking in automatico.';
                bodyEl.appendChild(msg);

                if (data.tracking) {
                    const tn = document.createElement('p');
                    tn.className = 'sq-m-0 sq-mt-8';
                    tn.textContent = 'Tracking: ' + String(data.tracking);
                    bodyEl.appendChild(tn);
                }

                if (data.url_tracking) {
                    const linkWrap = document.createElement('p');
                    linkWrap.className = 'sq-m-0 sq-mt-12';
                    const link = document.createElement('a');
                    link.href = String(data.url_tracking);
                    link.target = '_blank';
                    link.rel = 'noopener noreferrer';
                    link.textContent = 'Clicca qui per andare sulla pagina del trasportatore';
                    linkWrap.appendChild(link);
                    bodyEl.appendChild(linkWrap);
                } else {
                    const hint = document.createElement('p');
                    hint.className = 'sq-m-0 sq-mt-12 sq-text-muted';
                    hint.textContent = 'Il link alla pagina del trasportatore non è ancora configurato per questo corriere.';
                    bodyEl.appendChild(hint);
                }

                return;
            }

            if (titleEl) {
                titleEl.textContent = tipo === 'non_tracciabile' ? 'Tracking non disponibile' : 'Tracking';
            }
            const p = document.createElement('p');
            p.className = 'sq-m-0';
            p.textContent = data && data.messaggio
                ? String(data.messaggio)
                : 'Impossibile recuperare il tracking.';
            bodyEl.appendChild(p);
        };

        document.addEventListener('click', async (ev) => {
            const btn = ev.target.closest('.js-spedizione-tracking-btn');
            if (!btn) {
                return;
            }
            ev.preventDefault();

            const url = btn.getAttribute('data-tracking-url');
            if (!url) {
                return;
            }

            setLoading();

            try {
                const response = await fetch(url, {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                });
                const data = await response.json();
                if (!response.ok) {
                    renderResponse({
                        tipo: 'errore',
                        messaggio: data && data.message ? String(data.message) : 'Errore durante il tracking.',
                    });
                    return;
                }
                renderResponse(data);
            } catch {
                renderResponse({
                    tipo: 'errore',
                    messaggio: 'Errore di rete durante il tracking.',
                });
            }
        });

        modal.querySelectorAll('.js-spedizione-tracking-modal-close').forEach((el) => {
            el.addEventListener('click', closeModal);
        });

        document.addEventListener('keydown', (ev) => {
            if (ev.key === 'Escape' && !modal.hidden) {
                closeModal();
            }
        });
    })();
    </script>
@endonce
