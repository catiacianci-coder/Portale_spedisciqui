@extends('layouts.app')

@section('content')
@php
    $step2 = (isset($current_step) && (int) $current_step === 2) || (int) session('current_step', 0) === 2;
    $tempData = session('temp_user_data', []);
    $tipo = $tempData['tipo_utente'] ?? auth()->user()?->tipo_utente ?? 'privato';
    $turnstileEnabled = \App\Services\TurnstileVerifier::isConfigured();
    $turnstileSiteKey = \App\Services\ParametriApiConfig::turnstileSiteKey();
@endphp

<div class="register-container">
    @if (session('info_anagrafica'))
        <div class="sq-notice-iva sq-mb-18 sq-text-14">
            {{ session('info_anagrafica') }}
        </div>
    @endif

    {{-- STEP 1: Account --}}
    <form action="{{ route('register.store') }}" method="POST" id="form_step1" class="{{ $step2 ? 'hidden' : '' }}">
        @csrf
        <h2>Crea il tuo Account</h2>
        <label>Email <span>*</span></label>
        <input type="email" name="email" value="{{ old('email') }}" required>
        @error('email') <span class="error-validation">{{ $message }}</span> @enderror

        <label for="register_password">Password <span>*</span></label>
        <x-sq-password-input id="register_password" name="password" autocomplete="new-password" :required="true" :minlength="8" />
        <p class="sq-register-password-hint">
            Minimo 8 caratteri, almeno un numero, una lettera maiuscola e un carattere speciale
            (<span class="sq-register-password-specials">{{ \App\Rules\PasswordPortale::SPECIALS_DISPLAY }}</span>).
        </p>
        @error('password') <span class="error-validation">{{ $message }}</span> @enderror

        <label for="register_password_confirmation">Ripeti password <span>*</span></label>
        <x-sq-password-input id="register_password_confirmation" name="password_confirmation" autocomplete="new-password" :required="true" :minlength="8" />
        @error('password_confirmation') <span class="error-validation">{{ $message }}</span> @enderror

        <label>Tipo Utente <span>*</span></label>
        <select name="tipo_utente" id="tipo_utente_select" required>
            <option value="privato" {{ old('tipo_utente') == 'privato' ? 'selected' : '' }}>Privato</option>
            <option value="ditta" {{ old('tipo_utente') == 'ditta' ? 'selected' : '' }}>Ditta Individuale</option>
            <option value="professionista" {{ old('tipo_utente') == 'professionista' ? 'selected' : '' }}>Professionista</option>
            <option value="societa" {{ old('tipo_utente') == 'societa' ? 'selected' : '' }}>Società / Impresa</option>
        </select>

        @if ($turnstileEnabled)
            <div class="register-turnstile-wrap">
                <div class="cf-turnstile" data-sitekey="{{ $turnstileSiteKey }}"></div>
            </div>
            @error('cf-turnstile-response')
                <span class="error-validation">{{ $message }}</span>
            @enderror
        @endif

        <button type="submit">Continua</button>
    </form>

    {{-- STEP 2 e 3: Anagrafica --}}
    <form action="{{ route('anagrafica.update') }}" method="POST" id="form_step2" class="{{ $step2 ? '' : 'hidden' }}">
        @csrf
        <h2>Dati Intestatario</h2>
        
        <div id="cf_error"></div>

        <label>Codice Fiscale <span>*</span></label>
        <div class="cf-row">
            @php $max = ($tipo == 'societa') ? 11 : 16; @endphp
            <input type="text" id="cf_input" name="codice_fiscale" value="{{ old('codice_fiscale') }}" required maxlength="{{ $max }}" oninput="handleCFInput()">
            <button type="button" id="btn_verify_cf" class="btn-verify" onclick="verifyCF()" disabled>Verifica</button>
        </div>
        <p id="cf_hint" class="sq-register-cf-hint">Inserisci il codice fiscale per caricare i dati.</p>

        <div id="fase3_fields" class="hidden">
            
            @if($tipo !== 'privato')
                <label>Ragione Sociale / Denominazione <span>*</span></label>
                <input type="text" id="denominazione_ragione_sociale" name="denominazione_ragione_sociale" required>

                <label>Partita IVA <span>*</span></label>
                <input type="text" id="partita_iva" name="partita_iva" maxlength="11" required>
            @endif

            <div class="row">
                <div>
                    <label>Nome <span>*</span></label>
                    <input type="text" id="nome" name="nome" required>
                </div>
                <div>
                    <label>Cognome <span>*</span></label>
                    <input type="text" id="cognome" name="cognome" required>
                </div>
            </div>

            <div class="register-row--addr">
                <div>
                    <label>Indirizzo <span>*</span></label>
                    <input type="text" id="indirizzo" name="indirizzo" required>
                </div>
                <div>
                    <label>Civico <span>*</span></label>
                    <input type="text" id="civico" name="civico" required>
                </div>
            </div>
            
            @livewire('comune')

            <label>Telefono <span>*</span></label>
            <input type="text" id="telefono" name="telefono" required>

            @if($tipo !== 'privato')
                <div class="row">
                    <div>
                        <label>PEC</label>
                        <input type="email" id="pec" name="pec">
                    </div>
                    <div>
                        <label>Codice SDI</label>
                        <input type="text" id="codice_sdi" name="codice_sdi" maxlength="7">
                    </div>
                </div>
            @endif

            <button type="submit" class="register-btn-step2">Salva e Completa</button>
        </div>
    </form>
