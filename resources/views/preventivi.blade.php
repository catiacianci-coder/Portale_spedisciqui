@extends('layouts.app')
@section('content')
@php
    $p = $preventivo;
@endphp

<div class="preventivi-page sq-page-preventivi"
     data-punti-servizio-url="{{ route('preventivi.punti-servizio') }}"
     data-cap-origine="{{ $capOriginePreventivo ?? '' }}"
     data-citta-origine="{{ $cittaOriginePreventivo ?? '' }}"
     data-cap-destino="{{ (string) data_get($preventivo, 'input.cap_destino', '') }}"
     data-citta-destino="{{ (string) data_get($preventivo, 'destino.comune', '') }}">

    @if ($errors->has('checkout'))
        <div class="sq-alert sq-alert--error sq-mb-14">{{ $errors->first('checkout') }}</div>
    @endif

    @if (session('ok'))
        <div class="sq-alert sq-alert--success sq-mb-14">{{ session('ok') }}</div>
    @endif

    <p class="sq-prev-riepilogo-line sq-mb-14">
        DA {{ $p['origine']['comune'] ?? '—' }} ({{ $p['origine']['provincia'] ?? '—' }}) CAP {{ $p['input']['cap_origine'] }}
        &nbsp;&nbsp;&nbsp;
        A {{ $p['destino']['comune'] ?? '—' }} ({{ $p['destino']['provincia'] ?? '—' }}) CAP {{ $p['input']['cap_destino'] }}
        Dimensioni: {{ number_format($p['input']['altezza'], 2, ',', '.') }} × {{ number_format($p['input']['larghezza'], 2, ',', '.') }} × {{ number_format($p['input']['spessore'], 2, ',', '.') }} cm
        | Peso: {{ number_format($p['input']['peso'], 2, ',', '.') }} kg
        - <strong>I prezzi esposti sono IVA esclusa</strong>
    </p>

    <div class="sq-prev-card sq-prev-card--no-mb">
        <div class="sq-prev-toolbar">
            <div class="preventivi-ordina-row sq-prev-ordina-row">
                <label for="ordina-preventivi" class="sq-prev-ordina-label">Ordina:</label>
                <select id="ordina-preventivi" class="sq-prev-ordina-select">
                    <option value="totale_asc">Prezzo: crescente</option>
                    <option value="totale_desc">Prezzo: decrescente</option>
                    <option value="consegna_asc">Consegna: più veloce</option>
                    <option value="consegna_desc">Consegna: più lenta</option>
                </select>
            </div>
        </div>

        @php
            $mostrati = 0;
            $idDestino = (int) ($p['input']['id_comune_destino'] ?? 0);
            $utenteLiccardi = (bool) ($utenteLiccardi ?? false);

            $prezzoClienteDaCostoApi = static function (?float $costoApi, array $cor, bool $applicaBasePremiumLiccardi = false): ?float {
                if ($costoApi === null) {
                    return null;
                }
                if ($applicaBasePremiumLiccardi) {
                    $costoApi = \App\Support\LiccardiPremiumPricing::costoTrasportoBase($costoApi);
                }
                $pct = (float) ($cor['ricarico_percentuale'] ?? 0);

                return round($costoApi * (1 + ($pct / 100)), 2);
            };

            $corrieriIdsNascosti = $corrieriIdsNascosti ?? [];

            $righeOk = collect($p['righe'] ?? [])
                ->filter(function ($r) use ($sendcloudQuotePerCorriere, $liccardiQuotePerCorriere, $spedisciQuotePerCorriere, $utenteLiccardi, $corrieriIdsNascosti) {
                    $cid = (int) ($r['corriere']['id'] ?? 0);
                    if (in_array($cid, $corrieriIdsNascosti, true)) {
                        return false;
                    }
                    $cor = $r['corriere'] ?? [];
                    $piattaforma = \App\Support\PiattaformaCorriere::normalizza($cor['piattaforma'] ?? '');
                    $usaTariffaInterna = (bool) ($cor['tariffa_interna'] ?? true);
                    $isLiccardiTms = \App\Support\PiattaformaCorriere::usaPreventiviLiccardiTms($piattaforma) && ! $usaTariffaInterna;
                    if ($isLiccardiTms && ! $utenteLiccardi) {
                        return false;
                    }

                    return \App\Support\PreventivoRigaSelezionabile::isSelezionabile($r)
                        && \App\Support\PreventivoRigaSelezionabile::haQuotazioneEsternaValida(
                            $r,
                            $sendcloudQuotePerCorriere ?? [],
                            $liccardiQuotePerCorriere ?? [],
                            $spedisciQuotePerCorriere ?? [],
                        );
                })
                ->sortBy(function ($r) use ($sendcloudQuotePerCorriere, $liccardiQuotePerCorriere, $spedisciQuotePerCorriere, $corriereCampiAggiornati, $prezzoClienteDaCostoApi) {
                    $cid = (int) ($r['corriere']['id'] ?? 0);
                    $cor = array_merge($r['corriere'] ?? [], ($corriereCampiAggiornati[$cid] ?? []));
                    $piattaforma = \App\Support\PiattaformaCorriere::normalizza($cor['piattaforma'] ?? '');
                    if (\App\Support\PiattaformaCorriere::usaPreventiviSendcloud($piattaforma)) {
                        $q = $sendcloudQuotePerCorriere[$cid]['quote']['price_amount'] ?? null;
                        if ($q !== null) {
                            return (float) ($prezzoClienteDaCostoApi((float) $q, $cor) ?? $q);
                        }
                    }
                    if (\App\Support\PiattaformaCorriere::usaPreventiviLiccardiTms($piattaforma)
                        && ! (bool) ($cor['tariffa_interna'] ?? true)) {
                        $q = $liccardiQuotePerCorriere[$cid]['quote']['price_amount'] ?? null;
                        if ($q !== null) {
                            return (float) ($prezzoClienteDaCostoApi((float) $q, $cor, true) ?? $q);
                        }
                    }
                    if (\App\Support\PiattaformaCorriere::usaPreventiviSpedisciOnline($piattaforma)
                        && ! (bool) ($cor['tariffa_interna'] ?? true)) {
                        $q = $spedisciQuotePerCorriere[$cid]['quote']['price_amount'] ?? null;
                        if ($q !== null) {
                            return (float) ($prezzoClienteDaCostoApi((float) $q, $cor) ?? $q);
                        }
                    }

                    return (float) ($r['prezzo_finale'] ?? 0);
                })
                ->values();

            $extraGiorniDisagiato = function (int $corriereId) use ($idDestino) {
                if ($idDestino <= 0) {
                    return null;
                }

                $row = \App\Models\disagiato::query()
                    ->where('corriere_id', $corriereId)
                    ->where('comune_id', $idDestino)
                    ->first();

                if (!$row) {
                    return null;
                }

                $raw = trim((string) ($row->varie_1 ?? ''));
                if ($raw !== '' && ctype_digit($raw)) {
                    return (int) $raw;
                }

                return 2;
            };
            $colonnePagamento = $colonnePagamento ?? \App\Support\PreventivoColonnePagamento::colonneAttive();
            $numColonnePrezzo = max(1, count($colonnePagamento));
        @endphp

        <div class="sq-prev-table-scroll" style="--sq-prev-price-cols: {{ $numColonnePrezzo }}">
            <div class="sq-prev-table-min">
                <div class="sq-prev-thead-shell">
                    <div class="sq-prev-thead-bar"></div>
                    <div class="sq-prev-thead-grid">
                        <div class="sq-prev-th">Corriere</div>
                        <div class="sq-prev-th sq-prev-th-punti" aria-hidden="true">&nbsp;</div>
                        <div class="sq-prev-th">Ritiro</div>
                        <div class="sq-prev-th">Consegna</div>
                        <div class="sq-prev-th">Tempi</div>
                        @foreach ($colonnePagamento as $col)
                            <div class="sq-prev-th sq-prev-th-prezzo">{{ $col['titolo'] }}</div>
                        @endforeach
                        <div class="sq-prev-th sq-prev-th-center" aria-hidden="true">&nbsp;</div>
                    </div>
                </div>
                <div id="preventivi-cards">
                    @forelse ($righeOk as $r)
                        @php
                            $mostrati++;
                            $cid = (int) ($r['corriere']['id'] ?? 0);
                            $cor = array_merge($r['corriere'] ?? [], $corriereCampiAggiornati[$cid] ?? []);
                            $piattaforma = \App\Support\PiattaformaCorriere::normalizza($cor['piattaforma'] ?? '');
                            $usaTariffaInterna = (bool) ($cor['tariffa_interna'] ?? data_get($r, 'corriere.tariffa_interna', true));
                            $isSendcloud = \App\Support\PiattaformaCorriere::usaPreventiviSendcloud($piattaforma);
                            $isLiccardiTms = \App\Support\PiattaformaCorriere::usaPreventiviLiccardiTms($piattaforma) && ! $usaTariffaInterna;
                            $isSpedisciOnline = \App\Support\PiattaformaCorriere::usaPreventiviSpedisciOnline($piattaforma) && ! $usaTariffaInterna;
                            $sendcloudProbe = $sendcloudQuotePerCorriere[$cid] ?? null;
                            $sendcloudQuote = is_array($sendcloudProbe['quote'] ?? null) ? $sendcloudProbe['quote'] : null;
                            $liccardiProbe = $liccardiQuotePerCorriere[$cid] ?? null;
                            $liccardiQuote = is_array($liccardiProbe['quote'] ?? null) ? $liccardiProbe['quote'] : null;
                            $spedisciProbe = $spedisciQuotePerCorriere[$cid] ?? null;
                            $spedisciQuote = is_array($spedisciProbe['quote'] ?? null) ? $spedisciProbe['quote'] : null;
                            $extraGg = $extraGiorniDisagiato($cid);
                            $formatGgLavorativi = static function (int $giorniMin, int $ampiezza = 2): string {
                                $giorniMax = $giorniMin + $ampiezza;

                                return $giorniMin.'–'.$giorniMax.' <span class="sq-nowrap">gg lavorativi</span>';
                            };
                            $tempi = $formatGgLavorativi(2);
                            if ($isSendcloud && $sendcloudQuote !== null && isset($sendcloudQuote['lead_time_hours'])) {
                                $leadHours = (int) $sendcloudQuote['lead_time_hours'];
                                $leadDays = max(1, (int) ceil($leadHours / 24));
                                $tempi = $formatGgLavorativi($leadDays + (int) ($extraGg ?? 0));
                            } elseif ($extraGg !== null) {
                                $tempi = $formatGgLavorativi(2 + (int) $extraGg);
                            }
                            $logoUrl = $logoUrlPerCorriere[$cid] ?? null;
                            $logoHint = 'public/images/loghi_corrieri/'.$cid.'.png (o .jpg) oppure public/loghi_corrieri/';
                            $nomePreventivo = trim((string) ($cor['nome_corriere_preventivo'] ?? ''));
                            $nomeVisFallback = trim((string) ($cor['nome_visualizzato'] ?? ''));
                            $iniziale = mb_strtoupper(mb_substr(
                                $nomePreventivo !== '' ? $nomePreventivo : ($nomeVisFallback !== '' ? $nomeVisFallback : '?'),
                                0,
                                1,
                            ));
                            $costoApiTrasporto = null;
                            if ($isSendcloud && isset($sendcloudQuote['price_amount']) && $sendcloudQuote['price_amount'] !== null) {
                                $costoApiTrasporto = (float) $sendcloudQuote['price_amount'];
                            } elseif ($isLiccardiTms && isset($liccardiQuote['price_amount']) && $liccardiQuote['price_amount'] !== null) {
                                $costoApiTrasporto = (float) $liccardiQuote['price_amount'];
                            } elseif ($isSpedisciOnline && isset($spedisciQuote['price_amount']) && $spedisciQuote['price_amount'] !== null) {
                                $costoApiTrasporto = (float) $spedisciQuote['price_amount'];
                            }
                            if ($costoApiTrasporto !== null) {
                                $prezzoClienteTrasporto = (float) ($prezzoClienteDaCostoApi(
                                    $costoApiTrasporto,
                                    $cor,
                                    $isLiccardiTms && $utenteLiccardi,
                                ) ?? $costoApiTrasporto);
                            } else {
                                $prezzoClienteTrasporto = (float) ($r['prezzo_finale'] ?? 0);
                            }
                            $prezzoTrasporto = $prezzoClienteTrasporto;
                            $prezziPerColonna = [];
                            foreach ($colonnePagamento as $col) {
                                $prezziPerColonna[] = \App\Support\PreventivoColonnePagamento::prezzoPerColonna(
                                    $prezzoTrasporto,
                                    (float) ($col['commissioni_pct'] ?? 0),
                                );
                            }
                            $prezzoSort = $prezziPerColonna !== [] ? min($prezziPerColonna) : $prezzoTrasporto;
                            $giorniBase = ($isSendcloud && isset($sendcloudQuote['lead_time_hours']) && $sendcloudQuote['lead_time_hours'] !== null)
                                ? max(1, (int) ceil(((int) $sendcloudQuote['lead_time_hours']) / 24))
                                : 3;
                            $giorniSort = $giorniBase + (int) ($extraGg ?? 0);
                            $pickupMode = trim((string) ($cor['pickup'] ?? ''));
                            $consegnaMode = trim((string) ($cor['consegna'] ?? ''));
                            $puntoRitiroLabel = \App\Support\CorrierePuntoEtichetta::ritiroConsultabileInPreventivi(
                                $pickupMode,
                                (string) ($cor['punto_ritiro'] ?? ''),
                            )
                                ? trim((string) ($cor['punto_ritiro'] ?? ''))
                                : '';
                            $renderPrezzoValore = function (?float $importoCliente): string {
                                $valore = $importoCliente !== null
                                    ? \App\Support\ImportoEuro::format($importoCliente)
                                    : '—';

                                return '<div class="sq-prev-prezzo-block">'
                                    .'<div class="sq-prev-prezzo-val">'.$valore.'</div>'
                                    .'</div>';
                            };
                        @endphp

                        <div class="preventivo-card sq-prev-card-mb"
                             data-corriere-id="{{ $cid }}"
                             data-totale="{{ $prezzoSort }}"
                             data-consegna-giorni="{{ $giorniSort }}">
                            <div class="sq-prev-card-shell">
                                <div class="preventivo-card-inner sq-prev-card-grid">
                                    <div class="sq-minw-0">
                                        <div class="sq-prev-corriere-col">
                                            @if ($logoUrl)
                                                <img src="{{ $logoUrl }}" alt="" class="sq-prev-corriere-logo">
                                            @else
                                                <div class="sq-prev-corriere-ph" title="Nessun file trovato. Atteso: {{ $logoHint }}">{{ $iniziale }}</div>
                                            @endif
                                            @if ($nomePreventivo !== '')
                                                <div class="sq-prev-corriere-name">{{ $nomePreventivo }}</div>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="sq-prev-punti-ritiro-cell">
                                        @if ($puntoRitiroLabel !== '')
                                            @include('preventivi.partials.punti-ritiro-link', [
                                                'linkLabel' => $puntoRitiroLabel,
                                                'corriereId' => $cid,
                                                'tipo' => 'ritiro',
                                                'sendcloudConfigured' => $sendcloudConfigured ?? false,
                                            ])
                                        @endif
                                    </div>
                                    <div class="sq-prev-mode-cell">
                                        @include('preventivi.partials.mode-colonna', ['mode' => $pickupMode])
                                    </div>
                                    <div class="sq-prev-mode-cell">
                                        @include('preventivi.partials.mode-colonna', ['mode' => $consegnaMode])
                                    </div>
                                    <div class="sq-prev-tempi">
                                        {!! $tempi !!}
                                    </div>
                                    @foreach ($colonnePagamento as $colIdx => $col)
                                        @php
                                            $prezzoCol = \App\Support\PreventivoColonnePagamento::prezzoPerColonna(
                                                $prezzoTrasporto,
                                                (float) ($col['commissioni_pct'] ?? 0),
                                            );
                                        @endphp
                                        <div class="sq-prev-prezzo-cell">
                                            {!! $renderPrezzoValore($prezzoCol) !!}
                                        </div>
                                    @endforeach
                                    <div class="sq-prev-actions-cell">
                                        <a href="{{ route('spedizione.indirizzi', ['corriere' => $cid]) }}" title="Indirizzi e poi pagamento" aria-label="Indirizzi e poi pagamento"
                                           class="btn-seleziona-corriere sq-prev-btn-corriere">
                                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                <path d="M14 3h7v7" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round"/>
                                                <path d="M10 14L21 3" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round"/>
                                                <path d="M21 14v6a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h6" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                        </a>
                                    </div>
                                </div>

                                @if ($isLiccardiTms && ! $utenteLiccardi)
                                    @php
                                        $prezzoVolumeLiccardi = \App\Support\LiccardiVolumeSconto::trasportoScontato($prezzoClienteTrasporto);
                                    @endphp
                                    <div class="sq-prev-liccardi-volume-row">
                                        <p class="sq-prev-liccardi-volume-msg sq-m-0">
                                            {{ \App\Support\LiccardiVolumeSconto::messaggioPreventivo() }}
                                        </p>
                                        <p class="sq-prev-liccardi-volume-prezzo sq-m-0">
                                            Prezzo con sconto volume: {{ \App\Support\ImportoEuro::format($prezzoVolumeLiccardi) }}
                                        </p>
                                    </div>
                                @endif

                                @php $serviziInd = $serviziIndicativiPerCorriere[$cid] ?? []; @endphp
                                @if (! empty($serviziInd))
                                    <div class="preventivo-servizi-orizzontali sq-prev-servizi-row" data-corriere-id="{{ $cid }}">
                                        <p class="sq-prev-servizi-disponibili sq-m-0">
                                            Servizi aggiuntivi (acquistabili al momento del check-out):
                                            @foreach ($serviziInd as $siIdx => $si)
                                                <span class="sq-prev-servizio-nome">{{ $si['nome'] }}</span>@if ($siIdx < count($serviziInd) - 1) - @endif
                                            @endforeach
                                        </p>
                                    </div>
                                @endif

                            </div>
                        </div>
                    @empty
                        <div class="sq-prev-empty-risultato">
                            Nessun corriere disponibile per i vincoli attuali (tariffa interna o tariffa API).
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        @if ($mostrati === 0)
            {{-- già gestito dal empty --}}
        @endif
    </div>
