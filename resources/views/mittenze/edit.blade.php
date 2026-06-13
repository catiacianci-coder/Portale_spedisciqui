@extends('layouts.app')

@section('content')
@include('mittenze._form', [
    'titolo' => 'Modifica mittente',
    'action' => route('mittenze.update', $mittenza),
    'method' => 'PUT',
    'mittenza' => $mittenza,
    'idComuneCorrente' => $idComuneCorrente,
    'tipoUtente' => $tipoUtente,
])
@endsection
