@extends('layouts.app')
@section('content')
@php
    use App\Models\ordine;
    $deleteMessage = "Attenzione! L'ordine contiene spedizione. Eliminando l'ordine annulli tutte le spedizioni presenti. Se l'ordine è pagato e ha lettera di vettura Spedisci, verrà chiamata anche l'API di cancellazione. Vuoi continuare?";

    $abaTitles = [
        'non_pagati' => 'Ordini — Non pagati',
        'pagati' => 'Ordini — Pagati',
        'annullati' => 'Ordini — Annullati',
    ];
    $abaTitle = $abaTitles[$aba] ?? 'Ordini spedizioni';

    $urlAba = static fn (string $key): string => request()->fullUrlWithQuery(['aba' => $key, 'page' => 1]);
@endphp
<div class="sq-bleed-layout">
    <x-sq-page-banner :title="$abaTitle" icon="fa-boxes" class="sq-page-banner--full" />
    <div class="ordini-index-page">

    @if (session('ok'))
        <div class="sq-alert sq-alert--success sq-mb-16">{{ session('ok') }}</div>
    @endif
    @if ($errors->has('rimborso'))
        <div class="sq-alert sq-alert--error sq-mb-16">{{ $errors->first('rimborso') }}</div>
    @endif
    @if (! empty($filtroErrors))
        <div class="sq-alert sq-alert--error sq-mb-16">
            @foreach ($filtroErrors as $fe)
                <div>{{ $fe }}</div>
            @endforeach
        </div>
    @endif

    <div class="sq-listing-tabs" role="tablist" aria-label="Stato ordini">
        <a href="{{ $urlAba('non_pagati') }}" role="tab" aria-selected="{{ $aba === 'non_pagati' ? 'true' : 'false' }}" class="sq-listing-tab-card @if ($aba === 'non_pagati') is-active @endif">
            <div class="sq-listing-tab-card__title">Non pagati</div>
            <div class="sq-listing-tab-card__count">{{ $contagens['non_pagati'] }}</div>
            <div class="sq-listing-tab-card__sub">ordini</div>
        </a>
        <a href="{{ $urlAba('pagati') }}" role="tab" aria-selected="{{ $aba === 'pagati' ? 'true' : 'false' }}" class="sq-listing-tab-card @if ($aba === 'pagati') is-active @endif">
            <div class="sq-listing-tab-card__title">Pagati</div>
            <div class="sq-listing-tab-card__count">{{ $contagens['pagati'] }}</div>
            <div class="sq-listing-tab-card__sub">ordini</div>
        </a>
        <a href="{{ $urlAba('annullati') }}" role="tab" aria-selected="{{ $aba === 'annullati' ? 'true' : 'false' }}" class="sq-listing-tab-card @if ($aba === 'annullati') is-active @endif">
            <div class="sq-listing-tab-card__title">Annullati</div>
            <div class="sq-listing-tab-card__count">{{ $contagens['annullati'] }}</div>
            <div class="sq-listing-tab-card__sub">ordini</div>
        </a>
    </div>

    <form method="GET" action="{{ route('ordini.index') }}" class="sq-filtri-form" id="form-filtri-ordini" autocomplete="off">
        <input type="hidden" name="aba" value="{{ $aba }}">
        <p class="sq-filtri-title">Filtri</p>
        <div class="sq-filtri-row">
            <div>
                <label for="filtro-numero-ordine" class="sq-filtri-label">Numero ordine</label>
                <input id="filtro-numero-ordine" name="numero_ordine" type="text" value="{{ $filtroNumeroOrdine }}"
                       class="sq-filtri-email-input" placeholder="es. 27">
            </div>
            @include('partials.filtri-periodo', [
                'periodId' => 'period_ordini',
                'wrapId' => 'wrap-date-ordini',
                'filtroPeriod' => $filtroPeriod,
                'filtroDataDa' => $filtroDataDa,
                'filtroDataA' => $filtroDataA,
                'labelPeriodo' => $aba === 'pagati' ? 'Data pagamento' : 'Data creazione',
            ])
            <div class="sq-filtri-actions">
                <button type="submit" class="sq-filtri-submit">Cerca</button>
                <a href="{{ route('ordini.index', ['aba' => $aba]) }}" class="sq-filtri-reset">Reimposta</a>
            </div>
        </div>
    </form>

    <div class="sq-ordini-tab-section" role="tabpanel">
        @if ($ordini->isEmpty())
            <p class="sq-ordini-empty">Nessun ordine in questa sezione.</p>
            @if ($aba === 'non_pagati')
                <p class="sq-m-0 sq-mt-8"><a href="{{ route('carrello.index') }}">Vai al carrello</a></p>
            @endif
        @else
            <div class="sq-table-wrap">
            <table class="sq-table sq-ordini-table">
                <colgroup>
                    <col class="sq-col-numero">
                    <col class="sq-col-data">
                    @if ($aba !== 'non_pagati')
                        <col class="sq-col-data">
                    @endif
                    <col style="width:14%">
                    <col style="width:10%">
                    <col class="sq-col-azioni">
                </colgroup>
                <thead>
                    <tr class="sq-thead-row sq-thead-row--neutral">
                        <th class="sq-th">N. ordine</th>
                        <th class="sq-th">Data</th>
                        @if ($aba !== 'non_pagati')
                            <th class="sq-th">{{ $aba === 'annullati' ? 'Data annullamento' : 'Data pagamento' }}</th>
                        @endif
                        <th class="sq-th sq-th--right">@include('partials.th-importo-iva-inclusa')</th>
                        <th class="sq-th">Spedizioni</th>
                        <th class="sq-th sq-th--right">Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($ordini as $o)
                        @php
                            if ($aba === 'non_pagati') {
                                $totaliDuali = \App\Support\OrdineRiepilogo::totaliDualiNonPagato($o);
                                $costoOrdineStandard = $totaliDuali['standard'];
                                $costoOrdineWallet = $totaliDuali['wallet'];
                            } else {
                                $costoOrdine = \App\Support\OrdineRiepilogo::totaleIvatoAttivo($o);
                            }
                            $qtdSpedizioni = (int) ($o->spedizioni_count ?? $o->spedizioni()->count());
                            $dataPagamento = $o->data_pagamento;
                            $dataAnnullamento = $o->annullato_in ?? ($aba === 'annullati' ? $o->updated_at : null);
                        @endphp
                        <tr>
                            <td class="sq-td sq-fw-700">{{ $o->id }}</td>
                            <td class="sq-td sq-td--muted sq-nowrap">{{ $o->created_at?->format('d/m/Y H:i') }}</td>
                            @if ($aba !== 'non_pagati')
                                <td class="sq-td sq-td--muted sq-nowrap">
                                    @if ($aba === 'annullati')
                                        {{ $dataAnnullamento?->format('d/m/Y H:i') ?? '—' }}
                                    @elseif ($dataPagamento)
                                        {{ $dataPagamento->format('d/m/Y H:i') }}
                                    @else
                                        <span class="sq-text-muted">—</span>
                                    @endif
                                </td>
                            @endif
                            <td class="sq-td sq-td--right sq-nowrap">
                                @if ($aba === 'non_pagati')
                                    @include('partials.due-prezzi-standard-wallet', [
                                        'prezzoStandard' => $costoOrdineStandard,
                                        'prezzoWallet' => $costoOrdineWallet,
                                        'compact' => true,
                                    ])
                                @elseif ($aba === 'pagati')
                                    <span class="sq-fw-700">{{ \App\Support\OrdineRiepilogo::importoPagatoTabella($o) }}</span>
                                @else
                                    <span class="sq-fw-700">{{ \App\Support\ImportoEuro::format($costoOrdine) }}</span>
                                @endif
                            </td>
                            <td class="sq-td">{{ $qtdSpedizioni }}</td>
                            <td class="sq-td sq-td--right">
                                <div class="sq-ordini-actions-icons">
                                    @if ($aba === 'pagati')
                                        <a
                                            href="{{ route('ordini.show', $o) }}"
                                            class="sq-ordini-icon-action sq-ordini-icon-action--view"
                                            title="Dettaglio ordine e lettere di vettura"
                                            aria-label="Dettaglio ordine {{ e($o->codice) }}"
                                        >
                                            <i class="fas fa-magnifying-glass" aria-hidden="true"></i>
                                        </a>
                                    @elseif ($aba === 'annullati')
                                        <a
                                            href="{{ route('ordini.show', $o) }}"
                                            class="sq-ordini-icon-action sq-ordini-icon-action--view"
                                            title="Dettaglio ordine annullato"
                                            aria-label="Dettaglio ordine {{ e($o->codice) }}"
                                        >
                                            <i class="fas fa-magnifying-glass" aria-hidden="true"></i>
                                        </a>
                                    @else
                                        <a
                                            href="{{ route('ordini.show', $o) }}"
                                            class="sq-ordini-icon-action sq-ordini-icon-action--view"
                                            title="Dettaglio ordine e spedizioni"
                                            aria-label="Dettaglio ordine {{ e($o->codice) }}"
                                        >
                                            <i class="fas fa-magnifying-glass" aria-hidden="true"></i>
                                        </a>
                                        <a
                                            href="{{ route('ordini.pagamento.show', $o) }}"
                                            class="sq-ordini-icon-action sq-ordini-icon-action--pay"
                                            title="Vai al pagamento"
                                            aria-label="Pagamento ordine {{ e($o->codice) }}"
                                        >
                                            <i class="fa-solid fa-euro-sign" aria-hidden="true"></i>
                                        </a>
                                        <form
                                            method="post"
                                            action="{{ route('ordini.annulla', $o) }}"
                                            class="sq-ordini-inline-form"
                                            onsubmit="return confirm(@json($deleteMessage));"
                                        >
                                            @csrf
                                            <button type="submit" class="sq-ordini-icon-action sq-ordini-icon-action--delete" title="Annulla ordine" aria-label="Annulla ordine {{ e($o->codice) }}">
                                                <i class="fas fa-trash" aria-hidden="true"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            </div>

            @include('partials.tabella-paginazione', [
                'paginator' => $ordini,
                'perPage' => $perPage,
                'queryParams' => $queryParams,
            ])
        @endif
    </div>
    </div>

    <div id="sq-ordini-detail-modal" class="sq-modal sq-modal--movimenti-ordine ordini-detail-modal" hidden data-ordini-detail-modal>
        <div class="sq-modal-backdrop js-ordini-detail-modal-close" tabindex="-1" aria-hidden="true"></div>
        <div
            class="sq-modal-panel sq-spedizioni-modal-panel ordini-detail-modal-panel"
            role="dialog"
            aria-modal="true"
            aria-labelledby="sq-ordini-detail-modal-title"
        >
            <div class="sq-spedizioni-modal-head js-ordini-modal-drag-handle">
                <h2 id="sq-ordini-detail-modal-title" class="sq-modal-title sq-m-0 sq-spedizioni-modal-order-band">Dettaglio ordine</h2>
                <button type="button" class="sq-ordini-icon-action sq-ordini-icon-action--delete js-ordini-detail-modal-close" title="Chiudi" aria-label="Chiudi dettaglio">
                    <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                </button>
            </div>
            <div id="sq-ordini-detail-modal-body" class="sq-modal-text ordini-detail-modal-body"></div>
            <div class="sq-modal-actions sq-spedizioni-modal-actions">
                <button type="button" class="sq-btn-secondary sq-modal-btn js-ordini-detail-modal-close">Chiudi</button>
            </div>
        </div>
    </div>