</div>

<div id="sq-prev-sp-overlay" class="sq-prev-sp-overlay" hidden aria-hidden="true">
    <div class="sq-prev-sp-panel" role="dialog" aria-labelledby="sq-prev-sp-title" aria-modal="true">
        <div class="sq-prev-sp-panel-head">
            <h3 id="sq-prev-sp-title" class="sq-prev-sp-title">Punti ritiro mittente</h3>
            <button type="button" class="sq-prev-sp-close" id="sq-prev-sp-close" aria-label="Chiudi">×</button>
        </div>
        <p class="sq-prev-sp-sub" id="sq-prev-sp-sub">Solo visualizzazione — elenco punti vicino al mittente.</p>
        <div class="sq-prev-sp-body" id="sq-prev-sp-body">
            <p class="sq-text-muted">Seleziona un servizio con Punto Poste o Ufficio postale in ritiro.</p>
        </div>
    </div>
</div>

<script>
(() => {
    const page = document.querySelector('.preventivi-page');
    const overlay = document.getElementById('sq-prev-sp-overlay');
    const panelBody = document.getElementById('sq-prev-sp-body');
    const panelSub = document.getElementById('sq-prev-sp-sub');
    const closeBtn = document.getElementById('sq-prev-sp-close');
    const puntiUrl = page?.dataset.puntiServizioUrl || '';
    const capOrigine = page?.dataset.capOrigine || '';
    const cittaOrigine = page?.dataset.cittaOrigine || '';

    const escapeHtml = (s) => String(s)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');

    const renderPointsList = (points) => {
        if (!points.length) {
            return '<p class="sq-text-muted">Nessun punto trovato in questa zona.</p>';
        }
        let html = '<ul class="sq-prev-sp-list">';
        points.forEach((p) => {
            const hours = Array.isArray(p.opening_hours) ? p.opening_hours : [];
            const hoursRows = hours.map((h) =>
                `<tr><td>${escapeHtml(h.day)}</td><td>${escapeHtml(h.hours)}</td></tr>`,
            ).join('');
            html += `<li class="sq-prev-sp-item">
                <details>
                    <summary>
                        <strong>${escapeHtml(p.name)}</strong>
                        <span class="sq-prev-sp-addr">${escapeHtml(p.address_line)} — ${escapeHtml(p.postal_code)} ${escapeHtml(p.city)}</span>
                    </summary>
                    ${hoursRows ? `<table class="sq-sc-hours-table"><tbody>${hoursRows}</tbody></table>` : '<p class="sq-text-muted sq-prev-sp-no-hours">Orari non disponibili.</p>'}
                </details>
            </li>`;
        });
        html += '</ul>';

        return html;
    };

    const openPanel = async (btn) => {
        if (!overlay || !panelBody || !puntiUrl) return;

        const corriereId = btn.dataset.corriereId || '';
        const label = btn.dataset.linkLabel || 'Punti vicini';
        const capRef = capOrigine;
        const cittaRef = cittaOrigine;

        overlay.hidden = false;
        overlay.setAttribute('aria-hidden', 'false');
        document.body.classList.add('sq-prev-sp-open');
        if (panelSub) {
            panelSub.textContent = `${label} — CAP ${capRef}${cittaRef ? ' · '.$cittaRef : ''} (solo visualizzazione)`;
        }
        panelBody.innerHTML = '<p class="sq-text-muted">Caricamento punti…</p>';

        const params = new URLSearchParams();
        if (corriereId) params.set('corriere_id', corriereId);
        params.set('tipo', 'ritiro');

        try {
            const res = await fetch(`${puntiUrl}?${params.toString()}`, {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });
            const data = await res.json();
            if (!data.ok) {
                panelBody.innerHTML = `<p class="sq-alert sq-alert--info-warm">${escapeHtml(data.error || 'Errore nel recupero punti.')}</p>`;

                return;
            }
            const count = data.count ?? (data.points?.length ?? 0);
            panelBody.innerHTML = `<p class="sq-prev-sp-count"><strong>${count}</strong> punti trovati</p>${renderPointsList(data.points || [])}`;
        } catch {
            panelBody.innerHTML = '<p class="sq-alert sq-alert--info-warm">Errore di rete durante il caricamento.</p>';
        }
    };

    const closePanel = () => {
        if (!overlay) return;
        overlay.hidden = true;
        overlay.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('sq-prev-sp-open');
    };

    document.querySelectorAll('.sq-prev-sp-link-btn').forEach((btn) => {
        btn.addEventListener('click', () => openPanel(btn));
    });
    closeBtn?.addEventListener('click', closePanel);
    overlay?.addEventListener('click', (e) => {
        if (e.target === overlay) closePanel();
    });
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && overlay && !overlay.hidden) closePanel();
    });

    const parseNum = (v) => {
        if (v === null || v === undefined) return 0;
        const s = String(v).trim();
        if (!s) return 0;
        const normalized = s.replace(',', '.');
        const x = Number(normalized);
        return Number.isFinite(x) ? x : 0;
    };

    const cardsRoot = document.getElementById('preventivi-cards');
    const sortSelect = document.getElementById('ordina-preventivi');
    const sortRows = () => {
        if (!cardsRoot || !sortSelect) return;
        const mode = sortSelect.value;
        const rows = Array.from(cardsRoot.querySelectorAll('.preventivo-card'));
        const mul = mode.endsWith('desc') ? -1 : 1;
        const byConsegna = mode.startsWith('consegna');

        rows.sort((a, b) => {
            const va = byConsegna
                ? parseNum(a.dataset.consegnaGiorni)
                : parseNum(a.dataset.totale);
            const vb = byConsegna
                ? parseNum(b.dataset.consegnaGiorni)
                : parseNum(b.dataset.totale);
            return (va - vb) * mul;
        });

        rows.forEach((card) => {
            cardsRoot.appendChild(card);
        });
    };

    sortSelect?.addEventListener('change', sortRows);
    sortRows();
})();
</script>
@endsection
