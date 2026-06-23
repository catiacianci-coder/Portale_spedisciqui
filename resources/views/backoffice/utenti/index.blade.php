@extends('layouts.app')
@section('content')
<div class="sq-bo-utenti-page">
    @if (session('ok'))
        <div class="sq-alert sq-alert--success sq-mb-16">{{ session('ok') }}</div>
    @endif

    <div class="sq-bo-users-wrap">
        <div class="sq-bo-users-toolbar">
            <p class="sq-bo-users-intro">Lista clienti con collegamenti operativi al back office.</p>
            <form method="GET" action="{{ route('backoffice.utenti.index') }}" class="sq-bo-users-search-grid">
                <div class="sq-bo-filter-field">
                    <label for="filtro-q">Ricerca generale</label>
                    <input type="text" id="filtro-q" name="q" value="{{ $filters['q'] }}" placeholder="ID, nome, e-mail">
                </div>
                <div class="sq-bo-filter-field">
                    <label for="filtro-created">Data creazione</label>
                    <select id="filtro-created" name="created_range">
                        <option value="" @selected($filters['created_range'] === '')>Tutti</option>
                        <option value="today" @selected($filters['created_range'] === 'today')>Oggi</option>
                        <option value="7d" @selected($filters['created_range'] === '7d')>Ultimi 7 giorni</option>
                        <option value="30d" @selected($filters['created_range'] === '30d')>Ultimi 30 giorni</option>
                        <option value="90d" @selected($filters['created_range'] === '90d')>Ultimi 90 giorni</option>
                    </select>
                </div>
                <div class="sq-bo-filter-field">
                    <label for="filtro-cf">CF / P. IVA</label>
                    <input type="text" id="filtro-cf" name="cf_piva" value="{{ $filters['cf_piva'] }}">
                </div>
                <div class="sq-bo-filter-field">
                    <label for="filtro-cap">CAP</label>
                    <input type="text" id="filtro-cap" name="cap" value="{{ $filters['cap'] }}">
                </div>
                <div class="sq-bo-filter-field">
                    <label for="filtro-tel">Telefono</label>
                    <input type="text" id="filtro-tel" name="telefono" value="{{ $filters['telefono'] }}">
                </div>
                <div class="sq-bo-filter-field">
                    <label for="filtro-nome">Nome</label>
                    <input type="text" id="filtro-nome" name="nome" value="{{ $filters['nome'] }}">
                </div>
                <div class="sq-bo-filter-field">
                    <label for="filtro-cognome">Cognome</label>
                    <input type="text" id="filtro-cognome" name="cognome" value="{{ $filters['cognome'] }}">
                </div>
                <div class="sq-bo-filter-field">
                    <label for="filtro-rs">Ragione sociale</label>
                    <input type="text" id="filtro-rs" name="ragione_sociale" value="{{ $filters['ragione_sociale'] }}">
                </div>
                <div class="sq-bo-filter-field">
                    <label for="filtro-hab">Abilitato</label>
                    <select id="filtro-hab" name="habilitado">
                        <option value="todos" @selected($filters['habilitado'] === 'todos')>Tutti</option>
                        <option value="sim" @selected($filters['habilitado'] === 'sim')>Sì</option>
                        <option value="nao" @selected($filters['habilitado'] === 'nao')>No</option>
                    </select>
                </div>
                <div class="sq-bo-filter-field">
                    <label for="filtro-nc">Con pratiche NC</label>
                    <select id="filtro-nc" name="com_pratiche">
                        <option value="todos" @selected($filters['com_pratiche'] === 'todos')>Tutti</option>
                        <option value="sim" @selected($filters['com_pratiche'] === 'sim')>Sì</option>
                        <option value="nao" @selected($filters['com_pratiche'] === 'nao')>No</option>
                    </select>
                </div>
                <div class="sq-bo-filter-field">
                    <label for="filtro-utenti-per-page">Per pagina</label>
                    <select id="filtro-utenti-per-page" name="per_page" onchange="this.form.submit()">
                        @foreach ([10, 25, 50, 100] as $n)
                            <option value="{{ $n }}" @selected((int) $filters['per_page'] === $n)>{{ $n }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="sq-bo-filter-actions">
                    <button type="submit" class="sq-bo-btn-link sq-bo-btn-blue">Cerca</button>
                    <a href="{{ route('backoffice.utenti.index') }}" class="sq-bo-btn-link sq-bo-btn-gray sq-bo-btn-clear">Pulisci</a>
                </div>
            </form>
        </div>

        <div class="sq-bo-users-list">
            @forelse ($users as $user)
                @php
                    $an = $user->anagrafiche->firstWhere('attivo', true) ?? $user->anagrafiche->first();
                    $cf = trim((string) ($an?->codice_fiscale ?? ''));
                    $piva = trim((string) ($an?->partita_iva ?? ''));
                    $docLabel = $piva !== '' ? 'P.IVA '.$piva : ($cf !== '' ? 'CF '.$cf : '');
                    $tipoUtente = match ($user->tipo_utente) {
                        'privato' => 'Privato',
                        'ditta' => 'Ditta',
                        'societa' => 'Società',
                        'professionista' => 'Professionista',
                        default => ucfirst((string) $user->tipo_utente),
                    };
                    $ncCount = (int) ($user->nc_pratiche_count ?? 0);
                    $ordiniCount = (int) ($user->ordini_count ?? 0);
                    $spedizioniCount = (int) ($user->spedizioni_count ?? 0);
                    $ricaricheCount = (int) ($user->ricariche_count ?? 0);
                    $rimborsiCount = (int) ($user->rimborsi_count ?? 0);
                    $anagraficaCount = (int) ($user->anagrafiche_count ?? 0);
                    $mittentiCount = (int) ($user->mittenze_count ?? 0);
                    $disabled = (bool) $user->is_account_disabled;
                    $badge = $user->postingBlockedBadgeMeta();
                    $walletLabel = 'W: '.\App\Support\ImportoEuro::format($user->walletSaldoAsFloat());
                @endphp
                <article class="sq-bo-user-row">
                    <div class="sq-bo-user-main">
                        <div class="sq-bo-user-cell">
                            <div class="sq-bo-user-strong">#{{ $user->id }} {{ $user->displayNameForBackoffice() }}</div>
                            <div>{{ $user->email }}</div>
                            <div>{{ $tipoUtente }}@if ($docLabel !== '') — {{ $docLabel }}@endif</div>
                        </div>
                        <div class="sq-bo-user-cell">
                            <div class="sq-bo-user-strong">Anagrafica</div>
                            <div>{{ trim(($an?->indirizzo ?? '').' '.($an?->civico ?? '')) ?: '—' }}</div>
                            <div>{{ $an?->citta ?? '—' }}@if ($an?->provincia) ({{ $an->provincia }})@endif</div>
                            <div>CAP: {{ $an?->cap ?? '—' }}</div>
                        </div>
                        <div class="sq-bo-user-cell">
                            <div class="sq-bo-user-strong">Contatto</div>
                            <div>Tel: {{ $an?->telefono ?: '—' }}</div>
                            <div>Liccardi: {{ $user->is_liccardi ? 'SÌ' : 'NO' }}</div>
                            <div>Confermato: {{ $user->email_verified_at ? 'SÌ' : 'NO' }}</div>
                        </div>
                        <div class="sq-bo-user-cell">
                            <div class="sq-bo-user-strong">Conto</div>
                            <div>Creato: {{ $user->created_at?->format('d/m/Y') ?? '—' }}</div>
                            @if ($disabled)
                                <div class="sq-bo-user-status-off">
                                    DISABILITATO
                                    @if ($badge)
                                        <span class="{{ $badge['class'] }}" title="{{ $badge['title'] }}">{{ $badge['abbr'] }}</span>
                                    @endif
                                </div>
                            @else
                                <div class="sq-bo-user-status-on">ABILITATO</div>
                            @endif
                        </div>
                    </div>

                    <div class="sq-bo-user-actions">
                        <a href="{{ route('backoffice.nc.index', ['user_id' => $user->id]) }}"
                           class="sq-bo-btn-link {{ $ncCount > 0 ? 'sq-bo-btn-red' : 'sq-bo-btn-gray' }}">
                            NC ({{ $ncCount }})
                        </a>
                        <a href="{{ route('backoffice.ordini.index', ['user_id' => $user->id]) }}"
                           class="sq-bo-btn-link {{ $ordiniCount > 0 ? 'sq-bo-btn-blue' : 'sq-bo-btn-gray' }}">
                            Ordini ({{ $ordiniCount }})
                        </a>
                        <a href="{{ route('backoffice.spedizioni.index', ['utente' => $user->email]) }}"
                           class="sq-bo-btn-link {{ $spedizioniCount > 0 ? 'sq-bo-btn-blue' : 'sq-bo-btn-gray' }}">
                            Spedizioni ({{ $spedizioniCount }})
                        </a>
                        <a href="{{ route('backoffice.ricariche.index', ['user_id' => $user->id]) }}"
                           class="sq-bo-btn-link {{ $ricaricheCount > 0 ? 'sq-bo-btn-blue' : 'sq-bo-btn-gray' }}">
                            Ricariche ({{ $ricaricheCount }})
                        </a>
                        <a href="{{ route('backoffice.utenti.section', ['user' => $user->id, 'section' => 'anagrafica']) }}"
                           class="sq-bo-btn-link {{ $anagraficaCount > 0 ? 'sq-bo-btn-green' : 'sq-bo-btn-blue' }}">
                            Anagrafica ({{ $anagraficaCount }})
                        </a>
                        <a href="{{ route('backoffice.utenti.section', ['user' => $user->id, 'section' => 'mittenti']) }}"
                           class="sq-bo-btn-link {{ $mittentiCount > 0 ? 'sq-bo-btn-blue' : 'sq-bo-btn-gray' }}">
                            Mittenti ({{ $mittentiCount }})
                        </a>
                        <a href="{{ route('backoffice.rimborsi.pendentes', ['user_id' => $user->id]) }}"
                           class="sq-bo-btn-link {{ $rimborsiCount > 0 ? 'sq-bo-btn-blue' : 'sq-bo-btn-gray' }}">
                            Rimborsi ({{ $rimborsiCount }})
                        </a>
                        <form method="POST" action="{{ route('backoffice.utenti.liccardi.toggle', $user) }}" class="sq-bo-user-toggle-form">
                            @csrf
                            <button type="submit" class="sq-bo-btn-link {{ $user->is_liccardi ? 'sq-bo-btn-red' : 'sq-bo-btn-green' }}">
                                {{ $user->is_liccardi ? 'Disabilita Liccardi' : 'Abilita Liccardi' }}
                            </button>
                        </form>
                        <form method="POST" action="{{ route('backoffice.utenti.habilitacao_postagem.toggle', $user) }}" class="sq-bo-user-toggle-form">
                            @csrf
                            <button type="submit" class="sq-bo-btn-link {{ $disabled ? 'sq-bo-btn-red' : 'sq-bo-btn-green' }}">
                                {{ $disabled ? 'Abilita postagem' : 'Disabilita postagem' }}
                            </button>
                        </form>
                        <a href="{{ route('backoffice.wallet.cliente', ['user_id' => $user->id]) }}"
                           class="sq-bo-btn-link sq-bo-btn-wallet"
                           title="Estratto wallet del cliente">
                            {{ $walletLabel }}
                        </a>
                    </div>
                </article>
            @empty
                <div class="sq-bo-user-row sq-bo-user-row--empty">
                    <div class="sq-bo-user-cell">Nessun utente trovato.</div>
                </div>
            @endforelse
        </div>

        @if ($users->hasPages())
            <div class="sq-bo-users-pagination">
                {{ $users->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
