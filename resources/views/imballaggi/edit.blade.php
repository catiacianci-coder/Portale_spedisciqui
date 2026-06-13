@extends('layouts.app')

@section('content')
<div style="padding: 20px 24px 40px; max-width: 560px;">
    <h1 style="color:#333; margin-top:0;">Modifica Package</h1>
    <form action="{{ route('imballaggi.update', $imballaggio) }}" method="POST" style="background:#fff; border:1px solid #e5e5e5; border-radius:12px; padding:24px;">
        @csrf
        @method('PUT')
        @include('imballaggi._form', ['tipi' => $tipi, 'imballaggio' => $imballaggio])
        <div style="margin-top:20px; display:flex; gap:12px;">
            <button type="submit" style="background:#ff6600; color:#fff; border:0; padding:12px 20px; border-radius:8px; font-weight:700; cursor:pointer;">Aggiorna</button>
            <a href="{{ route('imballaggi.index') }}" style="align-self:center; color:#666;">Annulla</a>
        </div>
    </form>
</div>
@endsection
