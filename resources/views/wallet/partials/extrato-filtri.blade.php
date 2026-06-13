@php
    $formAction = $formAction ?? route('wallet.movimenti');
    $filtros = $filtros ?? [];
    $perPage = $perPage ?? 10;
    $selectedUser = $selectedUser ?? null;
    $formId = $formId ?? 'form-filtri-wallet';
    $periodoId = $periodoId ?? 'filtro-wallet-periodo';
    $customWrapId = $customWrapId ?? 'filtro-wallet-datas-custom';
    $tipoId = $tipoId ?? 'filtro-wallet-tipo';
    $perPageId = $perPageId ?? 'filtro-wallet-per-page';
@endphp
<form method="get" action="{{ $formAction }}" class="sq-wallet-extrato-filtri" id="{{ $formId }}" autocomplete="off">
    @if ($selectedUser !== null)
        <input type="hidden" name="user_id" value="{{ $selectedUser->id }}">
    @endif
    <div class="sq-wallet-extrato-filtri__row">
        @if ($showUsuarioColumn ?? false)
            <div class="sq-wallet-extrato-filtri__campo sq-wallet-extrato-filtri__campo--usuario">
                <label class="sq-wallet-extrato-filtri__label" for="filtro-wallet-usuario">Utente</label>
                @if ($selectedUser !== null)
                    <input type="text" id="filtro-wallet-usuario" class="sq-wallet-extrato-filtri__input-readonly" value="{{ $selectedUser->email }}" readonly>
                @else
                    <input type="text" id="filtro-wallet-usuario" name="usuario" value="{{ $busca ?? '' }}" placeholder="E-mail cliente" autocomplete="off" inputmode="email">
                @endif
            </div>
        @endif
        <div class="sq-wallet-extrato-filtri__campo">
            <label class="sq-wallet-extrato-filtri__label" for="{{ $periodoId }}">Data</label>
            <select name="periodo" id="{{ $periodoId }}" class="sq-wallet-extrato-filtri__select js-wallet-extrato-periodo" data-custom-wrap="{{ $customWrapId }}">
                <option value="" @selected(($filtros['periodo'] ?? '') === '')>Qualsiasi periodo</option>
                <option value="oggi" @selected(($filtros['periodo'] ?? '') === 'oggi')>Oggi</option>
                <option value="7" @selected(($filtros['periodo'] ?? '') === '7')>Ultimi 7 giorni</option>
                <option value="30" @selected(($filtros['periodo'] ?? '') === '30')>Ultimi 30 giorni</option>
                <option value="custom" @selected(($filtros['periodo'] ?? '') === 'custom')>Personalizzato</option>
            </select>
        </div>
        <div class="sq-wallet-extrato-filtri__campo sq-wallet-extrato-filtri__custom-datas @if(($filtros['periodo'] ?? '') === 'custom') is-on @endif" id="{{ $customWrapId }}">
            <div>
                <label class="sq-wallet-extrato-filtri__label" for="{{ $customWrapId }}-de">Da</label>
                <input type="date" id="{{ $customWrapId }}-de" name="data_de" value="{{ $filtros['data_de'] ?? '' }}" class="sq-wallet-extrato-filtri__date">
            </div>
            <span class="sq-wallet-extrato-filtri__date-sep">a</span>
            <div>
                <label class="sq-wallet-extrato-filtri__label" for="{{ $customWrapId }}-ate">A</label>
                <input type="date" id="{{ $customWrapId }}-ate" name="data_ate" value="{{ $filtros['data_ate'] ?? '' }}" class="sq-wallet-extrato-filtri__date">
            </div>
        </div>
        <div class="sq-wallet-extrato-filtri__campo sq-wallet-extrato-filtri__campo--tipo">
            <label class="sq-wallet-extrato-filtri__label" for="{{ $tipoId }}">Tipo</label>
            <select name="wallet_descrizione_id" id="{{ $tipoId }}" class="sq-wallet-extrato-filtri__select">
                <option value="" @selected(($filtros['wallet_descrizione_id'] ?? '') === '')>Tutti</option>
                @foreach ($tiposMovimento as $tipo)
                    <option value="{{ $tipo->id }}" @selected((string) ($filtros['wallet_descrizione_id'] ?? '') === (string) $tipo->id)>{{ $tipo->descrizione }}</option>
                @endforeach
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
                <label class="sq-wallet-extrato-filtri__label" for="{{ $perPageId }}">Per pagina</label>
                <select id="{{ $perPageId }}" name="per_page" class="sq-wallet-extrato-filtri__select sq-wallet-extrato-filtri__select--per-page" onchange="this.form.submit()">
                    @foreach ([10, 25, 50, 100] as $n)
                        <option value="{{ $n }}" @selected($perPage === $n)>{{ $n }}</option>
                    @endforeach
                </select>
            </div>
            @if ($showUsuarioColumn && $selectedUser !== null)
                <div class="sq-wallet-extrato-filtri__campo">
                    <label class="sq-wallet-extrato-filtri__label sq-sr-only">Cambia cliente</label>
                    <a href="{{ route('backoffice.wallet.cliente') }}" class="sq-wallet-extrato-filtri__btn-change-user">Cambia cliente</a>
                </div>
            @endif
        </div>
    </div>
</form>