</div>

@if ($turnstileEnabled)
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
@endif

<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script>
    const csrfMeta = document.querySelector('meta[name="csrf-token"]');
    if (csrfMeta) {
        axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfMeta.getAttribute('content');
        axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
        axios.defaults.headers.common['Accept'] = 'application/json';
    }
    function handleCFInput() {
        const cfInput = document.getElementById('cf_input');
        const btnVerify = document.getElementById('btn_verify_cf');
        const tipoUtente = "{{ $tipo }}";
        const targetLen = (tipoUtente === 'societa') ? 11 : 16;

        cfInput.value = cfInput.value.toUpperCase().replace(/\s/g, '');
        btnVerify.disabled = (cfInput.value.length !== targetLen);
        btnVerify.style.backgroundColor = btnVerify.disabled ? "#ccc" : "#FF6600";
    }

    function verifyCF() {
        const cf = document.getElementById('cf_input').value;
        const btn = document.getElementById('btn_verify_cf');
        const tipoUtente = "{{ $tipo }}";
        
        btn.innerText = "Verifica...";
        btn.disabled = true;

        axios.post("{{ route('anagrafica.check') }}", { codice_fiscale: cf })
        .then(response => {
            if (response.data.status === 'success') {
                const d = response.data.dati;
                
                if(document.getElementById('denominazione_ragione_sociale')) 
                    document.getElementById('denominazione_ragione_sociale').value = d.denominazione_ragione_sociale || '';
                
                if(document.getElementById('partita_iva')) 
                    document.getElementById('partita_iva').value = d.partita_iva || '';

                if(tipoUtente !== 'privato') {
                    document.getElementById('nome').value = '';
                    document.getElementById('cognome').value = '';
                } else {
                    document.getElementById('nome').value = d.nome || '';
                    document.getElementById('cognome').value = d.cognome || '';
                }

                document.getElementById('indirizzo').value = d.indirizzo || '';
                document.getElementById('civico').value = d.civico || '';
                
                // IMPORTANTE: Questi ID devono corrispondere a quelli nel componente Livewire
                if(document.getElementById('cap')) document.getElementById('cap').value = d.cap || '';
                if(document.getElementById('citta')) document.getElementById('citta').value = d.citta || '';
                if(document.getElementById('provincia')) document.getElementById('provincia').value = d.provincia || '';
                
                document.getElementById('telefono').value = d.telefono || '';
                
                if(document.getElementById('pec'))
                    document.getElementById('pec').value = d.pec || '';
                
                if(document.getElementById('codice_sdi'))
                    document.getElementById('codice_sdi').value = d.codice_sdi || '';
                
                showFase3();
            } else {
                alert(response.data.message);
                btn.innerText = "Verifica";
                btn.disabled = false;
            }
        })
        .catch(error => {
            const data = error.response?.data;
            const msg = (typeof data === 'object' && data?.error)
                ? data.error
                : (data?.message || 'Errore di connessione o verifica non riuscita.');
            document.getElementById('cf_error').innerText = msg;
            document.getElementById('cf_error').style.display = 'block';
            btn.innerText = "Verifica";
            btn.disabled = false;
        });
    }

    function showFase3() {
        document.getElementById('cf_error').style.display = 'none';
        document.getElementById('fase3_fields').classList.remove('hidden');
        document.getElementById('cf_input').readOnly = true;
        document.getElementById('btn_verify_cf').style.display = 'none';
        document.getElementById('cf_hint').innerText = "Verifica completata. Controlla i dati obbligatori prima di salvare.";
    }
</script>
@endsection