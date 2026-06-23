@extends('layouts.app')
@section('content')

<div class="sq-bleed-layout">
    <x-sq-page-banner title="Lettere di vettura" icon="fa-file-lines" class="sq-page-banner--full" />
    <div class="home-spedizione-wrap sq-sped-clienti-page">

    @if (! empty($filtroErrors))
        <div class="sq-alert sq-alert--error sq-mb-16">
            @foreach ($filtroErrors as $fe)
                <div>{{ $fe }}</div>
            @endforeach
        </div>
    @endif

    <form method="GET" action="{{ route('spedizioni.index') }}" class="sq-filtri-form sq-mb-20">
        <p class="sq-filtri-title">Filtri</p>
        <div class="sq-filtri-row">
            @include('partials.filtri-periodo', [
                'periodId' => 'period_sped',
                'wrapId' => 'wrap-date-sped-cli',
                'filtroPeriod' => $filtroPeriod,
                'filtroDataDa' => $filtroDataDa,
                'filtroDataA' => $filtroDataA,
            ])
            <div>
                <label for="numero_ordine" class="sq-filtri-label">Numero ordine</label>
                <input id="numero_ordine" name="numero_ordine" type="text" value="{{ $filtroNumeroOrdine }}"
                       class="sq-filtri-email-input" placeholder="es. 27">
            </div>
            <div>
                <label for="codice" class="sq-filtri-label">Codice spedizione</label>
                <input id="codice" name="codice" type="text" value="{{ $filtroCodice }}"
                       class="sq-filtri-email-input" placeholder="codice spedizione">
            </div>
            <div>
                <label for="tracking" class="sq-filtri-label">Lettera di vettura</label>
                <input id="tracking" name="tracking" type="text" value="{{ $filtroTracking }}"
                       class="sq-filtri-email-input" placeholder="N. lettera di vettura">
            </div>
            <div class="sq-filtri-actions">
                <button type="submit" class="sq-filtri-submit">Cerca</button>
                <a href="{{ route('spedizioni.index') }}" class="sq-filtri-reset">Reimposta</a>
            </div>
        </div>
    </form>

    @if ($spedizioni->isEmpty())
        <p class="sq-text-666 sq-m-0 sq-mb-8">Nessuna lettera di vettura nel periodo selezionato.</p>
        <p class="sq-m-0"><a href="{{ route('home') }}">Crea una nuova spedizione</a></p>
    @else
        @include('spedizioni.partials.tabella-lettere-vettura', [
            'spedizioni' => $spedizioni,
        ])

        @include('partials.tabella-paginazione', [
            'paginator' => $spedizioni,
            'perPage' => $perPage,
            'queryParams' => $queryParams,
        ])
    @endif

    <div
        id="sq-spedizioni-ordine-modal"
        class="sq-modal sq-modal--movimenti-ordine"
        hidden
        data-spedizioni-ordine-modal
    >
        <div class="sq-modal-backdrop js-spedizioni-ordine-modal-close" tabindex="-1" aria-hidden="true"></div>
        <div
            class="sq-modal-panel sq-spedizioni-modal-panel"
            role="dialog"
            aria-modal="true"
            aria-labelledby="sq-spedizioni-ordine-modal-title"
        >
            <div class="sq-spedizioni-modal-head js-spedizioni-modal-drag-handle">
                <h2 id="sq-spedizioni-ordine-modal-title" class="sq-modal-title sq-m-0 sq-spedizioni-modal-order-band"></h2>
                <button type="button" class="sq-ordini-icon-action sq-ordini-icon-action--delete js-spedizioni-ordine-modal-close" title="Chiudi" aria-label="Chiudi dettaglio">
                    <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                </button>
            </div>
            <div id="sq-spedizioni-ordine-modal-body" class="sq-modal-text"></div>
            <div class="sq-modal-actions sq-spedizioni-modal-actions">
                <button type="button" class="sq-btn-secondary sq-modal-btn js-spedizioni-ordine-modal-close">Chiudi</button>
            </div>
        </div>
    </div>
    </div>
