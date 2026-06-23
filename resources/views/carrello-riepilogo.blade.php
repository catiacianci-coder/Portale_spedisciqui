@extends('layouts.app')
@section('content')
<div class="carrello-page sq-page-preventivi">
    @if (session('ok'))
        <div class="sq-alert sq-alert--success sq-mb-16">
            {{ session('ok') }}
        </div>
    @endif

    @if ($errors->has('carrello'))
        <div class="sq-alert sq-alert--error sq-mb-16">
            {{ $errors->first('carrello') }}
        </div>
    @endif

    @php
        $lv = $liccardiVolume ?? ['applicato' => false, 'righe_liccardi' => 0, 'sconto_totale' => 0.0];
    @endphp
    @if ($lv['applicato'] ?? false)
        <div class="sq-alert sq-alert--success sq-mb-16">
            Sconto volume Liccardi applicato: −{{ \App\Support\ImportoEuro::format((float) ($lv['sconto_totale'] ?? 0)) }}
            su {{ (int) ($lv['righe_liccardi'] ?? 0) }} spedizioni.
        </div>
    @endif

    <ul class="sq-carrello-list sq-mb-18">
        @foreach ($items as $it)
            <li class="sq-list-item-none">
                <div class="sq-carrello-row">
                    <div class="sq-carrello-card-wrap">
                        @include('partials.spedizione-card-operativa', [
                            'it' => $it,
                            'spedCardWhiteBg' => true,
                            'spedCardCompact' => true,
                        ])
                    </div>
                    <form method="POST" action="{{ route('carrello.rimuovi') }}" class="sq-form-shrink"
                          onsubmit="return confirm('Rimuovendo questa spedizione perdi tutti i dati associati (indirizzi, pacco, servizi e prezzo). L’operazione non è annullabile. Vuoi procedere?');">
                        @csrf
                        @if (! empty($it['id']))
                            <input type="hidden" name="item_id" value="{{ $it['id'] }}">
                        @else
                            <input type="hidden" name="item_index" value="{{ $loop->index }}">
                        @endif
                        <button type="submit" class="sq-ordini-icon-action sq-ordini-icon-action--remove"
                                title="Rimuovi dal carrello" aria-label="Rimuovi dal carrello">
                            <i class="fa-solid fa-trash-can" aria-hidden="true"></i>
                        </button>
                    </form>
                </div>
            </li>
        @endforeach
    </ul>

    <div class="sq-card sq-mb-18">
        <h2 class="sq-h2-card sq-mb-14">Seleziona il metodo di pagamento</h2>
        <div class="sq-table-wrap">
            <table class="sq-table">
                <thead>
                    <tr class="sq-thead-row sq-thead-row--neutral">
                        <th class="sq-th sq-th--8">Metodo</th>
                        <th class="sq-th sq-th--8 sq-th--right">Trasporto</th>
                        <th class="sq-th sq-th--8 sq-th--right">Servizi</th>
                        <th class="sq-th sq-th--8 sq-th--right">Imponibile</th>
                        <th class="sq-th sq-th--8 sq-th--right">IVA</th>
                        <th class="sq-th sq-th--8 sq-th--right">Totale</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($metodiJson as $mj)
                        <tr class="js-metodo-row" data-pct="{{ $mj['pct'] }}" data-abs="{{ $mj['abs'] }}">
                            <td class="sq-td sq-td--8 sq-fw-700">{{ $mj['nome'] }}</td>
                            <td class="js-td-trasporto sq-td sq-td--8 sq-td--right sq-nowrap">—</td>
                            <td class="js-td-servizi sq-td sq-td--8 sq-td--right sq-nowrap">—</td>
                            <td class="js-td-imponibile sq-td sq-td--8 sq-td--right sq-nowrap">—</td>
                            <td class="js-td-iva sq-td sq-td--8 sq-td--right sq-nowrap">—</td>
                            <td class="js-td-totale sq-td sq-td--8 sq-td--right sq-nowrap sq-fw-700">—</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="sq-carrello-footer-bar sq-carrello-footer-bar--solo-cta">
        <div class="sq-carrello-footer-cta">
            <form method="POST" action="{{ route('carrello.conferma') }}" class="sq-form-zero sq-carrello-conferma-form">
                @csrf
                <button type="submit" class="sq-btn-cta-lg sq-btn-cta-lg--button">
                    Conferma ordine e chiudi il carrello (stato: non pagato)
                </button>
            </form>
        </div>
    </div>
</div>

<script>
(() => {
    const baseTrasporto = @json($totaleTrasportoSolo);
    const extraFisso = @json($totaleExtraServizi);
    const aliquotaIva = @json($aliquotaIva);
    const formatIt = (n) => new Intl.NumberFormat('it-IT', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(n);
    const parseNum = (v) => {
        if (v === null || v === undefined) return 0;
        const s = String(v).trim();
        if (!s) return 0;
        const x = Number(s.replace(',', '.'));
        return Number.isFinite(x) ? x : 0;
    };
    const netto = baseTrasporto + extraFisso;
    document.querySelectorAll('.carrello-page .js-metodo-row').forEach((row) => {
        const pct = parseNum(row.getAttribute('data-pct'));
        const abs = parseNum(row.getAttribute('data-abs'));
        const imponibile = netto * (1 + pct / 100) + abs;
        const iva = imponibile * (aliquotaIva / 100);
        const totale = imponibile + iva;
        const tTr = row.querySelector('.js-td-trasporto');
        const tSe = row.querySelector('.js-td-servizi');
        const tIm = row.querySelector('.js-td-imponibile');
        const tIv = row.querySelector('.js-td-iva');
        const tTo = row.querySelector('.js-td-totale');
        if (tTr) tTr.textContent = '€ ' + formatIt(baseTrasporto);
        if (tSe) tSe.textContent = '€ ' + formatIt(extraFisso);
        if (tIm) tIm.textContent = '€ ' + formatIt(imponibile);
        if (tIv) tIv.textContent = '€ ' + formatIt(iva);
        if (tTo) tTo.textContent = '€ ' + formatIt(totale);
    });
})();
</script>
@endsection
