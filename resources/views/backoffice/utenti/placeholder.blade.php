@extends('layouts.app')
@section('content')
<div class="sq-bo-utenti-page">
    <p class="sq-mb-16">
        <a href="{{ route('backoffice.utenti.index') }}" class="sq-link-back">← Torna all'elenco utenti</a>
    </p>
    <p>Utente #{{ $user->id }} — sezione «{{ $sectionLabel }}» (in preparazione).</p>
</div>
@endsection
