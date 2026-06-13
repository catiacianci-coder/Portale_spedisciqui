@extends('layouts.app')
@section('content')
<div class="home-spedizione-wrap">
    <h1 class="home-spedizione-title">I miei rimborsi</h1>

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

    <div class="sq-table-wrap sq-table-wrap--warm">
        <table class="sq-table">
            <thead>
                <tr class="sq-thead-row sq-thead-row--warm">
                    <th class="sq-th sq-th--warm">Token</th>
                    <th class="sq-th sq-th--warm">Spedizione</th>
                    <th class="sq-th sq-th--warm">Ordine</th>
                    <th class="sq-th sq-th--warm">Richiesta</th>
                    <th class="sq-th sq-th--warm">Prevista</th>
                    <th class="sq-th sq-th--warm">Importo</th>
                    <th class="sq-th sq-th--warm">Stato</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rimborsi as $r)
                    <tr id="rimborso-{{ $r->id }}">
                        <td class="sq-td sq-td--border-warm">{{ $r->token ?? '—' }}</td>
                        <td class="sq-td sq-td--border-warm">{{ $r->codice_interno }}</td>
                        <td class="sq-td sq-td--border-warm">{{ $r->spedizione?->ordine?->codice ?? '—' }}</td>
                        <td class="sq-td sq-td--border-warm">{{ $r->data_richiesta?->format('d/m/Y') ?? '—' }}</td>
                        <td class="sq-td sq-td--border-warm">{{ $r->data_prevista?->format('d/m/Y') ?? '—' }}</td>
                        <td class="sq-td sq-td--border-warm sq-td--right">{{ number_format((float) $r->valore, 2, ',', '.') }} €</td>
                        <td class="sq-td sq-td--border-warm">
                            @if ($r->data_reale)
                                <span class="sq-badge sq-badge--paid">Rimborsato {{ $r->data_reale->format('d/m/Y') }}</span>
                            @else
                                <span class="sq-badge sq-badge--unpaid">In attesa</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="sq-td sq-text-muted">Nessun rimborso trovato.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @include('partials.tabella-paginazione', ['paginator' => $rimborsi])
</div>

<script>
(() => {
    const hash = (window.location.hash || '').replace(/^#/, '');
    if (hash.indexOf('rimborso-') === 0) {
        const destRow = document.getElementById(hash);
        if (destRow) {
            destRow.classList.add('rimborso-row--evidenziato');
            destRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }
})();
</script>
@endsection
