@extends('layouts.app')
@section('content')
@php
    $fmt = fn ($n) => number_format((float) $n, 2, ',', '.');
    $numeroOrdine = fn ($r) => $r->numero_ordine_wallet ?? ('ORW-'.$r->id);
@endphp

<div class="sq-bleed-layout sq-wallet-ricariche-page">
    <x-sq-page-banner title="Ricariche wallet" icon="fa-coins" class="sq-page-banner--full" />

    <div class="sq-listing-page">
        <div class="sq-wallet-extrato-toolbar">
            <a href="{{ route('wallet.ricarica') }}" class="sq-wallet-extrato-btn-ricarica">
                <i class="fas fa-plus-circle" aria-hidden="true"></i>
                Nuova ricarica
            </a>
        </div>

        @if (session('ok'))
            <div class="sq-alert sq-alert--success sq-mb-16">{{ session('ok') }}</div>
        @endif

        @if ($errors->has('ricarica'))
            <div class="sq-alert sq-alert--error sq-mb-16">{{ $errors->first('ricarica') }}</div>
        @endif

        @if ($customPeriodoSemDatas ?? false)
            <p class="sq-wallet-extrato-hint">Periodo personalizzato: indica le date <strong>Da</strong> e/o <strong>A</strong> e clicca il filtro.</p>
        @endif

        @include('wallet.partials.ricariche-filtri', [
            'formAction' => route('wallet.ricariche'),
            'filtros' => $filtros,
            'perPage' => $perPage,
            'formId' => 'form-filtri-ricariche-fo',
            'periodoId' => 'filtro-ricariche-periodo-fo',
            'customWrapId' => 'filtro-ricariche-datas-custom-fo',
            'statoId' => 'filtro-ricariche-stato-fo',
            'perPageId' => 'filtro-ricariche-per-page-fo',
        ])

        <div class="sq-wallet-extrato-card">
            @if ($ricariche->total() === 0)
                <div class="sq-wallet-extrato-empty">
                    {{ ($hasActiveFilters ?? false) ? 'Nessuna ricarica con questi filtri.' : 'Nessuna ricarica registrata.' }}
                </div>
            @else
                <div class="sq-table-wrap sq-wallet-extrato-table-wrap">
                    <table class="sq-table sq-wallet-extrato-table sq-wallet-ricariche-table">
                        <thead>
                            <tr class="sq-thead-row">
                                <th class="sq-th">N. ordine</th>
                                <th class="sq-th">Data</th>
                                <th class="sq-th sq-th--right">Importo</th>
                                <th class="sq-th">Metodo di pagamento</th>
                                <th class="sq-th">Stato</th>
                                <th class="sq-th sq-th--right">Azioni</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($ricariche as $r)
                                <tr>
                                    <td class="sq-td sq-fw-700">{{ $numeroOrdine($r) }}</td>
                                    <td class="sq-td sq-text-muted sq-nowrap">{{ $r->created_at?->timezone(config('app.timezone'))->format('d/m/Y H:i') ?? '—' }}</td>
                                    <td class="sq-td sq-td--right sq-wallet-ricariche-importo">{{ $fmt($r->importo) }} €</td>
                                    <td class="sq-td">{{ $r->metodoPagamento?->metodo_pagamento ?? '—' }}</td>
                                    <td class="sq-td">
                                        @if ($r->stato === 'accreditata')
                                            <span class="sq-wallet-ricariche-stato sq-wallet-ricariche-stato--pagato">Pagato</span>
                                        @else
                                            <span class="sq-wallet-ricariche-stato sq-wallet-ricariche-stato--non-pagato">Non pagato</span>
                                        @endif
                                    </td>
                                    <td class="sq-td sq-td--right">
                                        @if ($r->stato === 'in_attesa')
                                            <div class="sq-ordini-actions-icons">
                                                <button
                                                    type="button"
                                                    class="sq-ordini-icon-action sq-ordini-icon-action--pay"
                                                    disabled
                                                    title="Pagamento online in arrivo"
                                                    aria-label="Pagamento online in arrivo"
                                                >
                                                    <i class="fa-solid fa-credit-card" aria-hidden="true"></i>
                                                </button>
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
                @if ($ricariche->hasPages())
                    <div class="sq-wallet-extrato-pag">
                        {{ $ricariche->onEachSide(1)->links() }}
                    </div>
                @endif
            @endif
        </div>
    </div>
</div>
<script>
(() => {
    document.querySelectorAll('.js-wallet-extrato-periodo').forEach((sel) => {
        const wrapId = sel.getAttribute('data-custom-wrap');
        const wrap = wrapId ? document.getElementById(wrapId) : null;
        if (!wrap) return;
        const sync = () => wrap.classList.toggle('is-on', sel.value === 'custom');
        sel.addEventListener('change', sync);
        sync();
    });
})();
</script>
@endsection
