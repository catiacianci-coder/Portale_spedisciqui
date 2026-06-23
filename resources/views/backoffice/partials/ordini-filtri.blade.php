@php
    $formAction = $formAction ?? route('backoffice.ordini.index');
    $filtros = $filtros ?? [];
    $perPage = $perPage ?? 10;
    $pagamentoFiltroUi = $pagamentoFiltroUi ?? 'tutti';
    $selectedUser = $selectedUser ?? null;
@endphp
<form method="get" action="{{ $formAction }}" class="sq-wallet-extrato-filtri sq-bo-ordini-filtri" id="form-filtri-bo-ordini" autocomplete="off">
    @if (($filtros['user_id'] ?? '') !== '')
        <input type="hidden" name="user_id" value="{{ $filtros['user_id'] }}">
    @endif
    <div class="sq-wallet-extrato-filtri__row">
        <div class="sq-wallet-extrato-filtri__campo sq-bo-ordini-filtri__campo--numero">
            <label class="sq-wallet-extrato-filtri__label" for="filtro-bo-ordini-numero">N. ordine</label>
            <input type="search" id="filtro-bo-ordini-numero" name="numero" value="{{ $filtros['numero'] ?? '' }}"
                   class="sq-wallet-extrato-filtri__select" placeholder="Es.: 27" autocomplete="off">
        </div>
        <div class="sq-wallet-extrato-filtri__campo sq-bo-ordini-filtri__campo--cliente">
            <label class="sq-wallet-extrato-filtri__label" for="filtro-bo-ordini-cliente">
                Cliente
                @if (($filtros['user_id'] ?? '') !== '' || ($filtros['usuario'] ?? '') !== '')
                    <a href="{{ route('backoffice.ordini.index', request()->except(['user_id', 'usuario', 'page'])) }}" class="sq-bo-ordini-filtri__clear">Cancella</a>
                @endif
            </label>
            @if ($selectedUser !== null)
                <input type="text" id="filtro-bo-ordini-cliente" class="sq-wallet-extrato-filtri__input-readonly" value="{{ $selectedUser->email }}" readonly>
            @else
                <input type="search" id="filtro-bo-ordini-cliente" name="usuario" value="{{ $filtros['usuario'] ?? '' }}"
                       class="sq-wallet-extrato-filtri__select" placeholder="E-mail" autocomplete="off">
            @endif
        </div>
        <div class="sq-wallet-extrato-filtri__campo">
            <label class="sq-wallet-extrato-filtri__label" for="filtro-bo-ordini-periodo">Periodo (creazione)</label>
            <select name="periodo" id="filtro-bo-ordini-periodo" class="sq-wallet-extrato-filtri__select js-wallet-extrato-periodo" data-custom-wrap="filtro-bo-ordini-datas-custom">
                <option value="" @selected(($filtros['periodo'] ?? '') === '')>Qualsiasi periodo</option>
                <option value="7" @selected(($filtros['periodo'] ?? '') === '7')>Ultimi 7 giorni</option>
                <option value="15" @selected(($filtros['periodo'] ?? '') === '15')>Ultimi 15 giorni</option>
                <option value="30" @selected(($filtros['periodo'] ?? '') === '30')>Ultimi 30 giorni</option>
                <option value="custom" @selected(($filtros['periodo'] ?? '') === 'custom')>Personalizzato</option>
            </select>
        </div>
        <div class="sq-wallet-extrato-filtri__campo sq-wallet-extrato-filtri__custom-datas @if(($filtros['periodo'] ?? '') === 'custom') is-on @endif" id="filtro-bo-ordini-datas-custom">
            <div>
                <label class="sq-wallet-extrato-filtri__label" for="filtro-bo-ordini-data-de">Da</label>
                <input type="date" id="filtro-bo-ordini-data-de" name="data_de" value="{{ $filtros['data_de'] ?? '' }}" class="sq-wallet-extrato-filtri__date">
            </div>
            <span class="sq-wallet-extrato-filtri__date-sep">a</span>
            <div>
                <label class="sq-wallet-extrato-filtri__label" for="filtro-bo-ordini-data-a">A</label>
                <input type="date" id="filtro-bo-ordini-data-a" name="data_a" value="{{ $filtros['data_a'] ?? '' }}" class="sq-wallet-extrato-filtri__date">
            </div>
        </div>
        <div class="sq-wallet-extrato-filtri__campo sq-bo-ordini-filtri__campo--pagamento">
            <label class="sq-wallet-extrato-filtri__label" for="filtro-bo-ordini-pagamento">Pagamento</label>
            <select name="pagamento" id="filtro-bo-ordini-pagamento" class="sq-wallet-extrato-filtri__select">
                <option value="tutti" @selected($pagamentoFiltroUi === 'tutti')>Tutti</option>
                <option value="pagato" @selected($pagamentoFiltroUi === 'pagato')>Pagati</option>
                <option value="non_pagato" @selected($pagamentoFiltroUi === 'non_pagato')>Non pagati</option>
                <option value="annullato" @selected($pagamentoFiltroUi === 'annullato')>Annullati</option>
            </select>
        </div>
        <div class="sq-wallet-extrato-filtri__campo sq-wallet-extrato-filtri__campo--submit">
            <label class="sq-wallet-extrato-filtri__label sq-sr-only">Filtra</label>
            <button type="submit" class="sq-wallet-extrato-filtri__btn-filter" title="Applica filtri" aria-label="Applica filtri">
                <i class="fas fa-filter" aria-hidden="true"></i>
            </button>
        </div>
        <div class="sq-wallet-extrato-filtri__tail">
            <div class="sq-wallet-extrato-filtri__campo">
                <label class="sq-wallet-extrato-filtri__label" for="filtro-bo-ordini-per-page">Per pagina</label>
                <select id="filtro-bo-ordini-per-page" name="per_page" class="sq-wallet-extrato-filtri__select sq-wallet-extrato-filtri__select--per-page" onchange="this.form.submit()">
                    @foreach ([10, 25, 50, 100] as $n)
                        <option value="{{ $n }}" @selected($perPage === $n)>{{ $n }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>
</form>