</div>
<script>
(() => {
    const modal = document.querySelector('[data-spedizioni-ordine-modal]');
    if (!modal) return;

    const titleEl = document.getElementById('sq-spedizioni-ordine-modal-title');
    const bodyEl = document.getElementById('sq-spedizioni-ordine-modal-body');
    const panelEl = modal.querySelector('.sq-spedizioni-modal-panel');
    const dragHandleEl = modal.querySelector('.js-spedizioni-modal-drag-handle');
    const pageWrapEl = document.querySelector('.sq-sped-clienti-page');
    let dragState = null;

    const closeModal = () => {
        modal.hidden = true;
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('sq-modal-open');
        if (panelEl) {
            panelEl.style.left = '';
            panelEl.style.top = '';
        }
    };

    const centerModalOnPageWrap = () => {
        if (!panelEl) return;
        const panelRect = panelEl.getBoundingClientRect();
        const baseRect = pageWrapEl ? pageWrapEl.getBoundingClientRect() : { left: 0, width: window.innerWidth, top: 0, height: window.innerHeight };
        const targetCenterX = baseRect.left + (baseRect.width / 2);
        const left = Math.max(12, Math.min(targetCenterX - (panelRect.width / 2), window.innerWidth - panelRect.width - 12));
        const currentTop = parseFloat(panelEl.style.top || '');
        const top = Number.isFinite(currentTop) ? currentTop : 78;
        panelEl.style.left = Math.round(left) + 'px';
        panelEl.style.top = Math.round(top) + 'px';
    };

    const startDrag = (ev) => {
        if (!panelEl || !dragHandleEl) return;
        const target = ev.target;
        if (target && target.closest && target.closest('.js-spedizioni-ordine-modal-close')) return;
        const panelRect = panelEl.getBoundingClientRect();
        dragState = {
            startX: ev.clientX,
            startY: ev.clientY,
            left: panelRect.left,
            top: panelRect.top,
        };
        document.body.classList.add('sq-modal-dragging');
        ev.preventDefault();
    };

    const onDrag = (ev) => {
        if (!dragState || !panelEl) return;
        const nextLeft = dragState.left + (ev.clientX - dragState.startX);
        const nextTop = dragState.top + (ev.clientY - dragState.startY);
        const panelRect = panelEl.getBoundingClientRect();
        const clampedLeft = Math.max(12, Math.min(nextLeft, window.innerWidth - panelRect.width - 12));
        const clampedTop = Math.max(12, Math.min(nextTop, window.innerHeight - panelRect.height - 12));
        panelEl.style.left = Math.round(clampedLeft) + 'px';
        panelEl.style.top = Math.round(clampedTop) + 'px';
    };

    const stopDrag = () => {
        dragState = null;
        document.body.classList.remove('sq-modal-dragging');
    };

    const textOrDash = (value) => value != null && String(value).trim() !== '' ? String(value) : '—';

    const openModal = (payload) => {
        const codice = (payload && payload.codice) ? String(payload.codice) : '';
        if (titleEl) {
            titleEl.textContent = 'Ordine ' + codice;
        }
        if (!bodyEl) return;
        bodyEl.replaceChildren();

        const spedizioniAll = payload && Array.isArray(payload.spedizioni) ? payload.spedizioni : [];
        const selectedId = payload && payload.selected_spedizione_id != null ? Number(payload.selected_spedizione_id) : 0;
        const spedizioni = selectedId > 0
            ? spedizioniAll.filter((sp) => Number(sp && sp.id ? sp.id : 0) === selectedId)
            : spedizioniAll;

        if (spedizioni.length === 0) {
            const p = document.createElement('p');
            p.className = 'sq-m-0';
            p.textContent = 'Nessuna spedizione associata.';
            bodyEl.appendChild(p);
        } else {
            spedizioni.forEach((sp) => {
                const section = document.createElement('section');
                section.className = 'sq-wallet-popup-spedizione sq-spedizioni-popup-item';

                const line1 = document.createElement('div');
                line1.className = 'sq-wallet-popup-sped-head';
                const tipDim = [sp.tipologia, sp.dimensioni_peso].filter((x) => x && String(x).trim() !== '').join(' ');
                line1.textContent = [textOrDash(sp.codice_interno), textOrDash(sp.corriere_nome), textOrDash(tipDim)].join(' - ');
                section.appendChild(line1);

                const line3 = document.createElement('p');
                line3.className = 'sq-wallet-popup-indirizzo-testo sq-m-0';
                const mittLab = document.createElement('strong');
                mittLab.textContent = 'Mittente:';
                line3.appendChild(mittLab);
                const mittTxt = textOrDash(sp.mittente);
                const mittNote = sp && sp.mittente_note ? String(sp.mittente_note).trim() : '';
                line3.appendChild(document.createTextNode(' ' + mittTxt + (mittNote !== '' ? (' Note: ' + mittNote) : '')));
                section.appendChild(line3);

                const line4 = document.createElement('p');
                line4.className = 'sq-wallet-popup-indirizzo-testo sq-m-0';
                const destLab = document.createElement('strong');
                destLab.textContent = 'Destinatario:';
                line4.appendChild(destLab);
                const destTxt = textOrDash(sp.destinatario);
                const destNote = sp && sp.destinatario_note ? String(sp.destinatario_note).trim() : '';
                line4.appendChild(document.createTextNode(' ' + destTxt + (destNote !== '' ? (' Note: ' + destNote) : '')));
                section.appendChild(line4);

                if (payload && payload.show_tracking_right) {
                    const lineTracking = document.createElement('p');
                    lineTracking.className = 'sq-wallet-popup-indirizzo-testo sq-m-0 sq-spedizioni-popup-tracking-right';
                    const trLab = document.createElement('strong');
                    trLab.textContent = 'Tracking:';
                    lineTracking.appendChild(trLab);
                    lineTracking.appendChild(document.createTextNode(' ' + textOrDash(sp.tracking)));
                    section.appendChild(lineTracking);
                }

                const line5 = document.createElement('div');
                line5.className = 'sq-wallet-popup-indirizzo-testo sq-m-0';
                const servLab2 = document.createElement('strong');
                servLab2.textContent = 'Servizi aggiuntivi:';
                line5.appendChild(servLab2);
                const items = Array.isArray(sp.servizi_aggiuntivi_items) ? sp.servizi_aggiuntivi_items : [];
                const rendered = items.length > 0
                    ? items.map((sx) => {
                        const nome = textOrDash(sx && sx.nome);
                        const val = sx && sx.valore ? String(sx.valore) : '';
                        return val !== '' ? `${nome} (${val})` : nome;
                    }).join(' - ')
                    : '—';
                line5.appendChild(document.createTextNode(' ' + rendered));
                section.appendChild(line5);

                bodyEl.appendChild(section);
            });
        }

        modal.hidden = false;
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('sq-modal-open');
        centerModalOnPageWrap();
    };

    document.querySelectorAll('.js-spedizioni-ordine-detail-btn').forEach((btn) => {
        btn.addEventListener('click', () => {
            let payload = null;
            try {
                payload = JSON.parse(btn.getAttribute('data-ordine-dettaglio') || 'null');
            } catch (e) {
                payload = null;
            }
            if (payload) {
                payload.selected_spedizione_id = Number(btn.getAttribute('data-spedizione-id') || 0);
                payload.show_tracking_right = btn.getAttribute('data-show-tracking-right') === '1';
            }
            openModal(payload);
        });
    });

    modal.querySelectorAll('.js-spedizioni-ordine-modal-close').forEach((el) => {
        el.addEventListener('click', () => closeModal());
    });
    dragHandleEl?.addEventListener('pointerdown', startDrag);
    window.addEventListener('pointermove', onDrag);
    window.addEventListener('pointerup', stopDrag);
    window.addEventListener('resize', () => {
        if (!modal.hidden && !dragState) centerModalOnPageWrap();
    });
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !modal.hidden) closeModal();
    });
})();
</script>
@endsection
