@extends('layouts.app')
@section('content')
<div class="sq-bleed-layout sq-etichette-page">
    <x-sq-page-banner title="Etichette" icon="fa-tag" class="sq-page-banner--full" />

    <div class="sq-listing-page sq-etichette-listing">

        @if (session('ok'))
            <div class="sq-alert sq-alert--success sq-mb-16">{{ session('ok') }}</div>
        @endif
        @if ($errors->has('etichette'))
            <div class="sq-alert sq-alert--error sq-mb-16">{{ $errors->first('etichette') }}</div>
        @endif
        @if (session('error'))
            <div class="sq-alert sq-alert--error sq-mb-16">{{ session('error') }}</div>
        @endif

        @if (! empty($filtroErrors))
            <div class="sq-alert sq-alert--error sq-mb-16">
                @foreach ($filtroErrors as $fe)
                    <div>{{ $fe }}</div>
                @endforeach
            </div>
        @endif

        <form method="GET" action="{{ route('etichette.index') }}" class="sq-listing-toolbar" autocomplete="off">
            <div class="sq-filtros-card sq-etichette-filtri">
                <div class="filtros-row">
                    <div class="campo sq-etichette-filtro-codice">
                        <label for="codice_etichetta" class="filtro-label">Codice o etichetta</label>
                        <input id="codice_etichetta" name="codice_etichetta" type="text"
                               value="{{ $filtroCodiceEtichetta }}" placeholder="Codice o etichetta">
                    </div>
                    <div class="campo">
                        <label for="numero_ordine" class="filtro-label">N. ordine</label>
                        <input id="numero_ordine" name="numero_ordine" type="text"
                               value="{{ $filtroNumeroOrdine }}" placeholder="Es.: 19 o ORS-19">
                    </div>
                    <div class="campo sq-etichette-filtro-dest">
                        <label for="destinatario" class="filtro-label">Destinatario</label>
                        <input id="destinatario" name="destinatario" type="search" list="destinatari-suggest"
                               value="{{ $filtroDestinatario }}" placeholder="Digita per suggerire…">
                        <datalist id="destinatari-suggest">
                            @foreach ($suggerimentiDestinatario as $nome)
                                <option value="{{ $nome }}"></option>
                            @endforeach
                        </datalist>
                    </div>
                    @include('etichette.partials.filtro-data-pagamento')
                    <div class="campo sq-etichette-filtro-submit">
                        <span class="filtro-label sq-sr-only">Filtra</span>
                        <button type="submit" class="sq-btn-filtrar-icon" title="Applica filtri" aria-label="Applica filtri">
                            <i class="fa-solid fa-filter" aria-hidden="true"></i>
                        </button>
                    </div>
                    <div class="campo sq-etichette-filtro-per-page">
                        <label for="per_page_etichette" class="filtro-label">Per pagina</label>
                        <select id="per_page_etichette" name="per_page" class="sq-etichette-per-page-select" onchange="this.form.submit()">
                            @foreach ([10, 25, 50, 100] as $n)
                                <option value="{{ $n }}" @selected($perPage === $n)>{{ $n }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </form>

        <div class="sq-listing-panel sq-listing-panel--spedizioni">
            @if ($spedizioni->isEmpty())
                <p class="sq-listing-empty">Nessuna etichetta trovata con i filtri selezionati.</p>
                <p class="sq-listing-empty"><a href="{{ route('home') }}">Crea una nuova spedizione</a></p>
            @else
                @include('etichette.partials.tabella')
                <div class="sq-listing-panel-footer">
                    @include('partials.tabella-paginazione', [
                        'paginator' => $spedizioni,
                        'perPage' => $perPage,
                        'queryParams' => array_merge($queryParams, ['per_page' => $perPage]),
                    ])
                </div>
            @endif
        </div>
    </div>

    @include('etichette.partials.modal-dettaglio')
</div>
@endsection
