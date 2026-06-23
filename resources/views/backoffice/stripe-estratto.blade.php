@extends('layouts.app')

@section('content')
@php
    $fmt = static fn (float $n): string => number_format($n, 2, ',', '.');
    $baseQuery = array_merge($filtri->queryParams(), ['cerca' => '1']);
@endphp

<div class="home-spedizione-wrap backoffice-stripe-estratto">
    <p class="sq-intro">
        <a href="{{ route('backoffice.index') }}" class="sq-header-link">← Menu back office</a>
    </p>
    <p class="sq-intro sq-m-0">
        Estratto movimenti Stripe via API (non serve accedere alla dashboard Stripe). I dati provengono da
        <strong>Balance Transactions</strong>: pagamenti carta, rimborsi, commissioni Stripe e bonifici verso banca.
    </p>

    @if (! ($stripeConfigured ?? true))
        <div class="sq-alert sq-alert--error sq-mt-16">
            Stripe non configurato. Imposta la <strong>secret key</strong> in
            <a href="{{ route('backoffice.parametri_globali.edit') }}">Parametri globali</a>.
        </div>
    @else
        @if (! empty($filtroErrors))
            <div class="sq-alert sq-alert--error sq-mt-16">
                @foreach ($filtroErrors as $fe)
                    <div>{{ $fe }}</div>
                @endforeach
            </div>
        @endif

        @if (! empty($result['balance']))
            <div class="sq-bo-stripe-saldo sq-mt-16">
                <div>
                    <span class="sq-bo-stripe-saldo-label">Saldo disponibile</span>
                    <strong>{{ $fmt((float) $result['balance']['available']) }} {{ $result['balance']['currency'] }}</strong>
                </div>
                <div>
                    <span class="sq-bo-stripe-saldo-label">In attesa</span>
                    <strong>{{ $fmt((float) $result['balance']['pending']) }} {{ $result['balance']['currency'] }}</strong>
                </div>
            </div>
        @endif

        <form method="GET" action="{{ route('backoffice.stripe_estratto.index') }}" class="sq-filtri-form sq-mt-16">
            <input type="hidden" name="cerca" value="1">
            <p class="sq-filtri-title">Periodo</p>
            <div class="sq-bo-stripe-filtri-grid">
                <div>
                    <label for="period" class="sq-filtri-label">Intervallo</label>
                    <select id="period" name="period" class="sq-filtri-select">
                        <option value="oggi" @selected($filtri->period === 'oggi')>Oggi</option>
                        <option value="7" @selected($filtri->period === '7')>Ultimi 7 giorni</option>
                        <option value="15" @selected($filtri->period === '15')>Ultimi 15 giorni</option>
                        <option value="30" @selected($filtri->period === '30')>Ultimi 30 giorni</option>
                        <option value="custom" @selected($filtri->period === 'custom')>Personalizzato</option>
                    </select>
                </div>
                <div id="wrap-date-stripe-custom" class="sq-bo-stripe-custom-dates">
                    <label for="data_da" class="sq-filtri-label">Da</label>
                    <input id="data_da" name="data_da" type="date" value="{{ $filtri->dataDa }}" class="sq-filtri-email-input">
                </div>
                <div id="wrap-date-stripe-custom-a" class="sq-bo-stripe-custom-dates">
                    <label for="data_a" class="sq-filtri-label">A</label>
                    <input id="data_a" name="data_a" type="date" value="{{ $filtri->dataA }}" class="sq-filtri-email-input">
                </div>
                <div>
                    <label for="limit" class="sq-filtri-label">Righe per pagina</label>
                    <select id="limit" name="limit" class="sq-filtri-select">
                        @foreach ([25, 50, 100] as $n)
                            <option value="{{ $n }}" @selected($filtri->limit === $n)>{{ $n }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="sq-filtri-actions">
                    <button type="submit" class="sq-filtri-submit">Mostra estratto</button>
                </div>
            </div>
        </form>

        @if ($haRicerca && ($from ?? null) && ($to ?? null))
            <p class="sq-text-muted sq-mt-12 sq-mb-0">
                Periodo: <strong>{{ $from->format('d/m/Y') }}</strong> — <strong>{{ $to->format('d/m/Y') }}</strong>
                @if (! empty($result['righe']))
                    · {{ count($result['righe']) }} movimenti in questa pagina
                @endif
            </p>

            @if (! ($result['ok'] ?? true) && ! empty($result['message']))
                <div class="sq-alert sq-alert--error sq-mt-12">{{ $result['message'] }}</div>
            @elseif (empty($result['righe']))
                <p class="sq-text-muted sq-mt-16">Nessun movimento Stripe nel periodo selezionato.</p>
            @else
                <div class="sq-bo-stripe-actions-row sq-mt-12">
                    <a
                        href="{{ route('backoffice.stripe_estratto.index', array_merge($baseQuery, ['export' => 'csv'])) }}"
                        class="sq-btn-secondary sq-btn-sm"
                    >Scarica CSV (periodo completo)</a>
                </div>

                <div class="sq-table-wrap sq-mt-12">
                    <table class="sq-table sq-bo-stripe-table">
                        <thead>
                            <tr class="sq-thead-row">
                                <th class="sq-th">Data</th>
                                <th class="sq-th">Tipo</th>
                                <th class="sq-th">Descrizione</th>
                                <th class="sq-th sq-th--right">Lordo</th>
                                <th class="sq-th sq-th--right">Fee Stripe</th>
                                <th class="sq-th sq-th--right">Netto</th>
                                <th class="sq-th">Ordine</th>
                                <th class="sq-th">Riferimenti</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($result['righe'] as $r)
                                @php
                                    $amount = (float) ($r['amount'] ?? 0);
                                    $net = (float) ($r['net'] ?? 0);
                                @endphp
                                <tr>
                                    <td class="sq-td sq-nowrap sq-text-muted">
                                        {{ ($r['created_at'] ?? null)?->timezone(config('app.timezone'))->format('d/m/Y H:i') ?? '—' }}
                                    </td>
                                    <td class="sq-td">{{ $r['type_label'] ?? '—' }}</td>
                                    <td class="sq-td sq-bo-stripe-desc">{{ ($r['description'] ?? '') !== '' ? $r['description'] : '—' }}</td>
                                    <td class="sq-td sq-td--right @if($amount < 0) sq-bo-stripe-neg @endif">{{ \App\Support\ImportoEuro::format($amount) }}</td>
                                    <td class="sq-td sq-td--right sq-text-muted">{{ \App\Support\ImportoEuro::format((float) ($r['fee'] ?? 0)) }}</td>
                                    <td class="sq-td sq-td--right sq-fw-700 @if($net < 0) sq-bo-stripe-neg @endif">{{ \App\Support\ImportoEuro::format($net) }}</td>
                                    <td class="sq-td">
                                        @if (! empty($r['ordine_id']))
                                            <a href="{{ route('backoffice.spedizioni.index', ['cerca' => 1, 'numero_ordine' => $r['ordine_id']]) }}">#{{ $r['ordine_id'] }}</a>
                                        @else
                                            <span class="sq-text-muted">—</span>
                                        @endif
                                    </td>
                                    <td class="sq-td sq-text-13 sq-text-muted">
                                        @if (! empty($r['payment_intent_id']))
                                            <div><code>{{ \Illuminate\Support\Str::limit($r['payment_intent_id'], 28) }}</code></div>
                                        @endif
                                        @if (! empty($r['id']))
                                            <div class="sq-bo-stripe-tx-id">{{ $r['id'] }}</div>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @php
                    $prevUrl = $result['first_id']
                        ? route('backoffice.stripe_estratto.index', array_merge($baseQuery, [
                            'limit' => $filtri->limit,
                            'ending_before' => $result['first_id'],
                        ]))
                        : null;
                    $nextUrl = ($result['has_more'] ?? false) && ! empty($result['last_id'])
                        ? route('backoffice.stripe_estratto.index', array_merge($baseQuery, [
                            'limit' => $filtri->limit,
                            'starting_after' => $result['last_id'],
                        ]))
                        : null;
                @endphp
                <div class="sq-bo-stripe-pager sq-mt-12">
                    @if ($prevUrl)
                        <a href="{{ $prevUrl }}" class="sq-btn-secondary sq-btn-sm">← Precedenti</a>
                    @endif
                    @if ($nextUrl)
                        <a href="{{ $nextUrl }}" class="sq-btn-secondary sq-btn-sm">Successivi →</a>
                    @endif
                </div>
            @endif
        @elseif (! $haRicerca)
            <p class="sq-text-muted sq-mt-16">Seleziona il periodo e clicca <strong>Mostra estratto</strong>.</p>
        @endif
    @endif
</div>

<script>
(function () {
    var sel = document.getElementById('period');
    var wrapDa = document.getElementById('wrap-date-stripe-custom');
    var wrapA = document.getElementById('wrap-date-stripe-custom-a');
    if (!sel) return;
    function sync() {
        var on = sel.value === 'custom';
        if (wrapDa) wrapDa.style.display = on ? '' : 'none';
        if (wrapA) wrapA.style.display = on ? '' : 'none';
    }
    sel.addEventListener('change', sync);
    sync();
})();
</script>
@endsection
