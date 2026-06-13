@extends('layouts.app')

@section('content')
<style>
    .verify-container {
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 60px 0;
        font-family: sans-serif;
    }
    .verify-card { 
        background: white; 
        padding: 2.5rem; 
        border-radius: 12px; 
        box-shadow: 0 10px 25px rgba(0,0,0,0.1); 
        width: 100%; 
        max-width: 500px; 
        border-top: 5px solid #FF6600; 
        text-align: center;
    }
    .verify-card h2 { 
        margin-bottom: 1.5rem; 
        color: #333; 
        font-weight: 600;
    }
    .verify-card p {
        color: #555;
        line-height: 1.6;
        margin-bottom: 20px;
    }
    .alert-success { 
        background-color: #f0fff4; 
        color: #276749; 
        padding: 1rem; 
        border-radius: 6px; 
        border: 1px solid #c6f6d5;
        margin-bottom: 1.5rem; 
        font-size: 0.95rem; 
    }
    .btn-resend { 
        width: 100%; 
        padding: 0.9rem; 
        background-color: #FF6600; 
        border: none; 
        border-radius: 6px; 
        color: white; 
        font-size: 1rem; 
        font-weight: bold;
        cursor: pointer; 
        transition: background 0.3s ease;
        margin-top: 1rem;
    }
    .btn-resend:hover { 
        background-color: #e65c00; 
    }
    hr {
        border: 0;
        border-top: 1px solid #eee;
        margin: 20px 0;
    }
</style>

<div class="verify-container">
    <div class="verify-card">
        <h2>Verifica la tua Email</h2>

        @if (session('message'))
            <div class="alert-success" role="alert">
                {{ session('message') }}
            </div>
        @endif

        <h4>Quasi fatto!</h4>
        <p>Prima di procedere, controlla la tua casella di posta: ti abbiamo inviato un link di verifica.</p>
        
        <hr>
        
        <p>Se non hai ricevuto l'email, clicca il tasto qui sotto per richiederne una nuova.</p>
        
        <form method="POST" action="{{ route('verification.resend') }}">
            @csrf
            <button type="submit" class="btn-resend">Invia di nuovo email di verifica</button>
        </form>
    </div>
</div>
@endsection