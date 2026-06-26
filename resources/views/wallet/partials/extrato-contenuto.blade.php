@php
    /** @var \Illuminate\Contracts\Pagination\LengthAwarePaginator|\Illuminate\Support\Collection $linhas */
    $linhas = $linhas ?? collect();
    $showUsuarioColumn = $showUsuarioColumn ?? false;
    $showNotaInterna = $showNotaInterna ?? false;
    $editNotaInterna = $editNotaInterna ?? false;
    $queryParams = $queryParams ?? [];
    $hasActiveFilters = $hasActiveFilters ?? false;
    $walletSaldoFormatado = $walletSaldoFormatado ?? null;
    $selectedUser = $selectedUser ?? null;
    $total = method_exists($linhas, 'total') ? $linhas->total() : $linhas->count();
@endphp

@if ($walletSaldoFormatado !== null)
    <div class="sq-wallet-extrato-saldo" role="status" aria-live="polite">
        <div>
            <div class="sq-wallet-extrato-saldo__label">Saldo wallet</div>
            <div class="sq-wallet-extrato-saldo__valor">{{ $walletSaldoFormatado }}</div>
        </div>
        @if ($selectedUser !== null)
            <div class="sq-wallet-extrato-saldo__user">
                <strong>#{{ $selectedUser->id }}</strong> · {{ $selectedUser->email }}
            </div>
        @endif
    </div>
@endif

<div class="sq-wallet-extrato-card">
    @if ($total === 0)
        <div class="sq-wallet-extrato-empty">
            @if ($showUsuarioColumn && ($selectedUser ?? null) === null)
                @if ($buscaSemResultado ?? false)
                    Nessun utente corrisponde alla ricerca.
                @elseif ($invalidUserId ?? false)
                    Utente non trovato.
                @elseif (($candidatos ?? collect())->isNotEmpty())
                    Seleziona un utente dall'elenco qui sotto.
                @else
                    Indica un utente e applica i filtri per vedere l'estratto.
                @endif
            @else
                {{ $hasActiveFilters ? 'Nessun movimento con questi filtri.' : 'Nessun movimento registrato.' }}
            @endif
        </div>
    @else
        <div class="sq-table-wrap sq-wallet-extrato-table-wrap">
            <table class="sq-table sq-wallet-extrato-table">
                <thead>
                    <tr class="sq-thead-row">
                        @if ($showUsuarioColumn)
                            <th class="sq-th">Utente</th>
                        @endif
                        <th class="sq-th">Data</th>
                        <th class="sq-th">Descrizione</th>
                        <th class="sq-th">Dettaglio</th>
                        @if ($showNotaInterna)
                            <th class="sq-th">Nota interna</th>
                        @endif
                        <th class="sq-th sq-th--right">Importo</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($linhas as $linha)
                        @php
                            $valorFmt = ($linha->isCredito ? '+ ' : '− ').\App\Support\ImportoEuro::format($linha->valor);
                            $u = $linha->usuario;
                        @endphp
                        <tr>
                            @if ($showUsuarioColumn)
                                <td class="sq-td">
                                    @if ($u)
                                        <div class="sq-wallet-extrato-user-name">{{ $u->headerDisplayName() }}</div>
                                        <div class="sq-wallet-extrato-user-email">{{ $u->email }}</div>
                                    @else
                                        —
                                    @endif
                                </td>
                            @endif
                            <td class="sq-td sq-text-muted sq-nowrap">{{ $linha->sortAt->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</td>
                            <td class="sq-td">{{ $linha->dettaglio }}</td>
                            <td class="sq-td sq-wallet-extrato-riferimento">{{ $linha->ordineLdv }}</td>
                            @if ($showNotaInterna)
                                <td class="sq-td sq-wallet-extrato-nota-interna">
                                    @if ($editNotaInterna)
                                        <form
                                            method="POST"
                                            action="{{ route('backoffice.wallet.movimento.nota_interna', $linha->movimentoId) }}"
                                            class="sq-wallet-nota-interna-form"
                                        >
                                            @csrf
                                            @method('PATCH')
                                            @foreach ($queryParams as $name => $value)
                                                <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                                            @endforeach
                                            <label class="sq-sr-only" for="nota-interna-{{ $linha->movimentoId }}">Nota interna</label>
                                            <textarea
                                                id="nota-interna-{{ $linha->movimentoId }}"
                                                name="nota_interna"
                                                maxlength="500"
                                                rows="2"
                                                placeholder="Nota visibile solo in backoffice…"
                                                class="sq-wallet-nota-interna-form__input"
                                            >{{ $linha->notaInterna ?? '' }}</textarea>
                                            <button type="submit" class="sq-wallet-nota-interna-form__btn">Salva</button>
                                        </form>
                                    @else
                                        {{ $linha->notaInterna ?? '—' }}
                                    @endif
                                </td>
                            @endif
                            <td class="sq-td sq-td--right sq-wallet-extrato-valor {{ $linha->isCredito ? 'is-credito' : 'is-debito' }}">{{ $valorFmt }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if (method_exists($linhas, 'hasPages') && $linhas->hasPages())
            <div class="sq-wallet-extrato-pag">
                {{ $linhas->onEachSide(1)->links() }}
            </div>
        @endif
    @endif
</div>
