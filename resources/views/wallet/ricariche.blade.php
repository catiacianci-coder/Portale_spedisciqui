@extends('layouts.app')
@section('content')
@php
    $numeroOrdine = fn ($r) => $r->numero_ordine_wallet ?? ('ORW-'.$r->id);
@endphp

<div class="sq-bleed-layout">
    <x-sq-page-banner title="Ricariche wallet" icon="fa-coins" class="sq-page-banner--full" />

    <div class="ordini-index-page sq-wallet-ricariche-page sq-listing-page">
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

        <div class="sq-ordini-tab-section" role="region" aria-label="Elenco ricariche">
            @if ($ricariche->total() === 0)
                <p class="sq-ordini-empty">
                    {{ ($hasActiveFilters ?? false) ? 'Nessuna ricarica con questi filtri.' : 'Nessuna ricarica registrata.' }}
                </p>
            @else
                @include('wallet.partials.ricariche-tabella', [
                    'ricariche' => $ricariche,
                    'numeroOrdine' => $numeroOrdine,
                    'hasMetodiPagamentoRicarica' => $hasMetodiPagamentoRicarica ?? true,
                ])

                @include('partials.tabella-paginazione', ['paginator' => $ricariche])
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
