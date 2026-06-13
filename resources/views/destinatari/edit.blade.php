@extends('layouts.app')

@section('content')
@include('destinatari._form', [
    'titolo' => 'Modifica destinatario',
    'action' => route('destinatari.update', $destinatario),
    'method' => 'PUT',
    'destinatario' => $destinatario,
    'idComuneCorrente' => $idComuneCorrente,
    'tipoUtente' => $tipoUtente,
])
@endsection
