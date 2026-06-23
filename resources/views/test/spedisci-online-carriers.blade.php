@extends('layouts.app')
@section('content')
<div class="sq-sim-page">
    <div class="sq-sim-card">
        <h1 class="sq-sim-h1">Corrieri disponibili — Spedisci.online</h1>
        <p class="sq-mb-14 sq-text-muted">
            Chiamata <code class="sq-code">GET /carriers</code> per elencare vettori e contratti attivi sull’account API.
            Utile per allineare <code class="sq-code">carrier_code</code> e <code class="sq-code">contract_code</code> in tabella <code class="sq-code">corrieri</code>.
        </p>

        <p class="sq-mb-14">
            <a href="{{ route('test.spedisci-online') }}" class="sq-link-back">← Prova API completa (rates / create / …)</a>
        </p>

        <form method="POST" action="{{ route('test.spedisci-online-carriers') }}" class="sq-sim-form sq-mb-24">
            @csrf
            <div class="sq-sim-row">
                <div class="sq-sim-field">
                    <label for="tenant"><strong>Tenant API</strong></label>
                    <select id="tenant" name="tenant" class="sq-sim-input">
                        <option value="eamulti" @selected($tenant === 'eamulti')>Eamultiexpr (hub principale)</option>
                        <option value="liccardi" @selected($tenant === 'liccardi')>Liccardi</option>
                    </select>
                </div>
            </div>
            <button type="submit" class="sq-btn-primary">Esegui GET /carriers</button>
        </form>

        <dl class="sq-sim-list-plain sq-mb-18">
            <li><strong>Base URL:</strong> <code class="sq-code">{{ $apiBase }}</code></li>
            <li><strong>API key:</strong> {{ $configured ? 'Configurata' : 'Mancante' }}</li>
        </dl>

        @if ($executed)
            @if ($errorMessage)
                <div class="sq-alert sq-alert--error sq-mb-18">{{ $errorMessage }}</div>
            @elseif ($httpStatus !== null)
                <div class="sq-alert sq-alert--success sq-mb-18">
                    HTTP {{ $httpStatus }} —
                    <strong>{{ count($carriers) }}</strong> vettore/i,
                    <strong>{{ count($contractRows) }}</strong> contratto/i.
                </div>
            @endif

            @if (count($contractRows) > 0)
                <h2 class="sq-sim-h2">Contratti disponibili (API)</h2>
                <div class="sq-sim-table-wrap sq-mb-24" style="overflow-x:auto;">
                    <table class="sq-table" style="width:100%; border-collapse:collapse;">
                        <thead>
                            <tr>
                                <th>Vettore (pannello)</th>
                                <th><code>carrierCode</code></th>
                                <th>Contratto (pannello)</th>
                                <th><code>contractCode</code></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($contractRows as $row)
                                <tr>
                                    <td>{{ $row['carrier_name'] }}</td>
                                    <td><code class="sq-code">{{ $row['carrier_code'] ?: '—' }}</code></td>
                                    <td>{{ $row['contract_name'] }}</td>
                                    <td><code class="sq-code">{{ $row['contract_code'] }}</code></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @elseif ($httpStatus !== null && ! $errorMessage)
                <p class="sq-text-muted sq-mb-18">Nessun contratto restituito dall’API (array vuoto o struttura non riconosciuta).</p>
            @endif

            @if ($rawBody !== null && $rawBody !== '')
                <details class="sq-mb-24">
                    <summary class="sq-sim-h2" style="cursor:pointer;">Risposta JSON grezza</summary>
                    <pre class="sq-sim-box sq-mt-12" style="overflow:auto; max-height:420px; font-size:12px;">{{ $rawBody }}</pre>
                </details>
            @endif
        @endif

        @if ($corrieriDb->isNotEmpty())
            <h2 class="sq-sim-h2">Corrieri attivi in Spedisciqui (DB)</h2>
            <p class="sq-text-muted sq-mb-12">Confronta i codici sotto con quelli restituiti dall’API.</p>
            <div class="sq-sim-table-wrap" style="overflow-x:auto;">
                <table class="sq-table" style="width:100%; border-collapse:collapse;">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Corriere / servizio</th>
                            <th>Piattaforma</th>
                            <th><code>carrier_code</code></th>
                            <th><code>contract_code</code></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($corrieriDb as $c)
                            <tr>
                                <td>#{{ $c->id }}</td>
                                <td>{{ $c->nome_corriere }} — {{ $c->nome_servizio }}</td>
                                <td><code class="sq-code">{{ $c->piattaforma }}</code></td>
                                <td><code class="sq-code">{{ $c->carrier_code ?: '—' }}</code></td>
                                <td><code class="sq-code">{{ $c->contract_code ?: '—' }}</code></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
