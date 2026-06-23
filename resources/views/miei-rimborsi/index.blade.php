@extends('layouts.app')
@section('content')
@php
    $fmt = fn ($n) => number_format((float) $n, 2, ',', '.');
    $ordineLabel = static function ($r): string {
        $oid = (int) ($r->ordine_id ?? $r->spedizione?->ordine_id ?? 0);

        return $oid > 0 ? (string) $oid : '—';
    };
    $statoLabel = static function ($r): string {
        if ($r->data_reale) {
            return 'Rimborsato '.$r->data_reale->format('d/m/Y');
        }

        return 'In attesa';
    };
@endphp
<div class="sq-bleed-layout">
    <x-sq-page-banner title="I miei rimborsi" icon="fa-money-bill-transfer" class="sq-page-banner--full" />

    <div class="home-spedizione-wrap sq-bo-reemb-page">
        <form method="GET" class="sq-filtri-form sq-mb-20">
            <p class="sq-filtri-title">Filtri</p>
            <div class="sq-filtri-row">
                <div>
                    <label class="sq-filtri-label" for="situazione">Situazione</label>
                    <select id="situazione" name="situazione" class="sq-filtri-email-input">
                        <option value="tutti" @selected($situazione === 'tutti')>Tutti</option>
                        <option value="attesa" @selected($situazione === 'attesa')>In attesa</option>
                        <option value="rimborsato" @selected($situazione === 'rimborsato')>Rimborsati</option>
                    </select>
                </div>
                <div>
                    <label class="sq-filtri-label" for="codice">Codice spedizione</label>
                    <input id="codice" name="codice" type="text" value="{{ e($codice) }}" class="sq-filtri-email-input">
                </div>
                <div class="sq-filtri-actions">
                    <button type="submit" class="sq-filtri-submit">Cerca</button>
                </div>
            </div>
        </form>

        <div class="sq-bo-reemb-card">
            <div class="sq-bo-reemb-table-wrap">
                <table class="sq-bo-reemb-table sq-miei-rimborsi-table">
                    <thead>
                        <tr>
                            <th>Spedizione</th>
                            <th>Ordine</th>
                            <th>Richiesta</th>
                            <th>Prevista</th>
                            <th>Importo</th>
                            <th>Stato</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rimborsi as $r)
                            <tr id="rimborso-{{ $r->id }}">
                                <td>{{ $r->codice_interno ?: '—' }}</td>
                                <td>{{ $ordineLabel($r) }}</td>
                                <td>{{ $r->data_richiesta?->format('d/m/Y') ?? '—' }}</td>
                                <td>{{ $r->data_prevista?->format('d/m/Y') ?? '—' }}</td>
                                <td>{{ \App\Support\ImportoEuro::format($r->valore) }}</td>
                                <td>{{ $statoLabel($r) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="sq-td-muted" style="padding:20px;text-align:center;">Nessun rimborso trovato.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @include('partials.tabella-paginazione', ['paginator' => $rimborsi])
    </div>
</div>

<script>
(() => {
    const hash = (window.location.hash || '').replace(/^#/, '');
    if (hash.indexOf('rimborso-') === 0) {
        const destRow = document.getElementById(hash);
        if (destRow) {
            destRow.classList.add('sq-bo-reemb-row--evidenziato');
            destRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }
})();
</script>
@endsection
