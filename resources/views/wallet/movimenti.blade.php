@extends('layouts.app')
@section('content')
<div class="sq-bleed-layout sq-wallet-extrato-page sq-wallet-extrato-page--front">
    <x-sq-page-banner title="Estratto wallet" icon="fa-receipt" class="sq-page-banner--full" />

    <div class="sq-listing-page">
        <div class="sq-wallet-extrato-toolbar">
            <a href="{{ route('wallet.ricarica') }}" class="sq-wallet-extrato-btn-ricarica">
                <i class="fas fa-plus-circle" aria-hidden="true"></i>
                Nuova ricarica
            </a>
        </div>

        @if ($walletSaldoFormatado ?? null)
            <div class="sq-wallet-extrato-saldo" role="status" aria-live="polite">
                <div>
                    <div class="sq-wallet-extrato-saldo__label">Saldo wallet</div>
                    <div class="sq-wallet-extrato-saldo__valor">{{ $walletSaldoFormatado }}</div>
                </div>
            </div>
        @endif

        @if (! empty($customPeriodoSemDatas))
            <p class="sq-wallet-extrato-hint">Periodo personalizzato: indica le date <strong>Da</strong> e/o <strong>A</strong> e clicca il filtro.</p>
        @endif

        @include('wallet.partials.extrato-filtri', [
            'formAction' => $formAction,
            'filtros' => $filtros,
            'perPage' => $perPage,
            'tiposMovimento' => $tiposMovimento,
            'showUsuarioColumn' => false,
            'formId' => 'form-filtri-wallet-fo',
            'periodoId' => 'filtro-wallet-periodo-fo',
            'customWrapId' => 'filtro-wallet-datas-custom-fo',
            'tipoId' => 'filtro-wallet-tipo-fo',
            'perPageId' => 'filtro-wallet-per-page-fo',
        ])

        @include('wallet.partials.extrato-contenuto', [
            'linhas' => $linhas,
            'showUsuarioColumn' => false,
            'hasActiveFilters' => $hasActiveFilters,
            'walletSaldoFormatado' => null,
        ])
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
