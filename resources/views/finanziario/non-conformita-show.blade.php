@extends('layouts.app')
@section('content')
@php
    $fmt = fn ($n) => number_format((float) $n, 2, ',', '.');
    $rigaPagabile = fn ($r) => $r->stato_riga === \App\Models\nc_pratica_riga::STATO_NON_PAGATO && (float) $r->delta > 0.009;
    $haRighePagabili = $pratica->righe->contains(fn ($r) => $rigaPagabile($r));
@endphp
<div class="sq-page-960-tight">
    <p class="sq-m-0 sq-mb-12"><a href="{{ route('finanziario.nc.index') }}">← Non conformità</a></p>
    <h1 class="sq-h1-carrello sq-mb-8">Pratica {{ $pratica->numero_pratica }}</h1>
    <p class="sq-text-muted-14 sq-m-0 sq-mb-16">
        Stato: <strong>{{ $pratica->stato === \App\Models\nc_pratica::STATO_CHIUSO ? 'Chiusa' : 'Aperta' }}</strong>
        @if ($pratica->pdf_path)
            — <a href="{{ route('finanziario.nc.pdf', $pratica->id) }}">Scarica PDF</a>
        @endif
    </p>

    @php
        $residuoTot = (float) $pratica->righe
            ->where('stato_riga', \App\Models\nc_pratica_riga::STATO_NON_PAGATO)
            ->sum(fn ($r) => max((float) $r->delta, 0.0));
    @endphp

    <div class="sq-card sq-card--mb-14 sq-card--p-14-16">
        <p class="sq-m-0 sq-text-main">Residuo complessivo da pagare: <strong>{{ \App\Support\ImportoEuro::format($residuoTot) }}</strong></p>
        @if ($residuoTot > 0 && $pratica->stato === \App\Models\nc_pratica::STATO_APERTO)
            <form class="sq-mt-12" method="get" action="{{ route('pagamento_nc.index') }}">
                <input type="hidden" name="pratica" value="{{ $pratica->id }}">
                <input type="hidden" name="tutto" value="1">
                <button type="submit" class="sq-btn-primary">Paga tutta la pratica</button>
            </form>
        @endif
    </div>

    @if ($haRighePagabili)
        <form method="get" action="{{ route('pagamento_nc.index') }}" class="sq-nc-multi-pay-form" id="form-nc-paga-selezionate">
            <div class="sq-nc-select-all-bar">
                <label class="sq-nc-select-all-label">
                    <input type="checkbox" id="nc-pratica-select-all" class="sq-nc-select-all-input" autocomplete="off">
                    <span>Seleziona tutte le spedizioni in questa pratica</span>
                </label>
            </div>
    @endif
            <div class="sq-table-wrap">
                <table class="sq-table @if ($haRighePagabili) sq-table--nc-righe @endif">
                    <thead>
                        <tr class="sq-thead-row sq-thead-row--neutral">
                            @if ($haRighePagabili)
                                <th class="sq-th sq-th--nc-flag" scope="col"><span class="sq-sr-only">Selezione</span></th>
                            @endif
                            <th class="sq-th">Codice</th>
                            <th class="sq-th sq-th--right">Delta</th>
                            <th class="sq-th">Stato riga</th>
                            <th class="sq-th sq-th--right">Azione</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($pratica->righe as $r)
                            <tr>
                                @if ($haRighePagabili)
                                    <td class="sq-td sq-td--nc-flag sq-td--vcenter">
                                        @if ($rigaPagabile($r))
                                            <input type="checkbox" name="riga_id[]" value="{{ $r->id }}" class="sq-nc-riga-cb" autocomplete="off" aria-label="Seleziona spedizione {{ e($r->codice_interno) }}">
                                        @else
                                            <span class="sq-nc-flag-placeholder" aria-hidden="true"></span>
                                        @endif
                                    </td>
                                @endif
                                <td class="sq-td sq-fw-700">{{ $r->codice_interno }}</td>
                                <td class="sq-td sq-td--right">{{ \App\Support\ImportoEuro::format($r->delta) }}</td>
                                <td class="sq-td">{{ $r->stato_riga === \App\Models\nc_pratica_riga::STATO_PAGATO ? 'Pagata' : 'Da pagare' }}</td>
                                <td class="sq-td sq-td--right">
                                    @if ($rigaPagabile($r))
                                        <a href="{{ route('pagamento_nc.index', ['riga_id' => $r->id]) }}" class="sq-btn-pay-sm sq-btn-pay-sm--link">Paga</a>
                                    @else
                                        <span class="sq-text-muted">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
    @if ($haRighePagabili)
            <div class="sq-nc-paga-selezionate-wrap">
                <button type="submit" class="sq-btn-primary">Paga selezionate</button>
            </div>
        </form>
    @endif
</div>
@if ($haRighePagabili)
<script>
(() => {
    const form = document.getElementById('form-nc-paga-selezionate');
    const master = document.getElementById('nc-pratica-select-all');
    const cbs = () => Array.from(document.querySelectorAll('.sq-nc-riga-cb'));
    const syncMaster = () => {
        const list = cbs();
        if (!master || list.length === 0) return;
        const checked = list.filter((c) => c.checked).length;
        master.checked = checked === list.length;
        master.indeterminate = checked > 0 && checked < list.length;
    };
    master?.addEventListener('change', () => {
        const on = master.checked;
        cbs().forEach((cb) => { cb.checked = on; });
        master.indeterminate = false;
    });
    cbs().forEach((cb) => cb.addEventListener('change', syncMaster));
    syncMaster();
    form?.addEventListener('submit', (e) => {
        if (cbs().filter((c) => c.checked).length === 0) {
            e.preventDefault();
            alert('Seleziona almeno una spedizione da pagare.');
        }
    });
})();
</script>
@endif
@endsection