</div>
</div>
<script>
(() => {
    const detailModal = document.querySelector('[data-ordini-detail-modal]');
    const detailTitle = document.getElementById('sq-ordini-detail-modal-title');
    const detailBody = document.getElementById('sq-ordini-detail-modal-body');
    const detailOpens = document.querySelectorAll('.js-ordine-detail-modal-open');
    const detailPanel = detailModal?.querySelector('.sq-spedizioni-modal-panel');
    const dragHandleEl = detailModal?.querySelector('.js-ordini-modal-drag-handle');
    const pageWrapEl = document.querySelector('.ordini-index-page');
    let dragState = null;

    const closeDetailModal = () => {
        if (!detailModal) return;
        detailModal.hidden = true;
        detailModal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('sq-modal-open');
        if (detailPanel) {
            detailPanel.style.left = '';
            detailPanel.style.top = '';
        }
    };

    const centerModalOnPageWrap = () => {
        if (!detailPanel) return;
        const panelRect = detailPanel.getBoundingClientRect();
        const baseRect = pageWrapEl ? pageWrapEl.getBoundingClientRect() : { left: 0, width: window.innerWidth };
        const targetCenterX = baseRect.left + (baseRect.width / 2);
        const left = Math.max(12, Math.min(targetCenterX - (panelRect.width / 2), window.innerWidth - panelRect.width - 12));
        const currentTop = parseFloat(detailPanel.style.top || '');
        const top = Number.isFinite(currentTop) ? currentTop : 78;
        detailPanel.style.left = Math.round(left) + 'px';
        detailPanel.style.top = Math.round(top) + 'px';
    };

    const startDrag = (ev) => {
        if (!detailPanel || !dragHandleEl) return;
        const target = ev.target;
        if (target && target.closest && target.closest('.js-ordini-detail-modal-close')) return;
        const panelRect = detailPanel.getBoundingClientRect();
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
        if (!dragState || !detailPanel) return;
        const nextLeft = dragState.left + (ev.clientX - dragState.startX);
        const nextTop = dragState.top + (ev.clientY - dragState.startY);
        const panelRect = detailPanel.getBoundingClientRect();
        const clampedLeft = Math.max(12, Math.min(nextLeft, window.innerWidth - panelRect.width - 12));
        const clampedTop = Math.max(12, Math.min(nextTop, window.innerHeight - panelRect.height - 12));
        detailPanel.style.left = Math.round(clampedLeft) + 'px';
        detailPanel.style.top = Math.round(clampedTop) + 'px';
    };

    const stopDrag = () => {
        dragState = null;
        document.body.classList.remove('sq-modal-dragging');
    };

    const openDetailModal = (btn) => {
        if (!detailModal || !detailTitle || !detailBody) return;
        let payload = null;
        try {
            payload = JSON.parse(btn.getAttribute('data-ordine-dettaglio') || 'null');
        } catch (e) {
            payload = null;
        }
        const codice = (payload && payload.codice) ? String(payload.codice) : (btn.getAttribute('data-ordine-codice') || '');
        detailTitle.textContent = codice ? `Dettaglio ordine ${codice}` : 'Dettaglio ordine';
        detailBody.replaceChildren();

        const textOrDash = (v) => v != null && String(v).trim() !== '' ? String(v) : '—';
        const spedizioni = payload && Array.isArray(payload.spedizioni) ? payload.spedizioni : [];
        const pagamentoWallet = !!(payload && payload.pagamento_wallet);
        const mostraPrezziDuali = !!(payload && payload.mostra_prezzi_duali);

        const renderImportoCell = (tdImporto, sp) => {
            tdImporto.replaceChildren();
            if (mostraPrezziDuali && (sp.importo_ivato_fmt || sp.importo_ivato_wallet_fmt)) {
                const wrap = document.createElement('div');
                wrap.className = 'sq-due-prezzi sq-due-prezzi--compact';

                const rigaStandard = document.createElement('div');
                rigaStandard.className = 'sq-due-prezzi-riga';
                const labelStandard = document.createElement('span');
                labelStandard.className = 'sq-due-prezzi-label';
                labelStandard.textContent = 'Carte/Bonifico';
                const valStandard = document.createElement('span');
                valStandard.className = 'sq-due-prezzi-val sq-fw-700';
                valStandard.textContent = sp.importo_ivato_fmt ? String(sp.importo_ivato_fmt) : '—';
                rigaStandard.appendChild(labelStandard);
                rigaStandard.appendChild(valStandard);

                const rigaWallet = document.createElement('div');
                rigaWallet.className = 'sq-due-prezzi-riga sq-due-prezzi-riga--wallet';
                const labelWallet = document.createElement('span');
                labelWallet.className = 'sq-due-prezzi-label';
                labelWallet.textContent = 'Wallet';
                const valWallet = document.createElement('span');
                valWallet.className = 'sq-due-prezzi-val sq-fw-700';
                valWallet.textContent = sp.importo_ivato_wallet_fmt ? String(sp.importo_ivato_wallet_fmt) : '—';
                rigaWallet.appendChild(labelWallet);
                rigaWallet.appendChild(valWallet);

                wrap.appendChild(rigaStandard);
                wrap.appendChild(rigaWallet);
                tdImporto.appendChild(wrap);
                return;
            }

            const importoMain = document.createElement('div');
            importoMain.className = 'sq-fw-700';
            importoMain.textContent = sp.importo_ivato_fmt ? String(sp.importo_ivato_fmt) : '—';
            tdImporto.appendChild(importoMain);
            if (pagamentoWallet && sp.importo_ivato_fmt) {
                const notaWallet = document.createElement('div');
                notaWallet.className = 'sq-ordini-importo-wallet-note';
                notaWallet.textContent = 'importo scontato con wallet';
                tdImporto.appendChild(notaWallet);
            }
        };

        const statoClass = (badge) => {
            const map = {
                pagato: 'sq-stato-tabella--ok',
                rimborsata: 'sq-stato-tabella--ok',
                rimborsato: 'sq-stato-tabella--ok',
                in_attesa_rimborso: 'sq-stato-tabella--pending',
                non_pagato: 'sq-stato-tabella--pending',
                cancellato: 'sq-stato-tabella--cancel',
            };
            return map[badge] || 'sq-stato-tabella--muted';
        };

        if (spedizioni.length === 0) {
            const p = document.createElement('p');
            p.className = 'sq-m-0 sq-text-muted';
            p.textContent = 'Nessuna spedizione associata.';
            detailBody.appendChild(p);
        } else {
            const wrap = document.createElement('div');
            wrap.className = 'sq-table-wrap sq-table-wrap--warm';

            const table = document.createElement('table');
            table.className = 'sq-table';

            const thead = document.createElement('thead');
            const headRow = document.createElement('tr');
            headRow.className = 'sq-thead-row sq-thead-row--warm';
            [
                { label: 'Codice interno', right: false },
                { label: 'Destinatario', right: false },
                { label: 'Servizio', right: false },
                { label: 'Status', right: false },
                { label: 'Lettera di vettura', right: false },
                { label: 'Importo (IVA inclusa)', right: true },
            ].forEach(({ label, right }) => {
                const th = document.createElement('th');
                th.className = right ? 'sq-th sq-th--warm sq-th--right' : 'sq-th sq-th--warm';
                th.textContent = label;
                headRow.appendChild(th);
            });
            thead.appendChild(headRow);
            table.appendChild(thead);

            const tbody = document.createElement('tbody');
            spedizioni.forEach((sp, idx) => {
                const tr = document.createElement('tr');
                tr.className = idx % 2 === 0 ? 'sq-sped-row--stripe-white' : 'sq-sped-row--stripe-grey';

                const tdCodice = document.createElement('td');
                tdCodice.className = 'sq-td sq-td--border-warm sq-fw-700 sq-nowrap';
                tdCodice.textContent = textOrDash(sp.codice_interno);
                tr.appendChild(tdCodice);

                const tdDest = document.createElement('td');
                tdDest.className = 'sq-td sq-td--border-warm';
                tdDest.textContent = textOrDash(sp.destinatario_tabella);
                tr.appendChild(tdDest);

                const tdServ = document.createElement('td');
                tdServ.className = 'sq-td sq-td--border-warm sq-text-14';
                tdServ.textContent = textOrDash(sp.servizio_tabella);
                tr.appendChild(tdServ);

                const tdStato = document.createElement('td');
                tdStato.className = 'sq-td sq-td--border-warm';
                const statoSpan = document.createElement('span');
                statoSpan.className = 'sq-stato-tabella ' + statoClass(sp.stato_badge);
                statoSpan.textContent = textOrDash(sp.stato_label);
                tdStato.appendChild(statoSpan);
                tr.appendChild(tdStato);

                const tdTrack = document.createElement('td');
                tdTrack.className = 'sq-td sq-td--border-warm sq-text-14';
                const track = sp.tracking_tabella != null ? String(sp.tracking_tabella).trim() : '';
                if (track !== '') {
                    const trackSpan = document.createElement('span');
                    trackSpan.className = 'sq-sped-track-txt';
                    trackSpan.title = track;
                    trackSpan.textContent = track.length > 40 ? track.slice(0, 40) + '…' : track;
                    tdTrack.appendChild(trackSpan);
                }
                tr.appendChild(tdTrack);

                const tdImporto = document.createElement('td');
                tdImporto.className = 'sq-td sq-td--border-warm sq-td--right sq-nowrap';
                renderImportoCell(tdImporto, sp);
                tr.appendChild(tdImporto);

                tbody.appendChild(tr);
            });
            table.appendChild(tbody);
            wrap.appendChild(table);
            detailBody.appendChild(wrap);
        }

        detailModal.hidden = false;
        detailModal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('sq-modal-open');
        centerModalOnPageWrap();
    };

    detailOpens.forEach((btn) => {
        btn.addEventListener('click', () => openDetailModal(btn));
    });
    detailModal?.querySelectorAll('.js-ordini-detail-modal-close').forEach((el) => {
        el.addEventListener('click', () => closeDetailModal());
    });
    dragHandleEl?.addEventListener('pointerdown', startDrag);
    window.addEventListener('pointermove', onDrag);
    window.addEventListener('pointerup', stopDrag);
    window.addEventListener('resize', () => {
        if (detailModal && !detailModal.hidden && !dragState) centerModalOnPageWrap();
    });
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && detailModal && !detailModal.hidden) closeDetailModal();
    });
})();
</script>

@if (session('checkout_ordine_creato_id'))
    @php $ordineCreatoId = (int) session('checkout_ordine_creato_id'); @endphp
    <div id="sq-checkout-ordine-modal" class="sq-modal" data-checkout-ordine-modal>
        <div class="sq-modal-backdrop js-checkout-ordine-modal-close" tabindex="-1" aria-hidden="true"></div>
        <div class="sq-modal-panel" role="dialog" aria-modal="true" aria-labelledby="sq-checkout-ordine-modal-title">
            <h2 id="sq-checkout-ordine-modal-title" class="sq-modal-title">Ordine creato</h2>
            <p class="sq-modal-text sq-m-0 sq-mb-16">
                Questa spedizione si trova nell’ordine n. {{ $ordineCreatoId }}; puoi selezionarlo dai tuoi ordini non pagati e procedere al pagamento.
            </p>
            <div class="sq-modal-actions">
                <button type="button" class="sq-btn-primary sq-modal-btn js-checkout-ordine-modal-close">OK</button>
            </div>
        </div>
    </div>
    <script>
    (() => {
        const modal = document.querySelector('[data-checkout-ordine-modal]');
        if (!modal) return;
        modal.hidden = false;
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('sq-modal-open');
        const close = () => {
            modal.hidden = true;
            modal.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('sq-modal-open');
        };
        modal.querySelectorAll('.js-checkout-ordine-modal-close').forEach((el) => {
            el.addEventListener('click', () => close());
        });
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && !modal.hidden) close();
        });
    })();
    </script>
@endif
@endsection
