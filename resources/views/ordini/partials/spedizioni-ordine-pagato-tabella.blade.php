@php
    use App\Models\stato_spedizione;
    use App\Support\EtichettaSpedizioneAccess;
    use App\Support\EtichetteListing;
    use App\Support\OrdineRiepilogo;
    use App\Support\SpedisciOnlineIntegrazione;

    $variant = (string) ($variant ?? 'pagato');
    $isAnnullato = $variant === 'annullato';
    $spedizioniOrdinate = $ordine->spedizioni->sortByDesc('id')->values();
    $dataPagamentoOrdine = $ordine->data_pagamento?->format('d/m/Y H:i');
    $dataAnnullamentoOrdine = ($ordine->annullato_in ?? $ordine->updated_at)?->format('d/m/Y H:i');
    $colspanVazio = $isAnnullato ? 9 : 10;
    $mostraMetodoPagamento = ! $isAnnullato && $ordine->stato === \App\Models\ordine::STATO_PAGATO;
@endphp
<div class="sq-ordine-remessas-card sq-ordine-pagato-etq-card @if ($isAnnullato) sq-ordine-annullato-etq-card @endif">
    <div class="sq-ordine-remessas-card-head">
        <strong>{{ $cardTitle ?? 'Spedizioni dell\'ordine' }}</strong>
    </div>
    <div class="sq-table-wrap sq-ordine-remessas-table-wrap">
        <table class="sq-table sq-ordine-remessas-table sq-ordine-pagato-etq-table">
            <thead>
                <tr class="sq-thead-row sq-thead-row--neutral">
                    <th class="sq-th">Codice</th>
                    <th class="sq-th">Ordine</th>
                    <th class="sq-th sq-nowrap">Data pagamento</th>
                    @if ($isAnnullato)
                        <th class="sq-th sq-nowrap">Data cancellazione</th>
                    @endif
                    <th class="sq-th">Destinatario</th>
                    <th class="sq-th">Servizio</th>
                    <th class="sq-th">Servizi aggiuntivi</th>
                    <th class="sq-th sq-th--right">@include('partials.th-importo-iva-inclusa')</th>
                    <th class="sq-th">Status</th>
                    @unless ($isAnnullato)
                        <th class="sq-th">Lettera di vettura</th>
                        <th class="sq-th sq-th--right sq-th--actions">Azioni</th>
                    @endunless
                </tr>
            </thead>
            <tbody>
                @forelse ($spedizioniOrdinate as $s)
                    @php
                        $annullata = (int) $s->spedizione_stato_id === stato_spedizione::ANNULLATA;
                        $servico = EtichetteListing::nomeServizio($s);
                        $dataCancellazioneRiga = ($s->cancellata_il ?? $ordine->annullato_in ?? $ordine->updated_at)?->format('d/m/Y H:i');
                        $codiceLdV = trim((string) ($s->tracking ?? ''));
                        $ldvCancellata = EtichettaSpedizioneAccess::etichettaCancellata($s);
                        $ldvStampabile = ! $isAnnullato && ! $ldvCancellata && (
                            SpedisciOnlineIntegrazione::etichettaStampabile($s)
                            || trim((string) $s->etiqueta_pdf_path) !== ''
                            || trim((string) $s->id_shipment) !== ''
                        );
                        $importoIvato = $s->prezzoClienteIvato();
                        $statoLabel = (string) ($s->spedizioneStato?->denominazione_stato ?? '—');
                    @endphp
                    <tr @class(['sq-ordine-remessa--annullata' => $annullata || $isAnnullato])>
                        <td class="sq-td sq-ordine-remessa-codice" data-label="Codice">{{ $s->codice_interno ?: '—' }}</td>
                        <td class="sq-td sq-fw-700" data-label="Ordine">{{ $ordine->id }}</td>
                        <td class="sq-td sq-nowrap" data-label="Data pagamento">{{ $dataPagamentoOrdine ?? '—' }}</td>
                        @if ($isAnnullato)
                            <td class="sq-td sq-nowrap" data-label="Data cancellazione">{{ $dataCancellazioneRiga ?? '—' }}</td>
                        @endif
                        <td class="sq-td sq-ordine-remessa-person" data-label="Destinatario">
                            @include('ordini.partials.spedizione-destinatario-tabella', ['spedizione' => $s])
                        </td>
                        <td class="sq-td" data-label="Servizio">{{ $servico !== '' ? $servico : '—' }}</td>
                        <td class="sq-td sq-text-14" data-label="Servizi aggiuntivi">
                            @include('ordini.partials.spedizione-servizi-aggiuntivi', ['spedizione' => $s])
                        </td>
                        <td class="sq-td sq-td--right" data-label="Importo (IVA inclusa)">
                            <span class="sq-importo-ivato-val sq-fw-700 sq-nowrap">
                                {{ OrdineRiepilogo::importoIvatoRigaTabella($importoIvato, $ordine, $mostraMetodoPagamento) }}
                            </span>
                        </td>
                        <td class="sq-td" data-label="Status">
                            <span class="sq-etichetta-stato">{{ $statoLabel }}</span>
                        </td>
                        @unless ($isAnnullato)
                            <td class="sq-td sq-ordine-etq-tracking" data-label="Lettera di vettura">
                                @if ($codiceLdV !== '')
                                    {{ $codiceLdV }}
                                @else
                                    <span class="sq-text-muted">—</span>
                                @endif
                            </td>
                            <td class="sq-td sq-td--right" data-label="Azioni">
                                <div class="sq-ordine-etq-print-cell sq-ordini-actions-icons">
                                    @if ($ldvStampabile)
                                        <a
                                            href="{{ route('spedizioni.etichetta', $s) }}"
                                            class="sq-ordini-icon-action sq-ordini-icon-action--pay"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            title="Stampa lettera di vettura (PDF)"
                                            aria-label="Stampa lettera di vettura {{ $s->codice_interno }}"
                                        >
                                            <i class="fa-solid fa-print" aria-hidden="true"></i>
                                        </a>
                                    @else
                                        <span
                                            class="sq-ordini-icon-action is-disabled"
                                            title="{{ $ldvCancellata ? 'Lettera di vettura cancellata' : 'Lettera di vettura non ancora disponibile' }}"
                                        >
                                            <i class="fa-solid fa-print" aria-hidden="true"></i>
                                        </span>
                                    @endif
                                    @include('partials.spedizione-tracking-icon', ['spedizione' => $s])
                                </div>
                            </td>
                        @endunless
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $colspanVazio }}" class="sq-td">Nessuna spedizione in questo ordine.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
