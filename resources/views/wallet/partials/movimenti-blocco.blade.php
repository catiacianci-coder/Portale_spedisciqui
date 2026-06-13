{{--
  $saldo (float), $movimenti (Collection|\Illuminate\Contracts\Pagination\Paginator), $emailCliente (string|null)
  $linkOrdineCliente (bool): true = layout pagina movimenti cliente (Ordine prima, colonna Azioni)
--}}
@php
    $fmt = fn ($n) => number_format((float) $n, 2, ',', '.');
    $fmtImportoMov = static function ($mv) use ($fmt): string {
        $n = $fmt($mv->importo) . ' €';

        return ($mv->tipo === 'debito') ? '-'.$n : $n;
    };
@endphp

<div class="wallet-movimenti-blocco sq-wallet-saldo-card">
    @if (empty($emailCliente))
        <p class="sq-wallet-saldo-line">Saldo attuale <strong>{{ $fmt($saldo) }} €</strong></p>
    @else
        <div class="sq-wallet-saldo-row">
            <div>
                <p class="sq-wallet-saldo-label">Saldo attuale</p>
                <p class="sq-wallet-saldo-value">{{ $fmt($saldo) }} €</p>
            </div>
            <p class="sq-wallet-cliente-email">Cliente: <strong class="sq-text-heading">{{ $emailCliente }}</strong></p>
        </div>
    @endif
</div>

<div class="sq-table-wrap sq-table-wrap--warm">
    @if (! empty($linkOrdineCliente))
        <table class="sq-table">
            <thead>
                <tr class="sq-thead-row sq-thead-row--warm">
                    <th class="sq-th sq-th--warm">Ordine</th>
                    <th class="sq-th sq-th--warm">Data</th>
                    <th class="sq-th sq-th--warm">Tipo</th>
                    <th class="sq-th sq-th--warm">Descrizione</th>
                    <th class="sq-th sq-th--warm sq-th--right">Importo</th>
                    <th class="sq-th sq-th--warm sq-th--right">Azioni</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($movimenti as $mv)
                    @php
                        $o = $mv->ordine;
                        $orw = trim((string) ($mv->ricaricaRichiesta?->numero_ordine_wallet ?? ''));
                    @endphp
                    <tr>
                        <td class="sq-td sq-td--border-warm sq-mov-td-muted-sm">
                            @if ($mv->ordine_id && $o)
                                {{ $o->codice }}
                            @elseif ($orw !== '')
                                {{ $orw }}
                            @elseif ($mv->ordine_id)
                                #{{ $mv->ordine_id }}
                            @else
                                —
                            @endif
                        </td>
                        <td class="sq-td sq-td--border-warm sq-text-muted sq-nowrap">{{ $mv->data_movimento?->format('d/m/Y H:i') ?? '—' }}</td>
                        <td class="sq-td sq-td--border-warm">
                            @if ($mv->tipo === 'credito')
                                <span class="sq-mov-credito">Credito</span>
                            @else
                                <span class="sq-mov-debito">Debito</span>
                            @endif
                        </td>
                        <td class="sq-td sq-td--border-warm">{{ $mv->descrizione?->descrizione ?? '—' }}</td>
                        <td class="sq-td sq-td--border-warm sq-td--right sq-fw-700">{{ $fmtImportoMov($mv) }}</td>
                        <td class="sq-td sq-td--border-warm sq-td--right">
                            @if ($o)
                                @php
                                    $ordineDettaglioPopup = \App\Support\WalletMovimentiOrdinePopupPayload::fromOrdine($o);
                                @endphp
                                <div class="sq-ordini-actions-icons">
                                    <button
                                        type="button"
                                        class="sq-ordini-icon-action sq-ordini-icon-action--view js-movimenti-ordine-detail-btn"
                                        title="Dettaglio ordine e spedizioni"
                                        aria-label="Dettaglio ordine e spedizioni"
                                        data-ordine-dettaglio='@json($ordineDettaglioPopup)'
                                    >
                                        <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                                    </button>
                                </div>
                            @else
                                <span class="sq-mov-azioni-vuoto">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="sq-mov-empty">Nessun movimento registrato.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    @else
        <table class="sq-table">
            <thead>
                <tr class="sq-thead-row sq-thead-row--warm">
                    <th class="sq-th sq-th--warm">Data</th>
                    <th class="sq-th sq-th--warm">Tipo</th>
                    <th class="sq-th sq-th--warm">Descrizione</th>
                    <th class="sq-th sq-th--warm sq-th--right">Importo</th>
                    <th class="sq-th sq-th--warm">Ordine</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($movimenti as $mv)
                    <tr>
                        <td class="sq-td sq-td--border-warm sq-text-muted sq-nowrap">{{ $mv->data_movimento?->format('d/m/Y H:i') ?? '—' }}</td>
                        <td class="sq-td sq-td--border-warm">
                            @if ($mv->tipo === 'credito')
                                <span class="sq-mov-credito">Credito</span>
                            @else
                                <span class="sq-mov-debito">Debito</span>
                            @endif
                        </td>
                        <td class="sq-td sq-td--border-warm">{{ $mv->descrizione?->descrizione ?? '—' }}</td>
                        <td class="sq-td sq-td--border-warm sq-td--right sq-fw-700">{{ $fmtImportoMov($mv) }}</td>
                        <td class="sq-td sq-td--border-warm sq-mov-td-muted-sm">
                            @php
                                $o = $mv->ordine;
                                $orw = trim((string) ($mv->ricaricaRichiesta?->numero_ordine_wallet ?? ''));
                            @endphp
                            @if ($mv->ordine_id && $o)
                                {{ $o->codice }}
                            @elseif ($orw !== '')
                                {{ $orw }}
                            @elseif ($mv->ordine_id)
                                #{{ $mv->ordine_id }}
                            @else
                                —
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="sq-mov-empty">Nessun movimento registrato.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    @endif
</div>

@if ($movimenti instanceof \Illuminate\Pagination\AbstractPaginator && ! empty($queryParams))
    @include('partials.tabella-paginazione', [
        'paginator' => $movimenti,
        'perPage' => $perPage ?? 10,
        'queryParams' => $queryParams,
    ])
@elseif ($movimenti instanceof \Illuminate\Pagination\AbstractPaginator)
    <div class="sq-wallet-mov-pager sq-bo-sped-pager">
        <div class="sq-text-muted">
            Visualizzazione {{ $movimenti->firstItem() ?? 0 }}-{{ $movimenti->lastItem() ?? 0 }} di {{ $movimenti->total() }}
        </div>
        {{ $movimenti->onEachSide(1)->links() }}
    </div>
@endif
