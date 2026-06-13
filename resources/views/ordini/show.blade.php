@extends('layouts.app')
@section('content')
<div class="sq-bleed-layout">
    <x-sq-page-banner title="Ordine {{ $ordine->codice }}" icon="fa-boxes" class="sq-page-banner--full" />
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
            'mostraSelezione' => true,
            'podeEditar' => $podeEditarSpedizioni ?? true,
            'servizioPerSpedizione' => $servizioPerSpedizione ?? [],
            'totaleIvatoOrdine' => $totaleIvatoOrdine ?? 0,
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

@if ($ordine->stato === \App\Models\ordine::STATO_NON_PAGATO && ($podeEditarSpedizioni ?? false))
<script>
(() => {
    const marcarTodos = document.getElementById('marcar-todos-spedizioni');
    const checkboxes = Array.prototype.slice.call(document.querySelectorAll('.chk-spedizione-ordine'));
    const btnElimina = document.getElementById('btn-elimina-spedizioni-marcate');
    const form = document.getElementById('form-elimina-spedizioni-marcate');
    if (!marcarTodos || !btnElimina || !form || !checkboxes.length) return;

    const syncButton = () => {
        btnElimina.disabled = !checkboxes.some((c) => c.checked);
    };

    marcarTodos.addEventListener('change', () => {
        checkboxes.forEach((c) => { c.checked = marcarTodos.checked; });
        syncButton();
    });

    checkboxes.forEach((c) => {
        c.addEventListener('change', () => {
            syncButton();
            const attive = checkboxes;
            marcarTodos.checked = attive.length > 0 && attive.every((x) => x.checked);
        });
    });

    form.addEventListener('submit', (e) => {
        if (!checkboxes.some((c) => c.checked)) {
            e.preventDefault();
            return;
        }
        if (!confirm('Vuoi eliminare le spedizioni selezionate dall\'ordine?')) {
            e.preventDefault();
        }
    });

    syncButton();
})();
</script>
@endif
@endsection
