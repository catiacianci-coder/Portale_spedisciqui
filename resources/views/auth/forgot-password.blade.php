@extends('layouts.app')

@section('content')
<style>
    .login-container { display: flex; justify-content: center; align-items: center; padding: 50px 0; }
    .login-card {
        background: white; padding: 2.5rem; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        width: 100%; max-width: 400px; border-top: 5px solid #FF6600;
    }
    .login-card h2 { margin-bottom: 0.75rem; color: #333; text-align: center; font-weight: 600; font-family: sans-serif; }
    .login-card .login-intro { text-align: center; color: #666; font-size: 0.95rem; margin-bottom: 1.5rem; line-height: 1.45; font-family: sans-serif; }
    .form-group { margin-bottom: 1.2rem; }
    .form-group label { display: block; margin-bottom: 0.5rem; color: #555; font-weight: 500; font-family: sans-serif; }
    .form-group input {
        width: 100%; padding: 0.8rem; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box; font-size: 1rem;
    }
    .form-group input:focus { outline: none; border-color: #FF6600; box-shadow: 0 0 0 2px rgba(255, 102, 0, 0.2); }
    .btn-login {
        width: 100%; padding: 0.9rem; background-color: #FF6600; border: none; border-radius: 6px; color: white;
        font-size: 1.1rem; font-weight: bold; cursor: pointer; transition: background 0.3s ease; margin-top: 0.5rem; font-family: sans-serif;
    }
    .btn-login:hover { background-color: #e65c00; }
    .login-links { text-align: center; margin-top: 1.25rem; font-size: 0.95rem; font-family: sans-serif; }
    .login-links a { color: #FF6600; font-weight: 600; text-decoration: none; }
    .login-links a:hover { text-decoration: underline; }
    .error-box { background-color: #fff5f5; color: #c53030; padding: 1rem; border-radius: 6px; border: 1px solid #feb2b2; margin-bottom: 1.5rem; font-size: 0.95rem; font-family: sans-serif; }
    .error-box ul { margin: 0; padding-left: 1.2rem; }
    .success-box { background: #f0fdf4; color: #166534; padding: 1rem; border-radius: 6px; border: 1px solid #bbf7d0; margin-bottom: 1.5rem; font-size: 0.95rem; font-family: sans-serif; }
</style>

<div class="login-container">
    <div class="login-card">
        <h2>Recupera password</h2>

        @if ($errors->any())
            <div class="error-box">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (session('status'))
            <div class="success-box">{{ session('status') }}</div>
            <p class="login-intro" style="margin-bottom: 0.5rem;">Controlla anche la cartella spam.</p>
            <div class="login-links" style="margin-top: 0.75rem;">
                <a href="{{ route('login') }}">Torna al login</a>
                <span style="color:#999;"> · </span>
                <a href="{{ route('password.request') }}">Prova con un’altra email</a>
            </div>
        @else
            <p class="login-intro">Inserisci l’email del tuo account: ti invieremo un link per impostare una nuova password.</p>

            <form action="{{ route('password.email') }}" method="POST">
                @csrf
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" required autocomplete="email" placeholder="La tua email di registrazione">
                </div>
                <button type="submit" class="btn-login">Invia link</button>
            </form>

            <div class="login-links">
                <a href="{{ route('login') }}">Torna al login</a>
            </div>
        @endif
    </div>
</div>
@endsection
