@php
    /** @var \App\Models\wallet_ricarica_richiesta $ricarica */
    $cardTitle = (string) ($cardTitle ?? 'Riepilogo ricarica');
    $numeroOrdine = $ricarica->numero_ordine_wallet ?? ('ORW-'.$ricarica->id);
    $importo = (float) $ricarica->importo;
    $statoLabel = match ($ricarica->stato) {
        'accreditata' => 'Pagato',
        'annullata' => 'Annullato',
        default => 'Non pagato',
    };
    $statoClass = match ($ricarica->stato) {
        'accreditata' => 'sq-wallet-ricariche-stato--pagato',
        'annullata' => 'sq-wallet-ricariche-stato--annullata',
        default => 'sq-wallet-ricariche-stato--non-pagato',
    };
@endphp
<div class="sq-ordine-remessas-card">
    <div class="sq-ordine-remessas-card-head">
        <strong>{{ $cardTitle }}</strong>
    </div>
    <div class="sq-table-wrap sq-ordine-remessas-table-wrap">
        <table class="sq-table sq-ordine-remessas-table sq-wallet-ricariche-table">
            <thead>
                <tr class="sq-thead-row sq-thead-row--neutral">
                    <th class="sq-th">Data</th>
                    <th class="sq-th">N. ordine</th>
                    <th class="sq-th">Metodo di pagamento</th>
                    <th class="sq-th">Stato</th>
                    <th class="sq-th sq-th--right">Importo</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="sq-td sq-nowrap" data-label="Data">
                        {{ $ricarica->created_at?->timezone(config('app.timezone'))->format('d/m/Y H:i') ?? '—' }}
                    </td>
                    <td class="sq-td sq-ordine-remessa-codice sq-fw-700" data-label="N. ordine">{{ $numeroOrdine }}</td>
                    <td class="sq-td" data-label="Metodo di pagamento">{{ \App\Support\WalletRicaricaMetodoPagamento::labelCliente($ricarica) }}</td>
                    <td class="sq-td" data-label="Stato">
                        <span @class(['sq-wallet-ricariche-stato', $statoClass])>{{ $statoLabel }}</span>
                    </td>
                    <td class="sq-td sq-td--right sq-fw-700" data-label="Importo">
                        {{ \App\Support\ImportoEuro::format($importo) }}
                    </td>
                </tr>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4" class="sq-td sq-td--right sq-fw-700">Totale ricarica</td>
                    <td class="sq-td sq-td--right sq-fw-700">{{ \App\Support\ImportoEuro::format($importo) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
