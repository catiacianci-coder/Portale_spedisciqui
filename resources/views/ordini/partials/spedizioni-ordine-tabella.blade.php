@php
    use App\Models\stato_spedizione;
    use App\Support\EtichetteListing;

    $cardTitle = (string) ($cardTitle ?? 'Spedizioni dell\'ordine');
    $spedizioniOrdinate = $ordine->spedizioni->sortByDesc('id')->values();
    $totaleIvatoOrdine = (float) ($totaleIvatoOrdine ?? 0);
    $totaleIvatoStandard = (float) ($totaleIvatoStandard ?? $totaleIvatoOrdine);
    $totaleIvatoWallet = (float) ($totaleIvatoWallet ?? 0);
    $mostraPrezziDuali = (bool) ($mostraPrezziDuali ?? false);
    $mostraMittente = (bool) ($mostraMittente ?? true);
    $colBase = 7 + ($mostraMittente ? 1 : 0);
    $colspanVazio = $colBase;
    $colspanFooter = $colBase - 1;
@endphp
<div class="sq-ordine-remessas-card">
    <div class="sq-ordine-remessas-card-head">
        <strong>{{ $cardTitle }}</strong>
        @isset($cardMeta)
            <span class="sq-ordine-remessas-card-meta">{!! $cardMeta !!}</span>
        @endisset
    </div>
    <div class="sq-table-wrap sq-ordine-remessas-table-wrap">
        <table class="sq-table sq-ordine-remessas-table">
            <thead>
                <tr class="sq-thead-row sq-thead-row--neutral">
                    <th class="sq-th">Data</th>
                    <th class="sq-th">Codice spedizione</th>
                    <th class="sq-th">Ordine</th>
                    @if ($mostraMittente)
                        <th class="sq-th">Mittente</th>
                    @endif
                    <th class="sq-th">Destinatario</th>
                    <th class="sq-th">Servizio</th>
                    <th class="sq-th">Servizi aggiuntivi</th>
                    <th class="sq-th sq-th--right">@include('partials.th-importo-iva-inclusa')</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($spedizioniOrdinate as $s)
                    @php
                        $annullata = (int) $s->spedizione_stato_id === stato_spedizione::ANNULLATA;
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
                        $importoIvato = $s->prezzoClienteIvato();
                        $importoIvatoWallet = $mostraPrezziDuali ? $s->prezzoClienteIvatoWallet() : null;
                    @endphp
                    <tr @class(['sq-ordine-remessa--annullata' => $annullata])>
                        <td class="sq-td sq-nowrap" data-label="Data">
                            {{ $s->created_at?->format('d/m/Y H:i') ?? '—' }}
                            @if ($annullata)
                                <div class="sq-ordine-remessa-badges">
                                    <span class="sq-badge sq-badge--muted">Annullata</span>
                                </div>
                            @endif
                        </td>
                        <td class="sq-td sq-ordine-remessa-codice" data-label="Codice spedizione">{{ $s->codice_interno ?: '—' }}</td>
                        <td class="sq-td sq-fw-700" data-label="Ordine">{{ $ordine->id }}</td>
                        @if ($mostraMittente)
                            <td class="sq-td sq-ordine-remessa-person" data-label="Mittente">
                                <span class="sq-ordine-remessa-nome">{{ $nomeRem !== '' ? $nomeRem : '—' }}</span>
                                @if ($addrRem !== '')
                                    <span class="sq-ordine-remessa-indirizzo">{{ $addrRem }}</span>
                                @endif
                                @if ($linhaRem2 !== '')
                                    <span class="sq-ordine-remessa-indirizzo">{{ $linhaRem2 }}</span>
                                @endif
                            </td>
                        @endif
                        <td class="sq-td sq-ordine-remessa-person" data-label="Destinatario">
                            @include('ordini.partials.spedizione-destinatario-tabella', ['spedizione' => $s])
                        </td>
                        <td class="sq-td" data-label="Servizio">{{ $servico !== '' ? $servico : '—' }}</td>
                        <td class="sq-td sq-text-14" data-label="Servizi aggiuntivi">
                            @include('ordini.partials.spedizione-servizi-aggiuntivi', ['spedizione' => $s])
                        </td>
                        <td class="sq-td sq-td--right" data-label="Importo (IVA inclusa)">
                            @if ($annullata)
                                <span class="sq-text-muted">—</span>
                            @else
                                @if ($mostraPrezziDuali)
                                    @include('partials.due-prezzi-standard-wallet', [
                                        'prezzoStandard' => $importoIvato,
                                        'prezzoWallet' => $importoIvatoWallet,
                                        'compact' => true,
                                    ])
                                @else
                                    @include('partials.td-importo-ivato', ['importoIvato' => $importoIvato])
                                @endif
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $colspanVazio }}" class="sq-td">Nessuna spedizione in questo ordine.</td>
                    </tr>
                @endforelse
            </tbody>
            @if ($spedizioniOrdinate->isNotEmpty())
                <tfoot>
                    <tr>
                        <td colspan="{{ $colspanFooter }}" class="sq-td sq-td--right sq-fw-700">Totale ordine (IVA inclusa)</td>
                        <td class="sq-td sq-td--right sq-fw-700">
                            @if ($mostraPrezziDuali)
                                @include('partials.due-prezzi-standard-wallet', [
                                    'prezzoStandard' => $totaleIvatoStandard,
                                    'prezzoWallet' => $totaleIvatoWallet,
                                    'compact' => true,
                                ])
                            @else
                                {{ \App\Support\ImportoEuro::format($totaleIvatoOrdine) }}
                            @endif
                        </td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>
</div>
