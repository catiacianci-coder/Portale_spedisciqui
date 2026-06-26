@extends('layouts.app')
@section('content')
<div class="sq-bo-utenti-page sq-bo-ana-page">
    <p class="sq-mb-16">
        <a href="{{ route('backoffice.utenti.index') }}" class="sq-link-back">← Torna all'elenco utenti</a>
    </p>

    @if (session('ok'))
        <div class="sq-alert sq-alert--success sq-mb-16">{{ session('ok') }}</div>
    @endif
    @if ($errors->has('anagrafica'))
        <div class="sq-alert sq-alert--error sq-mb-16">{{ $errors->first('anagrafica') }}</div>
    @endif

    <div class="sq-bo-ana-hab-bar">
        <h2 class="sq-bo-ana-title">Utente #{{ $user->id }} — {{ $user->displayNameForBackoffice() }}</h2>
        <p class="sq-bo-ana-hab-text">
            @if ($user->is_account_disabled)
                Postagem automatica <strong>disabilitata</strong>.
                @if ($user->postagem_bloqueado_pelo_bo)
                    Blocco imposto dall'operatore back office.
                @else
                    Nuovo utente in attesa di prima abilitazione.
                @endif
            @else
                Postagem automatica <strong>abilitata</strong>: dopo il pagamento le etichette possono essere generate.
            @endif
        </p>
        <form method="POST" action="{{ route('backoffice.utenti.habilitacao_postagem.toggle', $user) }}">
            @csrf
            <button type="submit" class="sq-bo-btn-link {{ $user->is_account_disabled ? 'sq-bo-btn-green' : 'sq-bo-btn-red' }}">
                {{ $user->is_account_disabled ? 'Abilita postagem' : 'Disabilita postagem' }}
            </button>
        </form>
    </div>

    <div class="sq-bo-ana-hab-bar sq-mb-16">
        <p class="sq-bo-ana-hab-text sq-m-0">
            Tariffe Liccardi:
            @if ($user->is_liccardi)
                <strong>abilitate</strong> — il cliente vede i preventivi Liccardi con tariffa scontata.
            @else
                <strong>non abilitate</strong> — i preventivi Liccardi non sono visibili.
            @endif
        </p>
        <form method="POST" action="{{ route('backoffice.utenti.liccardi.toggle', $user) }}">
            @csrf
            <button type="submit" class="sq-bo-btn-link {{ $user->is_liccardi ? 'sq-bo-btn-red' : 'sq-bo-btn-green' }}">
                {{ $user->is_liccardi ? 'Disabilita Liccardi' : 'Abilita Liccardi' }}
            </button>
        </form>
    </div>

    @if ($anagraficaAttiva)
        @php
            $a = $anagraficaAttiva;
            $tipo = $user->tipo_utente ?? 'privato';
            $isImpresa = $tipo !== 'privato';
            $titoloDati = $isImpresa ? 'Dati azienda e referente' : 'Dati personali';
        @endphp

        <form method="POST" action="{{ route('backoffice.utenti.anagrafica.update', $user) }}" id="form-bo-anagrafica" class="sq-profilo-page sq-bo-ana-form" autocomplete="off">
            @csrf
            <input type="hidden" name="id_comune" id="id_comune_bo_anag" value="{{ old('id_comune', $idComuneCorrente !== null ? (string) $idComuneCorrente : '') }}">

            <div class="sq-profilo-cards-grid">
                <div class="sq-profilo-card sq-profilo-card--stacked" id="bo-card-dati">
                    <div class="sq-profilo-card-head">
                        <h3 class="sq-profilo-card-title">{{ $titoloDati }}</h3>
                    </div>
                    <div class="sq-profilo-card-stack">
                        <div class="sq-profilo-card-view" id="bo-view-dati" aria-hidden="false">
                            <div class="sq-profilo-kv"><span class="sq-profilo-k">Email</span><span class="sq-profilo-v">{{ e($user->email) }}</span></div>
                            @if ($isImpresa)
                                <div class="sq-profilo-kv"><span class="sq-profilo-k">Ragione sociale</span><span class="sq-profilo-v" id="bo-disp-denominazione">{{ e($a->denominazione_ragione_sociale ?? '—') }}</span></div>
                                <div class="sq-profilo-kv"><span class="sq-profilo-k">P. IVA</span><span class="sq-profilo-v" id="bo-disp-partita_iva">{{ e($a->partita_iva ?? '—') }}</span></div>
                                <div class="sq-profilo-kv"><span class="sq-profilo-k">PEC</span><span class="sq-profilo-v" id="bo-disp-pec">{{ e($a->pec ?? '—') }}</span></div>
                                <div class="sq-profilo-kv"><span class="sq-profilo-k">Codice SDI</span><span class="sq-profilo-v" id="bo-disp-codice_sdi">{{ e($a->codice_sdi ?? '—') }}</span></div>
                            @endif
                            <div class="sq-profilo-kv"><span class="sq-profilo-k">Codice fiscale</span><span class="sq-profilo-v">{{ e($a->codice_fiscale ?? '—') }}</span></div>
                            <div class="sq-profilo-kv"><span class="sq-profilo-k">Nome</span><span class="sq-profilo-v" id="bo-disp-nome">{{ e($a->nome ?? '—') }}</span></div>
                            <div class="sq-profilo-kv"><span class="sq-profilo-k">Cognome</span><span class="sq-profilo-v" id="bo-disp-cognome">{{ e($a->cognome ?? '—') }}</span></div>
                            <div class="sq-profilo-kv"><span class="sq-profilo-k">Telefono</span><span class="sq-profilo-v" id="bo-disp-telefono">{{ e($a->telefono ?? '—') }}</span></div>
                            <div class="sq-profilo-kv"><span class="sq-profilo-k">Sede Liccardi</span><span class="sq-profilo-v" id="bo-disp-sede_liccardi">{{ ($a->sede_liccardi ?? false) ? 'Sì' : 'No' }}</span></div>
                        </div>

                        <div class="sq-profilo-card-form sq-profilo-hidden" id="bo-edit-dati" aria-hidden="true">
                            <div class="sq-profilo-field">
                                <label class="sq-profilo-label">Email</label>
                                <input type="email" class="sq-profilo-input sq-profilo-input--ro" value="{{ e($user->email) }}" readonly disabled tabindex="-1">
                            </div>
                            @if ($isImpresa)
                                <div class="sq-profilo-field">
                                    <label for="bo_denominazione_ragione_sociale" class="sq-profilo-label">Ragione sociale / denominazione <span class="sq-profilo-req">*</span></label>
                                    <input type="text" name="denominazione_ragione_sociale" id="bo_denominazione_ragione_sociale" class="sq-profilo-input" required maxlength="255"
                                           value="{{ old('denominazione_ragione_sociale', $a->denominazione_ragione_sociale) }}">
                                    @error('denominazione_ragione_sociale')<span class="sq-profilo-err">{{ $message }}</span>@enderror
                                </div>
                                <div class="sq-profilo-field">
                                    <label for="bo_partita_iva" class="sq-profilo-label">Partita IVA <span class="sq-profilo-req">*</span></label>
                                    <input type="text" name="partita_iva" id="bo_partita_iva" class="sq-profilo-input" required maxlength="11" inputmode="numeric"
                                           value="{{ old('partita_iva', $a->partita_iva) }}">
                                    @error('partita_iva')<span class="sq-profilo-err">{{ $message }}</span>@enderror
                                </div>
                                <div class="sq-profilo-field">
                                    <label for="bo_pec" class="sq-profilo-label">PEC</label>
                                    <input type="email" name="pec" id="bo_pec" class="sq-profilo-input" maxlength="255" value="{{ old('pec', $a->pec) }}">
                                    @error('pec')<span class="sq-profilo-err">{{ $message }}</span>@enderror
                                </div>
                                <div class="sq-profilo-field">
                                    <label for="bo_codice_sdi" class="sq-profilo-label">Codice SDI</label>
                                    <input type="text" name="codice_sdi" id="bo_codice_sdi" class="sq-profilo-input" maxlength="7" value="{{ old('codice_sdi', $a->codice_sdi) }}">
                                    @error('codice_sdi')<span class="sq-profilo-err">{{ $message }}</span>@enderror
                                </div>
                            @endif
                            <div class="sq-profilo-field">
                                <label for="bo_nome" class="sq-profilo-label">Nome <span class="sq-profilo-req">*</span></label>
                                <input type="text" name="nome" id="bo_nome" class="sq-profilo-input" required maxlength="255" value="{{ old('nome', $a->nome) }}">
                                @error('nome')<span class="sq-profilo-err">{{ $message }}</span>@enderror
                            </div>
                            <div class="sq-profilo-field">
                                <label for="bo_cognome" class="sq-profilo-label">Cognome <span class="sq-profilo-req">*</span></label>
                                <input type="text" name="cognome" id="bo_cognome" class="sq-profilo-input" required maxlength="255" value="{{ old('cognome', $a->cognome) }}">
                                @error('cognome')<span class="sq-profilo-err">{{ $message }}</span>@enderror
                            </div>
                            <div class="sq-profilo-field">
                                <label for="bo_telefono" class="sq-profilo-label">Telefono <span class="sq-profilo-req">*</span></label>
                                <input type="tel" name="telefono" id="bo_telefono" class="sq-profilo-input" required maxlength="20" value="{{ old('telefono', $a->telefono) }}">
                                @error('telefono')<span class="sq-profilo-err">{{ $message }}</span>@enderror
                            </div>
                            <div class="sq-profilo-field">
                                <label class="sq-profilo-label sq-profilo-label--check">
                                    <input type="hidden" name="sede_liccardi" value="0">
                                    <input type="checkbox" name="sede_liccardi" id="bo_sede_liccardi" value="1"
                                           @checked(old('sede_liccardi', $a->sede_liccardi ?? false))>
                                    Sede Liccardi
                                </label>
                                @error('sede_liccardi')<span class="sq-profilo-err">{{ $message }}</span>@enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="sq-profilo-card sq-profilo-card--stacked" id="bo-card-indirizzo">
                    <div class="sq-profilo-card-head">
                        <h3 class="sq-profilo-card-title">Indirizzo</h3>
                    </div>
                    <div class="sq-profilo-card-stack">
                        <div class="sq-profilo-card-view" id="bo-view-indirizzo" aria-hidden="false">
                            <div class="sq-profilo-kv"><span class="sq-profilo-k">Città</span><span class="sq-profilo-v" id="bo-disp-citta">{{ e($a->citta ?? '—') }}</span></div>
                            <div class="sq-profilo-kv"><span class="sq-profilo-k">CAP</span><span class="sq-profilo-v" id="bo-disp-cap">{{ e($a->cap ?? '—') }}</span></div>
                            <div class="sq-profilo-kv"><span class="sq-profilo-k">Provincia</span><span class="sq-profilo-v" id="bo-disp-provincia">{{ e($a->provincia ?? '—') }}</span></div>
                            <div class="sq-profilo-kv"><span class="sq-profilo-k">Strada</span><span class="sq-profilo-v" id="bo-disp-indirizzo">{{ e($a->indirizzo ?? '—') }}</span></div>
                            <div class="sq-profilo-kv"><span class="sq-profilo-k">Numero</span><span class="sq-profilo-v" id="bo-disp-civico">{{ e($a->civico ?? '—') }}</span></div>
                        </div>

                        <div class="sq-profilo-card-form sq-profilo-hidden" id="bo-edit-indirizzo" aria-hidden="true">
                            <div class="sq-profilo-field sq-profilo-field--suggest sq-profilo-mb-15">
                                <label for="bo_citta" class="sq-profilo-label">Città <span class="sq-profilo-req">*</span></label>
                                <input type="text" name="citta" id="bo_citta" class="sq-profilo-input" required maxlength="255"
                                       placeholder="Inizia a scrivere il comune…" value="{{ old('citta', $a->citta) }}">
                                <div class="sq-profilo-suggest" id="suggest_bo_citta" hidden></div>
                                @error('citta')<span class="sq-profilo-err">{{ $message }}</span>@enderror
                            </div>
                            <div class="sq-profilo-cap-pv-row">
                                <div class="sq-profilo-field sq-profilo-field--suggest sq-profilo-mb-0">
                                    <label for="bo_cap" class="sq-profilo-label">CAP <span class="sq-profilo-req">*</span></label>
                                    <input type="text" name="cap" id="bo_cap" class="sq-profilo-input" required maxlength="5" inputmode="numeric" placeholder="CAP…"
                                           value="{{ old('cap', $a->cap) }}">
                                    <div class="sq-profilo-suggest" id="suggest_bo_cap" hidden></div>
                                    @error('cap')<span class="sq-profilo-err">{{ $message }}</span>@enderror
                                </div>
                                <div class="sq-profilo-field sq-profilo-mb-0">
                                    <label for="bo_provincia" class="sq-profilo-label">Prov. <span class="sq-profilo-req">*</span></label>
                                    <input type="text" name="provincia" id="bo_provincia" class="sq-profilo-input sq-profilo-input--ro" required maxlength="2" placeholder="PV"
                                           value="{{ old('provincia', $a->provincia) }}" readonly tabindex="-1">
                                    @error('provincia')<span class="sq-profilo-err">{{ $message }}</span>@enderror
                                </div>
                            </div>
                            <div class="sq-profilo-addr-row">
                                <div class="sq-profilo-field sq-profilo-mb-0">
                                    <label for="bo_strada" class="sq-profilo-label">Strada <span class="sq-profilo-req">*</span></label>
                                    <input type="text" name="indirizzo" id="bo_strada" class="sq-profilo-input" required maxlength="255" value="{{ old('indirizzo', $a->indirizzo) }}">
                                    @error('indirizzo')<span class="sq-profilo-err">{{ $message }}</span>@enderror
                                </div>
                                <div class="sq-profilo-field sq-profilo-mb-0">
                                    <label for="bo_civico" class="sq-profilo-label">Civico <span class="sq-profilo-req">*</span></label>
                                    <input type="text" name="civico" id="bo_civico" class="sq-profilo-input" required maxlength="10" value="{{ old('civico', $a->civico) }}">
                                    @error('civico')<span class="sq-profilo-err">{{ $message }}</span>@enderror
                                </div>
                            </div>
                            @error('id_comune')<span class="sq-profilo-err sq-profilo-err-block">{{ $message }}</span>@enderror
                            <p class="sq-profilo-suggest-hint sq-text-muted sq-text-14 sq-m-0 sq-mt-10">
                                Scrivi in <strong>Città</strong> o in <strong>CAP</strong> e scegli una riga dall’elenco.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="sq-profilo-global-actions" id="sq-bo-anagrafica-actions" data-profilo-actions="idle">
                <button type="button" class="sq-profilo-azione-btn sq-profilo-btn-sm" id="btn-bo-modifica-anag">Modifica</button>
                <button type="submit" class="sq-profilo-azione-btn sq-profilo-btn-sm" id="btn-bo-conferma-anag" disabled>Conferma</button>
                <button type="button" class="sq-profilo-azione-btn sq-profilo-btn-sm" id="btn-bo-annulla-anag" disabled>Annulla</button>
            </div>
        </form>

        @if (($revisioniAnagrafica ?? collect())->count() > 1)
            <div class="sq-bo-ana-card sq-mt-16">
                <h3 class="sq-bo-ana-card-title">Storico revisioni</h3>
                <div class="sq-bo-ana-rev-table-wrap">
                    <table class="sq-bo-ana-rev-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Stato</th>
                                <th>Nome</th>
                                <th>Indirizzo</th>
                                <th>Aggiornata</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($revisioniAnagrafica as $rev)
                                <tr @class(['is-active' => $rev->attivo])>
                                    <td>#{{ $rev->id }}</td>
                                    <td>{{ $rev->attivo ? 'Attiva' : 'Archiviata' }}</td>
                                    <td>{{ $rev->nome }} {{ $rev->cognome }}</td>
                                    <td>{{ $rev->indirizzo }} {{ $rev->civico }}, {{ $rev->cap }} {{ $rev->citta }}</td>
                                    <td>{{ $rev->updated_at?->format('d/m/Y H:i') ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        @include('partials.anagrafica-unchanged-modal')

        @include('backoffice.utenti.partials.anagrafica-edit-script', [
            'isImpresa' => $isImpresa,
            'idComuneCorrente' => $idComuneCorrente,
        ])
    @else
        <div class="sq-alert sq-alert--info">Nessuna anagrafica attiva per questo utente.</div>
    @endif
</div>
@endsection
