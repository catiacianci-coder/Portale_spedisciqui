@extends('layouts.app')

@section('content')
@include('destinatari._form', [
    'titolo' => 'Nuovo destinatario',
    'action' => route('destinatari.store'),
    'method' => 'POST',
    'destinatario' => null,
    'idComuneCorrente' => null,
    'tipoUtente' => $tipoUtente,
])
@endsection
