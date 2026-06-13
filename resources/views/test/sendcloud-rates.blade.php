@extends('layouts.app')
@section('content')
<div class="sq-sim-page sq-sendcloud-test-page">
    <div class="sq-sim-card">
        <h1 class="sq-sim-h1">Prova API Sendcloud</h1>
        <p class="sq-mb-14 sq-text-muted">
            Pagina di collaudo (non è la pagina preventivi cliente). Base API:
            <code class="sq-code">{{ $apiBase }}</code>
        </p>

        @if (! $configured)
            <p class="sq-alert sq-alert--info-warm">
                Imposta <code class="sq-code">sendcloud_public_key</code> e <code class="sq-code">sendcloud_secret_key</code> in
                <a href="{{ route('backoffice.parametri_globali.edit') }}">Parametri globali</a>.
            </p>
        @endif

        @include('test.partials.sendcloud-catalog')

        <hr class="sq-sim-hr">

        <h2 class="sq-sim-h2">1. Preventivi — shipping-options</h2>
        <p class="sq-mb-14 sq-text-muted">
            <code class="sq-code">POST {{ $apiBase }}/shipping-options</code>
            — IT → IT, <code class="sq-code">calculate_quotes: true</code>.
            Filtro codici da <code class="sq-code">corrieres</code> (sendcloud attivi): <strong>{{ count($allowedCodes ?? []) }}</strong>.
        </p>

        <form method="POST" action="{{ route('test.sendcloud-rates') }}" class="sq-sim-form sq-mb-24">
            @csrf
            <input type="hidden" name="probe" value="rates">
            <div class="sq-sim-row">
                <div class="sq-sim-field">
                    <label for="cap_origine"><strong>CAP origine</strong></label>
                    <input id="cap_origine" name="cap_origine" class="sq-sim-input"
                           value="{{ old('cap_origine', $input['cap_origine']) }}">
                </div>
                <div class="sq-sim-field">
                    <label for="citta_origine"><strong>Città origine</strong></label>
                    <input id="citta_origine" name="citta_origine" class="sq-sim-input"
                           value="{{ old('citta_origine', $input['citta_origine']) }}">
                </div>
                <div class="sq-sim-field">
                    <label for="cap_destino"><strong>CAP destino</strong></label>
                    <input id="cap_destino" name="cap_destino" class="sq-sim-input"
                           value="{{ old('cap_destino', $input['cap_destino']) }}">
                </div>
                <div class="sq-sim-field">
                    <label for="citta_destino"><strong>Città destino</strong></label>
                    <input id="citta_destino" name="citta_destino" class="sq-sim-input"
                           value="{{ old('citta_destino', $input['citta_destino']) }}">
                </div>
            </div>
            <div class="sq-sim-row">
                <div class="sq-sim-field">
                    <label for="spessore"><strong>Lunghezza (cm)</strong></label>
                    <input id="spessore" name="spessore" class="sq-sim-input"
                           value="{{ old('spessore', $input['spessore']) }}">
                </div>
                <div class="sq-sim-field">
                    <label for="larghezza"><strong>Larghezza (cm)</strong></label>
                    <input id="larghezza" name="larghezza" class="sq-sim-input"
                           value="{{ old('larghezza', $input['larghezza']) }}">
                </div>
                <div class="sq-sim-field">
                    <label for="altezza"><strong>Altezza (cm)</strong></label>
                    <input id="altezza" name="altezza" class="sq-sim-input"
                           value="{{ old('altezza', $input['altezza']) }}">
                </div>
                <div class="sq-sim-field">
                    <label for="peso"><strong>Peso (kg)</strong></label>
                    <input id="peso" name="peso" class="sq-sim-input"
                           value="{{ old('peso', $input['peso']) }}">
                </div>
                <div class="sq-sim-field">
                    <label for="valore_assicurazione_test"><strong>Valore assicurazione test (€)</strong></label>
                    <input id="valore_assicurazione_test" name="valore_assicurazione_test" class="sq-sim-input"
                           value="{{ old('valore_assicurazione_test', $input['valore_assicurazione_test'] ?? '500') }}">
                </div>
            </div>
            <button type="submit" class="sq-sim-btn">Richiedi preventivi</button>
        </form>

        @if ($ratesSearched && ! empty($quoteRows))
            <h3 class="sq-sim-h2">Preventivi ({{ count($quoteRows) }})</h3>
            <table class="sq-table sq-mb-24" style="width:100%; border-collapse: collapse;">
                <thead>
                    <tr>
                        <th style="text-align:left; padding:8px; border-bottom:1px solid #ddd;">Codice</th>
                        <th style="text-align:left; padding:8px; border-bottom:1px solid #ddd;">Servizio</th>
                        <th style="text-align:left; padding:8px; border-bottom:1px solid #ddd;">Corriere</th>
                        <th style="text-align:left; padding:8px; border-bottom:1px solid #ddd;">Contrassegno</th>
                        <th style="text-align:left; padding:8px; border-bottom:1px solid #ddd;">Tracking</th>
                        <th style="text-align:right; padding:8px; border-bottom:1px solid #ddd;">Assic. test</th>
                        <th style="text-align:right; padding:8px; border-bottom:1px solid #ddd;">Prezzo</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($quoteRows as $row)
                        <tr>
                            <td style="padding:8px; border-bottom:1px solid #eee;"><code class="sq-code">{{ $row['code'] }}</code></td>
                            <td style="padding:8px; border-bottom:1px solid #eee;">{{ $row['name'] }}</td>
                            <td style="padding:8px; border-bottom:1px solid #eee;">{{ $row['carrier'] }}</td>
                            <td style="padding:8px; border-bottom:1px solid #eee;">{{ $row['contrassegno_label'] ?? '—' }}</td>
                            <td style="padding:8px; border-bottom:1px solid #eee;">{{ $row['tracking_label'] ?? '—' }}</td>
                            <td style="padding:8px; border-bottom:1px solid #eee; text-align:right;">{{ $row['insurance_label'] ?? '—' }}</td>
                            <td style="padding:8px; border-bottom:1px solid #eee; text-align:right;"><strong>{{ $row['price'] }}</strong></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        @if ($ratesSearched)
            @include('test.partials.spedisci-api-result', [
                'searched' => true,
                'endpoint' => $endpoint,
                'httpStatus' => $httpStatus,
                'errorMessage' => $errorMessage,
                'payload' => $payload,
                'rawBody' => $rawBody,
                'valoreAssicurazioneTest' => $valoreAssicurazioneTest ?? 0,
                'insurancePayload' => $insurancePayload ?? null,
                'insuranceHttpStatus' => $insuranceHttpStatus ?? null,
                'insuranceRawBody' => $insuranceRawBody ?? null,
                'insuranceError' => $insuranceError ?? null,
            ])
        @endif

        <hr class="sq-sim-hr sq-mt-24">

        <h2 class="sq-sim-h2">2. Punto mittente (deposito)</h2>
        <p class="sq-mb-14 sq-text-muted">
            <code class="sq-code">GET {{ $apiBase }}/service-points</code> — ricerca vicino al <strong>mittente</strong>.
            Elenco espandibile con <strong>orari</strong>; nessuna mappa a tutto schermo.
        </p>

        @include('test.partials.sendcloud-points-search-form', [
            'prefix' => 'mitt',
            'probe' => 'points_mittente',
            'submitLabel' => 'Cerca punti mittente',
            'input' => $input,
        ])

        @include('test.partials.sendcloud-points-mittente')

        @include('test.partials.sendcloud-points-debug', [
            'searched' => $mittSearched ?? false,
            'httpStatus' => $mittHttpStatus ?? null,
            'query' => $mittQuery ?? null,
            'rawBody' => $mittRawBody ?? null,
        ])

        <hr class="sq-sim-hr sq-mt-24">

        <h2 class="sq-sim-h2">3. Punto destinatario (consegna)</h2>
        <p class="sq-mb-14 sq-text-muted">
            Ricerca vicino al <strong>destinatario</strong>: <strong>elenco a sinistra</strong>, <strong>mappa compatta</strong> a destra (non fullscreen).
            Clic sulla riga per selezionare e centrare il pin.
        </p>

        @include('test.partials.sendcloud-points-search-form', [
            'prefix' => 'dest',
            'probe' => 'points_destinatario',
            'submitLabel' => 'Cerca punti destinatario',
            'input' => $input,
        ])

        @include('test.partials.sendcloud-points-destinatario', [
            'mapPrefix' => 'dest',
            'rows' => $destRows ?? [],
            'searched' => $destSearched ?? false,
            'errorMessage' => $destErrorMessage ?? null,
            'geocoding' => $destGeocoding ?? ['status' => null, 'precision' => null],
        ])

        @include('test.partials.sendcloud-points-debug', [
            'searched' => $destSearched ?? false,
            'httpStatus' => $destHttpStatus ?? null,
            'query' => $destQuery ?? null,
            'rawBody' => $destRawBody ?? null,
        ])

        <hr class="sq-sim-hr sq-mt-24">

        <h2 class="sq-sim-h2">4. Locker InPost (destinatario)</h2>
        <p class="sq-mb-14 sq-text-muted">
            Stessa API <code class="sq-code">GET {{ $apiBase }}/service-points</code> con
            <code class="sq-code">carrier_code = inpost_it</code> — locker vicino al destinatario
            (per i servizi <em>Address to Locker</em>).
        </p>

        @include('test.partials.sendcloud-points-search-form', [
            'prefix' => 'inpost',
            'probe' => 'points_inpost',
            'submitLabel' => 'Cerca locker InPost',
            'input' => $input,
            'defaultCarrierCode' => 'inpost_it',
            'defaultShopType' => 'locker',
            'defaultUseIntegration' => '0',
        ])

        @include('test.partials.sendcloud-points-destinatario', [
            'mapPrefix' => 'inpost',
            'rows' => $inpostRows ?? [],
            'searched' => $inpostSearched ?? false,
            'errorMessage' => $inpostErrorMessage ?? null,
            'geocoding' => $inpostGeocoding ?? ['status' => null, 'precision' => null],
            'listTitle' => 'Elenco locker InPost',
            'mapAriaLabel' => 'Mappa locker InPost',
            'selectLabel' => 'Seleziona locker InPost',
            'selectedTitle' => 'Locker InPost selezionato (per to_service_point)',
            'emptyHint' => 'Nessun locker InPost in questa zona.',
            'introHint' => 'Scegli il locker di consegna per InPost Address to Locker.',
        ])

        @include('test.partials.sendcloud-points-debug', [
            'searched' => $inpostSearched ?? false,
            'httpStatus' => $inpostHttpStatus ?? null,
            'query' => $inpostQuery ?? null,
            'rawBody' => $inpostRawBody ?? null,
        ])
    </div>
