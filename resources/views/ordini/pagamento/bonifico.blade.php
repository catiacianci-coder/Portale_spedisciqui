@extends('layouts.app')
@section('content')
@php
    $totaleFmt = \App\Support\ImportoEuro::format((float) ($metodoJson['totale'] ?? 0));
@endphp
<x-ordine-pagamento-checkout-shell :ordine="$ordine" :metodo-json="$metodoJson">
    <h2 class="sq-ordine-pagamento-panel-title">Bonifico bancario</h2>

    <p class="sq-ordine-pagamento-total-label">Importo da bonificare:</p>
    <p class="sq-ordine-pagamento-total-value">{{ $totaleFmt }}</p>

    @include('ordini.partials.bonifico-pagamento-contenuto', [
        'iban' => $ibanBonifico ?? '',
        'chiaveCausale' => $ordine->chiave_causale,
        'formAction' => route('ordini.pagamento', $ordine),
        'metodoId' => (int) $metodoJson['id'],
    ])
</x-ordine-pagamento-checkout-shell>
@endsection
