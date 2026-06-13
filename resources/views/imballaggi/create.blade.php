@extends('layouts.app')

@section('content')
<div style="padding: 20px 24px 40px; max-width: 560px;">
    <h1 style="color:#333; margin-top:0;">Nuovo Package</h1>
    <form action="{{ route('imballaggi.store') }}" method="POST" style="background:#fff; border:1px solid #e5e5e5; border-radius:12px; padding:24px;">
        @csrf
        @include('imballaggi._form', ['tipi' => $tipi])
        <div style="margin-top:20px; display:flex; gap:12px;">
            <button type="submit" style="background:#ff6600; color:#fff; border:0; padding:12px 20px; border-radius:8px; font-weight:700; cursor:pointer;">Salva</button>
            <a href="{{ route('imballaggi.index') }}" style="align-self:center; color:#666;">Annulla</a>
        </div>
    </form>
</div>
@endsection
