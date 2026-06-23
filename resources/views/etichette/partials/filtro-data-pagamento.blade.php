@php
    $periodId = $periodId ?? 'period_etichette';
    $wrapId = $wrapId ?? 'wrap-date-etichette';
    $filtroPeriod = $filtroPeriod ?? 'tutti';
    $filtroDataDa = $filtroDataDa ?? '';
    $filtroDataA = $filtroDataA ?? '';
@endphp
<div class="campo sq-etichette-filtro-periodo">
    <label for="{{ $periodId }}" class="filtro-label">Data pagamento</label>
    <select id="{{ $periodId }}" name="period" class="js-filtro-periodo-etichette" data-wrap-target="{{ $wrapId }}">
        <option value="tutti" @selected($filtroPeriod === 'tutti')>Qualsiasi periodo</option>
        <option value="7" @selected($filtroPeriod === '7')>Ultimi 7 giorni</option>
        <option value="15" @selected($filtroPeriod === '15')>Ultimi 15 giorni</option>
        <option value="30" @selected($filtroPeriod === '30')>Ultimi 30 giorni</option>
        <option value="custom" @selected($filtroPeriod === 'custom')>Personalizzato</option>
    </select>
</div>
<div id="{{ $wrapId }}" class="campo filtros-custom-datas sq-etichette-filtro-custom @if($filtroPeriod === 'custom') is-on @endif">
    <div class="sq-etichette-filtro-custom-campo">
        <label for="{{ $wrapId }}_da" class="filtro-label">Da</label>
        <input id="{{ $wrapId }}_da" name="data_da" type="date" value="{{ $filtroDataDa }}">
    </div>
    <span class="sq-etichette-filtro-custom-sep" aria-hidden="true">–</span>
    <div class="sq-etichette-filtro-custom-campo">
        <label for="{{ $wrapId }}_a" class="filtro-label">A</label>
        <input id="{{ $wrapId }}_a" name="data_a" type="date" value="{{ $filtroDataA }}">
    </div>
</div>
<script>
(() => {
    document.querySelectorAll('.js-filtro-periodo-etichette').forEach((sel) => {
        const wrapId = sel.getAttribute('data-wrap-target');
        const wrap = wrapId ? document.getElementById(wrapId) : null;
        if (!wrap) return;
        const sync = () => {
            const isCustom = sel.value === 'custom';
            wrap.classList.toggle('is-on', isCustom);
            sel.closest('.sq-etichette-filtri')?.classList.toggle('sq-etichette-filtri--periodo-custom', isCustom);
        };
        sel.addEventListener('change', sync);
        sync();
    });
})();
</script>
