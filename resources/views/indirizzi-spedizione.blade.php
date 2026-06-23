@extends('layouts.app')
@section('content')
@php
    $v = $values;
    $canRubricaDest = Auth::check() && Auth::user()->hasVerifiedEmail();
    $mittentiRubrica = $mittentiRubrica ?? [];
    $mittenteRubricaIdSelezionato = $mittenteRubricaIdSelezionato ?? 0;
    $mittenteDenomChiuso = $mittenteDenominazioneChiuso ?? false;
    $isReso = $isReso ?? false;
    $consegnaPunto = $consegnaPunto ?? false;
@endphp

<div class="indirizzi-page sq-page-indirizzi">
    @if ($errors->has('indirizzi'))
        <div class="sq-alert sq-alert--error sq-mb-14">{{ $errors->first('indirizzi') }}</div>
    @endif

    @if ($errors->any() && ! $errors->has('indirizzi'))
        <div class="sq-alert sq-alert--error sq-mb-14">
            <ul class="sq-alert-list">
                @foreach ($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <h1 class="sq-h1-carrello sq-text-heading sq-mb-18">Indirizzi</h1>

    <form method="POST" action="{{ route('spedizione.indirizzi.store') }}" class="sq-indirizzi-form"
          data-consegna-punto="{{ $consegnaPunto ? '1' : '0' }}">
        @csrf
        <input type="hidden" name="corriere_id" value="{{ $corriereId }}">
        <input type="hidden" name="mittente_rubrica_id" id="mittente_rubrica_id" value="{{ $mittenteRubricaIdSelezionato > 0 ? $mittenteRubricaIdSelezionato : '' }}">
        <input type="hidden" name="destinatario_rubrica_id" id="destinatario_rubrica_id" value="{{ old('destinatario_rubrica_id', '') }}">

        <div class="sq-indirizzi-two-col">
            <div class="sq-indirizzi-col sq-indirizzi-col--mittente sq-indirizzi-tab-section">
                <h2 class="sq-indirizzi-tab-title">Mittente</h2>
                <div class="sq-indirizzi-col-panel">
                @if (! $isReso)
                <div class="sq-indirizzi-mb-12">
                    <select id="mittente_rubrica_select" class="sq-indirizzi-input" autocomplete="off" aria-label="Rubrica mittenti"
                            @unless ($canRubricaDest) disabled @endunless>
                        <option value=""></option>
                        @foreach ($mittentiRubrica as $mr)
                            @php
                                $pref = ! empty($mr['is_preferito']);
                                $etichetta = trim(trim((string) ($mr['cognome'] ?? '')) . ' ' . trim((string) ($mr['nome'] ?? '')));
                                if ($etichetta === '') {
                                    $etichetta = trim((string) ($mr['denominazione_ragione_sociale'] ?? ''));
                                }
                                if ($etichetta === '') {
                                    $etichetta = 'Mittente #' . (int) ($mr['id'] ?? 0);
                                }
                                if ($pref) {
                                    $etichetta = '★ ' . $etichetta;
                                }
                            @endphp
                            <option value="{{ (int) $mr['id'] }}" {{ (int) ($mr['id'] ?? 0) === (int) $mittenteRubricaIdSelezionato ? 'selected' : '' }}>{{ e($etichetta) }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
                <div class="sq-indirizzi-grid-2">
                    <div>
                        <label class="sq-indirizzi-label sq-indirizzi-label--muted">CAP</label>
                        <input type="text" readonly value="{{ e($capPartenza) }}" class="indirizzi-readonly sq-indirizzi-input sq-indirizzi-input--ro">
                    </div>
                    <div>
                        <label class="sq-indirizzi-label sq-indirizzi-label--muted">Provincia</label>
                        <input type="text" readonly value="{{ e($pvPartenza) }}" class="indirizzi-readonly sq-indirizzi-input sq-indirizzi-input--ro">
                    </div>
                </div>
                <div class="sq-indirizzi-mb-12">
                    <label class="sq-indirizzi-label sq-indirizzi-label--muted">Città</label>
                    <input type="text" readonly value="{{ e($cittaPartenza) }}" class="sq-indirizzi-input sq-indirizzi-input--ro">
                </div>

                <div class="sq-indirizzi-mb-12">
                    <label for="denominazione_mittente" class="sq-indirizzi-label sq-indirizzi-label--main">Denominazione impresa</label>
                    <input id="denominazione_mittente" name="denominazione_mittente" maxlength="255"
                           value="{{ e($v['denominazione_mittente']) }}"
                           placeholder="Non compilare se il mittente è un privato"
                           autocomplete="organization"
                           class="sq-indirizzi-input @if ($mittenteDenomChiuso) sq-indirizzi-input--ro @endif"
                           @if ($mittenteDenomChiuso) readonly @endif>
                </div>

                <div class="sq-indirizzi-grid-2 sq-indirizzi-mb-12">
                    <div>
                        <label for="nome_mittente" class="sq-indirizzi-label sq-indirizzi-label--main">Nome mittente <span class="sq-indirizzi-star">*</span></label>
                        <input id="nome_mittente" name="nome_mittente" required maxlength="120" value="{{ e($v['nome_mittente']) }}"
                               autocomplete="given-name" class="sq-indirizzi-input">
                    </div>
                    <div>
                        <label for="cognome_mittente" class="sq-indirizzi-label sq-indirizzi-label--main">Cognome mittente <span class="sq-indirizzi-star">*</span></label>
                        <input id="cognome_mittente" name="cognome_mittente" required maxlength="120" value="{{ e($v['cognome_mittente']) }}"
                               autocomplete="family-name" class="sq-indirizzi-input">
                    </div>
                </div>

                <div class="sq-indirizzi-grid-2 sq-indirizzi-mb-12">
                    <div>
                        <label for="telefono_mittente" class="sq-indirizzi-label sq-indirizzi-label--main">Telefono mittente <span class="sq-indirizzi-star">*</span></label>
                        <input id="telefono_mittente" name="telefono_mittente" type="tel" required maxlength="40" value="{{ e($v['telefono_mittente']) }}"
                               autocomplete="tel" class="sq-indirizzi-input">
                    </div>
                    <div>
                        <label for="email_mittente" class="sq-indirizzi-label sq-indirizzi-label--main">Email mittente <span class="sq-indirizzi-star">*</span></label>
                        <input id="email_mittente" name="email_mittente" type="email" required maxlength="160" value="{{ e($v['email_mittente']) }}"
                               autocomplete="email" class="sq-indirizzi-input">
                    </div>
                </div>

                <div class="sq-indirizzi-grid-via">
                    <div>
                        <label for="via_partenza" class="sq-indirizzi-label sq-indirizzi-label--main">Via / Piazza <span class="sq-indirizzi-star">*</span></label>
                        <input id="via_partenza" name="via_partenza" required maxlength="160" value="{{ e($v['via_partenza']) }}"
                               class="sq-indirizzi-input">
                    </div>
                    <div>
                        <label for="numero_partenza" class="sq-indirizzi-label sq-indirizzi-label--main">N. civico <span class="sq-indirizzi-star">*</span></label>
                        <input id="numero_partenza" name="numero_partenza" required maxlength="32" value="{{ e($v['numero_partenza']) }}"
                               class="sq-indirizzi-input">
                    </div>
                </div>

                <div class="sq-indirizzi-mb-12">
                    <label for="note_partenza" class="sq-indirizzi-label sq-indirizzi-label--main">Note per il ritiro (opzionale)</label>
                    <textarea id="note_partenza" name="note_partenza" maxlength="1000" rows="3" class="sq-indirizzi-textarea"
                              placeholder="{{ e($notePartenzaPlaceholder ?? 'Istruzioni per il corriere al ritiro') }}">{{ e($v['note_partenza']) }}</textarea>
                </div>
                <div class="sq-indirizzi-col-grow" aria-hidden="true"></div>
                @if ($canRubricaDest && ! $isReso)
                    <div class="sq-indirizzi-rubrica-save sq-indirizzi-mb-0">
                        <label class="sq-indirizzi-check-label">
                            <input type="checkbox" name="salva_mittente_rubrica" value="1" class="sq-indirizzi-check" id="salva_mittente_rubrica"
                                   @if ($mittenteRubricaIdSelezionato > 0) disabled @endif>
                            <span>Salva mittente in rubrica</span>
                        </label>
                    </div>
                @endif
                </div>
            </div>

            <div class="sq-indirizzi-col sq-indirizzi-col--dest sq-indirizzi-tab-section">
                <h2 class="sq-indirizzi-tab-title">Destinatario</h2>
                <div class="sq-indirizzi-col-panel">
                @if (! $isReso)
                <div class="sq-indirizzi-mb-12">
                    <select id="destinatario_rubrica_select" class="sq-indirizzi-input" autocomplete="off" aria-label="Rubrica destinatari"
                            @unless ($canRubricaDest) disabled @endunless>
                        <option value=""></option>
                        @foreach ($destinatariRubrica as $dr)
                            @php
                                $etichetta = trim(trim((string) ($dr['cognome'] ?? '')) . ' ' . trim((string) ($dr['nome'] ?? '')));
                                if ($etichetta === '') {
                                    $etichetta = 'Destinatario #' . (int) ($dr['id'] ?? 0);
                                }
                            @endphp
                            <option value="{{ (int) $dr['id'] }}">{{ e($etichetta) }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
                <div class="sq-indirizzi-grid-2">
                    <div>
                        <label class="sq-indirizzi-label sq-indirizzi-label--muted">CAP</label>
                        <input type="text" readonly value="{{ e($capArrivo) }}" class="indirizzi-readonly sq-indirizzi-input sq-indirizzi-input--ro">
                    </div>
                    <div>
                        <label class="sq-indirizzi-label sq-indirizzi-label--muted">Provincia</label>
                        <input type="text" readonly value="{{ e($pvArrivo) }}" class="indirizzi-readonly sq-indirizzi-input sq-indirizzi-input--ro">
                    </div>
                </div>
                <div class="sq-indirizzi-mb-12">
                    <label class="sq-indirizzi-label sq-indirizzi-label--muted">Città</label>
                    <input type="text" readonly value="{{ e($cittaArrivo) }}" class="sq-indirizzi-input sq-indirizzi-input--ro">
                </div>

                @if (! $consegnaPunto)
                <div class="sq-indirizzi-mb-12 sq-indirizzi-dest-indirizzo">
                    <label for="denominazione_destinatario" class="sq-indirizzi-label sq-indirizzi-label--main">Denominazione impresa</label>
                    <input id="denominazione_destinatario" name="denominazione_destinatario" maxlength="255"
                           value="{{ e($v['denominazione_destinatario']) }}"
                           placeholder="Compila solo per destinatario azienda (opzionale)"
                           autocomplete="organization"
                           class="sq-indirizzi-input">
                </div>
                @endif

                <div class="sq-indirizzi-grid-2 sq-indirizzi-mb-12">
                    <div>
                        <label for="nome_destinatario" class="sq-indirizzi-label sq-indirizzi-label--main">Nome destinatario <span class="sq-indirizzi-star">*</span></label>
                        <input id="nome_destinatario" name="nome_destinatario" required maxlength="120" value="{{ e($v['nome_destinatario']) }}"
                               autocomplete="shipping given-name" class="sq-indirizzi-input">
                    </div>
                    <div>
                        <label for="cognome_destinatario" class="sq-indirizzi-label sq-indirizzi-label--main">Cognome destinatario <span class="sq-indirizzi-star">*</span></label>
                        <input id="cognome_destinatario" name="cognome_destinatario" required maxlength="120" value="{{ e($v['cognome_destinatario']) }}"
                               autocomplete="shipping family-name" class="sq-indirizzi-input">
                    </div>
                </div>

                <div class="sq-indirizzi-grid-2 sq-indirizzi-mb-12">
                    <div>
                        <label for="telefono_destinatario" class="sq-indirizzi-label sq-indirizzi-label--main">Telefono destinatario <span class="sq-indirizzi-star">*</span></label>
                        <input id="telefono_destinatario" name="telefono_destinatario" type="tel" required maxlength="40" value="{{ e($v['telefono_destinatario']) }}"
                               autocomplete="shipping tel" class="sq-indirizzi-input">
                    </div>
                    <div>
                        <label for="email_destinatario" class="sq-indirizzi-label sq-indirizzi-label--main">Email destinatario <span class="sq-indirizzi-star">*</span></label>
                        <input id="email_destinatario" name="email_destinatario" type="email" required maxlength="160" value="{{ e($v['email_destinatario']) }}"
                               autocomplete="shipping email" class="sq-indirizzi-input">
                    </div>
                </div>

                @if ($consegnaPunto)
                    <p class="sq-text-muted sq-mb-12">
                        Consegna presso punto di ritiro: inserisci solo i contatti del destinatario.
                        Sceglierai il punto nella pagina di checkout.
                    </p>
                @endif

                @if (! $consegnaPunto)
                <div class="sq-indirizzi-grid-via sq-indirizzi-dest-indirizzo">
                    <div>
                        <label for="via_destinazione" class="sq-indirizzi-label sq-indirizzi-label--main">Via / Piazza <span class="sq-indirizzi-star">*</span></label>
                        <input id="via_destinazione" name="via_destinazione" required maxlength="160" value="{{ e($v['via_destinazione']) }}"
                               class="sq-indirizzi-input">
                    </div>
                    <div>
                        <label for="numero_destinazione" class="sq-indirizzi-label sq-indirizzi-label--main">N. civico <span class="sq-indirizzi-star">*</span></label>
                        <input id="numero_destinazione" name="numero_destinazione" required maxlength="32" value="{{ e($v['numero_destinazione']) }}"
                               class="sq-indirizzi-input">
                    </div>
                </div>
                @endif

                @if (! $consegnaPunto)
                <div class="sq-indirizzi-mb-12 sq-indirizzi-dest-indirizzo">
                    <label for="note_destinazione" class="sq-indirizzi-label sq-indirizzi-label--main">Note per la consegna (opzionale)</label>
                    <textarea id="note_destinazione" name="note_destinazione" maxlength="1000" rows="3" class="sq-indirizzi-textarea"
                              placeholder="Istruzioni per il corriere in consegna">{{ e($v['note_destinazione']) }}</textarea>
                </div>
                @endif
                <div class="sq-indirizzi-col-grow" aria-hidden="true"></div>
                @if ($canRubricaDest && ! $isReso && ! $consegnaPunto)
                    <div class="sq-indirizzi-rubrica-save sq-indirizzi-mb-0">
                        <label class="sq-indirizzi-check-label">
                            <input type="checkbox" name="salva_destinatario_rubrica" value="1" class="sq-indirizzi-check" id="salva_destinatario_rubrica">
                            <span>Salva destinatario in rubrica</span>
                        </label>
                    </div>
                @endif
                </div>
            </div>
        </div>

        <div class="sq-indirizzi-divider" role="presentation" aria-hidden="true"></div>

        <div class="sq-indirizzi-actions">
            <button type="submit" class="sq-btn-primary sq-indirizzi-btn-confirm">Conferma</button>
            <a href="{{ route('preventivi') }}" class="sq-btn-secondary sq-indirizzi-action-link">Annulla e torna ai preventivi</a>
            <a href="{{ route('home') }}" class="sq-btn-secondary sq-indirizzi-action-link">Annulla e torna alla home</a>
        </div>
    </form>
</div>

<script>
    (function () {
        var rubrica = @json($mittentiRubrica);
        var sel = document.getElementById('mittente_rubrica_select');
        var hid = document.getElementById('mittente_rubrica_id');
        var cbSalva = document.getElementById('salva_mittente_rubrica');

        function syncSalvaMittenteRubrica() {
            if (!cbSalva || !hid) return;
            var fromRubrica = String(hid.value || '').trim() !== '';
            cbSalva.disabled = fromRubrica;
            if (fromRubrica) cbSalva.checked = false;
        }

        if (!sel || sel.disabled || !hid || !Array.isArray(rubrica)) {
            syncSalvaMittenteRubrica();
            return;
        }

        function clearMittenteRubricaFields() {
            ['nome_mittente', 'cognome_mittente', 'telefono_mittente', 'email_mittente', 'via_partenza', 'numero_partenza'].forEach(function (fid) {
                var el = document.getElementById(fid);
                if (el) el.value = '';
            });
            var den = document.getElementById('denominazione_mittente');
            if (den) {
                den.value = '';
                den.readOnly = false;
                den.classList.remove('sq-indirizzi-input--ro');
            }
        }

        function applyRow(row) {
            if (!row) return;
            var nome = document.getElementById('nome_mittente');
            var cognome = document.getElementById('cognome_mittente');
            var tel = document.getElementById('telefono_mittente');
            var em = document.getElementById('email_mittente');
            var via = document.getElementById('via_partenza');
            var civ = document.getElementById('numero_partenza');
            var den = document.getElementById('denominazione_mittente');
            var n = String(row.nome || '').trim();
            var c = String(row.cognome || '').trim();
            var drs = String(row.denominazione_ragione_sociale || '').trim();
            if (!n && drs) n = drs;
            if (nome) nome.value = n;
            if (cognome) cognome.value = c;
            if (tel) tel.value = row.telefono || '';
            if (em) em.value = row.email || '';
            if (via) via.value = row.indirizzo || '';
            if (civ) civ.value = row.civico || '';
            if (den) {
                den.value = drs;
                den.readOnly = !drs;
                den.classList.toggle('sq-indirizzi-input--ro', !drs);
            }
        }

        sel.addEventListener('change', function () {
            var id = String(sel.value || '');
            hid.value = id;
            if (!id) {
                clearMittenteRubricaFields();
                syncSalvaMittenteRubrica();
                return;
            }
            var row = rubrica.find(function (m) { return String(m.id) === id; });
            if (!row) return;
            applyRow(row);
            syncSalvaMittenteRubrica();
        });
        syncSalvaMittenteRubrica();
    })();
</script>
<script>
    (function () {
        var rubrica = @json($destinatariRubrica);
        var sel = document.getElementById('destinatario_rubrica_select');
        var hid = document.getElementById('destinatario_rubrica_id');
        var cbSalva = document.getElementById('salva_destinatario_rubrica');

        function syncSalvaDestinatarioRubrica() {
            if (!cbSalva || !hid || !sel) return;
            var id = String(sel.value || '').trim();
            hid.value = id;
            var fromRubrica = id !== '';
            cbSalva.disabled = fromRubrica;
            if (fromRubrica) cbSalva.checked = false;
        }

        var formInd = document.querySelector('.sq-indirizzi-form');
        var consegnaPuntoMode = formInd && formInd.dataset.consegnaPunto === '1';

        function clearDestinatarioRubricaFields() {
            ['nome_destinatario', 'cognome_destinatario', 'telefono_destinatario', 'email_destinatario'].forEach(function (fid) {
                var el = document.getElementById(fid);
                if (el) el.value = '';
            });
            if (!consegnaPuntoMode) {
                ['via_destinazione', 'numero_destinazione'].forEach(function (fid) {
                    var el = document.getElementById(fid);
                    if (el) el.value = '';
                });
                var den = document.getElementById('denominazione_destinatario');
                if (den) {
                    den.value = '';
                    den.readOnly = false;
                    den.classList.remove('sq-indirizzi-input--ro');
                }
            }
        }

        if (!sel || sel.disabled || !Array.isArray(rubrica)) {
            syncSalvaDestinatarioRubrica();
            return;
        }

        sel.addEventListener('change', function () {
            var id = String(this.value || '');
            if (hid) hid.value = id;
            if (!id) {
                clearDestinatarioRubricaFields();
                syncSalvaDestinatarioRubrica();
                return;
            }
            var row = rubrica.find(function (d) { return String(d.id) === id; });
            if (!row) {
                syncSalvaDestinatarioRubrica();
                return;
            }
            var nome = document.getElementById('nome_destinatario');
            var cognome = document.getElementById('cognome_destinatario');
            var tel = document.getElementById('telefono_destinatario');
            var em = document.getElementById('email_destinatario');
            var via = document.getElementById('via_destinazione');
            var civ = document.getElementById('numero_destinazione');
            var den = document.getElementById('denominazione_destinatario');
            if (nome) nome.value = row.nome || '';
            if (cognome) cognome.value = row.cognome || '';
            if (tel) tel.value = row.telefono || '';
            if (em) em.value = row.email || '';
            if (!consegnaPuntoMode) {
                if (via) via.value = row.indirizzo || '';
                if (civ) civ.value = row.civico || '';
                if (den) {
                    var drs = String(row.denominazione_ragione_sociale || '').trim();
                    den.value = drs;
                    den.readOnly = true;
                    den.classList.add('sq-indirizzi-input--ro');
                }
            }
            syncSalvaDestinatarioRubrica();
        });
        syncSalvaDestinatarioRubrica();
    })();
</script>
@if ($isReso)
<script>
    (function () {
        var keepEditable = {
            note_partenza: true,
            note_destinazione: true
        };
        document.querySelectorAll('.sq-indirizzi-form input, .sq-indirizzi-form textarea, .sq-indirizzi-form select').forEach(function (el) {
            if (!el || !el.id) return;
            if (el.type === 'hidden') return;
            if (keepEditable[el.id]) return;

            if (el.tagName === 'SELECT' || el.type === 'checkbox' || el.type === 'radio') {
                el.disabled = true;
                return;
            }
            el.readOnly = true;
            el.classList.add('sq-indirizzi-input--ro');
        });
    })();
</script>
@endif
@endsection