</div>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
<script>
(() => {
    document.querySelectorAll('.sq-sc-select-mitt').forEach((btn) => {
        btn.addEventListener('click', () => {
            let point;
            try {
                point = JSON.parse(btn.getAttribute('data-point') || '{}');
            } catch {
                return;
            }
            const box = document.getElementById('mitt-selected-box');
            const pre = document.getElementById('mitt-selected-json');
            if (!box || !pre) return;
            pre.textContent = JSON.stringify(point, null, 2);
            box.hidden = false;
            box.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        });
    });

    const initDestPointsMap = (prefix) => {
        const dataEl = document.getElementById(`${prefix}-points-data`);
        const mapEl = document.getElementById(`${prefix}-points-map`);
        if (!dataEl || !mapEl || typeof L === 'undefined') return;

        let points;
        try {
            points = JSON.parse(dataEl.textContent || '[]');
        } catch {
            return;
        }

        const list = document.getElementById(`${prefix}-points-list`);
        const selectedBox = document.getElementById(`${prefix}-selected-box`);
        const selectedJson = document.getElementById(`${prefix}-selected-json`);
        const confirmBtn = document.getElementById(`${prefix}-confirm-select`);
        const listHint = document.getElementById(`${prefix}-list-hint`);
        let activeIndex = null;

        const bounds = [];
        const markers = new Map();

        const focusPoint = (index) => {
            const point = points[index];
            if (!point) return;

            activeIndex = index;

            list?.querySelectorAll(`.sq-sc-dest-item[data-map-prefix="${prefix}"]`).forEach((btn) => {
                btn.classList.toggle('is-selected', Number(btn.dataset.index) === index);
                btn.setAttribute('aria-selected', Number(btn.dataset.index) === index ? 'true' : 'false');
            });

            const marker = markers.get(index);
            if (marker) {
                map.setView(marker.getLatLng(), Math.max(map.getZoom(), 15));
                marker.openPopup();
            }

            if (confirmBtn) {
                confirmBtn.disabled = false;
            }
            if (listHint) {
                listHint.textContent = `${point.name} — ${point.postal_code} ${point.city}`;
            }
        };

        const map = L.map(mapEl, { scrollWheelZoom: true });
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap',
        }).addTo(map);

        points.forEach((point, index) => {
            if (point.latitude == null || point.longitude == null) return;
            const lat = point.latitude;
            const lng = point.longitude;
            bounds.push([lat, lng]);
            const marker = L.marker([lat, lng]).addTo(map);
            marker.bindPopup(`<strong>${point.name}</strong><br>${point.address_line}`);
            markers.set(index, marker);
            marker.on('click', () => focusPoint(index));
        });

        if (bounds.length === 0) return;

        map.fitBounds(bounds, { padding: [24, 24] });

        const confirmSelection = () => {
            if (activeIndex === null) return;
            const point = points[activeIndex];
            if (!point || !selectedBox || !selectedJson) return;

            selectedJson.textContent = JSON.stringify(point, null, 2);
            selectedBox.hidden = false;
            selectedBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        };

        list?.querySelectorAll(`.sq-sc-dest-item[data-map-prefix="${prefix}"]`).forEach((btn) => {
            btn.addEventListener('click', () => focusPoint(Number(btn.dataset.index)));
        });

        confirmBtn?.addEventListener('click', confirmSelection);

        if (points.length > 0) {
            focusPoint(0);
        }

        setTimeout(() => map.invalidateSize(), 200);
    };

    ['dest', 'inpost'].forEach(initDestPointsMap);
})();
</script>
@endsection
