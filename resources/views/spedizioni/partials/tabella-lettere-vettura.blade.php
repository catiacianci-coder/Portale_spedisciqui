@php
    use App\Models\stato_spedizione;
    use App\Support\EtichetteListing;
    use App\Support\SpedisciOnlineIntegrazione;
@endphp
<div class="sq-table-wrap sq-ordine-remessas-table-wrap">
    <table class="sq-table sq-ordine-remessas-table sq-ordine-pagato-etq-table">
        <thead>
            <tr class="sq-thead-row sq-thead-row--neutral">
                <th class="sq-th">Codice</th>
                <th class="sq-th">Ordine</th>
                <th class="sq-th sq-nowrap">Data pagamento</th>
                <th class="sq-th">Mittente</th>
                <th class="sq-th">Destinatario</th>
                <th class="sq-th">Servizio</th>
                <th class="sq-th">Servizi aggiuntivi</th>
                <th class="sq-th sq-th--right">@include('partials.th-importo-iva-inclusa')</th>
                <th class="sq-th">Status</th>
                <th class="sq-th">Lettera di vettura</th>
                <th class="sq-th sq-th--right sq-th--actions">Azioni</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($spedizioni as $s)
                @php
                    $ord = $s->ordine;
                    $statoId = (int) $s->spedizione_stato_id;
                    $nomeRem = trim((string) ($s->razione_sociale_o ?: trim((string) (($s->nome_o ?? '') . ' ' . ($s->cognome_o ?? '')))));
                    $addrRem = trim(implode(' ', array_filter([
                        trim((string) ($s->indirizzo_o ?? '')),
                        trim((string) ($s->numero_o ?? '')),
                    ])));
                    $linhaRem2 = trim(implode(' - ', array_filter([
                        trim((string) ($s->frazione_o ?? '')),
                        trim(implode('/', array_filter([trim((string) ($s->citta_o ?? '')), trim((string) ($s->stato_o ?? ''))]))),
                        trim((string) ($s->cap_o ?? '')),
                    ])));
                    $servico = EtichetteListing::nomeServizio($s);
                    $dataPagamento = $ord?->data_pagamento?->format('d/m/Y H:i');
                    $codiceLdV = trim((string) ($s->tracking ?? ''));
                    $etichettaCancellata = SpedisciOnlineIntegrazione::etichettaCancellata($s);
                    $ldvStampabile = SpedisciOnlineIntegrazione::etichettaStampabile($s);
                    $importoIvato = $s->prezzoClienteIvato();
                    $statoLabel = (string) ($s->spedizioneStato?->denominazione_stato ?? '—');
                    $badgeRimborso = match ($statoId) {
                        stato_spedizione::RIMBORSATA => 'Rimborsata',
                        stato_spedizione::ANNULLATA => $ord?->data_pagamento ? 'In attesa di rimborso' : null,
                        default => null,
                    };
                    $rigaRimborso = in_array($statoId, [stato_spedizione::RIMBORSATA, stato_spedizione::ANNULLATA], true)
                        && $ord?->data_pagamento;
                @endphp
                <tr @class(['sq-ordine-remessa--annullata' => $rigaRimborso])>
                    <td class="sq-td sq-ordine-remessa-codice">
                        {{ $s->codice_interno ?: '—' }}
                        @if ($badgeRimborso)
                            <div class="sq-ordine-remessa-badges">
                                <span class="sq-badge sq-badge--rimborso-{{ $statoId === stato_spedizione::RIMBORSATA ? 'ok' : 'pending' }}">{{ $badgeRimborso }}</span>
                            </div>
                        @endif
                    </td>
                    <td class="sq-td sq-fw-700">{{ $ord?->id ?? '—' }}</td>
                    <td class="sq-td sq-nowrap">{{ $dataPagamento ?? '—' }}</td>
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
                        @include('ordini.partials.spedizione-destinatario-tabella', ['spedizione' => $s])
                    </td>
                    <td class="sq-td">{{ $servico !== '' ? $servico : '—' }}</td>
                    <td class="sq-td sq-text-14">
                        @include('ordini.partials.spedizione-servizi-aggiuntivi', ['spedizione' => $s])
                    </td>
                    <td class="sq-td sq-td--right">
                        @include('partials.td-importo-ivato', ['importoIvato' => $importoIvato])
                    </td>
                    <td class="sq-td">
                        <span class="sq-etichetta-stato">{{ $statoLabel }}</span>
                    </td>
                    <td class="sq-td sq-ordine-etq-tracking">
                        @if ($codiceLdV !== '')
                            {{ $codiceLdV }}
                        @else
                            <span class="sq-text-muted">—</span>
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
                                    title="{{ $etichettaCancellata ? 'Lettera di vettura cancellata' : 'Lettera di vettura non disponibile' }}"
                                >
                                    <i class="fa-solid fa-print" aria-hidden="true"></i>
                                </span>
                            @endif
                            @include('partials.spedizione-tracking-icon', ['spedizione' => $s])
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="11" class="sq-td">Nessuna lettera di vettura nel periodo selezionato.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@include('partials.spedizione-tracking-popup')
