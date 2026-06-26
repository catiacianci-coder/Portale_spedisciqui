@extends('layouts.app')

@section('content')
<div class="sq-profilo-page sq-profilo-page--password">
    <h1 class="sq-h1-carrello sq-text-heading sq-mb-8">Cambia password</h1>

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
                <button type="reset" class="sq-btn-secondary sq-modal-btn sq-profilo-btn-sm" id="btn-annulla-password">Annulla</button>
                <a href="{{ route('home') }}" class="sq-btn-secondary sq-modal-btn sq-profilo-btn-sm">Esci</a>
                <button type="submit" class="sq-btn-primary sq-profilo-btn-sm">Salva password</button>
            </div>
        </div>
    </form>
</div>

@if (session('password_saved'))
    <div id="sq-profilo-password-ok-modal" class="sq-modal sq-modal--profilo-password-ok" data-profilo-password-ok-modal>
        <div class="sq-modal-backdrop js-profilo-password-ok-home" tabindex="-1" aria-hidden="true"></div>
        <div class="sq-modal-panel" role="dialog" aria-modal="true" aria-labelledby="sq-profilo-password-ok-title">
            <h2 id="sq-profilo-password-ok-title" class="sq-modal-title">Password aggiornata</h2>
            <p class="sq-modal-text sq-m-0 sq-mb-16">La nuova password è stata salvata correttamente.</p>
            <div class="sq-modal-actions">
                <a href="{{ route('home') }}" class="sq-btn-primary sq-modal-btn">OK</a>
            </div>
        </div>
    </div>
    <script>
    (() => {
        const modal = document.querySelector('[data-profilo-password-ok-modal]');
        if (!modal) return;
        const homeUrl = @json(route('home'));
        modal.hidden = false;
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('sq-modal-open');
        modal.querySelectorAll('.js-profilo-password-ok-home').forEach((el) => {
            el.addEventListener('click', () => { window.location.href = homeUrl; });
        });
    })();
    </script>
@endif
@endsection
