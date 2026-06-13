@extends('layouts.app')
@section('content')
@php
    $ind = $indirizzi ?? [];
    use App\Models\parametri_globali;
    $ibanBonificoCheckout = parametri_globali::valoreTesto(parametri_globali::DENOM_IBAN_CC_R_B);
    $haBonificoCheckout = collect($metodiJson ?? [])->contains(fn ($m) => ! empty($m['is_bonifico']));
@endphp

@if (! empty($ind['partenza']) || ! empty($ind['destinazione']))
        @php
            $p = is_array($ind['partenza'] ?? null) ? $ind['partenza'] : [];
            $d = is_array($ind['destinazione'] ?? null) ? $ind['destinazione'] : [];
            $corriereNome = trim((string) ($nomeCorriere ?? ''));
            $tipoSped = trim((string) data_get($preventivo, 'tipo_spedizione.tipo_spedizione', ''));
            $peso = (float) data_get($preventivo, 'input.peso', 0);
            $alt = (float) data_get($preventivo, 'input.altezza', 0);
            $lar = (float) data_get($preventivo, 'input.larghezza', 0);
            $spe = (float) data_get($preventivo, 'input.spessore', 0);

            $mittNome = trim((string) (($p['nome'] ?? '') . ' ' . ($p['cognome'] ?? '')));
            $destNome = trim((string) (($d['nome'] ?? '') . ' ' . ($d['cognome'] ?? '')));
            if ($destNome === '') {
                $destNome = trim((string) ($d['nome_destinatario'] ?? ''));
            }
            $mittAddr = trim((string) (($p['indirizzo'] ?? '') !== '' ? ($p['indirizzo'] ?? '') : (($p['via'] ?? '').' '.($p['numero'] ?? ''))));
            $destProv = strtoupper(substr((string) ($d['provincia'] ?? ''), 0, 2));
            $puntoRiepilogo = null;
            $puntoId = (int) ($d['to_service_point'] ?? 0);
            if ($puntoId > 0 && ! empty($puntiDestRows ?? [])) {
                foreach ($puntiDestRows as $pr) {
                    if ((int) ($pr['id'] ?? 0) === $puntoId) {
                        $puntoRiepilogo = $pr;
                        break;
                    }
                }
            }
            if ($puntoRiepilogo === null && ! empty($d['punto_consegna']) && is_array($d['punto_consegna'])) {
                $puntoRiepilogo = $d['punto_consegna'];
            }
            if ($puntoRiepilogo !== null) {
                $destAddr = trim((string) ($puntoRiepilogo['address_line'] ?? ''));
                if ($destAddr === '' || $destAddr === '—') {
                    $destAddr = trim((string) (($puntoRiepilogo['street'] ?? '').' '.($puntoRiepilogo['house_number'] ?? '')));
                }
                $capPunto = trim((string) ($puntoRiepilogo['postal_code'] ?? ''));
                $cittaPunto = trim((string) ($puntoRiepilogo['city'] ?? ''));
                $destGeo = trim($capPunto.' '.$cittaPunto.($destProv !== '' ? ' ('.$destProv.')' : ''));
            } else {
                $destAddr = trim((string) (($d['indirizzo'] ?? '') !== '' ? ($d['indirizzo'] ?? '') : (($d['via'] ?? '').' '.($d['numero'] ?? ''))));
                if ($destAddr === '' && ! empty($d['nome_punto'])) {
                    $destAddr = (string) $d['nome_punto'];
                }
                $destGeo = trim((string) (($d['cap'] ?? '').' '.($d['comune'] ?? '').($destProv !== '' ? ' ('.$destProv.')' : '')));
            }
            $mittGeo = trim((string) (($p['cap'] ?? '').' '.($p['comune'] ?? '').' ('.strtoupper(substr((string) ($p['provincia'] ?? ''), 0, 2)).')'));
        @endphp
        <div class="sq-checkout-indirizzi sq-checkout-indirizzi--oneline sq-checkout-indirizzi--fullbleed">
            <div class="sq-checkout-indirizzi-inner">
                <span class="sq-checkout-indirizzi-seg">
                    <strong>{{ e($corriereNome !== '' ? $corriereNome : '—') }}</strong>
                    · {{ e($tipoSped !== '' ? $tipoSped : '—') }}
                    · {{ number_format($peso, 2, ',', '.') }} kg
                    · {{ number_format($alt, 2, ',', '.') }} × {{ number_format($lar, 2, ',', '.') }} × {{ number_format($spe, 2, ',', '.') }} cm
                </span><span class="sq-checkout-indirizzi-sep"> - </span><span class="sq-checkout-indirizzi-seg">
                    <strong class="sq-checkout-who-label">Mittente</strong>
                    {{ e($mittNome !== '' ? $mittNome : '—') }} - {{ e($mittAddr !== '' ? $mittAddr : '—') }} {{ e($mittGeo) }}
                </span><span class="sq-checkout-indirizzi-sep"> - </span><span class="sq-checkout-indirizzi-seg">
                    <strong class="sq-checkout-who-label">Destinatario</strong>
                    <span id="checkout-destinatario-riepilogo"
                          data-dest-nome="{{ e($destNome !== '' ? $destNome : '—') }}"
                          data-dest-prov="{{ e($destProv) }}">{{ e($destNome !== '' ? $destNome : '—') }} - {{ e($destAddr !== '' ? $destAddr : '—') }} {{ e($destGeo) }}</span>
                </span>
            </div>
            <div class="sq-checkout-indirizzo-row sq-checkout-servizi-riepilogo" id="checkout-servizi-riepilogo" hidden>
                <strong class="sq-checkout-servizi-riepilogo-label">Servizi aggiuntivi</strong>
                <span id="checkout-servizi-riepilogo-items"></span>
            </div>
        </div>
@endif

