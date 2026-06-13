@php
    $formAction = $formAction ?? route('wallet.ricariche');
    $filtros = $filtros ?? [];
    $perPage = $perPage ?? 10;
    $formId = $formId ?? 'form-filtri-ricariche';
    $periodoId = $periodoId ?? 'filtro-ricariche-periodo';
    $customWrapId = $customWrapId ?? 'filtro-ricariche-datas-custom';
    $statoId = $statoId ?? 'filtro-ricariche-stato';
    $perPageId = $perPageId ?? 'filtro-ricariche-per-page';
    $metodoId = $metodoId ?? 'filtro-ricariche-metodo';
    $showCliente = (bool) ($showCliente ?? false);
    $selectedUser = $selectedUser ?? null;
    $metodosWallet = $metodosWallet ?? collect();
    $showStatoAnnullata = (bool) ($showStatoAnnullata ?? false);
@endphp
<form method="get" action="{{ $formAction }}" class="sq-wallet-extrato-filtri sq-wallet-ricariche-filtri" id="{{ $formId }}" autocomplete="off">
    @if ($showCliente && ($filtros['user_id'] ?? '') !== '')
        <input type="hidden" name="user_id" value="{{ $filtros['user_id'] }}">
    @endif
    <div class="sq-wallet-extrato-filtri__row">
        @if ($showCliente)
            <div class="sq-wallet-extrato-filtri__campo sq-wallet-ricariche-filtri__campo--cliente">
                <label class="sq-wallet-extrato-filtri__label" for="filtro-ricariche-cliente">
                    Cliente
                    @if (($filtros['user_id'] ?? '') !== '' || ($filtros['cliente'] ?? '') !== '')
                        <a href="{{ route('backoffice.ricariche.index', request()->except(['user_id', 'cliente', 'page'])) }}" class="sq-wallet-ricariche-filtri__clear">Cancella</a>
                    @endif
                </label>
                @if ($selectedUser !== null)
                    <input type="text" id="filtro-ricariche-cliente" class="sq-wallet-extrato-filtri__input-readonly" value="{{ $selectedUser->email }}" readonly>
                @else
                    <input type="search" id="filtro-ricariche-cliente" name="cliente" value="{{ $filtros['cliente'] ?? '' }}"
                           class="sq-wallet-extrato-filtri__select" placeholder="E-mail o nome" autocomplete="off">
                @endif
            </div>
        @endif
        <div class="sq-wallet-extrato-filtri__campo sq-wallet-ricariche-filtri__campo--ordine">
            <label class="sq-wallet-extrato-filtri__label" for="filtro-ricariche-ordine">N. ordine</label>
            <input type="search" id="filtro-ricariche-ordine" name="numero_ordine" value="{{ $filtros['numero_ordine'] ?? '' }}"
                   class="sq-wallet-extrato-filtri__select" placeholder="Es.: 12 o ORW-12" autocomplete="off">
        </div>
        <div class="sq-wallet-extrato-filtri__campo">
            <label class="sq-wallet-extrato-filtri__label" for="{{ $periodoId }}">Data</label>
            <select name="periodo" id="{{ $periodoId }}" class="sq-wallet-extrato-filtri__select js-wallet-extrato-periodo" data-custom-wrap="{{ $customWrapId }}">
                <option value="" @selected(($filtros['periodo'] ?? '') === '')>Qualsiasi periodo</option>
                <option value="7" @selected(($filtros['periodo'] ?? '') === '7')>Ultimi 7 giorni</option>
                <option value="15" @selected(($filtros['periodo'] ?? '') === '15')>Ultimi 15 giorni</option>
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
                <input type="date" id="{{ $customWrapId }}-ate" name="data_a" value="{{ $filtros['data_a'] ?? '' }}" class="sq-wallet-extrato-filtri__date">
            </div>
        </div>
        <div class="sq-wallet-extrato-filtri__campo sq-wallet-ricariche-filtri__campo--importo">
            <label class="sq-wallet-extrato-filtri__label" for="filtro-ricariche-importo">Importo</label>
            <input type="text" inputmode="decimal" id="filtro-ricariche-importo" name="importo" value="{{ $filtros['importo'] ?? '' }}"
                   class="sq-wallet-extrato-filtri__select" placeholder="Es.: 150">
        </div>
        @if ($showCliente && $metodosWallet->isNotEmpty())
            <div class="sq-wallet-extrato-filtri__campo sq-wallet-ricariche-filtri__campo--metodo">
                <label class="sq-wallet-extrato-filtri__label" for="{{ $metodoId }}">Metodo di pagamento</label>
                <select name="metodo_pagamento_id" id="{{ $metodoId }}" class="sq-wallet-extrato-filtri__select">
                    <option value="" @selected(($filtros['metodo_pagamento_id'] ?? '') === '')>Tutti</option>
                    @foreach ($metodosWallet as $m)
                        <option value="{{ $m->id }}" @selected((string) ($filtros['metodo_pagamento_id'] ?? '') === (string) $m->id)>{{ $m->metodo_pagamento }}</option>
                    @endforeach
                </select>
            </div>
        @endif
        <div class="sq-wallet-extrato-filtri__campo sq-wallet-ricariche-filtri__campo--stato">
            <label class="sq-wallet-extrato-filtri__label" for="{{ $statoId }}">Stato</label>
            <select name="stato" id="{{ $statoId }}" class="sq-wallet-extrato-filtri__select">
                <option value="tutte" @selected(($filtros['stato'] ?? 'tutte') === 'tutte')>Tutti</option>
                <option value="pagato" @selected(($filtros['stato'] ?? '') === 'pagato')>Pagati</option>
                <option value="non_pagato" @selected(($filtros['stato'] ?? '') === 'non_pagato')>Non pagati</option>
                @if ($showStatoAnnullata)
                    <option value="annullata" @selected(($filtros['stato'] ?? '') === 'annullata')>Annullati</option>
                @endif
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
        </div>
    </div>
</form>
