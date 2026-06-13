@extends('layouts.app')

@section('content')
<style>
    .login-container {
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 50px 0;
    }
    .login-card { 
        background: white; 
        padding: 2.5rem; 
        border-radius: 12px; 
        box-shadow: 0 10px 25px rgba(0,0,0,0.1); 
        width: 100%; 
        max-width: 400px; 
        border-top: 5px solid #FF6600; 
    }
    .login-card h2 { 
        margin-bottom: 1.5rem; 
        color: #333; 
        text-align: center;
        font-weight: 600;
        font-family: sans-serif;
    }
    .form-group { margin-bottom: 1.2rem; }
    .form-group label { 
        display: block; 
        margin-bottom: 0.5rem; 
        color: #555; 
        font-weight: 500;
        font-family: sans-serif;
    }
    .form-group input { 
        width: 100%; 
        padding: 0.8rem; 
        border: 1px solid #ddd; 
        border-radius: 6px; 
        box-sizing: border-box; 
        font-size: 1rem;
    }
    .form-group input:focus {
        outline: none;
        border-color: #FF6600;
        box-shadow: 0 0 0 2px rgba(255, 102, 0, 0.2);
    }
    .btn-login { 
        width: 100%; 
        padding: 0.9rem; 
        background-color: #FF6600; 
        border: none; 
        border-radius: 6px; 
        color: white; 
        font-size: 1.1rem; 
        font-weight: bold;
        cursor: pointer; 
        transition: background 0.3s ease;
        margin-top: 1rem;
        font-family: sans-serif;
    }
    .btn-login:hover { 
        background-color: #e65c00; 
    }
    .error-box { 
        background-color: #fff5f5; 
        color: #c53030; 
        padding: 1rem; 
        border-radius: 6px; 
        border: 1px solid #feb2b2;
        margin-bottom: 1.5rem; 
        font-size: 0.95rem; 
        font-family: sans-serif;
    }
    .error-box ul { margin: 0; padding-left: 1.2rem; }
    .login-forgot { text-align: right; margin-top: 0.35rem; margin-bottom: 0.25rem; font-size: 0.9rem; font-family: sans-serif; }
    .login-forgot a { color: #FF6600; font-weight: 600; text-decoration: none; }
    .login-forgot a:hover { text-decoration: underline; }
    .login-register-hint {
        text-align: right;
        margin: 0.35rem 0 0;
        font-size: 0.75rem;
        line-height: 1.35;
        color: #666;
        font-family: sans-serif;
    }
    .login-register-hint a { color: #FF6600; font-weight: 600; text-decoration: none; }
    .login-register-hint a:hover { text-decoration: underline; }
</style>

<div class="login-container">
    <div class="login-card">
        <h2>Accedi al Portale</h2>

        @if (session('status'))
            <div class="success-box" style="background:#f0fdf4;color:#166534;padding:1rem;border-radius:6px;border:1px solid #bbf7d0;margin-bottom:1.5rem;font-size:0.95rem;font-family:sans-serif;">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="error-box">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('login.post') }}" method="POST">
            @csrf
            
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" required placeholder="Inserisci la tua email">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <x-sq-password-input id="password" name="password" autocomplete="current-password" :required="true" placeholder="Inserisci la password" />
                <div class="login-forgot">
                    <a href="{{ route('password.request') }}">Password dimenticata?</a>
                </div>
                <p class="login-register-hint">
                    Non hai un account? <a href="{{ route('register') }}">Clicca qui</a> per registrarti.
                </p>
            </div>

            <button type="submit" class="btn-login">ACCEDI</button>
        </form>
    </div>
</div>
@endsection