@php
    /** @var \Illuminate\Support\Collection|\Illuminate\Contracts\Pagination\LengthAwarePaginator $ricariche */
    $numeroOrdine = $numeroOrdine ?? static fn ($r) => $r->numero_ordine_wallet ?? ('ORW-'.$r->id);
    $cardTitle = (string) ($cardTitle ?? 'Le tue ricariche');
    $statoLabel = static function ($r): string {
        return match ($r->stato) {
            'accreditata' => 'Pagato',
            'annullata' => 'Annullato',
            default => 'Non pagato',
        };
    };
    $statoClass = static function ($r): string {
        return match ($r->stato) {
            'accreditata' => 'sq-wallet-ricariche-stato--pagato',
            'annullata' => 'sq-wallet-ricariche-stato--annullata',
            default => 'sq-wallet-ricariche-stato--non-pagato',
        };
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
                    <th class="sq-th sq-th--right">Azioni</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($ricariche as $r)
                    <tr @class(['sq-wallet-ricariche-row--annullata' => $r->stato === 'annullata'])>
                        <td class="sq-td sq-nowrap" data-label="Data">
                            {{ $r->created_at?->timezone(config('app.timezone'))->format('d/m/Y H:i') ?? '—' }}
                        </td>
                        <td class="sq-td sq-ordine-remessa-codice sq-fw-700" data-label="N. ordine">{{ $numeroOrdine($r) }}</td>
                        <td class="sq-td" data-label="Metodo di pagamento">{{ \App\Support\WalletRicaricaMetodoPagamento::labelCliente($r) }}</td>
                        <td class="sq-td" data-label="Stato">
                            <span @class(['sq-wallet-ricariche-stato', $statoClass($r)])>{{ $statoLabel($r) }}</span>
                        </td>
                        <td class="sq-td sq-td--right sq-fw-700" data-label="Importo">
                            {{ \App\Support\ImportoEuro::format($r->importo) }}
                        </td>
                        <td class="sq-td sq-td--right" data-label="Azioni">
                            @if ($r->stato === 'in_attesa')
                                <div class="sq-ordini-actions-icons">
                                    @if ($hasMetodiPagamentoRicarica ?? true)
                                        <a
                                            href="{{ route('wallet.ricariche.pagamento.show', $r) }}"
                                            class="sq-ordini-icon-action sq-ordini-icon-action--pay"
                                            title="Paga ricarica"
                                            aria-label="Paga ricarica {{ $numeroOrdine($r) }}"
                                        >
                                            <i class="fa-solid fa-credit-card" aria-hidden="true"></i>
                                        </a>
                                    @else
                                        <span
                                            class="sq-ordini-icon-action sq-ordini-icon-action--pay is-disabled"
                                            title="Nessun metodo di pagamento disponibile"
                                            aria-hidden="true"
                                        >
                                            <i class="fa-solid fa-credit-card" aria-hidden="true"></i>
                                        </span>
                                    @endif
                                    <form
                                        method="POST"
                                        action="{{ route('wallet.ricariche.destroy', $r) }}"
                                        class="sq-form-zero sq-wallet-ricarica-delete-form"
                                        onsubmit="return confirm('Annullare questa ricarica? L’operazione non è reversibile.');"
                                    >
                                        @csrf
                                        @method('DELETE')
                                        <button
                                            type="submit"
                                            class="sq-ordini-icon-action sq-ordini-icon-action--remove"
                                            title="Annulla ricarica"
                                            aria-label="Annulla ricarica"
                                        >
                                            <i class="fa-solid fa-trash-can" aria-hidden="true"></i>
                                        </button>
                                    </form>
                                </div>
                            @else
                                <span class="sq-text-muted">—</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
