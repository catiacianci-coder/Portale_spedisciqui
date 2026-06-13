@php
    use App\Models\stato_spedizione;
    use App\Support\EtichettaSpedizioneAccess;
    use App\Support\PiattaformaCorriere;
    use App\Support\SendcloudIntegrazione;
    use App\Support\SpedisciOnlineIntegrazione;

    $variant = (string) ($variant ?? 'pagato');
    $isAnnullato = $variant === 'annullato';
    $spedizioniOrdinate = $ordine->spedizioni->sortByDesc('id')->values();
    $dataPagamentoOrdine = $ordine->data_pagamento?->format('d/m/Y H:i');
    $dataAnnullamentoOrdine = ($ordine->annullato_in ?? $ordine->updated_at)?->format('d/m/Y H:i');
    $colspanVazio = $isAnnullato ? 9 : 10;
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
                    <th class="sq-th">Mittente</th>
                    <th class="sq-th">Destinatario</th>
                    <th class="sq-th">Servizio</th>
                    <th class="sq-th">Servizi aggiuntivi</th>
                    <th class="sq-th sq-th--right">@include('partials.th-importo-iva-inclusa')</th>
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
                        $nomeRem = trim((string) ($s->razione_sociale_o ?: trim((string) (($s->nome_o ?? '') . ' ' . ($s->cognome_o ?? '')))));
                        $nomeDest = trim((string) trim((string) (($s->nome_d ?? '') . ' ' . ($s->sobrenome_d ?? ''))));
                        $addrRem = trim(implode(' ', array_filter([
                            trim((string) ($s->indirizzo_o ?? '')),
                            trim((string) ($s->numero_o ?? '')),
                        ])));
                        $addrDest = trim(implode(' ', array_filter([
                            trim((string) ($s->indirizzo_d ?? '')),
                            trim((string) ($s->numero_d ?? '')),
                        ])));
                        $linhaRem2 = trim(implode(' - ', array_filter([
                            trim((string) ($s->frazione_o ?? '')),
                            trim(implode('/', array_filter([trim((string) ($s->citta_o ?? '')), trim((string) ($s->stato_o ?? ''))]))),
                            trim((string) ($s->cap_o ?? '')),
                        ])));
                        $linhaDest2 = trim(implode(' - ', array_filter([
                            trim((string) ($s->frazione_d ?? '')),
                            trim(implode('/', array_filter([trim((string) ($s->citta_d ?? '')), trim((string) ($s->stato_d ?? ''))]))),
                            trim((string) ($s->cap_d ?? '')),
                        ])));
                        $servico = trim((string) ($servizioPerSpedizione[(int) $s->id] ?? $s->service_description ?? $s->corriere ?? ''));
                        $dataCancellazioneRiga = ($s->cancellata_il ?? $ordine->annullato_in ?? $ordine->updated_at)?->format('d/m/Y H:i');
                        $codiceLdV = trim((string) ($s->tracking ?? ''));
                        $ldvCancellata = EtichettaSpedizioneAccess::etichettaCancellata($s);
                        $ldvStampabile = ! $isAnnullato && ! $ldvCancellata && (
                            SpedisciOnlineIntegrazione::etichettaStampabile($s)
                            || trim((string) $s->etiqueta_pdf_path) !== ''
                            || trim((string) $s->id_shipment) !== ''
                        );
                        $importoIvato = $s->prezzoClienteIvato();
                    @endphp
                    <tr @class(['sq-ordine-remessa--annullata' => $annullata || $isAnnullato])>
                        <td class="sq-td sq-ordine-remessa-codice">{{ $s->codice_interno ?: '—' }}</td>
                        <td class="sq-td sq-fw-700">{{ $ordine->codice }}</td>
                        <td class="sq-td sq-nowrap">{{ $dataPagamentoOrdine ?? '—' }}</td>
                        @if ($isAnnullato)
                            <td class="sq-td sq-nowrap">{{ $dataCancellazioneRiga ?? '—' }}</td>
                        @endif
                        <td class="sq-td sq-ordine-remessa-person">
                            <span class="sq-ordine-remessa-nome">{{ $nomeRem !== '' ? $nomeRem : '—' }}</span>
                            @if ($addrRem !== '')
                                <span class="sq-ordine-remessa-indirizzo">{{ $addrRem }}</span>
                            @endif
                            @if ($linhaRem2 !== '')
                                <span class="sq-ordine-remessa-indirizzo">{{ $linhaRem2 }}</span>
                            @endif
                        </td>
                        <td class="sq-td sq-ordine-remessa-person">
                            <span class="sq-ordine-remessa-nome">{{ $nomeDest !== '' ? $nomeDest : '—' }}</span>
                            @if ($addrDest !== '')
                                <span class="sq-ordine-remessa-indirizzo">{{ $addrDest }}</span>
                            @endif
                            @if ($linhaDest2 !== '')
                                <span class="sq-ordine-remessa-indirizzo">{{ $linhaDest2 }}</span>
                            @endif
                        </td>
                        <td class="sq-td">{{ $servico !== '' ? $servico : '—' }}</td>
                        <td class="sq-td sq-text-14">
                            @include('ordini.partials.spedizione-servizi-aggiuntivi', ['spedizione' => $s])
                        </td>
                        <td class="sq-td sq-td--right">
                            @include('partials.td-importo-ivato', ['importoIvato' => $importoIvato])
                        </td>
                        @unless ($isAnnullato)
                            <td class="sq-td sq-ordine-etq-tracking">
                                @if ($codiceLdV !== '')
                                    {{ $codiceLdV }}
                                @else
                                    <span class="sq-text-muted">—</span>
                                @endif
                                @if ($annullata)
                                    <div class="sq-ordine-remessa-badges">
                                        <span class="sq-badge sq-badge--muted">Annullata</span>
                                    </div>
                                @elseif ($ldvCancellata)
                                    <div class="sq-ordine-remessa-badges">
                                        <span class="sq-badge sq-badge--muted">Lettera di vettura cancellata</span>
                                    </div>
                                @endif
                            </td>
                            <td class="sq-td sq-td--right">
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
                    @php
                        $corriereSped = $s->corriereRecord;
                        $mostraTracciaSendcloud = ! $isAnnullato
                            && $corriereSped
                            && PiattaformaCorriere::corriereUsaAcquistoSendcloud($corriereSped)
                            && SendcloudIntegrazione::haTracciaApi($s);
                    @endphp
                    @if ($mostraTracciaSendcloud)
                        <tr class="sq-sped-sendcloud-api-trace-row">
                            <td colspan="{{ $colspanVazio }}" class="sq-td sq-td--trace">
                                @include('partials.spedizione-sendcloud-api-trace', ['spedizione' => $s])
                            </td>
                        </tr>
                    @endif
                @empty
                    <tr>
                        <td colspan="{{ $colspanVazio }}" class="sq-td">Nessuna spedizione in questo ordine.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
