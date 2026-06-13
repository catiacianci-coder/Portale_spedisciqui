@php
    use App\Support\EtichettaSpedizioneAccess;
    use App\Support\EtichetteListing;
    use App\Support\SpedizioneEtichettaStato;
@endphp
<div class="sq-listing-table-scroll">
    <table class="sq-listing-table sq-etichette-table">
        <thead>
            <tr>
                <th>Codice</th>
                <th>Ordine</th>
                <th class="sq-nowrap">Data pagamento</th>
                <th>Destinatario</th>
                <th>Servizio</th>
                <th>Status</th>
                <th>Etichetta</th>
                <th class="th-acoes">Azioni</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($spedizioni as $s)
                @php
                    $ord = $s->ordine;
                    $dettaglio = EtichetteListing::dettaglioPayload($s);
                    $destNome = EtichetteListing::destinatarioTabella($s);
                    $destAddrRighe = EtichetteListing::destinatarioIndirizzoRigheTabella($s);
                    $servico = EtichetteListing::nomeServizio($s);
                    $dataPagamento = $ord?->data_pagamento?->format('d/m/Y H:i');
                    $tracking = trim((string) ($s->tracking ?? ''));
                    $statoLabel = (string) ($s->spedizioneStato?->denominazione_stato ?? '—');
                    $ldvCancellata = EtichettaSpedizioneAccess::etichettaCancellata($s);
                    $ldvStampabile = (bool) ($dettaglio['etichetta_disponibile'] ?? false);
                    $pendente = (bool) ($dettaglio['etichetta_pendente'] ?? false);
                    $podeCorrigir = (bool) ($dettaglio['pode_corrigir'] ?? false);
                    $motivoCorrecao = (string) ($dettaglio['motivo_correcao'] ?? '');
                    $compensata = (bool) $s->compensata;
                @endphp
                <tr @class(['sq-etichette-row--compensata' => $compensata])>
                    <td class="codice-cell">{{ $s->codice_interno ?: '—' }}</td>
                    <td><strong>{{ $ord?->codice ?? '—' }}</strong></td>
                    <td class="sq-nowrap">{{ $dataPagamento ?? '—' }}</td>
                    <td class="sq-etichette-dest-cell">
                        <span class="sq-etichette-dest-nome">{{ $destNome }}</span>
                        @foreach ($destAddrRighe as $destRiga)
                            <span class="sq-etichette-dest-indirizzo">{{ $destRiga }}</span>
                        @endforeach
                    </td>
                    <td>{{ $servico !== '' ? $servico : '—' }}</td>
                    <td>
                        <span class="sq-etichetta-stato">{{ $statoLabel }}</span>
                    </td>
                    <td class="sq-etichette-tracking-cell">
                        @if ($tracking !== '')
                            {{ $tracking }}
                        @else
                            <span class="sq-text-muted">—</span>
                        @endif
                        @if ($ldvCancellata)
                            <span class="sq-badge sq-badge--muted sq-etichette-badge-cancellata">Cancellata</span>
                        @endif
                    </td>
                    <td class="td-acoes">
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
                            @if ($podeCorrigir && ! empty($dettaglio['correcao_url']))
                                <button
                                    type="button"
                                    class="sq-btn-icone sq-btn-icone--correcao js-etichetta-correcao-open"
                                    title="Correggi dati etichetta"
                                    aria-label="Correggi etichetta {{ $s->codice_interno }}"
                                    data-correcao-url="{{ $dettaglio['correcao_url'] }}"
                                    data-codice="{{ $s->codice_interno }}"
                                >
                                    <i class="fa-solid fa-pen-to-square" aria-hidden="true"></i>
                                </button>
                            @else
                                <span
                                    class="sq-btn-icone is-disabled"
                                    title="{{ $motivoCorrecao }}"
                                >
                                    <i class="fa-solid fa-pen-to-square" aria-hidden="true"></i>
                                </span>
                            @endif
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
