@extends('layouts.app')
@section('content')
<div class="home-spedizione-wrap backoffice-nc-page">
    <p class="sq-intro">Importazione CSV, elenco pratiche e filtri. Le pratiche sono raggruppate per cliente (email): apri il blocco con l’icona per vedere le pratiche.</p>

    @if (session('nc_import_outcome') === 'fallito')
        <div class="sq-alert sq-alert--error sq-mb-18">
            <strong>Import non riuscito</strong>
            <p class="sq-m-0 sq-mt-8">Non è stata creata alcuna pratica. Controlla il file e i messaggi qui sotto.</p>
            @if (session('nc_import_warnings') && count((array) session('nc_import_warnings')) > 0)
                <ul class="sq-m-8 sq-mb-0">
                    @foreach ((array) session('nc_import_warnings') as $w)
                        <li>{{ $w }}</li>
                    @endforeach
                </ul>
            @endif
        </div>
    @elseif (session('nc_import_outcome') === 'parziale')
        <div class="sq-alert sq-alert--info-warm sq-mb-18">
            <strong>Import parziale</strong>
            <p class="sq-m-0 sq-mt-8">
                Sono state create <strong>{{ (int) session('nc_import_pratiche', 0) }}</strong>
                pratica/e e <strong>{{ (int) session('nc_import_righe', 0) }}</strong> riga/e.
                Alcune righe del file non sono state importate:
            </p>
            @if (session('nc_import_warnings') && count((array) session('nc_import_warnings')) > 0)
                <ul class="sq-m-8 sq-mb-0">
                    @foreach ((array) session('nc_import_warnings') as $w)
                        <li>{{ $w }}</li>
                    @endforeach
                </ul>
            @endif
        </div>
    @elseif (session('nc_import_outcome') === 'ok')
        <div class="sq-alert sq-alert--success sq-mb-18">
            <strong>Import completato con successo</strong>
            <p class="sq-m-0 sq-mt-8">
                Pratiche create: <strong>{{ (int) session('nc_import_pratiche', 0) }}</strong> —
                Righe importate: <strong>{{ (int) session('nc_import_righe', 0) }}</strong>.
            </p>
        </div>
    @endif
    @if ($errors->any())
        <div class="sq-alert sq-alert--error sq-mb-18">{{ $errors->first() }}</div>
    @endif
    @foreach ($filtroErrors as $fe)
        <div class="sq-alert sq-alert--error sq-mb-18">{{ $fe }}</div>
    @endforeach

    <section class="sq-mb-28 sq-card sq-card--p-14-16">
        <h2 class="sq-h2-brand sq-mb-12">Importa CSV</h2>
        <p class="sq-text-muted sq-m-0 sq-mb-12 sq-text-14">
            Obbligatorio: <code class="sq-code">codice_interno</code>. Opzionali: <code class="sq-code">email_cliente</code> (o <code class="sq-code">email</code>) e <code class="sq-code">prezzo_pagato</code> — se omessi vengono usati email utente e il prezzo netto della riga nell’ordine collegato.
            Misure dichiarate nel file (suffissi <code class="sq-code">_dich</code> / nomi estesi): se assenti si usano pacco salvato sulla spedizione. Misure rilevate dal corriere: suffissi <code class="sq-code">_corriere</code> o nomi estesi snake_case.
            Opzionale: <code class="sq-code">importo_dovuto</code> per forzare l’importo calcolato esternamente.
            Se Excel mostra tutto nella sola colonna A con i <code class="sq-code">;</code> nel testo, va bene: il file viene comunque interpretato.
        </p>
        <form method="POST" action="{{ route('backoffice.nc.import') }}" enctype="multipart/form-data" class="sq-form-zero">
            @csrf
            <input type="file" name="file_csv" accept=".csv,.txt" required class="sq-mb-12">
            <button type="submit" class="sq-btn-primary">Carica ed elabora</button>
        </form>
    </section>

    <section class="sq-mb-18">
        <h2 class="sq-h2-brand sq-mb-12">Filtri</h2>
        <form method="get" action="{{ route('backoffice.nc.index') }}" class="sq-nc-bo-filters">
            <div class="sq-nc-bo-field">
                <label class="sq-label-sm-muted">Periodo emissione</label>
                <select name="period" class="sq-select-bo">
                    <option value="" @selected($filtroPeriod === '')>Tutti</option>
                    <option value="oggi" @selected($filtroPeriod === 'oggi')>Oggi</option>
                    <option value="7" @selected($filtroPeriod === '7')>Ultimi 7 giorni</option>
                    <option value="15" @selected($filtroPeriod === '15')>Ultimi 15 giorni</option>
                    <option value="30" @selected($filtroPeriod === '30')>Ultimi 30 giorni</option>
                    <option value="custom" @selected($filtroPeriod === 'custom')>Personalizzato</option>
                </select>
            </div>
            <div class="sq-nc-bo-field">
                <label class="sq-label-sm-muted">Da (AAAA-MM-GG)</label>
                <input type="date" name="data_inizio" value="{{ $filtroDataInizio }}" class="sq-select-bo sq-nc-date">
            </div>
            <div class="sq-nc-bo-field">
                <label class="sq-label-sm-muted">A (AAAA-MM-GG)</label>
                <input type="date" name="data_fine" value="{{ $filtroDataFine }}" class="sq-select-bo sq-nc-date">
            </div>
            <div class="sq-nc-bo-field sq-nc-bo-field--grow">
                <label class="sq-label-sm-muted">Utente (email)</label>
                <input type="email" name="cliente" value="{{ $filtroCliente }}" class="sq-select-bo sq-nc-email" placeholder="email@esempio.it">
            </div>
            <div class="sq-nc-bo-field">
                <label class="sq-label-sm-muted">Stato pratica</label>
                <select name="stato_pratica" class="sq-select-bo">
                    <option value="" @selected($filtroStatoPratica === '')>Tutti</option>
                    <option value="pagate" @selected($filtroStatoPratica === 'pagate')>Pagata (chiusa)</option>
                    <option value="non_pagate" @selected($filtroStatoPratica === 'non_pagate')>Non pagata (aperta, nessuna riga saldata)</option>
                    <option value="parziali" @selected($filtroStatoPratica === 'parziali')>Parzialmente pagata</option>
                </select>
            </div>
            <div class="sq-nc-bo-field">
                <label class="sq-label-sm-muted">N. pratica</label>
                <input type="text" name="numero_pratica" value="{{ $filtroNumeroPratica }}" class="sq-select-bo" placeholder="PRATNC-…">
            </div>
            <div class="sq-nc-bo-actions">
                <button type="submit" class="sq-btn-primary">Applica</button>
            </div>
        </form>
    </section>

    <section>
        <h2 class="sq-h2-brand sq-mb-12">Pratiche per cliente</h2>
        @if ($pratichePerCliente->isEmpty())
            <p class="sq-text-muted sq-m-0">Nessuna pratica con i filtri attuali.</p>
        @else
            @php
                $fmtEuro = static fn (float $v): string => number_format($v, 2, ',', '.');
            @endphp
            <div class="sq-nc-bo-client-list">
                @foreach ($pratichePerCliente as $blocco)
                    <details class="sq-nc-bo-client-details" aria-label="Pratiche non conformità per {{ e($blocco['user']?->email ?? 'cliente') }}">
                        <summary>
                            <span class="sq-sr-only">Mostra o nascondi l’elenco delle pratiche per questo cliente.</span>
                            <span class="sq-nc-bo-client-mail-only">{{ $blocco['user']?->email ?? '—' }}</span>
                            <span class="sq-nc-bo-client-toggle" aria-hidden="true">
                                <i class="fa-solid fa-eye sq-nc-ico-when-closed"></i>
                                <i class="fa-solid fa-eye-slash sq-nc-ico-when-open"></i>
                            </span>
                        </summary>
                        <div class="sq-nc-bo-client-inner">
                            <div class="sq-table-wrap sq-table-wrap--warm">
                                <table class="sq-table">
                                    <thead>
                                        <tr class="sq-thead-row sq-thead-row--warm">
                                            <th class="sq-th sq-th--warm">Pratica</th>
                                            <th class="sq-th sq-th--warm">Emissione</th>
                                            <th class="sq-th sq-th--warm">Stato</th>
                                            <th class="sq-th sq-th--warm sq-th--right">Totale pratica €</th>
                                            <th class="sq-th sq-th--warm sq-th--right">Totale aperto €</th>
                                            <th class="sq-th sq-th--warm sq-th--center">Spedizioni</th>
                                        </tr>
                                    </thead>
                                    @foreach ($blocco['pratiche'] as $p)
                                        @php
                                            $totPratica = (float) $p->righe->sum('delta');
                                            $totAperto = (float) $p->righe
                                                ->where('stato_riga', \App\Models\nc_pratica_riga::STATO_NON_PAGATO)
                                                ->sum('delta');
                                            $nSped = $p->righe->count();
                                            $statoLabel = $p->isParziale()
                                                ? 'Parziale'
                                                : match ($p->stato) {
                                                    \App\Models\nc_pratica::STATO_APERTO => 'Aperta',
                                                    \App\Models\nc_pratica::STATO_CHIUSO => 'Chiusa',
                                                    default => $p->stato,
                                                };
                                        @endphp
                                        <tbody class="sq-nc-bo-pratica-group">
                                            <tr class="sq-nc-bo-pratica-main">
                                                <td class="sq-td sq-td--border-warm sq-fw-700">{{ $p->numero_pratica }}</td>
                                                <td class="sq-td sq-td--border-warm sq-text-muted">{{ $p->created_at?->format('d/m/Y H:i') ?? '—' }}</td>
                                                <td class="sq-td sq-td--border-warm">{{ $statoLabel }}</td>
                                                <td class="sq-td sq-td--border-warm sq-td--right">{{ $fmtEuro($totPratica) }}</td>
                                                <td class="sq-td sq-td--border-warm sq-td--right">{{ $fmtEuro($totAperto) }}</td>
                                                <td class="sq-td sq-td--border-warm sq-nc-bo-sped-trigger-cell">
                                                    @if ($nSped > 0)
                                                        <div class="sq-nc-bo-sped-trigger-wrap">
                                                            <input type="checkbox" id="nc-sped-p-{{ $p->id }}" class="sq-nc-bo-sped-cb">
                                                            <label for="nc-sped-p-{{ $p->id }}" class="sq-nc-bo-sped-lbl">
                                                                <span class="sq-sr-only">Mostra o nascondi l’elenco spedizioni per la pratica {{ e($p->numero_pratica) }}.</span>
                                                                {{ $nSped }} {{ $nSped === 1 ? 'spedizione' : 'spedizioni' }}
                                                            </label>
                                                        </div>
                                                    @else
                                                        <span class="sq-text-muted sq-nc-bo-sped-zero">0</span>
                                                    @endif
                                                </td>
                                            </tr>
                                            @if ($nSped > 0)
                                                <tr class="sq-nc-bo-sped-wrap-tr">
                                                    <td colspan="6" class="sq-nc-bo-sped-wrap-td">
                                                        <div class="sq-nc-bo-sped-panel">
                                                            <table class="sq-nc-bo-sped-inner-table">
                                                                <thead>
                                                                    <tr>
                                                                        <th>Data ordine</th>
                                                                        <th>Codice interno</th>
                                                                        <th>Ordine</th>
                                                                        <th>Tracking</th>
                                                                        <th class="sq-nc-bo-sped-inner-num">Pagato €</th>
                                                                        <th class="sq-nc-bo-sped-inner-num">Dovuto €</th>
                                                                        <th class="sq-nc-bo-sped-inner-num">Diff. €</th>
                                                                        <th class="sq-nc-bo-sped-inner-center">Stato</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    @foreach ($p->righe as $r)
                                                                        @php
                                                                            $ord = $r->spedizione?->ordine;
                                                                            $dataOrd = $ord?->created_at;
                                                                            $stSped = $r->stato_riga === \App\Models\nc_pratica_riga::STATO_PAGATO ? 'Pagata' : 'Non pagata';
                                                                            $track = trim((string) ($r->spedizione?->tracking ?? ''));
                                                                        @endphp
                                                                        <tr>
                                                                            <td>{{ $dataOrd?->format('d/m/Y H:i') ?? '—' }}</td>
                                                                            <td><strong>{{ $r->codice_interno }}</strong></td>
                                                                            <td>{{ $ord?->codice ?? '—' }}</td>
                                                                            <td class="sq-nc-bo-sped-inner-track">{{ $track !== '' ? e(\Illuminate\Support\Str::limit($track, 48)) : '—' }}</td>
                                                                            <td class="sq-nc-bo-sped-inner-num">{{ $fmtEuro((float) $r->prezzo_pagato) }}</td>
                                                                            <td class="sq-nc-bo-sped-inner-num">{{ $fmtEuro((float) $r->importo_dovuto) }}</td>
                                                                            <td class="sq-nc-bo-sped-inner-num">{{ $fmtEuro((float) $r->delta) }}</td>
                                                                            <td class="sq-nc-bo-sped-inner-center">{{ $stSped }}</td>
                                                                        </tr>
                                                                    @endforeach
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endif
                                        </tbody>
                                    @endforeach
                                </table>
                            </div>
                        </div>
                    </details>
                @endforeach
            </div>
        @endif
    </section>
</div>
<script>
(function () {
    document.addEventListener('DOMContentLoaded', function () {
        var list = document.querySelector('.sq-nc-bo-client-list');
        if (!list) return;
        var blocks = list.querySelectorAll('.sq-nc-bo-client-details');
        blocks.forEach(function (el) {
            el.addEventListener('toggle', function () {
                if (!el.open) return;
                blocks.forEach(function (other) {
                    if (other !== el) other.removeAttribute('open');
                });
            });
        });
    });
})();
</script>
@endsection
