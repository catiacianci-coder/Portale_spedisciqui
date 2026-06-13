@php
    $periodId = $periodId ?? 'period';
    $wrapId = $wrapId ?? 'wrap-date-custom';
    $filtroPeriod = $filtroPeriod ?? '30';
    $filtroDataDa = $filtroDataDa ?? '';
    $filtroDataA = $filtroDataA ?? '';
    $showOggi = $showOggi ?? false;
    $showTutti = $showTutti ?? false;
    $showMeseScorso = $showMeseScorso ?? false;
    $labelPeriodo = $labelPeriodo ?? 'Periodo';
@endphp
<div class="sq-filtri-periodo-wrap">
    <div>
        <label for="{{ $periodId }}" class="sq-filtri-label">{{ $labelPeriodo }}</label>
        <select id="{{ $periodId }}" name="period" class="sq-filtri-select js-filtro-periodo" data-wrap-target="{{ $wrapId }}">
            @if ($showTutti)
                <option value="tutti" @selected($filtroPeriod === 'tutti')>Tutti</option>
            @endif
            @if ($showOggi)
                <option value="oggi" @selected($filtroPeriod === 'oggi')>Oggi</option>
            @endif
            <option value="7" @selected($filtroPeriod === '7')>Ultimi 7 giorni</option>
            <option value="15" @selected($filtroPeriod === '15')>Ultimi 15 giorni</option>
            <option value="30" @selected($filtroPeriod === '30')>Ultimi 30 giorni</option>
            @if ($showMeseScorso)
                <option value="mese_scorso" @selected($filtroPeriod === 'mese_scorso')>Mese scorso</option>
            @endif
            <option value="custom" @selected($filtroPeriod === 'custom')>Personalizzato</option>
        </select>
    </div>
    <div id="{{ $wrapId }}" class="sq-filtri-dates @if($filtroPeriod === 'custom') is-open @endif">
        <div>
            <label for="{{ $wrapId }}_da" class="sq-filtri-label">Da</label>
            <input id="{{ $wrapId }}_da" name="data_da" type="date" value="{{ $filtroDataDa }}" class="sq-filtri-date-input">
        </div>
        <div>
            <label for="{{ $wrapId }}_a" class="sq-filtri-label">A</label>
            <input id="{{ $wrapId }}_a" name="data_a" type="date" value="{{ $filtroDataA }}" class="sq-filtri-date-input">
        </div>
    </div>
</div>
<script>
(() => {
    document.querySelectorAll('.js-filtro-periodo').forEach((sel) => {
        const wrapId = sel.getAttribute('data-wrap-target');
        const wrap = wrapId ? document.getElementById(wrapId) : null;
        if (!wrap) return;
        const sync = () => wrap.classList.toggle('is-open', sel.value === 'custom');
        sel.addEventListener('change', sync);
        sync();
    });
})();
</script>
