@php
    $fmt = fn ($n) => number_format((float) $n, 2, ',', '.');
    $tabellaUnificata = ! empty($tabellaUnificata);
    $listingLayout = ! empty($listingLayout);
    $mostraStato = ($tabellaUnificata || ! empty($mostraStato)) && ! $listingLayout;
    $mostraColonneExtra = ! $listingLayout;
    $azioniNonPagateGlobal = ! empty($azioniNonPagate);
    $routeEtichetta = $routeEtichetta ?? 'spedizioni.etichetta';
    $routeTracking = $routeTracking ?? 'spedizioni.tracking';
    $mostraTracking = $tabellaUnificata || ! $azioniNonPagateGlobal;
    $tableClass = $listingLayout ? 'sq-listing-table' : 'sq-table sq-sped-clienti-table';
    $wrapClass = $listingLayout ? 'sq-listing-table-scroll' : 'sq-table-wrap sq-table-wrap--warm sq-sped-table-wrap';
    $tdClass = $listingLayout ? '' : 'sq-td sq-td--border-warm';
    $statoOrdineUi = static function (?string $stato): string {
        return match ($stato) {
            \App\Models\ordine::STATO_PAGATO => 'pagato',
            \App\Models\ordine::STATO_ANNULLATO => 'cancellato',
            default => 'non_pagato',
        };
    };
