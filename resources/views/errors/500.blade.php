@extends('errors.layout')

@section('title', 'Errore del server')

@section('content')
<div class="sq-error-page">
    <div class="sq-error-card">
        <p class="sq-error-code" aria-hidden="true">{{ $status ?? 500 }}</p>
        <h1 class="sq-error-title">Si è verificato un errore imprevisto</h1>
        <p class="sq-error-text">
            L’operazione non è stata completata a causa di un problema tecnico sul server.
            Se il problema si ripete, contatta l’amministratore del portale indicando cosa stavi facendo e l’orario dell’errore.
        </p>
        <div class="sq-error-actions">
            <a href="{{ url('/') }}" class="sq-btn-cta-lg sq-btn-cta-lg--button">Ritorna alla HomePage</a>
        </div>
    </div>
</div>
@endsection
