@extends('layouts.app')

@section('content')
<div class="sq-profilo-page sq-profilo-page--password">
    <h1 class="sq-h1-carrello sq-text-heading sq-mb-8">Cambia password</h1>

    @if (session('ok'))
        <div class="sq-alert sq-alert--success sq-mb-14">{{ session('ok') }}</div>
    @endif

    <form method="POST" action="{{ route('profilo.password.update') }}" class="sq-profilo-password-form" autocomplete="off">
        @csrf

        <div class="sq-profilo-card sq-profilo-card--stacked sq-profilo-password-card">
            <div class="sq-profilo-card-head">
                <h2 class="sq-profilo-card-title">Nuova password</h2>
            </div>

            <div class="sq-profilo-card-stack">
                <div class="sq-profilo-kv sq-profilo-kv--form-row">
                    <label for="current_password" class="sq-profilo-k">Password attuale <span class="sq-profilo-req">*</span></label>
                    <div class="sq-profilo-kv-field">
                        <x-sq-password-input id="current_password" name="current_password" input-class="sq-profilo-input" autocomplete="current-password" :required="true" />
                        @error('current_password')<span class="sq-profilo-err">{{ $message }}</span>@enderror
                    </div>
                </div>

                <div class="sq-profilo-kv sq-profilo-kv--form-row">
                    <label for="password" class="sq-profilo-k">Nuova password <span class="sq-profilo-req">*</span></label>
                    <div class="sq-profilo-kv-field">
                        <x-sq-password-input id="password" name="password" input-class="sq-profilo-input" autocomplete="new-password" :required="true" :minlength="8" />
                        <span class="sq-profilo-hint sq-text-muted sq-text-12 sq-profilo-kv-hint">
                            Minimo 8 caratteri, almeno un numero, una lettera maiuscola e un carattere speciale
                            (<span class="sq-profilo-password-specials">{{ \App\Rules\PasswordPortale::SPECIALS_DISPLAY }}</span>).
                        </span>
                        @error('password')<span class="sq-profilo-err">{{ $message }}</span>@enderror
                    </div>
                </div>

                <div class="sq-profilo-kv sq-profilo-kv--form-row">
                    <label for="password_confirmation" class="sq-profilo-k">Conferma nuova password <span class="sq-profilo-req">*</span></label>
                    <div class="sq-profilo-kv-field">
                        <x-sq-password-input id="password_confirmation" name="password_confirmation" input-class="sq-profilo-input" autocomplete="new-password" :required="true" :minlength="8" />
                        @error('password_confirmation')<span class="sq-profilo-err">{{ $message }}</span>@enderror
                    </div>
                </div>
            </div>

            <div class="sq-profilo-password-actions">
                <button type="submit" class="sq-btn-primary sq-profilo-btn-sm">Salva password</button>
            </div>
        </div>
    </form>
</div>
@endsection
