@extends('layouts.app')
@section('content')
@php
    $fmt = fn ($n) => number_format((float) $n, 2, ',', '.');
    $numeroOrdine = fn ($r) => $r->numero_ordine_wallet ?? ('ORW-'.$r->id);
@endphp

<div class="sq-bo-page-wrap sq-listing-page sq-wallet-ricariche-page sq-wallet-ricariche-page--bo">
    <p class="sq-wallet-ricariche-bo-lead">
        Richieste di ricarica (<code>ORW-…</code>). Per accreditare manualmente una ricarica in sospeso puoi usare anche
        <a href="{{ route('backoffice.wallet.cliente') }}">Movimenti wallet</a>.
    </p>

    @if (session('ok'))
        <div class="sq-alert sq-alert--success sq-mb-16">{{ session('ok') }}</div>
    @endif
    @if ($errors->has('backoffice'))
        <div class="sq-alert sq-alert--error sq-mb-16">{{ $errors->first('backoffice') }}</div>
    @endif

    @if ($customPeriodoSemDatas ?? false)
        <p class="sq-wallet-extrato-hint">Periodo personalizzato: indica le date <strong>Da</strong> e/o <strong>A</strong> e clicca il filtro.</p>
    @endif

    @include('wallet.partials.ricariche-filtri', [
        'formAction' => route('backoffice.ricariche.index'),
        'filtros' => array_merge($filtros, [
            'metodo_pagamento_id' => $filtros['metodo_pagamento_id'] ?? '',
        ]),
        'perPage' => $perPage,
        'showCliente' => true,
        'showStatoAnnullata' => true,
        'selectedUser' => $selectedUser ?? null,
        'metodosWallet' => $metodosWallet,
        'formId' => 'form-filtri-ricariche-bo',
        'periodoId' => 'filtro-ricariche-periodo-bo',
        'customWrapId' => 'filtro-ricariche-datas-custom-bo',
        'statoId' => 'filtro-ricariche-stato-bo',
        'perPageId' => 'filtro-ricariche-per-page-bo',
        'metodoId' => 'filtro-ricariche-metodo-bo',
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
                            <th class="sq-th">Cliente</th>
                            <th class="sq-th sq-th--right">Importo</th>
                            <th class="sq-th">Metodo di pagamento</th>
                            <th class="sq-th">Stato</th>
                            <th class="sq-th sq-th--right">Azione</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($ricariche as $r)
                            <tr @class(['sq-wallet-ricariche-row--annullata' => $r->stato === 'annullata'])>
                                <td class="sq-td sq-fw-700">{{ $numeroOrdine($r) }}</td>
                                <td class="sq-td sq-text-muted sq-nowrap">{{ $r->created_at?->timezone(config('app.timezone'))->format('d/m/Y H:i') ?? '—' }}</td>
                                <td class="sq-td">{{ $r->user?->email ?? '—' }}</td>
                                <td class="sq-td sq-td--right sq-wallet-ricariche-importo">{{ \App\Support\ImportoEuro::format($r->importo) }}</td>
                                <td class="sq-td">{{ \App\Support\WalletRicaricaMetodoPagamento::labelCliente($r) }}</td>
                                <td class="sq-td">
                                    @if ($r->stato === 'accreditata')
                                        <span class="sq-wallet-ricariche-stato sq-wallet-ricariche-stato--pagato">Pagato</span>
                                    @elseif ($r->stato === 'annullata')
                                        <span class="sq-wallet-ricariche-stato sq-wallet-ricariche-stato--annullata">Annullato</span>
                                    @else
                                        <span class="sq-wallet-ricariche-stato sq-wallet-ricariche-stato--non-pagato">Non pagato</span>
                                    @endif
                                </td>
                                <td class="sq-td sq-td--right">
                                    @if ($r->stato === 'in_attesa')
                                        @if (($metodiPagamentoAccredito ?? collect())->isEmpty())
                                            <span class="sq-text-muted sq-text-13">Nessun metodo attivo</span>
                                        @else
                                            <form
                                                method="POST"
                                                action="{{ route('backoffice.ricariche.accredita', $r->id) }}"
                                                class="sq-form-zero sq-wallet-ricariche-bo-paga-form"
                                                onsubmit="return confirm('Confermi il pagamento e l\'accredito di {{ \App\Support\ImportoEuro::format($r->importo) }} sul wallet del cliente?');"
                                            >
                                                @csrf
                                                @foreach ($queryParams as $name => $value)
                                                    <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                                                @endforeach
                                                <div class="sq-wallet-ricariche-bo-paga-row">
                                                    <label class="sq-sr-only" for="bo-ricarica-metodo-{{ $r->id }}">Metodo di pagamento</label>
                                                    <select
                                                        id="bo-ricarica-metodo-{{ $r->id }}"
                                                        name="metodo_pagamento_id"
                                                        class="sq-wallet-ricariche-bo-paga-select"
                                                        required
                                                    >
                                                        <option value="" disabled selected>Metodo…</option>
                                                        @foreach ($metodiPagamentoAccredito as $m)
                                                            <option value="{{ $m->id }}">{{ $m->metodo_pagamento }}</option>
                                                        @endforeach
                                                    </select>
                                                    <button type="submit" class="sq-wallet-ricariche-btn-paga">Paga</button>
                                                </div>
                                            </form>
                                        @endif
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
