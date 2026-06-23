@php
    use App\Support\ImportoEuro;

    $importoIvato = $importoIvato ?? null;
@endphp
<span class="sq-importo-ivato-val sq-fw-700 sq-nowrap">
    {{ ImportoEuro::format($importoIvato) }}
</span>
