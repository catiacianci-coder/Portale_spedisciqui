@extends('layouts.app')

@section('content')
@include('mittenze._form', [
    'titolo' => 'Nuovo mittente',
    'action' => route('mittenze.store'),
    'method' => 'POST',
    'mittenza' => null,
    'idComuneCorrente' => null,
    'tipoUtente' => $tipoUtente,
])
@endsection
