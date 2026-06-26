@php
    use App\Support\EtichettaSpedizioneAccess;
    use App\Support\EtichetteListing;
    use App\Support\FiltriTabella;
    use App\Support\ServizioAggiuntivoEtichetta;
    use App\Support\SpedizioneEtichettaStato;

    $sortColumn = $sortColumn ?? 'ordine';
    $sortDir = $sortDir ?? FiltriTabella::SORT_DIR_DESC;
    $ordineSortParams = FiltriTabella::parametriOrdinamentoToggle(
        $queryParams ?? [],
        'ordine',
        $sortColumn,
        $sortDir,
    );
@endphp
<div class="sq-listing-table-scroll">
    <table class="sq-listing-table sq-etichette-table">
        <thead>
            <tr>
                <th class="sq-th-sortable">
                    <a href="{{ route('etichette.index', $ordineSortParams) }}"
                       class="sq-th-sort-link @if($sortColumn === 'ordine') is-active is-{{ $sortDir }} @endif"
                       title="Ordina per numero ordine">
                        <span class="sq-th-sort-label">Ordine</span>
                        @if($sortColumn === 'ordine' && $sortDir === FiltriTabella::SORT_DIR_DESC)
                            <i class="fa-solid fa-sort-down sq-th-sort-icon" aria-hidden="true"></i>
                        @elseif($sortColumn === 'ordine' && $sortDir === FiltriTabella::SORT_DIR_ASC)
                            <i class="fa-solid fa-sort-up sq-th-sort-icon" aria-hidden="true"></i>
                        @else
                            <i class="fa-solid fa-arrows-up-down sq-th-sort-icon" aria-hidden="true"></i>
                        @endif
                        <span class="sq-sr-only">
                            @if($sortColumn === 'ordine' && $sortDir === FiltriTabella::SORT_DIR_DESC)
                                ordinato decrescente
                            @elseif($sortColumn === 'ordine' && $sortDir === FiltriTabella::SORT_DIR_ASC)
                                ordinato crescente
                            @else
                                clicca per ordinare
                            @endif
                        </span>
                    </a>
                </th>
                <th>LdV/Codice</th>
                <th class="sq-nowrap">Ritiro</th>
                <th>Corriere</th>
                <th>Servizi Agg.</th>
                <th>Destinatario</th>
                <th>Status</th>
                <th class="th-acoes">Azioni</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($spedizioni as $s)
                @php
                    $ord = $s->ordine;
                    $dettaglio = EtichetteListing::dettaglioPayload($s);
                    $servico = EtichetteListing::nomeServizio($s);
                    $dataPagamento = $ord?->data_pagamento?->format('d/m/Y H:i');
                    $dataRitiro = $s->data_ritiro?->format('d/m/Y');
                    $tracking = trim((string) ($s->tracking ?? ''));
                    $codiceInterno = trim((string) ($s->codice_interno ?? ''));
                    $statoLabel = (string) ($s->spedizioneStato?->denominazione_stato ?? '—');
                    $ldvCancellata = EtichettaSpedizioneAccess::etichettaCancellata($s);
                    $ldvStampabile = (bool) ($dettaglio['etichetta_disponibile'] ?? false);
                    $pendente = (bool) ($dettaglio['etichetta_pendente'] ?? false);
                    $compensata = (bool) $s->compensata;
                    $serviziLabels = [];
                    foreach ($s->serviziAggiuntiviRighe as $rigaServizio) {
                        $lbl = ServizioAggiuntivoEtichetta::abbrevEImportoEuro($rigaServizio);
                        if ($lbl !== '') {
                            $serviziLabels[] = $lbl;
                        }
                    }
                @endphp
                <tr @class(['sq-etichette-row--compensata' => $compensata])>
                    <td data-label="Ordine">{{ $ord?->id ?? '—' }}</td>
                    <td class="sq-etichette-ldv-codice-cell" data-label="LdV/Codice">
                        <div class="sq-etichette-ldv-stack">
                            <span class="sq-etichette-ldv-tracking">{{ $tracking !== '' ? $tracking : '—' }}</span>
                            @if ($codiceInterno !== '')
                                <span class="sq-etichette-ldv-codice">{{ $codiceInterno }}</span>
                            @endif
                            @if ($dataPagamento)
                                <span class="sq-etichette-ldv-pagamento">{{ $dataPagamento }}</span>
                            @endif
                        </div>
                    </td>
                    <td class="sq-nowrap" data-label="Ritiro">{{ $dataRitiro ?? '—' }}</td>
                    <td data-label="Corriere">{{ $servico !== '' ? $servico : '—' }}</td>
                    <td class="sq-etichette-servizi-cell" data-label="Servizi agg.">
                        @if ($serviziLabels !== [])
                            <span class="sq-text-14">{!! implode('<br>', array_map('e', $serviziLabels)) !!}</span>
                        @else
                            <span class="sq-text-muted">—</span>
                        @endif
                    </td>
                    <td class="sq-etichette-dest-cell sq-ordine-remessa-person" data-label="Destinatario">
                        @include('ordini.partials.spedizione-destinatario-tabella', ['spedizione' => $s])
                    </td>
                    <td data-label="Status">
                        <span class="sq-etichetta-stato">{{ $statoLabel }}</span>
                    </td>
                    <td class="td-acoes" data-label="Azioni">
                        <div class="acoes sq-etichette-acoes">
                            <button
                                type="button"
                                class="sq-btn-icone js-etichetta-dettaglio-open"
                                title="Dettagli spedizione"
                                aria-label="Dettagli spedizione {{ $s->codice_interno }}"
                                data-dettaglio-url="{{ $dettaglio['dettaglio_url'] }}"
                            >
                                <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                            </button>
                            @include('partials.spedizione-tracking-icon', [
                                'spedizione' => $s,
                                'trackingRoute' => 'spedizioni.tracking',
                                'btnClass' => 'sq-btn-icone',
                                'iconClass' => 'fa-solid fa-route',
                            ])
                            @if ($ldvStampabile && ! empty($dettaglio['etichetta_url']))
                                <a
                                    href="{{ $dettaglio['etichetta_url'] }}"
                                    class="sq-btn-icone sq-btn-icone--print"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    title="Apri etichetta PDF"
                                    aria-label="Apri etichetta {{ $s->codice_interno }}"
                                >
                                    <i class="fa-solid fa-print" aria-hidden="true"></i>
                                </a>
                            @else
                                <span
                                    class="sq-btn-icone is-disabled"
                                    title="{{ $compensata ? 'Etichetta sostituita' : ($ldvCancellata ? 'Etichetta cancellata' : 'Etichetta non disponibile') }}"
                                >
                                    <i class="fa-solid fa-print" aria-hidden="true"></i>
                                </span>
                            @endif
                            @if ($pendente && ! $compensata && ! empty($dettaglio['retry_url']))
                                <form method="POST" action="{{ $dettaglio['retry_url'] }}" class="sq-etichette-retry-form">
                                    @csrf
                                    <button
                                        type="submit"
                                        class="sq-btn-icone sq-btn-icone--retry"
                                        title="Rigenera etichetta"
                                        aria-label="Rigenera etichetta {{ $s->codice_interno }}"
                                    >
                                        <i class="fa-solid fa-rotate-right" aria-hidden="true"></i>
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
@include('partials.spedizione-tracking-popup')
