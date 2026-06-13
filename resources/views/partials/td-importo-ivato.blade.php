@php
    $importoIvato = $importoIvato ?? null;
@endphp
<span class="sq-importo-ivato-val sq-fw-700 sq-nowrap">
    {{ $importoIvato !== null ? number_format((float) $importoIvato, 2, ',', '.') : '—' }} €
</span>
