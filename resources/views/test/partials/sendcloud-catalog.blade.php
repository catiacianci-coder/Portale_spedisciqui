@php
    $dbCodes = collect($dbActiveServices ?? [])->pluck('codice')->filter()->unique()->flip();
    $catalogInDb = static function (string $code) use ($dbCodes): bool {
        if ($code === '' || $dbCodes->isEmpty()) {
            return false;
        }

        return $dbCodes->has($code);
    };
@endphp

<hr class="sq-sim-hr">

<h2 class="sq-sim-h2">Catalogo Sendcloud (completo)</h2>
<p class="sq-mb-14 sq-text-muted">
    Elenco da <code class="sq-code">POST {{ $apiBase }}/shipping-options</code>
    con i valori predefiniti del form (IT → IT, <code class="sq-code">calculate_quotes: true</code>).
    @if (($valoreAssicurazioneTest ?? 0) > 0)
        Assicurazione test: <strong>{{ \App\Support\ImportoEuro::format((float) $valoreAssicurazioneTest) }}</strong>.
    @endif
    @if ($catalogLoaded ?? false)
        HTTP <strong>{{ $catalogHttpStatus ?? '—' }}</strong>
        — <strong>{{ count($catalogRows ?? []) }}</strong> servizi.
    @endif
</p>

@if (! empty($catalogError))
    <p class="sq-alert sq-alert--info-warm sq-mb-14">{{ $catalogError }}</p>
@endif

@if (! empty($catalogRows))
    <table class="sq-table sq-mb-24 sq-sc-catalog-table">
        <thead>
            <tr>
                <th>Nome servizio</th>
                <th>Codice servizio</th>
                <th>Corriere</th>
                <th>Contrassegno</th>
                <th>Tracking</th>
                <th class="sq-td--right">Assic. test</th>
                <th class="sq-td--right">Prezzo</th>
                <th>In DB</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($catalogRows as $row)
                @php $inDb = $catalogInDb((string) ($row['code'] ?? '')); @endphp
                <tr @class(['sq-sc-catalog-row--in-db' => $inDb])>
                    <td>{{ $row['name'] ?? '—' }}</td>
                    <td><code class="sq-code">{{ $row['code'] ?? '—' }}</code></td>
                    <td>{{ $row['carrier'] ?? '—' }}</td>
                    <td>{{ $row['contrassegno_label'] ?? '—' }}</td>
                    <td>{{ $row['tracking_label'] ?? '—' }}</td>
                    <td class="sq-td--right">{{ $row['insurance_label'] ?? '—' }}</td>
                    <td class="sq-td--right"><strong>{{ $row['price'] ?? '—' }}</strong></td>
                    <td>{{ $inDb ? 'Sì' : '—' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif

<h2 class="sq-sim-h2 sq-mt-24">Sendcloud attivi nel portale (DB)</h2>
<p class="sq-mb-14 sq-text-muted">
    Da tabella <code class="sq-code">corrieres</code>:
    <code class="sq-code">piattaforma = sendcloud</code>, <code class="sq-code">attivo = true</code>.
    <strong>{{ count($dbActiveServices ?? []) }}</strong> righe con codice valorizzato.
</p>

@if (empty($dbActiveServices))
    <p class="sq-text-muted sq-mb-24">Nessun corriere Sendcloud attivo con <code class="sq-code">codice_servizio</code>.</p>
@else
    @php
        $catalogCodes = collect($catalogRows ?? [])->pluck('code')->filter()->unique()->flip();
        $dbInCatalog = static function (string $code) use ($catalogCodes): bool {
            return $code !== '' && $catalogCodes->has($code);
        };
    @endphp
    <table class="sq-table sq-mb-24 sq-sc-catalog-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nome servizio</th>
                <th>Codice servizio</th>
                <th>In catalogo</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($dbActiveServices as $row)
                @php
                    $codice = (string) ($row['codice'] ?? '');
                    $inCatalog = $dbInCatalog($codice);
                @endphp
                <tr @class(['sq-sc-catalog-row--missing' => ! $inCatalog && ($catalogLoaded ?? false)])>
                    <td>{{ $row['id'] ?? '—' }}</td>
                    <td>{{ $row['nome'] !== '' ? $row['nome'] : '—' }}</td>
                    <td><code class="sq-code">{{ $codice !== '' ? $codice : '—' }}</code></td>
                    <td>
                        @if (! ($catalogLoaded ?? false))
                            —
                        @elseif ($inCatalog)
                            Sì
                        @else
                            <span style="color:#c2410c;font-weight:600;">No (codice non trovato)</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif
