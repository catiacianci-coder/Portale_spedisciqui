@php
    /** @var \App\Support\ParametriGlobaliFiltri $filtriParametri */
    $filtriParametri = $filtriParametri ?? \App\Support\ParametriGlobaliFiltri::daRequest(request());
@endphp
<div class="sq-bo-utilities-filtri-wrap">
    <div class="sq-bo-utilities-filtri__head">
        <div>
            <h3 class="sq-bo-utilities-filtri__title">Filtri ricerca</h3>
            <p class="sq-bo-utilities-filtri__count">
                {{ $parametri->count() }} record visualizzati
                @if ($filtriParametri->haFiltri())
                    su {{ $parametriTotali }} totali
                @endif
            </p>
        </div>
        <button type="button" id="sq-bo-util-nuovo-open" class="sq-bo-utilities-nuovo-btn">
            <span aria-hidden="true">+</span> Nuovo parametro
        </button>
    </div>

    <form method="GET" action="{{ route('backoffice.utilities.index') }}" class="sq-bo-utilities-filtri" id="sq-bo-utilities-filtri-form" autocomplete="off">
        <input type="hidden" name="vista" value="parametri">
        <div class="sq-bo-utilities-filtri__row">
            <div class="sq-bo-utilities-filtri__campo sq-bo-utilities-filtri__campo--denom">
                <label class="sq-bo-utilities-filtri__label" for="filtro-pg-denominazione">Denominazione</label>
                <select id="filtro-pg-denominazione" name="denominazione" class="sq-bo-util-inp sq-bo-util-inp--filtro">
                    <option value="">Tutte</option>
                    @foreach ($denominazioni as $denom)
                        <option value="{{ $denom }}" @selected($filtriParametri->denominazione === $denom)>{{ $denom }}</option>
                    @endforeach
                </select>
            </div>

            <div class="sq-bo-utilities-filtri__gruppo">
                <span class="sq-bo-utilities-filtri__gruppo-titolo">Inizio validità</span>
                <div class="sq-bo-utilities-filtri__date-range">
                    <input type="date" id="filtro-pg-inizio-da" name="inizio_da" value="{{ $filtriParametri->inizioDa }}" class="sq-bo-util-inp sq-bo-util-inp--filtro" aria-label="Inizio validità da">
                    <span class="sq-bo-utilities-filtri__sep">–</span>
                    <input type="date" id="filtro-pg-inizio-a" name="inizio_a" value="{{ $filtriParametri->inizioA }}" class="sq-bo-util-inp sq-bo-util-inp--filtro" aria-label="Inizio validità a">
                </div>
            </div>

            <div class="sq-bo-utilities-filtri__gruppo">
                <span class="sq-bo-utilities-filtri__gruppo-titolo">Fine validità</span>
                <div class="sq-bo-utilities-filtri__fine-wrap">
                    <label class="sq-bo-utilities-filtri__check">
                        <input type="checkbox" name="fine_null" value="1" id="filtro-pg-fine-null" @checked($filtriParametri->fineNull)>
                        Null
                    </label>
                    <div class="sq-bo-utilities-filtri__date-range js-pg-fine-date-range">
                        <input type="date" id="filtro-pg-fine-da" name="fine_da" value="{{ $filtriParametri->fineDa }}" class="sq-bo-util-inp sq-bo-util-inp--filtro js-pg-fine-date" aria-label="Fine validità da" @disabled($filtriParametri->fineNull)>
                        <span class="sq-bo-utilities-filtri__sep">–</span>
                        <input type="date" id="filtro-pg-fine-a" name="fine_a" value="{{ $filtriParametri->fineA }}" class="sq-bo-util-inp sq-bo-util-inp--filtro js-pg-fine-date" aria-label="Fine validità a" @disabled($filtriParametri->fineNull)>
                    </div>
                </div>
            </div>

            <div class="sq-bo-utilities-filtri__actions">
                <button type="submit" class="sq-bo-utilities-filtri__btn sq-bo-utilities-filtri__btn--filtra">Filtra</button>
                @if ($filtriParametri->haFiltri())
                    <a href="{{ route('backoffice.utilities.index', ['vista' => 'parametri']) }}" class="sq-bo-utilities-filtri__btn sq-bo-utilities-filtri__btn--azzera">Azzera</a>
                @endif
            </div>
        </div>
    </form>
</div>

<script>
(() => {
    const fineNull = document.getElementById('filtro-pg-fine-null');
    const fineDates = () => [...document.querySelectorAll('.js-pg-fine-date')];
    if (!fineNull) {
        return;
    }
    const syncFineDates = () => {
        const disabled = fineNull.checked;
        fineDates().forEach((input) => {
            input.disabled = disabled;
            if (disabled) {
                input.value = '';
            }
        });
    };
    fineNull.addEventListener('change', syncFineDates);
})();
</script>
