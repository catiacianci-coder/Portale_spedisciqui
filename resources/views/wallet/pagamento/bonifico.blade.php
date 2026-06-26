@extends('layouts.app')
@section('content')
@php
    $totaleFmt = \App\Support\ImportoEuro::format((float) ($metodoJson['totale'] ?? 0));
    $numeroOrdine = $ricarica->numero_ordine_wallet ?? ('ORW-'.$ricarica->id);
@endphp
<div class="sq-bleed-layout">
    <x-sq-page-banner title="Pagamento ricarica {{ $numeroOrdine }}" icon="fa-credit-card" class="sq-page-banner--full" />
    <div class="ordine-show-page ordine-pagamento-page sq-wallet-ricarica-pagamento-show sq-mb-24">
        <x-wallet-ricarica-pagamento-shell :ricarica="$ricarica" :metodo-json="$metodoJson">
    <h2 class="sq-ordine-pagamento-panel-title">Bonifico bancario</h2>

    <p class="sq-ordine-pagamento-total-label">Importo da bonificare:</p>
    <p class="sq-ordine-pagamento-total-value">{{ $totaleFmt }}</p>

    @include('wallet.partials.bonifico-pagamento-contenuto', [
        'iban' => $ibanBonifico ?? '',
        'causaleBonifico' => $causaleBonifico ?? '',
        'formAction' => route('wallet.ricariche.pagamento.bonifico.store', $ricarica),
        'metodoId' => (int) $metodoJson['id'],
    ])
        </x-wallet-ricarica-pagamento-shell>
    </div>
</div>
@endsection