<div class="checkout-page sq-page-checkout">
    @if ($errors->has('checkout'))
        <div class="sq-alert sq-alert--error sq-mb-14">{{ $errors->first('checkout') }}</div>
    @endif

    @if (! ($stripeConfigured ?? true) && collect($metodiJson ?? [])->contains(fn ($m) => ! empty($m['is_carta'])))
        <div class="sq-alert sq-alert--info-warm sq-mb-14">
            Pagamento con carta non disponibile finché non configuri le chiavi Stripe in .env.
        </div>
    @endif

    @if (session('ok'))
        <div class="sq-alert sq-alert--success sq-mb-14">{{ session('ok') }}</div>
    @endif

    @if ($consegnaPunto ?? false)
            @include('checkout.partials.punto-consegna', [
                'puntiDestRows' => $puntiDestRows ?? [],
                'puntiDestError' => $puntiDestError ?? null,
                'puntoSelezionato' => $puntoSelezionato ?? [],
                'puntoConsegnaLabel' => $puntoConsegnaLabel ?? null,
            ])
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
    @endif

    <div class="sq-checkout-grid checkout-grid">
        <aside class="sq-checkout-aside">
            <h2 class="sq-h2-aside">Servizi aggiuntivi previsti dal Corriere</h2>
            @php $groups = $serviziCheckoutGrouped ?? []; @endphp
            @if (empty($groups))
                <p class="sq-text-muted sq-m-0 sq-text-14">questo corriere non offre servizi aggiuntivi</p>
            @else
                <ul class="sq-checkout-servizi-list">
                    @foreach ($groups as $g)
                        @php
                            $nome = $g['servizio']['denominazione_servizio'] ?? 'Servizio';
                            $sid = (int) ($g['id_servizi_aggiuntivi'] ?? 0);
                            $mod = $g['modalita'] ?? 'fisso';
                            $pv = $g['pivot_singolo'] ?? null;
                            $hint = '';
                            if ($mod === 'fisso' && is_array($pv)) {
                                $pct = $pv['percentuale_cor'] ?? null;
                                $abs = $pv['valore_fisso_cor'] ?? null;
                                if ($pct !== null && $pct !== '' && (float) $pct > 0) {
                                    $hint .= number_format((float) $pct * 100, 2, ',', '.').'% sul trasporto (listino)';
                                }
                                if ($abs !== null && $abs !== '' && (float) $abs > 0) {
                                    if ($hint !== '') {
                                        $hint .= ' + ';
                                    }
                                    $hint .= number_format((float) $abs, 2, ',', '.').' €';
                                }
                            } elseif ($mod === 'valore_merce') {
                                $hint = 'Dipende dal valore merce';
                            }
                        @endphp
                        <li class="sq-checkout-servizio-li">
                            <label class="sq-checkout-servizio-label">
                                <input type="checkbox" class="js-servizio-extra sq-servizio-extra-input"
                                       data-modalita="{{ $mod }}"
                                       data-id-servizio="{{ $sid }}"
                                       data-servizio-nome="{{ e($nome) }}"
                                       data-pid="{{ $mod === 'fisso' && is_array($pv) ? ($pv['id'] ?? '') : '' }}"
                                       data-pct="{{ $mod === 'fisso' && is_array($pv) && isset($pv['percentuale_cor']) && $pv['percentuale_cor'] !== null ? (string) $pv['percentuale_cor'] : '' }}"
                                       data-abs="{{ $mod === 'fisso' && is_array($pv) && isset($pv['valore_fisso_cor']) && $pv['valore_fisso_cor'] !== null ? (string) $pv['valore_fisso_cor'] : '' }}"
                                       data-ricarico-k91="{{ $mod === 'fisso' && is_array($pv) && isset($pv['ricarico_k91']) ? (string) $pv['ricarico_k91'] : '' }}"
                                       data-fisso-k91="{{ $mod === 'fisso' && is_array($pv) && isset($pv['valore_fisso_k91']) ? (string) $pv['valore_fisso_k91'] : '' }}"
                                       data-testo-servizio="{{ e($g['testo_servizio'] ?? $nome) }}"
                                       data-quote-api="{{ ($usaQuoteApiServizi ?? false) && $mod === 'valore_merce' ? '1' : '0' }}"
                                       @if ($mod === 'valore_merce') data-bands='@json($g['bands'] ?? [])' @endif>
                                <span class="sq-servizio-extra-text">
                                    <strong>{{ $nome }}</strong>
                                    @if ($hint !== '')
                                        <span class="sq-servizio-extra-hint">{{ $hint }}</span>
                                    @endif
                                </span>
                            </label>
                            @if ($mod === 'valore_merce')
                                @php
                                    $bandMin = null;
                                    $bandMax = null;
                                    foreach ($g['bands'] ?? [] as $b) {
                                        if (isset($b['min_fascia']) && $b['min_fascia'] !== null && $b['min_fascia'] !== '') {
                                            $bandMin = $bandMin === null ? (float) $b['min_fascia'] : min($bandMin, (float) $b['min_fascia']);
                                        }
                                        if (isset($b['max_fascia']) && $b['max_fascia'] !== null && $b['max_fascia'] !== '') {
                                            $bandMax = $bandMax === null ? (float) $b['max_fascia'] : max($bandMax, (float) $b['max_fascia']);
                                        }
                                    }
                                    $merceLabel = mb_strtolower(trim((string) ($g['testo_servizio'] ?? $nome))) === 'contrassegno'
                                        ? 'Importo contrassegno (€)'
                                        : 'Valore merce dichiarato (€)';
                                @endphp
                                <div class="sq-checkout-merce-wrap js-merce-wrap" hidden>
                                    <label class="sq-label-sm-muted sq-mt-8" for="merce-{{ $sid }}">{{ $merceLabel }}</label>
                                    @if ($bandMin !== null || $bandMax !== null)
                                        <span class="sq-text-muted sq-text-14 sq-d-block sq-mb-6">
                                            @if ($bandMin !== null && $bandMax !== null)
                                                Consentito da {{ number_format($bandMin, 2, ',', '.') }} € a {{ number_format($bandMax, 2, ',', '.') }} €
                                            @elseif ($bandMin !== null)
                                                Minimo {{ number_format($bandMin, 2, ',', '.') }} €
                                            @else
                                                Massimo {{ number_format($bandMax, 2, ',', '.') }} €
                                            @endif
                                        </span>
                                    @endif
                                    <input id="merce-{{ $sid }}" type="text" inputmode="decimal" autocomplete="off"
                                           class="home-input js-valore-merce" data-sid="{{ $sid }}"
                                           @if ($bandMin !== null) data-min-fascia="{{ $bandMin }}" @endif
                                           @if ($bandMax !== null) data-max-fascia="{{ $bandMax }}" @endif
                                           placeholder="es. 250,00">
                                    <span class="js-servizio-prezzo sq-servizio-prezzo" hidden aria-live="polite"></span>
                                    @if (($usaQuoteApiServizi ?? false))
                                        <div class="js-servizio-api-trace sq-servizio-api-trace" hidden></div>
                                    @endif
                                </div>
                            @else
                                <span class="js-servizio-prezzo sq-servizio-prezzo" hidden aria-live="polite"></span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif
        </aside>

        <div class="sq-checkout-main-panel">
            <h2 class="sq-h2-aside">Riepilogo e pagamento</h2>
            @if (! empty($isLiccardiTms) && ! empty($liccardiVolumeMessaggio))
                <div class="sq-alert sq-alert--info sq-mb-12">
                    {{ $liccardiVolumeMessaggio }}
                    @if ($liccardiPrezzoVolume !== null)
                        <span class="sq-prev-liccardi-volume-prezzo">
                            Prezzo con sconto volume: {{ number_format($liccardiPrezzoVolume, 2, ',', '.') }} €
                        </span>
                    @endif
                    <span class="sq-text-muted"> Lo sconto si applica confermando un ordine con almeno {{ \App\Support\LiccardiVolumeSconto::MIN_SPEDIZIONI }} spedizioni Liccardi nel carrello.</span>
                </div>
            @endif

            <div class="sq-table-wrap">
                <table class="sq-table">
                    <thead>
                        <tr class="sq-thead-row sq-thead-row--neutral">
                            <th class="sq-th sq-th--8">Metodo</th>
                            <th class="sq-th sq-th--8 sq-th--right">Spedizione</th>
                            <th class="sq-th sq-th--8 sq-th--right">Servizi</th>
                            <th class="sq-th sq-th--8 sq-th--right">Imponibile</th>
                            <th class="sq-th sq-th--8 sq-th--right">IVA</th>
                            <th class="sq-th sq-th--8 sq-th--right">Totale</th>
                            <th class="sq-th sq-th--8 sq-th--right" scope="col">Paga</th>
                        </tr>
                    </thead>
                    <tbody id="checkout-metodi-body">
                        @foreach ($metodiJson as $mj)
                            @php $iconUrl = $mj['icon_url'] ?? null; @endphp
                            <tr class="js-metodo-row"
                                data-pct="{{ $mj['pct'] }}"
                                data-abs="{{ $mj['abs'] }}"
                                data-is-wallet="{{ ! empty($mj['is_wallet']) ? '1' : '0' }}">
                                <td class="sq-td sq-td--8 sq-fw-700">{{ $mj['nome'] }}</td>
                                <td class="js-td-trasporto sq-td sq-td--8 sq-td--right sq-nowrap">—</td>
                                <td class="js-td-servizi sq-td sq-td--8 sq-td--right sq-nowrap">—</td>
                                <td class="js-td-imponibile sq-td sq-td--8 sq-td--right sq-nowrap">—</td>
                                <td class="js-td-iva sq-td sq-td--8 sq-td--right sq-nowrap">—</td>
                                <td class="js-td-totale sq-td sq-td--8 sq-td--right sq-nowrap sq-fw-700">—</td>
                                <td class="sq-td sq-td--8 sq-td--right">
                                    @auth
                                        @if (auth()->user()->hasVerifiedEmail())
                                            <div class="sq-ordini-actions-icons">
                                            @if (! empty($mj['is_wallet']))
                                                <button
                                                    type="button"
                                                    class="sq-ordini-icon-action sq-ordini-icon-action--pay js-checkout-wallet-btn js-wallet-open-modal-checkout"
                                                    title="Paga con {{ $mj['nome'] }}"
                                                    aria-label="Paga con {{ $mj['nome'] }}"
                                                    data-metodo-id="{{ (int) $mj['id'] }}"
                                                    data-totale-label=""
                                                >
                                                    @if ($iconUrl)
                                                        <img src="{{ $iconUrl }}" alt="" class="sq-pay-metodo-action-icon">
                                                    @endif
                                                </button>
                                            @elseif (! empty($mj['is_bonifico']))
                                                <button
                                                    type="button"
                                                    class="sq-ordini-icon-action sq-ordini-icon-action--pay js-bonifico-open-modal"
                                                    title="Paga con {{ $mj['nome'] }}"
                                                    aria-label="Paga con {{ $mj['nome'] }}"
                                                    data-modal-id="sq-checkout-bonifico-modal"
                                                    data-metodo-id="{{ (int) $mj['id'] }}"
                                                    data-chiave-causale=""
                                                    data-chiave-placeholder="Generato alla conferma"
                                                >
                                                    @if ($iconUrl)
                                                        <img src="{{ $iconUrl }}" alt="" class="sq-pay-metodo-action-icon">
                                                    @endif
                                                </button>
                                            @else
                                                @php
                                                    $cartaDisabilitata = ! empty($mj['is_carta']) && ! ($stripeConfigured ?? true);
                                                @endphp
                                                <form method="POST" action="{{ route('checkout.paga') }}" class="sq-form-inline sq-ordini-pay-form-inline js-checkout-paga-form">
                                                    @csrf
                                                    <input type="hidden" name="corriere_id" value="{{ $corriereId }}">
                                                    <input type="hidden" name="servizi_json" class="js-checkout-servizi-hidden" value="[]">
                                                    <input type="hidden" name="metodo_pagamento_id" value="{{ (int) $mj['id'] }}">
                                                    <button type="submit"
                                                        class="sq-ordini-icon-action sq-ordini-icon-action--pay"
                                                        title="{{ $cartaDisabilitata ? 'Stripe non configurato' : ('Paga con '.$mj['nome']) }}"
                                                        aria-label="Paga con {{ $mj['nome'] }}"
                                                        @if ($cartaDisabilitata) disabled @endif>
                                                        @if ($iconUrl)
                                                            <img src="{{ $iconUrl }}" alt="" class="sq-pay-metodo-action-icon">
                                                        @endif
                                                    </button>
                                                </form>
                                            @endif
                                            </div>
                                        @else
                                            <span class="sq-text-muted sq-text-14">Verifica email</span>
                                        @endif
                                    @else
                                        <a href="{{ route('login') }}">Accedi</a>
                                    @endauth
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @guest
                <p class="sq-text-muted sq-text-14 sq-mb-0">Per pagare serve un account con email verificata.</p>
            @endguest

            <div class="sq-checkout-cart-banner">
                <span class="sq-checkout-cart-banner-label">Voglio solo aggiungere al carrello</span>
                <form method="POST" action="{{ route('carrello.aggiungi') }}" id="form-carrello-checkout" class="sq-form-cart-checkout">
                    @csrf
                    <input type="hidden" name="corriere_id" value="{{ $corriereId }}">
                    <input type="hidden" name="servizi_json" id="checkout-servizi-json" value="[]">
                    <button type="submit" title="Aggiungi al carrello" aria-label="Aggiungi al carrello" class="sq-checkout-cart-icon-btn">
                        <i class="fa-solid fa-cart-shopping" aria-hidden="true"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>

    @auth
        @if (auth()->user()->hasVerifiedEmail())
            <div id="sq-checkout-wallet-modal" class="sq-modal" hidden data-checkout-wallet-modal>
                <div class="sq-modal-backdrop js-checkout-wallet-modal-close" tabindex="-1" aria-hidden="true"></div>
                <div class="sq-modal-panel" role="dialog" aria-modal="true" aria-labelledby="sq-checkout-wallet-modal-title">
                    <h2 id="sq-checkout-wallet-modal-title" class="sq-modal-title">Pagamento con Wallet</h2>
                    <p class="sq-modal-text sq-m-0 sq-mb-16" id="sq-checkout-wallet-modal-body"></p>
                    <form method="POST" action="{{ route('checkout.paga') }}" class="sq-modal-actions">
                        @csrf
                        <input type="hidden" name="corriere_id" value="{{ $corriereId }}">
                        <input type="hidden" name="servizi_json" id="checkout-servizi-json-wallet" value="[]">
                        <input type="hidden" name="metodo_pagamento_id" id="sq-checkout-wallet-metodo-id" value="">
                        <input type="hidden" name="conferma_wallet" value="1">
                        <button type="button" class="sq-btn-primary sq-modal-btn js-checkout-wallet-modal-close">Annulla</button>
                        <button type="submit" class="sq-btn-primary sq-modal-btn">Conferma</button>
                    </form>
                </div>
            </div>
        @endif
    @endauth

    @if ($haBonificoCheckout)
        @include('partials.bonifico-pagamento-popup', [
            'modalId' => 'sq-checkout-bonifico-modal',
            'formId' => 'sq-checkout-bonifico-form',
            'formAction' => route('checkout.paga'),
            'iban' => $ibanBonificoCheckout,
            'chiaveCausale' => '',
            'chiavePlaceholder' => 'Generato alla conferma',
            'formExtras' => '<input type="hidden" name="corriere_id" value="'.e((string) $corriereId).'"><input type="hidden" name="servizi_json" id="checkout-servizi-json-bonifico" value="[]">',
        ])
    @endif
