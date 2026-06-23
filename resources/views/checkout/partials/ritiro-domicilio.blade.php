@if ($ritiroDomicilio ?? false)
    @php
        $dateOpzioni = $dateRitiroOpzioni ?? [];
        $defaultData = old('data_ritiro', $dataRitiroSelezionata ?? ($dateOpzioni[0] ?? ''));
        $meseLabel = $dateOpzioni !== []
            ? \Illuminate\Support\Carbon::parse($dateOpzioni[0])->locale('it')->isoFormat('MMMM YYYY')
            : '';
        $defaultLabel = $defaultData !== ''
            ? \Illuminate\Support\Carbon::parse($defaultData)->locale('it')->isoFormat('dddd D MMMM YYYY')
            : '';
    @endphp

    <aside class="sq-checkout-aside sq-checkout-ritiro-panel" aria-labelledby="checkout-ritiro-title">
        <h2 class="sq-h2-aside sq-mb-8" id="checkout-ritiro-title">{{ $ritiroEtichetta ?? 'Data ritiro' }}</h2>
        <p class="sq-text-muted sq-text-14 sq-mb-14">
            Scegli il giorno in cui il corriere passerà a ritirare il pacco.
            Puoi selezionare uno dei prossimi {{ (int) ($giorniRitiro ?? 4) }} giorni lavorativi (lun–ven, a partire da domani).
        </p>

        @if ($meseLabel !== '')
            <p class="sq-checkout-ritiro-mese">
                <i class="fa-regular fa-calendar-days sq-checkout-ritiro-mese-icon" aria-hidden="true"></i>
                {{ ucfirst($meseLabel) }}
            </p>
        @endif

        <div class="sq-checkout-ritiro-calendario" id="checkout-ritiro-calendario" role="group" aria-label="Date ritiro disponibili">
            <div class="sq-checkout-ritiro-cal-head" aria-hidden="true">
                @foreach ($dateOpzioni as $data)
                    @php $dt = \Illuminate\Support\Carbon::parse($data); @endphp
                    <span class="sq-checkout-ritiro-cal-head-cell">{{ $dt->locale('it')->isoFormat('ddd') }}</span>
                @endforeach
            </div>
            <div class="sq-checkout-ritiro-cal-body">
                @foreach ($dateOpzioni as $data)
                    @php
                        $dt = \Illuminate\Support\Carbon::parse($data);
                        $sel = $defaultData === $data;
                        $labelLong = $dt->locale('it')->isoFormat('dddd D MMMM YYYY');
                    @endphp
                    <button type="button"
                            class="sq-checkout-ritiro-giorno {{ $sel ? 'is-selected' : '' }}"
                            data-date="{{ $data }}"
                            data-label-long="{{ $labelLong }}"
                            aria-pressed="{{ $sel ? 'true' : 'false' }}"
                            aria-label="Ritiro {{ $labelLong }}">
                        <span class="sq-checkout-ritiro-giorno-num">{{ $dt->format('d') }}</span>
                        <span class="sq-checkout-ritiro-giorno-mon">{{ $dt->locale('it')->isoFormat('MMM') }}</span>
                    </button>
                @endforeach
            </div>
        </div>

        <p class="sq-checkout-ritiro-scelta" id="checkout-ritiro-scelta" aria-live="polite">
            Ritiro:
            <strong id="checkout-ritiro-scelta-data">{{ $defaultLabel !== '' ? $defaultLabel : '—' }}</strong>
        </p>

        @error('data_ritiro')
            <p class="sq-profilo-err sq-mt-8">{{ $message }}</p>
        @enderror
    </aside>

    @if (! empty($ritiroApiRisposta))
        @php
            $ritiroEndpoint = $ritiroApiRisposta['endpoint'] ?? 'POST /pickup/create';
            $ritiroProvider = $ritiroApiRisposta['provider'] ?? 'spedisci_online';
            $ritiroProviderLabel = $ritiroProvider === 'sendcloud' ? 'Sendcloud' : 'Spedisci.online';
        @endphp
        <aside class="sq-checkout-aside sq-checkout-ritiro-api sq-mt-0">
            <h3 class="sq-h3-aside sq-mb-8">Risposta API ritiro ({{ $ritiroProviderLabel }})</h3>
            <p class="sq-text-muted sq-text-14 sq-mb-8">
                Esito: <strong class="{{ ($ritiroApiRisposta['ok'] ?? false) ? 'sq-text-success' : 'sq-text-danger' }}">
                    {{ ($ritiroApiRisposta['ok'] ?? false) ? 'OK' : 'Errore' }}
                </strong>
                @if (! empty($ritiroApiRisposta['message']))
                    — {{ $ritiroApiRisposta['message'] }}
                @endif
                @if (! empty($ritiroApiRisposta['pickup_id']))
                    · pickupId: <code class="sq-code">{{ $ritiroApiRisposta['pickup_id'] }}</code>
                @endif
            </p>
            @if (! empty($ritiroApiRisposta['payload']))
                <details class="sq-servizio-api-trace-call" open>
                    <summary>Invio · {{ $ritiroEndpoint }}</summary>
                    <pre class="sq-pre-json">{{ json_encode($ritiroApiRisposta['payload'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                </details>
            @endif
            @if (array_key_exists('response', $ritiroApiRisposta))
                <details class="sq-servizio-api-trace-call" open>
                    <summary>Riceviamo · HTTP {{ $ritiroApiRisposta['http_status'] ?? '—' }}</summary>
                    <pre class="sq-pre-json">{{ json_encode($ritiroApiRisposta['response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                </details>
            @endif
        </aside>
    @endif
@endif
