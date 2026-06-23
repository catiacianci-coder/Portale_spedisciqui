@extends('layouts.app')

@php
    $bannerTitle = match ($sezione ?? 'menu') {
        'pendentes' => 'Da rimborsare',
        'rimborsati' => 'Rimborsati',
        'per_ordine' => 'Rimborso per ordine',
        default => 'Gestione rimborsi',
    };
    $bannerIcon = match ($sezione ?? 'menu') {
        'pendentes' => 'fa-hourglass-half',
        'rimborsati' => 'fa-circle-check',
        'per_ordine' => 'fa-receipt',
        default => 'fa-money-bill-transfer',
    };
@endphp

@section('pageBanner')
    <x-sq-page-banner
        variant="backoffice"
        :title="$bannerTitle"
        :icon="$bannerIcon"
        class="sq-page-banner--full"
    />
@endsection

@section('content')
@php
    use App\Models\rimborso;
    $fmt = fn ($n) => number_format((float) $n, 2, ',', '.');
    $ordineLabel = static function (?int $ordineId): string {
        if ($ordineId === null || $ordineId <= 0) {
            return '—';
        }

        return (string) $ordineId;
    };
@endphp
<div class="sq-bo-page-wrap sq-bo-reemb-page">
    @if (session('rimborso_bo_ok'))
        <div class="sq-alert sq-alert--success sq-mb-16">{{ session('rimborso_bo_ok') }}</div>
    @endif
    @if (session('rimborso_bo_erro'))
        <div class="sq-alert sq-alert--error sq-mb-16">{{ session('rimborso_bo_erro') }}</div>
    @endif

    @if (($sezione ?? 'menu') !== 'menu')
        <nav class="sq-bo-reemb-subnav" aria-label="Sezioni rimborsi">
            <a href="{{ route('backoffice.rimborsi.index') }}">Home rimborsi</a>
            <span class="sq-bo-reemb-subnav-sep" aria-hidden="true">·</span>
            <a href="{{ route('backoffice.rimborsi.pendentes') }}" @class(['is-active' => ($sezione ?? '') === 'pendentes'])>Da rimborsare</a>
            <span class="sq-bo-reemb-subnav-sep" aria-hidden="true">·</span>
            <a href="{{ route('backoffice.rimborsi.rimborsati') }}" @class(['is-active' => ($sezione ?? '') === 'rimborsati'])>Rimborsati</a>
            <span class="sq-bo-reemb-subnav-sep" aria-hidden="true">·</span>
            <a href="{{ route('backoffice.rimborsi.per_ordine') }}" @class(['is-active' => ($sezione ?? '') === 'per_ordine'])>Per ordine</a>
            <span class="sq-bo-reemb-subnav-sep" aria-hidden="true">·</span>
            <a href="{{ route('backoffice.rimborsi.trasferimento_wallet') }}" @class(['is-active' => ($sezione ?? '') === 'trasferimento_wallet'])>Trasferimento wallet</a>
        </nav>
    @endif

    @if (($sezione ?? 'menu') === 'menu')
        <p class="sq-bo-reemb-card-lead" style="margin-top:0;">Scegli una sezione per aprire l’elenco completo.</p>
        <div class="sq-bo-reemb-hub-grid">
            <a href="{{ route('backoffice.rimborsi.pendentes') }}" class="sq-bo-reemb-hub-card">
                <i class="fa-solid fa-hourglass-half sq-bo-reemb-hub-card__icon" aria-hidden="true"></i>
                <h2>Da rimborsare</h2>
                <p class="sq-bo-reemb-hub-card__lead">Rimborsi in attesa di accredito sul wallet (data reale vuota).</p>
                <div class="sq-bo-reemb-hub-card__count">{{ $countPendentes ?? 0 }} <span>in coda</span></div>
            </a>
            <a href="{{ route('backoffice.rimborsi.rimborsati') }}" class="sq-bo-reemb-hub-card">
                <i class="fa-solid fa-circle-check sq-bo-reemb-hub-card__icon" aria-hidden="true"></i>
                <h2>Rimborsati</h2>
                <p class="sq-bo-reemb-hub-card__lead">Storico dei rimborsi già accreditati sul wallet dal back office.</p>
                <div class="sq-bo-reemb-hub-card__count">{{ $countRimborsati ?? 0 }} <span>record</span></div>
            </a>
            <a href="{{ route('backoffice.rimborsi.per_ordine') }}" class="sq-bo-reemb-hub-card">
                <i class="fa-solid fa-receipt sq-bo-reemb-hub-card__icon" aria-hidden="true"></i>
                <h2>Rimborso per ordine</h2>
                <p class="sq-bo-reemb-hub-card__lead">Consulta tutte le spedizioni di un ordine entrate nel flusso di rimborso.</p>
                <div class="sq-bo-reemb-hub-card__open">Apri</div>
            </a>
            <a href="{{ route('backoffice.rimborsi.trasferimento_wallet') }}" class="sq-bo-reemb-hub-card">
                <i class="fa-solid fa-wallet sq-bo-reemb-hub-card__icon" aria-hidden="true"></i>
                <h2>Trasferimento wallet</h2>
                <p class="sq-bo-reemb-hub-card__lead">Rimborsi accreditati sul wallet con richiesta di trasferimento su carta o bonifico.</p>
                <div class="sq-bo-reemb-hub-card__open">Apri</div>
            </a>
        </div>
    @endif

    @if (($sezione ?? '') === 'pendentes')
        @if ($selectedUser ?? null)
            <div class="sq-bo-user-filter-banner">
                <span>Filtro utente: <strong>#{{ $selectedUser->id }}</strong> — {{ $selectedUser->email }}</span>
                <span>
                    <a href="{{ route('backoffice.rimborsi.rimborsati', ['user_id' => $selectedUser->id]) }}">Vedi rimborsati</a>
                    ·
                    <a href="{{ route('backoffice.rimborsi.pendentes', request()->except(['user_id', 'page'])) }}">Rimuovi filtro</a>
                </span>
            </div>
        @endif
        <div class="sq-bo-reemb-card">
            <h2>Da rimborsare</h2>
            <p class="sq-bo-reemb-card-lead">
                Rimborsi con <strong>data reale</strong> vuota: l’accredito avviene sul <strong>wallet</strong> del cliente.
                Con etichetta e tracking automatico, alla richiesta il sistema cancella sul corriere e imposta lo stato spedizione
                «<strong>in attesa di rimborso</strong>»; in accredito verifica di nuovo tracking e stato.
                Le righe <strong>evidenziate in rosso</strong> richiedono verifica manuale sul sito del corriere (tracking non automatico).
                @if ($pagaOggi ?? false)
                    <strong>Filtro «Scadenza oggi»:</strong> solo quelli con data prevista ≤ oggi.
                @else
                    Senza filtro vedi tutti i pendenti; «Scadenza oggi» restringe alla data prevista già raggiunta.
                @endif
            </p>
            <div class="sq-bo-reemb-toolbar">
                @php
                    $pendentesQuery = ($filtroUserId ?? 0) > 0 ? ['user_id' => $filtroUserId] : [];
                @endphp
                @if ($pagaOggi ?? false)
                    <a href="{{ route('backoffice.rimborsi.pendentes', $pendentesQuery) }}" class="sq-bo-reemb-btn sq-bo-reemb-btn--sec">Mostra tutti ({{ $totalPendentes ?? 0 }})</a>
                @else
                    <a href="{{ route('backoffice.rimborsi.pendentes', array_merge($pendentesQuery, ['paga_oggi' => 1])) }}" class="sq-bo-reemb-btn">Scadenza oggi</a>
                @endif
            </div>
            @if ($pendentes->isEmpty())
                <div class="sq-bo-reemb-empty">
                    @if ($pagaOggi ?? false)
                        Nessun rimborso pendente con data prevista ≤ oggi.
                        @if (($totalPendentes ?? 0) > 0)
                            <p style="margin-top:12px;color:#64748b;">
                                Ci sono <strong>{{ $totalPendentes }}</strong> pendente/i in totale.
                                <a href="{{ route('backoffice.rimborsi.pendentes') }}">Mostra tutti</a>
                            </p>
                        @endif
                    @else
                        Nessun rimborso in attesa trovato.
                    @endif
                </div>
            @else
                <div class="sq-bo-reemb-table-wrap">
                    <table class="sq-bo-reemb-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Codice interno</th>
                                <th>Ordine</th>
                                <th>Spedizione</th>
                                <th>Tracking</th>
                                <th>Cliente</th>
                                <th>Importo</th>
                                <th>Metodo rimborso</th>
                                <th>Motivo</th>
                                <th>Richiesta</th>
                                <th>Stato sped.</th>
                                <th>Prevista</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($pendentes as $r)
                                @php
                                    $s = $r->spedizione;
                                    $oid = (int) ($r->ordine_id ?? $s?->ordine_id ?? 0);
                                    $atraso = $r->data_prevista && $r->data_prevista->lt(now()->startOfDay());
                                    $verificaManuale = $r->richiedeVerificaManualeOperatore();
                                @endphp
                                <tr @class(['sq-bo-reemb-row--evidenziato' => $verificaManuale])>
                                    <td>#{{ $r->id }}</td>
                                    <td>{{ $r->codice_interno ?? $s?->codice_interno ?? '—' }}</td>
                                    <td class="sq-td-muted">{{ $ordineLabel($oid > 0 ? $oid : null) }}</td>
                                    <td class="sq-td-muted">#{{ $s?->id ?? '—' }}</td>
                                    <td class="sq-td-rastreio">{{ $s?->codigoRastreio() ?: '—' }}</td>
                                    <td>{{ $s?->user?->email ?? '—' }}</td>
                                    <td class="sq-td-valore">{{ \App\Support\ImportoEuro::format($r->valore) }}</td>
                                    <td>{{ $r->metodoPagamentoRimborso?->metodo_pagamento ?? '—' }}</td>
                                    <td class="sq-td-muted">{{ $r->labelMotivo() }}</td>
                                    <td class="sq-td-muted">{{ $r->data_richiesta?->format('d/m/Y') ?? '—' }}</td>
                                    <td class="sq-td-muted">
                                        {{ $s?->spedizioneStato?->denominazione_stato ?? '—' }}
                                        @if ($verificaManuale)
                                            <div class="sq-bo-reemb-tag-manuale">Verifica manuale corriere</div>
                                        @endif
                                    </td>
                                    <td>
                                        {{ $r->data_prevista?->format('d/m/Y') ?? '—' }}
                                        @if ($atraso)
                                            <div class="sq-bo-reemb-tag-atraso">≤ oggi / in ritardo</div>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="sq-bo-reemb-acoes">
                                            <form method="POST" action="{{ route('backoffice.rimborsi.paga', $r) }}"
                                                  onsubmit="return confirm('Confermi l’accredito di {{ \App\Support\ImportoEuro::format($r->valore) }} sul wallet del cliente?');">
                                                @csrf
                                                <button type="submit" class="sq-filtri-submit">Accredita wallet</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @include('partials.tabella-paginazione', ['paginator' => $pendentes])
            @endif
        </div>
    @endif

    @if (($sezione ?? '') === 'rimborsati')
        @if ($selectedUser ?? null)
            <div class="sq-bo-user-filter-banner">
                <span>Filtro utente: <strong>#{{ $selectedUser->id }}</strong> — {{ $selectedUser->email }}</span>
                <span>
                    <a href="{{ route('backoffice.rimborsi.pendentes', ['user_id' => $selectedUser->id]) }}">Vedi pendenti</a>
                    ·
                    <a href="{{ route('backoffice.rimborsi.rimborsati', request()->except(['user_id', 'page'])) }}">Rimuovi filtro</a>
                </span>
            </div>
        @endif
        <div class="sq-bo-reemb-card">
            <h2>Rimborsati</h2>
            <p class="sq-bo-reemb-card-lead">Storico dei rimborsi già accreditati sul wallet dal back office.</p>
            @if ($rimborsati->isEmpty())
                <div class="sq-bo-reemb-empty">Nessun rimborso completato.</div>
            @else
                <div class="sq-bo-reemb-table-wrap">
                    <table class="sq-bo-reemb-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Codice interno</th>
                                <th>Ordine</th>
                                <th>Spedizione</th>
                                <th>Cliente</th>
                                <th>Importo</th>
                                <th>Metodo</th>
                                <th>Motivo</th>
                                <th>Richiesta</th>
                                <th>Accreditato</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rimborsati as $r)
                                @php
                                    $s = $r->spedizione;
                                    $oid = (int) ($r->ordine_id ?? $s?->ordine_id ?? 0);
                                @endphp
                                <tr>
                                    <td>#{{ $r->id }}</td>
                                    <td>{{ $r->codice_interno ?? $s?->codice_interno ?? '—' }}</td>
                                    <td class="sq-td-muted">{{ $ordineLabel($oid > 0 ? $oid : null) }}</td>
                                    <td class="sq-td-muted">#{{ $s?->id ?? '—' }}</td>
                                    <td>{{ $s?->user?->email ?? '—' }}</td>
                                    <td class="sq-td-valore">{{ \App\Support\ImportoEuro::format($r->valore) }}</td>
                                    <td>{{ $r->metodoPagamentoRimborso?->metodo_pagamento ?? '—' }}</td>
                                    <td class="sq-td-muted">{{ $r->labelMotivo() }}</td>
                                    <td class="sq-td-muted">{{ $r->data_richiesta?->format('d/m/Y') ?? '—' }}</td>
                                    <td class="sq-td-muted">{{ $r->data_reale?->format('d/m/Y H:i') ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @include('partials.tabella-paginazione', ['paginator' => $rimborsati])
            @endif
        </div>
    @endif

    @if (($sezione ?? '') === 'per_ordine')
        <div class="sq-bo-reemb-card">
            <h2>Rimborso per ordine</h2>
            <p class="sq-bo-reemb-card-lead">Inserisci l'ID ordine (solo cifre). Elenca solo le spedizioni con richiesta di rimborso registrata.</p>
            <form method="GET" action="{{ route('backoffice.rimborsi.per_ordine') }}" class="sq-bo-reemb-por-ordine-form">
                <div>
                    <label class="sq-filtri-label" for="ordine">Ordine</label>
                    <input id="ordine" name="ordine" type="text" value="{{ e($filtroOrdine ?? '') }}" placeholder="27" style="min-width:160px;">
                </div>
                <div>
                    <label class="sq-filtri-label" for="situazione">Situazione</label>
                    <select id="situazione" name="situazione">
                        <option value="tutti" @selected(($situazione ?? 'tutti') === 'tutti')>Tutti</option>
                        <option value="attesa" @selected(($situazione ?? '') === 'attesa')>In attesa</option>
                        <option value="rimborsato" @selected(($situazione ?? '') === 'rimborsato')>Rimborsati</option>
                    </select>
                </div>
                <div style="align-self:flex-end;">
                    <button type="submit" class="sq-bo-reemb-btn">Cerca</button>
                </div>
            </form>
            @if (! empty($erro))
                <div class="sq-alert sq-alert--error sq-mb-16 sq-mt-16">{{ $erro }}</div>
            @endif
            @if (! empty($lista))
                <p class="sq-fw-700 sq-mb-8 sq-mt-16">Ordine {{ $ordineLabel((int) ($ordine->id ?? 0)) }}</p>
                @if (($situazione ?? 'tutti') !== 'tutti' && trim((string) ($filtroOrdine ?? '')) !== '')
                    <div class="sq-bo-reemb-por-ordine-filtri" role="group" aria-label="Filtra per situazione">
                        <span class="sq-text-muted">Situazione:</span>
                        <a href="{{ route('backoffice.rimborsi.per_ordine', ['ordine' => $filtroOrdine, 'situazione' => 'tutti']) }}" @class(['is-active' => ($situazione ?? 'tutti') === 'tutti'])>Tutti</a>
                        <a href="{{ route('backoffice.rimborsi.per_ordine', ['ordine' => $filtroOrdine, 'situazione' => 'attesa']) }}" @class(['is-active' => ($situazione ?? '') === 'attesa'])>In attesa</a>
                        <a href="{{ route('backoffice.rimborsi.per_ordine', ['ordine' => $filtroOrdine, 'situazione' => 'rimborsato']) }}" @class(['is-active' => ($situazione ?? '') === 'rimborsato'])>Rimborsati</a>
                    </div>
                @endif
                <div class="sq-bo-reemb-table-wrap sq-mt-16">
                    <table class="sq-bo-reemb-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Codice interno</th>
                                <th>Spedizione</th>
                                <th>Tracking</th>
                                <th>Richiesta</th>
                                <th>Prevista</th>
                                <th>Stato</th>
                                <th>Importo</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($lista as $r)
                                @php $s = $r->spedizione; @endphp
                                <tr>
                                    <td>#{{ $r->id }}</td>
                                    <td>{{ $r->codice_interno ?? '—' }}</td>
                                    <td class="sq-td-muted">#{{ $s?->id ?? '—' }}</td>
                                    <td class="sq-td-rastreio">{{ $s?->codigoRastreio() ?: '—' }}</td>
                                    <td class="sq-td-muted">{{ $r->data_richiesta?->format('d/m/Y') ?? '—' }}</td>
                                    <td class="sq-td-muted">{{ $r->data_prevista?->format('d/m/Y') ?? '—' }}</td>
                                    <td>{{ $r->data_reale ? 'Rimborsato' : 'In attesa' }}</td>
                                    <td class="sq-td-valore">{{ \App\Support\ImportoEuro::format($r->valore) }}</td>
                                    <td>
                                        @if (! $r->data_reale)
                                            <div class="sq-bo-reemb-acoes">
                                                <form method="POST" action="{{ route('backoffice.rimborsi.paga', $r) }}"
                                                      onsubmit="return confirm('Confermi l’accredito di {{ \App\Support\ImportoEuro::format($r->valore) }} sul wallet del cliente?');">
                                                    @csrf
                                                    <button type="submit" class="sq-filtri-submit">Accredita wallet</button>
                                                </form>
                                            </div>
                                        @else
                                            <span class="sq-td-muted">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @include('partials.tabella-paginazione', ['paginator' => $lista])
            @elseif (trim((string) ($filtroOrdine ?? '')) !== '' && empty($erro))
                <div class="sq-bo-reemb-empty sq-mt-16">Nessun rimborso trovato per questo ordine e filtro.</div>
            @endif
        </div>
    @endif
</div>
@endsection
