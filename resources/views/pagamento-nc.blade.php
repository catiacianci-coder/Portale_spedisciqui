@extends('layouts.app')
@section('content')
<div class="sq-page-640">
    <h1 class="sq-h1-carrello sq-mb-12">Pagamento non conformità</h1>
    <p class="sq-text-muted-14 sq-m-0 sq-mb-16">
        Pagina provvisoria: qui collegheremo gateway, bonifico e wallet secondo le modalità configurate.
    </p>
    <div class="sq-card sq-card--p-14-16">
        <p class="sq-m-0 sq-text-main"><strong>Parametri ricevuti</strong></p>
        <ul class="sq-m-8 sq-text-14">
            <li>Pratica ID: {{ $praticaId ?? '—' }}</li>
            <li>Tutta la pratica: {{ ! empty($tutto) ? 'sì' : 'no' }}</li>
            <li>Righe selezionate: {{ count($rigaIds) ? implode(', ', $rigaIds) : '—' }}</li>
        </ul>
        <p class="sq-m-0 sq-mt-12"><a href="{{ route('finanziario.nc.index') }}">Torna a Non conformità</a></p>
    </div>
</div>
@endsection
