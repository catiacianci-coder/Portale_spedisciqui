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
    .form-group input[type="email"] {
        width: 100%; padding: 0.8rem; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box; font-size: 1rem;
        background: #f9f9f9;
    }
    .btn-login {
        width: 100%; padding: 0.9rem; background-color: #FF6600; border: none; border-radius: 6px; color: white;
        font-size: 1.1rem; font-weight: bold; cursor: pointer; transition: background 0.3s ease; margin-top: 0.5rem; font-family: sans-serif;
    }
    .btn-login:hover { background-color: #e65c00; }
    .login-links { text-align: center; margin-top: 1.25rem; font-size: 0.95rem; font-family: sans-serif; }
    .login-links a { color: #FF6600; font-weight: 600; text-decoration: none; }
    .error-box { background-color: #fff5f5; color: #c53030; padding: 1rem; border-radius: 6px; border: 1px solid #feb2b2; margin-bottom: 1.5rem; font-size: 0.95rem; font-family: sans-serif; }
    .error-box ul { margin: 0; padding-left: 1.2rem; }
    .pwd-hint { font-size: 12px; color: #666; margin-top: 6px; line-height: 1.4; }
</style>

<div class="login-container">
    <div class="login-card">
        <h2>Nuova password</h2>
        <p class="login-intro">Scegli una nuova password per il tuo account.</p>

        @if ($errors->any())
            <div class="error-box">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('password.update') }}" method="POST">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="{{ old('email', $email) }}" required readonly autocomplete="username">
            </div>

            <div class="form-group">
                <label for="reset_password">Nuova password</label>
                <x-sq-password-input id="reset_password" name="password" autocomplete="new-password" :required="true" :minlength="8" />
                <p class="pwd-hint">
                    Minimo 8 caratteri, almeno un numero, una lettera maiuscola e un carattere speciale
                    (<span style="word-break:break-word;">{{ \App\Rules\PasswordPortale::SPECIALS_DISPLAY }}</span>).
                </p>
                @error('password')<span style="color:#c53030;font-size:13px;display:block;margin-top:6px;">{{ $message }}</span>@enderror
            </div>

            <div class="form-group">
                <label for="reset_password_confirmation">Ripeti password</label>
                <x-sq-password-input id="reset_password_confirmation" name="password_confirmation" autocomplete="new-password" :required="true" :minlength="8" />
                @error('password_confirmation')<span style="color:#c53030;font-size:13px;display:block;margin-top:6px;">{{ $message }}</span>@enderror
            </div>

            <button type="submit" class="btn-login">Salva nuova password</button>
        </form>

        <div class="login-links">
            <a href="{{ route('login') }}">Torna al login</a>
        </div>
    </div>
</div>
@endsection
