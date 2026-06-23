@extends('layouts.app')
@section('content')
<div class="sq-bleed-layout">
    <x-sq-page-banner title="Ordine {{ $ordine->id }}" icon="fa-boxes" class="sq-page-banner--full" />
    <div class="ordine-show-page carrello-page sq-page-preventivi ordini-index-page">

    @if ($ordine->stato === \App\Models\ordine::STATO_ANNULLATO)
        <div class="sq-status-box is-annullato">
            Ordine annullato
            @if ($ordine->annullato_in)
                il {{ $ordine->annullato_in->format('d/m/Y H:i') }}
            @endif
            . Le spedizioni collegate risultano annullate.
        </div>
    @elseif ($ordine->stato === \App\Models\ordine::STATO_PAGATO)
        <div class="sq-status-box is-pagato">Ordine pagato il {{ $ordine->data_pagamento?->format('d/m/Y H:i') ?? '—' }}.</div>
    @endif

    @if (session('ok'))
        <div class="sq-alert sq-alert--success sq-mb-16">{{ session('ok') }}</div>
    @endif

    @if ($ordine->stato === \App\Models\ordine::STATO_NON_PAGATO)
        @include('ordini.partials.spedizioni-ordine-tabella', [
            'ordine' => $ordine,
            'mostraMittente' => false,
            'servizioPerSpedizione' => $servizioPerSpedizione ?? [],
            'totaleIvatoOrdine' => $totaleIvatoOrdine ?? 0,
            'totaleIvatoStandard' => $totaleIvatoStandard ?? 0,
            'totaleIvatoWallet' => $totaleIvatoWallet ?? 0,
            'mostraPrezziDuali' => $mostraPrezziDuali ?? true,
        ])
    @elseif ($ordine->stato === \App\Models\ordine::STATO_PAGATO)
        @include('ordini.partials.spedizioni-ordine-pagato-tabella', [
            'ordine' => $ordine,
            'servizioPerSpedizione' => $servizioPerSpedizione ?? [],
            'variant' => 'pagato',
        ])
    @elseif ($ordine->stato === \App\Models\ordine::STATO_ANNULLATO)
        @include('ordini.partials.spedizioni-ordine-pagato-tabella', [
            'ordine' => $ordine,
            'servizioPerSpedizione' => $servizioPerSpedizione ?? [],
            'variant' => 'annullato',
        ])
    @endif

    @if (in_array($ordine->stato, [\App\Models\ordine::STATO_PAGATO, \App\Models\ordine::STATO_ANNULLATO], true))
        @include('partials.spedizione-tracking-popup')
    @endif

    @if (session('mostra_popup_bonifico') && $ordine->stato === \App\Models\ordine::STATO_NON_PAGATO)
        @php
            $ibanBonificoShow = \App\Models\parametri_globali::valoreTesto(\App\Models\parametri_globali::DENOM_IBAN_CC_R_B);
        @endphp
        @include('partials.bonifico-pagamento-popup', [
            'modalId' => 'sq-bonifico-show-modal',
            'iban' => $ibanBonificoShow,
            'chiaveCausale' => $ordine->chiave_causale,
            'soloLettura' => true,
            'autoOpen' => true,
        ])
    @endif

    </div>
</div>
@endsection
