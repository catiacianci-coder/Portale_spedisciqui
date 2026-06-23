@extends('layouts.app')
@section('content')
@php
    $estraiImpresa = static function ($json): string {
        if (! is_array($json)) {
            return '';
        }
        foreach (['denominazione_impresa', 'denominazione_ragione_sociale', 'ragione_sociale', 'azienda', 'impresa', 'company', 'nome_impresa'] as $k) {
            $v = trim((string) ($json[$k] ?? ''));
            if ($v !== '') {
                return $v;
            }
        }

        return '';
    };
@endphp
<div class="home-spedizione-wrap backoffice-spedizioni">
    <p class="sq-intro">Ricerca spedizioni con filtri base e avanzati. Evidenza rossa per spedizioni con integrazione aperta.</p>

    @if (! empty($filtroErrors))
        <div class="sq-alert sq-alert--error sq-mb-18">
            @foreach ($filtroErrors as $fe)
                <div>{{ $fe }}</div>
            @endforeach
        </div>
    @endif

    @if (session('ok'))
        <div class="sq-alert sq-alert--success sq-mb-16">{{ session('ok') }}</div>
    @endif
    @if (session('error'))
        <div class="sq-alert sq-alert--error sq-mb-16">{{ session('error') }}</div>
    @endif

    <form method="GET" action="{{ route('backoffice.spedizioni.index') }}" class="sq-filtri-form sq-bo-sped-form">
        <input type="hidden" name="cerca" value="1">
        <p class="sq-filtri-title">Ricerca veloce</p>
        <div class="sq-bo-sped-quick-grid">
            <div>
                <label for="codice_invio" class="sq-filtri-label">Codice di invio (codice interno)</label>
                <input id="codice_invio" name="codice_invio" type="text" value="{{ $filtroCodiceInvio }}" class="sq-filtri-email-input" placeholder="codice spedizione">
            </div>
            <div>
                <label for="tracking" class="sq-filtri-label">Traking</label>
                <input id="tracking" name="tracking" type="text" value="{{ $filtroTracking }}" class="sq-filtri-email-input" placeholder="Numero tracking">
            </div>
            <div>
                <label for="numero_ordine" class="sq-filtri-label">Numero ordine</label>
                <input id="numero_ordine" name="numero_ordine" type="text" value="{{ $filtroNumeroOrdine }}" class="sq-filtri-email-input" placeholder="ID ordine">
            </div>
            <div class="sq-filtri-actions">
                <button type="submit" class="sq-filtri-submit">Cerca</button>
                <a href="{{ route('backoffice.spedizioni.index') }}" class="sq-filtri-reset">Reimposta</a>
            </div>
        </div>

        <details class="sq-bo-sped-advanced" @if($filtroUtente !== '' || $filtroMittenteNome !== '' || $filtroMittenteCognome !== '' || $filtroMittenteImpresa !== '' || $filtroMittenteIndirizzo !== '' || $filtroMittenteCap !== '' || $filtroMittenteCitta !== '' || $filtroDestinatarioNome !== '' || $filtroDestinatarioCognome !== '' || $filtroDestinatarioImpresa !== '' || $filtroDestinatarioIndirizzo !== '' || $filtroDestinatarioCap !== '' || $filtroDestinatarioCitta !== '' || $filtroCorriereNome !== '' || $filtroTipoSpedizione !== '' || $filtroServizio !== '' || $filtroPagata !== '' || $filtroPeriod !== '30' || ($filtroPeriod === 'custom' && ($filtroDataDa !== '' || $filtroDataA !== ''))) open @endif>
            <summary>Ricerca avanzata</summary>
            <div class="sq-bo-sped-adv-grid">
                <div class="sq-bo-sped-adv-title">Dati spedizione</div>
                <div class="sq-bo-sped-dati-spedizione-grid">
                    <div>
                        <label for="utente" class="sq-filtri-label">Utente</label>
                        <input id="utente" name="utente" type="text" list="sq-utenti-email-list" value="{{ $filtroUtente }}" class="sq-filtri-email-input" placeholder="utente@email.it">
                        <datalist id="sq-utenti-email-list">
                            @foreach ($utentiEmails as $emailUtente)
                                <option value="{{ $emailUtente }}"></option>
                            @endforeach
                        </datalist>
                    </div>
                    <div>
                        <label for="corriere_nome" class="sq-filtri-label">Corriere</label>
                        <select id="corriere_nome" name="corriere_nome" class="sq-filtri-select">
                            <option value="">Tutti</option>
                            @foreach ($corrieriNomi as $nomeCorriere)
                                <option value="{{ $nomeCorriere }}" @selected($filtroCorriereNome === $nomeCorriere)>{{ $nomeCorriere }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="tipo_spedizione" class="sq-filtri-label">Tipo spedizione</label>
                        <select id="tipo_spedizione" name="tipo_spedizione" class="sq-filtri-select">
                            <option value="">Tutti</option>
                            @foreach ($tipiSpedizione as $tipo)
                                <option value="{{ $tipo->id }}" @selected((string) $filtroTipoSpedizione === (string) $tipo->id)>{{ $tipo->tipo_spedizione }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="servizio" class="sq-filtri-label">Servizi</label>
                        <select id="servizio" name="servizio" class="sq-filtri-select">
                            <option value="">Tutti</option>
                            <option value="assicurata" @selected($filtroServizio === 'assicurata')>Assicurata</option>
                            <option value="contrassegno" @selected($filtroServizio === 'contrassegno')>Contrassegno</option>
                        </select>
                    </div>
                    <div>
                        <label for="pagata" class="sq-filtri-label">Pagata</label>
                        <select id="pagata" name="pagata" class="sq-filtri-select">
                            <option value="" @selected($filtroPagata === '')>Tutte</option>
                            <option value="si" @selected($filtroPagata === 'si')>Sì</option>
                            <option value="no" @selected($filtroPagata === 'no')>No</option>
                        </select>
                    </div>
                    <div>
                        <label for="period" class="sq-filtri-label">Periodo</label>
                        <select id="period" name="period" class="sq-filtri-select">
                            <option value="oggi" @selected($filtroPeriod === 'oggi')>Oggi</option>
                            <option value="7" @selected($filtroPeriod === '7')>Ultimi 7 giorni</option>
                            <option value="15" @selected($filtroPeriod === '15')>Ultimi 15 giorni</option>
                            <option value="30" @selected($filtroPeriod === '30')>Ultimi 30 giorni</option>
                            <option value="custom" @selected($filtroPeriod === 'custom')>Personalizzato</option>
                        </select>
                    </div>
                    <div id="wrap-date-sped-custom" class="sq-filtri-dates @if($filtroPeriod === 'custom') is-open @endif">
                        <div>
                            <label for="data_da" class="sq-filtri-label">Da</label>
                            <input id="data_da" name="data_da" type="date" value="{{ $filtroDataDa }}" class="sq-filtri-date-input">
                        </div>
                        <div>
                            <label for="data_a" class="sq-filtri-label">A</label>
                            <input id="data_a" name="data_a" type="date" value="{{ $filtroDataA }}" class="sq-filtri-date-input">
                        </div>
                    </div>
                </div>

                <div class="sq-bo-sped-adv-title">Dati mittente</div>
                <div class="sq-bo-sped-anag-grid">
                    <div>
                        <label for="mittente_nome" class="sq-filtri-label">Nome mittente</label>
                        <input id="mittente_nome" name="mittente_nome" type="text" value="{{ $filtroMittenteNome }}" class="sq-filtri-email-input" placeholder="Nome">
                    </div>
                    <div>
                        <label for="mittente_cognome" class="sq-filtri-label">Cognome mittente</label>
                        <input id="mittente_cognome" name="mittente_cognome" type="text" value="{{ $filtroMittenteCognome }}" class="sq-filtri-email-input" placeholder="Cognome">
                    </div>
                    <div>
                        <label for="mittente_impresa" class="sq-filtri-label">Nome impresa mittente</label>
                        <input id="mittente_impresa" name="mittente_impresa" type="text" value="{{ $filtroMittenteImpresa }}" class="sq-filtri-email-input" placeholder="Impresa">
                    </div>
                    <div>
                        <label for="mittente_indirizzo" class="sq-filtri-label">Indirizzo mittente</label>
                        <input id="mittente_indirizzo" name="mittente_indirizzo" type="text" value="{{ $filtroMittenteIndirizzo }}" class="sq-filtri-email-input" placeholder="Indirizzo">
                    </div>
                    <div class="sq-bo-sped-col-cap">
                        <label for="mittente_cap" class="sq-filtri-label">CAP mittente</label>
                        <input id="mittente_cap" name="mittente_cap" type="text" value="{{ $filtroMittenteCap }}" class="sq-filtri-email-input" placeholder="CAP">
                    </div>
                    <div>
                        <label for="mittente_citta" class="sq-filtri-label">Città mittente</label>
                        <input id="mittente_citta" name="mittente_citta" type="text" value="{{ $filtroMittenteCitta }}" class="sq-filtri-email-input" placeholder="Città">
                    </div>
                </div>

                <div class="sq-bo-sped-adv-title">Dati destinatario</div>
                <div class="sq-bo-sped-anag-grid">
                    <div>
                        <label for="destinatario_nome" class="sq-filtri-label">Nome destinatario</label>
                        <input id="destinatario_nome" name="destinatario_nome" type="text" value="{{ $filtroDestinatarioNome }}" class="sq-filtri-email-input" placeholder="Nome">
                    </div>
                    <div>
                        <label for="destinatario_cognome" class="sq-filtri-label">Cognome destinatario</label>
                        <input id="destinatario_cognome" name="destinatario_cognome" type="text" value="{{ $filtroDestinatarioCognome }}" class="sq-filtri-email-input" placeholder="Cognome">
                    </div>
                    <div>
                        <label for="destinatario_impresa" class="sq-filtri-label">Nome impresa destinatario</label>
                        <input id="destinatario_impresa" name="destinatario_impresa" type="text" value="{{ $filtroDestinatarioImpresa }}" class="sq-filtri-email-input" placeholder="Impresa">
                    </div>
                    <div>
                        <label for="destinatario_indirizzo" class="sq-filtri-label">Indirizzo destinatario</label>
                        <input id="destinatario_indirizzo" name="destinatario_indirizzo" type="text" value="{{ $filtroDestinatarioIndirizzo }}" class="sq-filtri-email-input" placeholder="Indirizzo">
                    </div>
                    <div class="sq-bo-sped-col-cap">
                        <label for="destinatario_cap" class="sq-filtri-label">CAP destinatario</label>
                        <input id="destinatario_cap" name="destinatario_cap" type="text" value="{{ $filtroDestinatarioCap }}" class="sq-filtri-email-input" placeholder="CAP">
                    </div>
                    <div>
                        <label for="destinatario_citta" class="sq-filtri-label">Città destinatario</label>
                        <input id="destinatario_citta" name="destinatario_citta" type="text" value="{{ $filtroDestinatarioCitta }}" class="sq-filtri-email-input" placeholder="Città">
                    </div>
                </div>
            </div>
        </details>
    </form>

    @if ($haRicerca)
        <div class="sq-table-wrap sq-table-wrap--warm">
            <table class="sq-table">
                <thead>
                    <tr class="sq-thead-row sq-thead-row--warm">
                        <th class="sq-th sq-th--warm">Id</th>
                        <th class="sq-th sq-th--warm">Data ritiro</th>
                        <th class="sq-th sq-th--warm">Utente</th>
                        <th class="sq-th sq-th--warm">Corriere</th>
                        <th class="sq-th sq-th--warm">Numero ordine</th>
                        <th class="sq-th sq-th--warm">Payment Intent</th>
                        <th class="sq-th sq-th--warm">Indirizzo partenza</th>
                        <th class="sq-th sq-th--warm">Indirizzo destino</th>
                        <th class="sq-th sq-th--warm">Servizi aggiuntivi</th>
                        <th class="sq-th sq-th--warm">Tipo</th>
                        <th class="sq-th sq-th--warm">Codice interno / Tracking</th>
                        <th class="sq-th sq-th--warm sq-th--center">Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($spedizioni as $s)
                    @php
                        $ordineSped = $s->ordine;
                        $serviziNomi = $s->serviziAggiuntiviRighe
                            ->map(fn ($r) => \App\Support\ServizioAggiuntivoEtichetta::nomeTabella($r))
                            ->filter(fn ($v) => is_string($v) && trim($v) !== '')
                            ->unique()
                            ->values();
                        $mittenteNominativo = trim(implode(' ', array_filter([
                            trim((string) ($s->nome_o ?? '')),
                            trim((string) ($s->cognome_o ?? '')),
                        ], fn ($v) => $v !== '')));
                        $destinatarioNominativo = trim(implode(' ', array_filter([
                            trim((string) ($s->nome_d ?? '')),
                            trim((string) ($s->sobrenome_d ?? '')),
                        ], fn ($v) => $v !== '')));
                        $mittenteImpresa = trim((string) ($s->ragione_sociale_o ?? ''));
                        $destinatarioImpresa = trim((string) ($s->ragione_sociale_d ?? ''));
                        $indirizzoPartenza = trim(implode(', ', array_filter([
                            $mittenteNominativo,
                            $mittenteImpresa,
                            trim((string) ($s->indirizzo_o ?? '')).' '.trim((string) ($s->numero_o ?? '')),
                            trim((string) ($s->cap_o ?? '')),
                            trim((string) ($s->citta_o ?? '')),
                            trim((string) ($s->stato_o ?? '')),
                        ], fn ($v) => $v !== '')));
                        $indirizzoDestinazione = trim(implode(', ', array_filter([
                            $destinatarioNominativo,
                            $destinatarioImpresa,
                            trim((string) ($s->indirizzo_d ?? '')).' '.trim((string) ($s->numero_d ?? '')),
                            trim((string) ($s->cap_d ?? '')),
                            trim((string) ($s->citta_d ?? '')),
                            trim((string) ($s->stato_d ?? '')),
                        ], fn ($v) => $v !== '')));
                        $tipologia = $s->tipoSpedizione?->tipo_spedizione
                            ?? $tipiSpedizione->firstWhere('id', (int) ($s->tipo_id ?? 0))?->tipo_spedizione;
                        $dettaglio = \App\Support\EtichetteListing::dettaglioPayloadBackoffice($s);
                        $etichettaCancellata = \App\Support\EtichettaSpedizioneAccess::etichettaCancellata($s);
                        $ldvStampabile = (bool) ($dettaglio['etichetta_disponibile'] ?? false);
                        $mostraTracciaSendcloudBo = $s->corriereRecord
                            && \App\Support\PiattaformaCorriere::corriereUsaAcquistoSendcloud($s->corriereRecord)
                            && \App\Support\SendcloudIntegrazione::haTracciaApi($s);
                    @endphp
                    <tr class="@if($s->esiste_integrazione) sq-bo-sped-row--integrazione @endif @if($etichettaCancellata) sq-bo-sped-row--etichetta-cancellata @endif">
                        <td class="sq-td sq-td--border-warm sq-fw-700">{{ $s->id }}</td>
                        <td class="sq-td sq-td--border-warm sq-text-muted">{{ $s->data_ritiro?->format('d/m/Y H:i') ?? '—' }}</td>
                        <td class="sq-td sq-td--border-warm">{{ $s->user?->email ?? '—' }}</td>
                        <td class="sq-td sq-td--border-warm">{{ $s->corriere ?? $s->corriereRecord?->nome_corriere ?? '—' }}</td>
                        <td class="sq-td sq-td--border-warm">{{ $s->ordine_id ?? '—' }}</td>
                        <td class="sq-td sq-td--border-warm sq-text-14 sq-nowrap">
                            @if (filled($s->stripe_payment_intent_id))
                                <code class="sq-code">{{ \Illuminate\Support\Str::limit($s->stripe_payment_intent_id, 24) }}</code>
                            @else
                                <span class="sq-text-muted">—</span>
                            @endif
                        </td>
                        <td class="sq-td sq-td--border-warm">{{ $indirizzoPartenza !== '' ? $indirizzoPartenza : '—' }}</td>
                        <td class="sq-td sq-td--border-warm">{{ $indirizzoDestinazione !== '' ? $indirizzoDestinazione : '—' }}</td>
                        <td class="sq-td sq-td--border-warm">
                            @if ($serviziNomi->isEmpty())
                                <span class="sq-text-muted">—</span>
                            @else
                                {{ $serviziNomi->implode(', ') }}
                            @endif
                        </td>
                        <td class="sq-td sq-td--border-warm">{{ $tipologia ?? '—' }}</td>
                        <td class="sq-td sq-td--border-warm">
                            <div class="sq-fw-700">{{ $s->codice_interno }}</div>
                            @if ($etichettaCancellata)
                                <span class="sq-badge sq-badge--muted" style="margin-top:4px;display:inline-block;">Etichetta cancellata</span>
                            @endif
                            <div class="sq-text-muted">TN:{{ $s->tracking ?: '-' }}</div>
                            @if ($ordineSped)
                                <div class="{{ $ordineSped->classeCssStatoOrdineBo() }}">{{ $ordineSped->labelStatoOrdine() }}</div>
                            @else
                                <div class="sq-bo-ordini-stato sq-bo-ordini-stato--non-pagato">—</div>
                            @endif
                        </td>
                        <td class="sq-td sq-td--border-warm sq-td--center">
                            <div class="sq-bo-sped-actions">
                                <button
                                    type="button"
                                    class="sq-btn-bo-ico js-etichetta-dettaglio-open"
                                    title="Dettagli spedizione"
                                    aria-label="Dettagli spedizione {{ $s->codice_interno }}"
                                    data-dettaglio-url="{{ $dettaglio['dettaglio_url'] }}"
                                >
                                    <i class="fa-solid fa-circle-info"></i>
                                </button>
                                @if ($ldvStampabile && ! empty($dettaglio['etichetta_url']))
                                    <a
                                        href="{{ $dettaglio['etichetta_url'] }}"
                                        class="sq-btn-bo-ico"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        title="Apri etichetta PDF"
                                        aria-label="Apri etichetta PDF"
                                    >
                                        <i class="fa-solid fa-print"></i>
                                    </a>
                                @else
                                    <span
                                        class="sq-btn-bo-ico is-disabled"
                                        title="{{ $etichettaCancellata ? 'Etichetta cancellata' : 'Etichetta non disponibile' }}"
                                        aria-hidden="true"
                                    >
                                        <i class="fa-solid fa-print"></i>
                                    </span>
                                @endif
                                @include('partials.spedizione-tracking-icon', [
                                    'spedizione' => $s,
                                    'trackingRoute' => 'backoffice.spedizioni.tracking',
                                    'btnClass' => 'sq-btn-bo-ico',
                                ])
                                <button type="button" class="sq-btn-bo-ico is-disabled" title="Modifica (in arrivo)" aria-label="Modifica spedizione" disabled>
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                                <button type="button" class="sq-btn-bo-ico sq-btn-bo-ico--danger" title="Elimina" aria-label="Elimina spedizione">
                                    <i class="fa-solid fa-xmark"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @if ($mostraTracciaSendcloudBo ?? false)
                        <tr>
                            <td colspan="12" class="sq-td sq-td--border-warm sq-td--trace">
                                @include('partials.spedizione-sendcloud-api-trace', ['spedizione' => $s])
                            </td>
                        </tr>
                    @endif
                    @empty
                        <tr>
                            <td colspan="12" class="sq-td sq-td--border-warm sq-text-muted">Nessuna spedizione trovata con i filtri selezionati.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @php
            $boQueryParams = array_merge($queryParams ?? [], ['cerca' => '1']);
        @endphp
        @include('partials.tabella-paginazione', [
            'paginator' => $spedizioni,
            'perPage' => $perPage,
            'queryParams' => $boQueryParams,
        ])
    @else
        <p class="sq-text-muted sq-m-0 sq-mt-10">Seleziona i filtri desiderati e clicca <strong>Cerca</strong> per visualizzare le spedizioni.</p>
    @endif
</div>

<script>
    (function () {
        var sel = document.getElementById('period');
        var wrap = document.getElementById('wrap-date-sped-custom');
        if (!sel || !wrap) return;
        function sync() {
            wrap.classList.toggle('is-open', sel.value === 'custom');
        }
        sel.addEventListener('change', sync);
        sync();
    })();
</script>
@include('partials.spedizione-tracking-popup')
@include('etichette.partials.modal-dettaglio')
@endsection