@endphp
<div class="{{ $wrapClass }}">
    <table class="{{ $tableClass }}">
        <thead>
            <tr @unless($listingLayout) class="sq-thead-row sq-thead-row--warm" @endunless>
                <th @unless($listingLayout) class="sq-th sq-th--warm sq-th--codice" @endunless>Codice</th>
                @if ($listingLayout)
                    <th>Destinatario</th>
                    <th class="sq-nowrap">CAP Da–A</th>
                    <th>N. ordine</th>
                @else
                    <th class="sq-th sq-th--warm">N. ordine</th>
                    @if ($mostraStato)
                        <th class="sq-th sq-th--warm">Stato</th>
                    @endif
                    <th class="sq-th sq-th--warm">Destinatario</th>
                    <th class="sq-th sq-th--warm sq-nowrap">Da-A</th>
                @endif
                <th @unless($listingLayout) class="sq-th sq-th--warm sq-th--right" @else class="valor-cell" @endunless>Importo</th>
                @if ($mostraColonneExtra)
                    <th class="sq-th sq-th--warm">Payment Intent</th>
                    <th class="sq-th sq-th--warm">Servizi aggiuntivi</th>
                @endif
                @if ($mostraTracking)
                    <th @unless($listingLayout) class="sq-th sq-th--warm" @endunless>Tracking</th>
                @endif
                <th class="@if($listingLayout) th-acoes @else sq-th sq-th--warm sq-th--right sq-th--actions @endif">Azioni</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($spedizioni as $s)
                @php
                    $mitt = \App\Support\SpedizioneCampiPersistenza::mittenteArray($s);
                    $dest = \App\Support\SpedizioneCampiPersistenza::destinatarioArray($s);
                    $nomeCognome = \App\Support\SpedizioneClienteDati::nomeECognomeDestinatario($dest);
                    $capDa = \App\Support\SpedizioneClienteDati::cap($mitt);
                    $capA = \App\Support\SpedizioneClienteDati::cap($dest);
                    $daA = ($capDa !== '' ? $capDa : '—').' - '.($capA !== '' ? $capA : '—');
                    $labels = [];
                    foreach ($s->serviziAggiuntiviRighe as $riga) {
                        $lbl = \App\Support\ServizioAggiuntivoEtichetta::perRiga($riga);
                        if ($lbl !== '') {
                            $labels[] = $lbl;
                        }
                    }
                    $track = trim((string) ($s->tracking ?? ''));
                    $importoRiga = $s->prezzoNettoCliente();
                    $codiceInterno = $s->codice_interno ?? '';
                    $statoSpedId = (int) $s->spedizione_stato_id;
                    $spedizioneAnnullata = $statoSpedId === \App\Models\stato_spedizione::ANNULLATA;
                    $etichettaCancellata = \App\Support\SpedisciOnlineIntegrazione::etichettaCancellata($s);
                    $etichettaStampabile = \App\Support\SpedisciOnlineIntegrazione::etichettaStampabile($s);
                    $stripeClass = $loop->odd ? 'sq-sped-row--stripe-white' : 'sq-sped-row--stripe-grey';
                    $ordineDettaglioPopup = $s->ordine ? \App\Support\WalletMovimentiOrdinePopupPayload::fromOrdine($s->ordine) : null;
                    $azioniNonPagate = $tabellaUnificata
                        ? ($s->ordine && $s->ordine->stato === \App\Models\ordine::STATO_NON_PAGATO)
                        : $azioniNonPagateGlobal;
                @endphp
                <tr class="{{ $listingLayout ? '' : $stripeClass }}{{ $spedizioneAnnullata ? ' sq-sped-row--annullata' : '' }}">
                    <td class="{{ $tdClass }} sq-nowrap codice-cell">
                        {{ e($codiceInterno) }}
                        @if ($s->reso)
                            <span class="sq-badge sq-badge--warn" style="margin-left:6px;">Reso</span>
                        @endif
                    </td>
                    @if ($listingLayout)
                        <td class="{{ $tdClass }}">
                            <span class="sq-sped-dest-nome-only">{{ $nomeCognome !== '' ? e($nomeCognome) : '—' }}</span>
                        </td>
                        <td class="{{ $tdClass }} sq-text-14 sq-nowrap">{{ e($daA) }}</td>
                        <td class="{{ $tdClass }}">
                            @if ($s->ordine)
                                <span class="sq-fw-700">{{ $s->ordine->id }}</span>
                            @else
                                <span class="sq-text-muted">—</span>
                            @endif
                        </td>
                    @else
                        <td class="{{ $tdClass }}">
                            @if ($s->ordine)
                                <span class="sq-fw-700">{{ $s->ordine->id }}</span>
                            @else
                                <span class="sq-text-muted">—</span>
                            @endif
                        </td>
                        @if ($mostraStato)
                            <td class="{{ $tdClass }}">
                                @include('partials.stato-tabella-badge', ['stato' => $statoOrdineUi($s->ordine?->stato)])
                            </td>
                        @endif
                        <td class="{{ $tdClass }}">
                            <span class="sq-sped-dest-nome-only">{{ $nomeCognome !== '' ? e($nomeCognome) : '—' }}</span>
                        </td>
                        <td class="{{ $tdClass }} sq-text-14 sq-nowrap">{{ e($daA) }}</td>
                    @endif
                    <td class="{{ $tdClass }} sq-td--right sq-nowrap valor-cell">{{ $importoRiga !== null ? \App\Support\ImportoEuro::format($importoRiga) : '—' }}</td>
                    @if ($mostraColonneExtra)
                        <td class="{{ $tdClass }} sq-text-14 sq-nowrap">
                            @if (filled($s->stripe_payment_intent_id))
                                <code class="sq-code" title="{{ e($s->stripe_payment_intent_id) }}">{{ \Illuminate\Support\Str::limit($s->stripe_payment_intent_id, 22) }}</code>
                            @else
                                <span class="sq-text-muted">—</span>
                            @endif
                        </td>
                        <td class="{{ $tdClass }} sq-text-14">
                            @if (count($labels) > 0)
                                {{ implode(', ', array_map('e', $labels)) }}
                            @else
                                <span class="sq-text-muted">—</span>
                            @endif
                        </td>
                    @endif
                    @if ($mostraTracking)
                        <td class="{{ $tdClass }} sq-text-14 sq-sped-track-cell">
                            @if ($spedizioneAnnullata)
                                <span class="sq-sped-annullata-tracking">Annullata</span>
                            @elseif ($track !== '')
                                <span class="sq-sped-track-txt" title="{{ e($track) }}">{{ e(\Illuminate\Support\Str::limit($track, 48)) }}</span>
                            @else
                                <span class="sq-text-muted">—</span>
                            @endif
                        </td>
                    @endif
                    <td class="{{ $tdClass }} td-acoes">
                        <div class="{{ $listingLayout ? 'acoes' : 'sq-ordini-actions-icons' }}">
                            @if ($azioniNonPagate)
                                @if ($s->ordine)
                                    <button
                                        type="button"
                                        class="{{ $listingLayout ? 'sq-btn-icone' : 'sq-ordini-icon-action sq-ordini-icon-action--view' }} js-spedizioni-ordine-detail-btn"
                                        title="Dettaglio ordine e spedizioni"
                                        aria-label="Dettaglio ordine e spedizioni"
                                        data-ordine-dettaglio='@json($ordineDettaglioPopup)'
                                        data-spedizione-id="{{ (int) $s->id }}"
                                        data-show-tracking-right="0"
                                    >
                                        <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                                        <span class="sq-sr-only">Dettaglio ordine</span>
                                    </button>
                                @else
                                    <span class="sq-ordini-icon-action is-disabled" title="Dettaglio non disponibile">
                                        <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                                        <span class="sq-sr-only">Dettaglio non disponibile</span>
                                    </span>
                                @endif
                            @else
                                @if (! $spedizioneAnnullata && $s->ordine)
                                    <button
                                        type="button"
                                        class="{{ $listingLayout ? 'sq-btn-icone' : 'sq-ordini-icon-action sq-ordini-icon-action--view' }} js-spedizioni-ordine-detail-btn"
                                        title="Dettaglio ordine e spedizioni"
                                        aria-label="Dettaglio ordine e spedizioni"
                                        data-ordine-dettaglio='@json($ordineDettaglioPopup)'
                                        data-spedizione-id="{{ (int) $s->id }}"
                                        data-show-tracking-right="1"
                                    >
                                        <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                                        <span class="sq-sr-only">Dettaglio ordine</span>
                                    </button>
                                @endif
                                @if ($etichettaStampabile)
                                    <a
                                        href="{{ route($routeEtichetta, $s) }}"
                                        class="{{ $listingLayout ? 'sq-btn-icone' : 'sq-ordini-icon-action sq-ordini-icon-action--view' }}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        title="Apri etichetta PDF"
                                    >
                                        <i class="fa-solid fa-print" aria-hidden="true"></i>
                                        <span class="sq-sr-only">Apri etichetta</span>
                                    </a>
                                @else
                                    <span class="sq-ordini-icon-action is-disabled" title="{{ $etichettaCancellata ? 'Etichetta cancellata' : 'Etichetta non disponibile' }}">
                                        <i class="fa-solid fa-print" aria-hidden="true"></i>
                                        <span class="sq-sr-only">{{ $etichettaCancellata ? 'Etichetta cancellata' : 'Etichetta non disponibile' }}</span>
                                    </span>
                                @endif
                                @include('partials.spedizione-tracking-icon', [
                                    'spedizione' => $s,
                                    'trackingRoute' => $routeTracking,
                                    'btnClass' => ($listingLayout ? 'sq-btn-icone' : 'sq-ordini-icon-action sq-ordini-icon-action--view'),
                                ])
                            @endif
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@include('partials.spedizione-tracking-popup')
