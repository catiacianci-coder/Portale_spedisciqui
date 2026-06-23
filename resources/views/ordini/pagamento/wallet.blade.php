@extends('layouts.app')
@section('content')
@php
    $totaleFmt = \App\Support\ImportoEuro::format((float) ($metodoJson['totale'] ?? 0));
@endphp
<x-ordine-pagamento-checkout-shell :ordine="$ordine" :metodo-json="$metodoJson">
    <h2 class="sq-ordine-pagamento-panel-title">Paga con Wallet</h2>

    <p class="sq-ordine-pagamento-panel-text">
        Verrà addebitato dal Wallet l&apos;importo di
        <strong class="sq-ordine-pagamento-inline-totale">{{ $totaleFmt }}</strong>.
    </p>

    @if (! ($walletSaldoOk ?? true))
        <div class="sq-alert sq-alert--error sq-mb-16">Saldo Wallet insufficiente per questo ordine.</div>
    @endif

    <form method="POST" action="{{ route('ordini.pagamento', $ordine) }}" class="sq-ordine-pagamento-panel-form">
        @csrf
        <input type="hidden" name="metodo_pagamento_id" value="{{ (int) $metodoJson['id'] }}">
        <input type="hidden" name="conferma_wallet" value="1">
        <button
            type="submit"
            class="sq-btn-primary sq-ordine-pagamento-submit-btn"
            @if (! ($walletSaldoOk ?? true)) disabled @endif
        >
            Conferma pagamento
        </button>
    </form>
</x-ordine-pagamento-checkout-shell>
@endsection
