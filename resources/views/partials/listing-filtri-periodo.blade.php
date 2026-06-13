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
<div class="campo filtros-periodo-wrap">
    <label for="{{ $periodId }}" class="filtro-label">{{ $labelPeriodo }}</label>
    <select id="{{ $periodId }}" name="period" class="js-filtro-periodo" data-wrap-target="{{ $wrapId }}">
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
<div class="campo filtros-custom-datas @if($filtroPeriod === 'custom') is-on @endif" id="{{ $wrapId }}">
    <div>
        <label for="{{ $wrapId }}_da" class="filtro-label">Da</label>
        <input id="{{ $wrapId }}_da" name="data_da" type="date" value="{{ $filtroDataDa }}">
    </div>
    <span>–</span>
    <div>
        <label for="{{ $wrapId }}_a" class="filtro-label">A</label>
        <input id="{{ $wrapId }}_a" name="data_a" type="date" value="{{ $filtroDataA }}">
    </div>
</div>