</div>

<script>
(() => {
    const baseTrasportoCliente = @json($trasportoIvaEsc);
    const baseTrasportoListino = @json($trasportoBaseListino ?? $trasportoIvaEsc);
    const ricaricoTariffaPct = @json($ricaricoTariffaPct ?? 0);
    const aliquotaIva = @json($aliquotaIva);
    const walletCommissionPct = @json($walletCommissionPct ?? 0);
    const usaQuoteApiServizi = @json($usaQuoteApiServizi ?? false);
    const quoteServizioUrl = @json($quoteServizioUrl ?? '');
    const corriereCheckoutId = {{ (int) ($corriereId ?? 0) }};
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const apiQuotes = new Map();
    const quoteTimers = new WeakMap();

    const formatIt = (n) => new Intl.NumberFormat('it-IT', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(n);

    const escapeHtml = (s) => String(s)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');

    const parseNum = (v) => {
        if (v === null || v === undefined) return 0;
        const s = String(v).trim();
        if (!s) return 0;
        const x = Number(s.replace(',', '.'));
        return Number.isFinite(x) ? x : 0;
    };

    const resolveBand = (bands, merce) => {
        if (!Array.isArray(bands) || bands.length === 0) return null;
        const sorted = [...bands].sort((a, b) => parseNum(a.min_fascia) - parseNum(b.min_fascia));
        for (const r of sorted) {
            const da = r.min_fascia === null || r.min_fascia === undefined || r.min_fascia === '' ? null : parseNum(r.min_fascia);
            const a = r.max_fascia === null || r.max_fascia === undefined || r.max_fascia === '' ? null : parseNum(r.max_fascia);
            if (da === null && a === null) continue;
            if (da !== null && merce < da) continue;
            if (a !== null && merce > a) continue;
            return r;
        }
        for (let i = sorted.length - 1; i >= 0; i--) {
            const r = sorted[i];
            const a = r.max_fascia === null || r.max_fascia === undefined || r.max_fascia === '' ? null : parseNum(r.max_fascia);
            if (a !== null) continue;
            const da = r.min_fascia === null || r.min_fascia === undefined || r.min_fascia === '' ? null : parseNum(r.min_fascia);
            if (da !== null && merce >= da) return r;
        }
        return sorted[sorted.length - 1];
    };

    const nettoListinoMerce = (row, merce) => {
        const pct = parseNum(row.percentuale_cor);
        const abs = parseNum(row.valore_fisso_cor);
        let importo = (merce * pct) + abs;
        const vmin = row.valore_minimo;
        const vmax = row.valore_massimo;
        if (vmin !== null && vmin !== undefined && vmin !== '') {
            importo = Math.max(importo, parseNum(vmin));
        }
        if (vmax !== null && vmax !== undefined && vmax !== '') {
            importo = Math.min(importo, parseNum(vmax));
        }
        return importo;
    };

    /**
     * (costo_corriere + valore_fisso_cor) × (1 + ricarico_k91) + valore_fisso_k91
     * costoCorriere arg = totale listino (quota % + fisso corriere).
     */
    const clienteDaCostoCorriere = (costoCorriere, rowOrCb) => {
        const rk = parseNum(rowOrCb.ricarico_k91 ?? rowOrCb.getAttribute?.('data-ricarico-k91'));
        const fissoK91 = parseNum(rowOrCb.valore_fisso_k91 ?? rowOrCb.getAttribute?.('data-fisso-k91'));
        const costoTotale = Math.max(0, costoCorriere);
        return costoTotale * (1 + rk) + fissoK91;
    };

    const nettoListinoFisso = (cb) => {
        const pct = parseNum(cb.getAttribute('data-pct'));
        const abs = parseNum(cb.getAttribute('data-abs'));
        return baseTrasportoListino * pct + abs;
    };

    const merceValoreInserito = (inp) => {
        if (!inp) return false;
        const s = String(inp.value ?? '').trim();
        if (s === '') return false;

        return parseNum(s) > 0;
    };

    const quoteCacheKey = (pivotId, merce) => `${pivotId}:${merce.toFixed(2)}`;

    const limitiFasciaInput = (inp) => {
        if (!inp) return { min: null, max: null };
        const minRaw = inp.getAttribute('data-min-fascia');
        const maxRaw = inp.getAttribute('data-max-fascia');
        const min = minRaw !== null && minRaw !== '' ? parseNum(minRaw) : null;
        const max = maxRaw !== null && maxRaw !== '' ? parseNum(maxRaw) : null;

        return { min, max };
    };

    const validaFasciaInput = (inp, merce) => {
        if (!inp) return null;
        const { min, max } = limitiFasciaInput(inp);
        if (min === null && max === null) return null;

        if (merce <= 0) {
            return 'Inserisci un importo maggiore di zero.';
        }
        if (min !== null && merce < min) {
            if (max !== null) {
                return 'Inserisci un valore compreso tra ' + formatIt(min) + ' € e ' + formatIt(max) + ' €.';
            }
            return 'Inserisci un valore di almeno ' + formatIt(min) + ' €.';
        }
        if (max !== null && merce > max) {
            if (min !== null) {
                return 'Inserisci un valore compreso tra ' + formatIt(min) + ' € e ' + formatIt(max) + ' €.';
            }
            return 'Inserisci un valore di massimo ' + formatIt(max) + ' €.';
        }
        return null;
    };

    const formatPrezzoServizioDual = (cliente, _nostro, extra = '') => {
        let text = formatIt(cliente) + ' €';
        if (extra) {
            text += ' ' + extra;
        }
        return text;
    };

    const renderServizioApiTrace = (cb, data) => {
        const el = cb.closest('.sq-checkout-servizio-li')?.querySelector('.js-servizio-api-trace');
        if (!el) return;

        const trace = data?.api_trace;
        if (!trace || !Array.isArray(trace.chiamate) || trace.chiamate.length === 0) {
            el.hidden = true;
            el.innerHTML = '';
            return;
        }

        let html = '<div class="sq-servizio-api-trace-head">API '
            + escapeHtml(trace.piattaforma || '—')
            + ' — invio e risposta</div>';

        trace.chiamate.forEach((c) => {
            const label = c.etichetta || 'Chiamata';
            html += '<details class="sq-servizio-api-trace-call" open>'
                + '<summary>' + escapeHtml(label) + '</summary>'
                + '<div class="sq-servizio-api-trace-block">'
                + '<div class="sq-servizio-api-trace-label">Inviamo · '
                + escapeHtml(String(c.metodo || 'POST'))
                + ' '
                + escapeHtml(String(c.path || ''))
                + '</div>'
                + '<pre class="sq-pre-json">'
                + escapeHtml(JSON.stringify(c.request ?? null, null, 2))
                + '</pre></div>'
                + '<div class="sq-servizio-api-trace-block">'
                + '<div class="sq-servizio-api-trace-label">Riceviamo · HTTP '
                + escapeHtml(String(c.http_status ?? '—'))
                + '</div>'
                + '<pre class="sq-pre-json">'
                + escapeHtml(JSON.stringify(c.response ?? null, null, 2))
                + '</pre></div>';

            if (c.tot_risposta !== null && c.tot_risposta !== undefined && c.tot_risposta !== '') {
                html += '<p class="sq-servizio-api-trace-meta">tot risposta: '
                    + escapeHtml(formatIt(parseNum(c.tot_risposta)))
                    + ' €</p>';
            }
            if (c.commissione_contrassegno !== null && c.commissione_contrassegno !== undefined && c.commissione_contrassegno !== '') {
                html += '<p class="sq-servizio-api-trace-meta">commissioneContrassegno: '
                    + escapeHtml(formatIt(parseNum(c.commissione_contrassegno)))
                    + ' €</p>';
            }
            if (c.commissione_assicurazione !== null && c.commissione_assicurazione !== undefined && c.commissione_assicurazione !== '') {
                html += '<p class="sq-servizio-api-trace-meta">commissioneAssicurazione: '
                    + escapeHtml(formatIt(parseNum(c.commissione_assicurazione)))
                    + ' €</p>';
            }
            if (c.errore) {
                html += '<p class="sq-servizio-api-trace-meta sq-servizio-api-trace-meta--err">'
                    + escapeHtml(String(c.errore))
                    + '</p>';
            }

            html += '</details>';
        });

        el.innerHTML = html;
        el.hidden = false;
    };

    const clearServizioApiTrace = (cb) => renderServizioApiTrace(cb, null);

    const setServizioPrezzoUi = (cb, text, isError = false) => {
        const el = cb.closest('.sq-checkout-servizio-li')?.querySelector('.js-servizio-prezzo');
        if (!el) return;
        if (!text) {
            el.hidden = true;
            el.textContent = '';
            el.classList.remove('sq-servizio-prezzo--error');
            return;
        }
        el.hidden = false;
        el.textContent = text;
        el.classList.toggle('sq-servizio-prezzo--error', isError);
    };

    const fetchQuoteApiServizio = async (cb, inp) => {
        const merce = parseNum(inp.value);
        const errFascia = validaFasciaInput(inp, merce);
        if (errFascia) {
            setServizioPrezzoUi(cb, errFascia, true);
            clearServizioApiTrace(cb);
            apiQuotes.delete(quoteCacheKey(0, merce));
            recalc();
            return;
        }

        let bands = [];
        try {
            bands = JSON.parse(cb.getAttribute('data-bands') || '[]');
        } catch (e) {
            bands = [];
        }
        const row = resolveBand(bands, merce);
        if (!row || !row.id) {
            setServizioPrezzoUi(cb, 'Fascia non valida per questo importo.', true);
            clearServizioApiTrace(cb);
            recalc();
            return;
        }

        setServizioPrezzoUi(cb, 'Quotazione in corso…', false);
        clearServizioApiTrace(cb);

        try {
            const res = await fetch(quoteServizioUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({
                    corriere_id: corriereCheckoutId,
                    pivot_id: parseNum(row.id),
                    valore_merce: merce,
                }),
            });
            const data = await res.json();
            if (!data.ok) {
                apiQuotes.delete(quoteCacheKey(row.id, merce));
                setServizioPrezzoUi(cb, data.error || 'Quotazione non disponibile.', true);
                renderServizioApiTrace(cb, data);
                recalc();
                return;
            }
            const key = quoteCacheKey(row.id, merce);
            apiQuotes.set(key, {
                pivot_id: parseNum(row.id),
                valore_merce: merce,
                costo_fornitore: parseNum(data.costo_fornitore),
                costo_cliente: parseNum(data.costo_cliente),
                fonte: data.fonte || 'api',
            });
            setServizioPrezzoUi(
                cb,
                formatPrezzoServizioDual(
                    parseNum(data.costo_cliente),
                    parseNum(data.costo_fornitore),
                    '(IVA esc., ' + (data.fonte || 'API') + ')',
                ),
                false,
            );
            renderServizioApiTrace(cb, data);
            recalc();
        } catch (e) {
            setServizioPrezzoUi(cb, 'Errore di rete durante la quotazione.', true);
            clearServizioApiTrace(cb);
            recalc();
        }
    };

    const scheduleQuoteApi = (cb, inp) => {
        const prev = quoteTimers.get(inp);
        if (prev) clearTimeout(prev);
        if (!merceValoreInserito(inp)) {
            setServizioPrezzoUi(cb, null);
            clearServizioApiTrace(cb);
            recalc();
            return;
        }
        const merce = parseNum(inp.value);
        const errFascia = validaFasciaInput(inp, merce);
        if (errFascia) {
            setServizioPrezzoUi(cb, errFascia, true);
            clearServizioApiTrace(cb);
            apiQuotes.clear();
            recalc();
            return;
        }
        quoteTimers.set(inp, setTimeout(() => fetchQuoteApiServizio(cb, inp), 450));
    };

    const dettaglioSingoloServizio = (cb) => {
        if (!cb.checked) return null;

        const mod = cb.getAttribute('data-modalita') || 'fisso';
        if (mod === 'valore_merce') {
            const inp = cb.closest('.sq-checkout-servizio-li')?.querySelector('.js-valore-merce');
            if (!merceValoreInserito(inp)) return null;

            const merce = parseNum(inp.value);
            if (validaFasciaInput(inp, merce)) return null;

            if (cb.getAttribute('data-quote-api') === '1') {
                let bands = [];
                try {
                    bands = JSON.parse(cb.getAttribute('data-bands') || '[]');
                } catch (e) {
                    bands = [];
                }
                const row = resolveBand(bands, merce);
                if (!row || !row.id) return null;
                const cached = apiQuotes.get(quoteCacheKey(row.id, merce));
                if (!cached || cached.costo_cliente <= 0) return null;
                return {
                    cliente: Math.round(cached.costo_cliente * 100) / 100,
                    nostro: Math.round(cached.costo_fornitore * 100) / 100,
                };
            }

            let bands = [];
            try {
                bands = JSON.parse(cb.getAttribute('data-bands') || '[]');
            } catch (e) {
                bands = [];
            }
            const row = resolveBand(bands, merce);
            if (!row) return null;

            const netto = nettoListinoMerce(row, merce);

            return {
                cliente: Math.round(clienteDaCostoCorriere(netto, row) * 100) / 100,
                nostro: Math.round(netto * 100) / 100,
            };
        }

        const netto = nettoListinoFisso(cb);

        return {
            cliente: Math.round(clienteDaCostoCorriere(netto, cb) * 100) / 100,
            nostro: Math.round(netto * 100) / 100,
        };
    };

    const prezzoSingoloServizio = (cb) => {
        const det = dettaglioSingoloServizio(cb);
        return det ? det.cliente : null;
    };

    const aggiornaPrezzoRigaServizio = (cb) => {
        if (!cb.checked) {
            setServizioPrezzoUi(cb, null);
            return;
        }

        const mod = cb.getAttribute('data-modalita') || 'fisso';
        if (mod === 'valore_merce') {
            const inp = cb.closest('.sq-checkout-servizio-li')?.querySelector('.js-valore-merce');
            if (!merceValoreInserito(inp)) {
                setServizioPrezzoUi(cb, null);
                return;
            }
            const errFascia = validaFasciaInput(inp, parseNum(inp.value));
            if (errFascia) {
                setServizioPrezzoUi(cb, errFascia, true);
                return;
            }
        }

        const det = dettaglioSingoloServizio(cb);
        if (det === null) {
            setServizioPrezzoUi(cb, null);
            return;
        }

        setServizioPrezzoUi(
            cb,
            formatPrezzoServizioDual(det.cliente, det.nostro, '(IVA esc.)'),
            false,
        );
    };

    const aggiornaTuttiPrezziServizi = () => {
        document.querySelectorAll('.checkout-page .js-servizio-extra').forEach(aggiornaPrezzoRigaServizio);
    };

    const extraServiziTotali = () => {
        let cliente = 0;
        let nostro = 0;
        document.querySelectorAll('.checkout-page .js-servizio-extra:checked').forEach((cb) => {
            const det = dettaglioSingoloServizio(cb);
            if (det === null) return;
            cliente += det.cliente;
            if (Number.isFinite(det.nostro)) {
                nostro += det.nostro;
            }
        });

        return {
            cliente: Math.round(cliente * 100) / 100,
            nostro: Math.round(nostro * 100) / 100,
        };
    };

    const extraServiziCliente = () => extraServiziTotali().cliente;

    const renderServiziRiepilogo = () => {
        const box = document.getElementById('checkout-servizi-riepilogo');
        const target = document.getElementById('checkout-servizi-riepilogo-items');
        if (!box || !target) return;

        const names = [];
        document.querySelectorAll('.checkout-page .js-servizio-extra:checked').forEach((cb) => {
            const name = String(cb.getAttribute('data-servizio-nome') || '').trim();
            if (name === '') return;
            const prezzo = prezzoSingoloServizio(cb);
            const inp = cb.closest('.sq-checkout-servizio-li')?.querySelector('.js-valore-merce');
            const merce = inp && merceValoreInserito(inp) ? parseNum(inp.value) : null;
            let label = name;
            if (merce !== null) {
                label += ' (' + formatIt(merce) + ' €)';
            }
            if (prezzo !== null) {
                label += ' — ' + formatIt(prezzo) + ' €';
            }
            names.push(label);
        });

        if (names.length === 0) {
            target.textContent = '';
            box.hidden = true;
            return;
        }

        target.textContent = names.join(' · ');
        box.hidden = false;
    };

    const round2 = (n) => Math.round(n * 100) / 100;

    const recalc = () => {
        aggiornaTuttiPrezziServizi();
        const extra = extraServiziTotali();

        document.querySelectorAll('.checkout-page .js-metodo-row').forEach((row) => {
            const isWallet = row.getAttribute('data-is-wallet') === '1';
            const pct = parseNum(row.getAttribute('data-pct'));
            const abs = parseNum(row.getAttribute('data-abs'));

            let trasportoCliente;
            let imponibile;

            if (isWallet) {
                trasportoCliente = round2(baseTrasportoCliente * (1 + walletCommissionPct / 100));
                imponibile = round2(trasportoCliente + extra.cliente);
            } else {
                trasportoCliente = baseTrasportoCliente;
                imponibile = round2((baseTrasportoCliente + extra.cliente) * (1 + pct / 100) + abs);
            }

            const iva = round2(imponibile * (aliquotaIva / 100));
            const totale = round2(imponibile + iva);

            const tTr = row.querySelector('.js-td-trasporto');
            const tSe = row.querySelector('.js-td-servizi');
            const tIm = row.querySelector('.js-td-imponibile');
            const tIv = row.querySelector('.js-td-iva');
            const tTo = row.querySelector('.js-td-totale');
            if (tTr) {
                tTr.textContent = formatIt(trasportoCliente) + ' €';
            }
            if (tSe) {
                tSe.textContent = formatIt(extra.cliente > 0 ? extra.cliente : 0) + ' €';
            }
            if (tIm) tIm.textContent = formatIt(imponibile) + ' €';
            if (tIv) tIv.textContent = formatIt(iva) + ' €';
            if (tTo) tTo.textContent = formatIt(totale) + ' €';

            const wBtn = row.querySelector('.js-checkout-wallet-btn');
            if (wBtn && tTo) {
                wBtn.setAttribute('data-totale-label', formatIt(totale));
            }
        });
    };

    document.querySelectorAll('.checkout-page .js-servizio-extra').forEach((cb) => {
        cb.addEventListener('change', () => {
            const li = cb.closest('.sq-checkout-servizio-li');
            const wrap = li?.querySelector('.js-merce-wrap');
            if (wrap) {
                wrap.hidden = !cb.checked;
                if (!cb.checked) {
                    const inp = wrap.querySelector('.js-valore-merce');
                    if (inp) {
                        inp.value = '';
                        apiQuotes.clear();
                    }
                    setServizioPrezzoUi(cb, null);
                    clearServizioApiTrace(cb);
                }
            }
            renderServiziRiepilogo();
            recalc();
        });
    });
    document.querySelectorAll('.checkout-page .js-valore-merce').forEach((inp) => {
        inp.addEventListener('input', () => {
            const cb = inp.closest('.sq-checkout-servizio-li')?.querySelector('.js-servizio-extra');
            if (!cb || !cb.checked) return;

            if (cb.getAttribute('data-quote-api') === '1') {
                scheduleQuoteApi(cb, inp);
            } else {
                aggiornaPrezzoRigaServizio(cb);
            }
            renderServiziRiepilogo();
            recalc();
        });
    });

    const buildServiziSelezioneJson = () => {
        const sel = [];
        document.querySelectorAll('.checkout-page .js-servizio-extra:checked').forEach((cb) => {
            const mod = cb.getAttribute('data-modalita') || 'fisso';
            if (mod === 'valore_merce') {
                const inp = cb.closest('.sq-checkout-servizio-li')?.querySelector('.js-valore-merce');
                if (!merceValoreInserito(inp)) {
                    return;
                }
                let bands = [];
                try {
                    bands = JSON.parse(cb.getAttribute('data-bands') || '[]');
                } catch (e) {
                    bands = [];
                }
                const merce = parseNum(inp.value);
                if (validaFasciaInput(inp, merce)) {
                    return;
                }
                const row = resolveBand(bands, merce);
                const testo = String(cb.getAttribute('data-testo-servizio') || '').trim();
                const entry = { valore_merce: merce };
                if (row && row.id) {
                    entry.id = parseNum(row.id);
                    if (cb.getAttribute('data-quote-api') === '1') {
                        const cached = apiQuotes.get(quoteCacheKey(row.id, merce));
                        if (cached) {
                            entry.costo_fornitore = cached.costo_fornitore;
                            entry.costo_cliente = cached.costo_cliente;
                            entry.quote_api = true;
                            entry.fonte = cached.fonte;
                        }
                    }
                    sel.push(entry);
                } else if (testo !== '') {
                    sel.push({ testo_servizio: testo, valore_merce: merce });
                }
            } else {
                sel.push({
                    id: parseNum(cb.getAttribute('data-pid')) || null,
                    valore_merce: null,
                });
            }
        });
        return JSON.stringify(sel);
    };

    const formCarrello = document.getElementById('form-carrello-checkout');
    const serviziJsonInput = document.getElementById('checkout-servizi-json');
    if (formCarrello && serviziJsonInput) {
        formCarrello.addEventListener('submit', () => {
            serviziJsonInput.value = buildServiziSelezioneJson();
        });
    }

    document.querySelectorAll('.js-checkout-paga-form').forEach((form) => {
        form.addEventListener('submit', () => {
            const inp = form.querySelector('.js-checkout-servizi-hidden');
            if (inp) inp.value = buildServiziSelezioneJson();
        });
    });

    const checkoutWalletModal = document.querySelector('[data-checkout-wallet-modal]');
    const checkoutWalletBody = document.getElementById('sq-checkout-wallet-modal-body');
    const checkoutWalletMetodoInput = document.getElementById('sq-checkout-wallet-metodo-id');
    const checkoutWalletServiziInput = document.getElementById('checkout-servizi-json-wallet');

    const closeCheckoutWalletModal = () => {
        if (!checkoutWalletModal) return;
        checkoutWalletModal.hidden = true;
        checkoutWalletModal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('sq-modal-open');
    };

    const openCheckoutWalletModal = (btn) => {
        if (!checkoutWalletModal || !checkoutWalletBody || !checkoutWalletMetodoInput) return;
        const id = btn.getAttribute('data-metodo-id');
        const totale = btn.getAttribute('data-totale-label') || '—';
        checkoutWalletMetodoInput.value = id || '';
        checkoutWalletBody.textContent =
            'Stai per pagare questa spedizione per un totale di ' + totale + ' € (IVA inclusa). Verrà creato l’ordine e addebitato il Wallet.';
        if (checkoutWalletServiziInput) checkoutWalletServiziInput.value = buildServiziSelezioneJson();
        checkoutWalletModal.hidden = false;
        checkoutWalletModal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('sq-modal-open');
    };

    document.querySelectorAll('.js-wallet-open-modal-checkout').forEach((btn) => {
        btn.addEventListener('click', () => openCheckoutWalletModal(btn));
    });
    if (checkoutWalletModal) {
        checkoutWalletModal.querySelectorAll('.js-checkout-wallet-modal-close').forEach((el) => {
            el.addEventListener('click', () => closeCheckoutWalletModal());
        });
    }
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && checkoutWalletModal && !checkoutWalletModal.hidden) closeCheckoutWalletModal();
    });

    const checkoutBonificoForm = document.getElementById('sq-checkout-bonifico-form');
    const checkoutBonificoServiziInput = document.getElementById('checkout-servizi-json-bonifico');
    if (checkoutBonificoForm && checkoutBonificoServiziInput) {
        checkoutBonificoForm.addEventListener('submit', () => {
            checkoutBonificoServiziInput.value = buildServiziSelezioneJson();
        });
    }

    const syncPuntoToForms = () => {
        const json = document.getElementById('checkout-punto-json');
        if (!json) return;
        const val = json.value || '';
        document.querySelectorAll('.checkout-page form').forEach((form) => {
            let inp = form.querySelector('input[name="punto_consegna_json"]');
            if (!inp) {
                inp = document.createElement('input');
                inp.type = 'hidden';
                inp.name = 'punto_consegna_json';
                form.appendChild(inp);
            }
            inp.value = val;
        });
    };

    const requirePunto = document.getElementById('checkout-punto-section');
    const validatePuntoBeforeSubmit = (e) => {
        if (!requirePunto) return;
        const json = document.getElementById('checkout-punto-json');
        if (!json || String(json.value || '').trim() === '') {
            e.preventDefault();
            const msg = requirePunto.dataset.puntoConsegnaLabel || 'Seleziona un punto di consegna';
            alert(msg + (String(msg).endsWith('.') ? '' : '.') + ' Confermalo prima di procedere.');
            requirePunto.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    };

    const validateServiziBeforeSubmit = (e) => {
        let errFasciaMsg = null;
        let invalidApi = false;

        document.querySelectorAll('.checkout-page .js-servizio-extra:checked').forEach((cb) => {
            const mod = cb.getAttribute('data-modalita') || 'fisso';
            if (mod !== 'valore_merce') return;

            const inp = cb.closest('.sq-checkout-servizio-li')?.querySelector('.js-valore-merce');
            if (!merceValoreInserito(inp)) {
                errFasciaMsg = errFasciaMsg || 'Inserisci un importo per ogni servizio selezionato.';
                return;
            }
            const merce = parseNum(inp.value);
            const err = validaFasciaInput(inp, merce);
            if (err) {
                errFasciaMsg = errFasciaMsg || err;
                return;
            }
            if (cb.getAttribute('data-quote-api') === '1' && prezzoSingoloServizio(cb) === null) {
                invalidApi = true;
            }
        });

        if (errFasciaMsg) {
            e.preventDefault();
            alert(errFasciaMsg);
            return;
        }
        if (invalidApi) {
            e.preventDefault();
            alert('Attendi la quotazione API per ogni servizio selezionato prima di procedere.');
        }
    };

    document.querySelectorAll('.checkout-page form').forEach((form) => {
        form.addEventListener('submit', (e) => {
            syncPuntoToForms();
            validateServiziBeforeSubmit(e);
            if (e.defaultPrevented) return;
            validatePuntoBeforeSubmit(e);
        });
    });

    renderServiziRiepilogo();
    recalc();
})();
</script>
@if ($consegnaPunto ?? false)
<script>
(() => {
    const jsonInput = document.getElementById('checkout-punto-json');
    const confirmBtn = document.getElementById('checkout-dest-confirm-select');
    const dataEl = document.getElementById('checkout-dest-points-data');
    const mapEl = document.getElementById('checkout-dest-points-map');

    const formatPuntoAddr = (point) => {
        const line = point.address_line && point.address_line !== '—' ? point.address_line : '';
        if (line) return line;
        return [point.street, point.house_number].filter((v) => v && v !== '—').join(' ');
    };

    const updateDestinatarioRiepilogo = (point) => {
        const el = document.getElementById('checkout-destinatario-riepilogo');
        if (!el || !point) return;
        const nome = el.dataset.destNome || '—';
        const prov = el.dataset.destProv || '';
        const addr = formatPuntoAddr(point);
        const cap = point.postal_code && point.postal_code !== '—' ? point.postal_code : '';
        const city = point.city && point.city !== '—' ? point.city : '';
        const geo = [cap, city].filter(Boolean).join(' ') + (prov ? ` (${prov})` : '');
        el.textContent = `${nome} - ${addr || '—'} ${geo}`.replace(/\s+/g, ' ').trim();
    };

    const applyPoint = (point) => {
        if (!point || !jsonInput) return;
        const payload = {
            id: point.id,
            name: point.name || '',
            to_post_number: point.to_post_number || '',
            street: point.street && point.street !== '—' ? point.street : (point.address_line || ''),
            house_number: point.house_number && point.house_number !== '—' ? point.house_number : '',
            address_line: point.address_line || '',
            postal_code: point.postal_code && point.postal_code !== '—' ? point.postal_code : '',
            city: point.city && point.city !== '—' ? point.city : '',
        };
        jsonInput.value = JSON.stringify(payload);
        updateDestinatarioRiepilogo(point);
        document.querySelectorAll('.checkout-page form').forEach((form) => {
            let inp = form.querySelector('input[name="punto_consegna_json"]');
            if (!inp) {
                inp = document.createElement('input');
                inp.type = 'hidden';
                inp.name = 'punto_consegna_json';
                form.appendChild(inp);
            }
            inp.value = jsonInput.value;
        });
    };

    if (jsonInput?.dataset.initial) {
        try {
            const initial = JSON.parse(jsonInput.dataset.initial);
            jsonInput.value = JSON.stringify(initial);
            applyPoint(initial);
        } catch (_) { /* ignore */ }
    }

    if (!dataEl || !mapEl || typeof L === 'undefined') return;

    let points;
    try { points = JSON.parse(dataEl.textContent || '[]'); } catch { return; }

    const list = document.getElementById('checkout-dest-points-list');
    let activeIndex = null;
    const markers = new Map();

    const map = L.map(mapEl, { scrollWheelZoom: true });
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap',
    }).addTo(map);

    const focusPoint = (index) => {
        const point = points[index];
        if (!point) return;
        activeIndex = index;
        list?.querySelectorAll('.sq-sc-dest-item').forEach((btn) => {
            btn.classList.toggle('is-selected', Number(btn.dataset.index) === index);
        });
        const marker = markers.get(index);
        if (marker) {
            map.setView(marker.getLatLng(), Math.max(map.getZoom(), 15));
            marker.openPopup();
        }
        if (confirmBtn) confirmBtn.disabled = false;
    };

    const bounds = [];
    points.forEach((point, index) => {
        if (point.latitude == null || point.longitude == null) return;
        const lat = point.latitude;
        const lng = point.longitude;
        bounds.push([lat, lng]);
        const marker = L.marker([lat, lng]).addTo(map);
        marker.bindPopup(`<strong>${point.name}</strong><br>${point.address_line}`);
        markers.set(index, marker);
        marker.on('click', () => focusPoint(index));
    });
    if (bounds.length) map.fitBounds(bounds, { padding: [24, 24] });

    list?.querySelectorAll('.sq-sc-dest-item').forEach((btn) => {
        btn.addEventListener('click', () => focusPoint(Number(btn.dataset.index)));
    });
    confirmBtn?.addEventListener('click', () => {
        if (activeIndex === null) return;
        applyPoint(points[activeIndex]);
    });

    if (points.length > 0) focusPoint(0);

    if (jsonInput?.value) {
        try {
            const saved = JSON.parse(jsonInput.value);
            const idx = points.findIndex((p) => String(p.id) === String(saved.id));
            if (idx >= 0) focusPoint(idx);
        } catch (_) { /* ignore */ }
    }

    setTimeout(() => map.invalidateSize(), 300);
})();
</script>
@endif
@endsection
