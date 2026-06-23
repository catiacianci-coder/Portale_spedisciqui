@extends('layouts.app')

@section('pageBanner')
    <x-sq-page-banner
        variant="backoffice"
        title="Trasferimento da wallet"
        icon="fa-wallet"
        class="sq-page-banner--full"
    />
@endsection

@section('content')
@php
    use App\Services\Revolut\RevolutConfig;
    $fmt = fn ($n) => number_format((float) $n, 2, ',', '.');
    $ordineLabel = static function (?int $ordineId): string {
        if ($ordineId === null || $ordineId <= 0) {
            return '—';
        }

        return (string) $ordineId;
    };
    $revolutConfigurato = RevolutConfig::isConfigured();
@endphp
<div class="sq-bo-page-wrap sq-bo-reemb-page">
    @if (session('rimborso_bo_ok'))
        <div class="sq-alert sq-alert--success sq-mb-16">{{ session('rimborso_bo_ok') }}</div>
    @endif
    @if (session('rimborso_bo_erro'))
        <div class="sq-alert sq-alert--error sq-mb-16">{{ session('rimborso_bo_erro') }}</div>
    @endif

    <nav class="sq-bo-reemb-subnav" aria-label="Sezioni rimborsi">
        <a href="{{ route('backoffice.rimborsi.index') }}">Home rimborsi</a>
        <span class="sq-bo-reemb-subnav-sep" aria-hidden="true">·</span>
        <a href="{{ route('backoffice.rimborsi.pendentes') }}">Da rimborsare</a>
        <span class="sq-bo-reemb-subnav-sep" aria-hidden="true">·</span>
        <a href="{{ route('backoffice.rimborsi.rimborsati') }}">Rimborsati</a>
        <span class="sq-bo-reemb-subnav-sep" aria-hidden="true">·</span>
        <a href="{{ route('backoffice.rimborsi.per_ordine') }}">Per ordine</a>
        <span class="sq-bo-reemb-subnav-sep" aria-hidden="true">·</span>
        <a href="{{ route('backoffice.rimborsi.trasferimento_wallet') }}" class="is-active">Trasferimento wallet</a>
    </nav>

    <div class="sq-bo-reemb-card">
        <h2>Trasferimento da wallet</h2>
        <p class="sq-bo-reemb-card-lead">
            Etichette già <strong>accreditate sul wallet</strong>: trasferisci l’importo sul
            <strong>metodo di pagamento originale</strong> dell’ordine.
            Carta → storno Stripe; bonifico → bonifico in uscita via Revolut Business API.
            Compila almeno un filtro per visualizzare l’elenco.
        </p>
        @if (! $revolutConfigurato)
            <p class="sq-alert sq-alert--warning sq-mb-16">
                Revolut non configurato: imposta token e ID conto in
                <a href="{{ route('backoffice.parametri_globali.edit') }}">Parametri globali</a>
                per abilitare i bonifici in uscita.
            </p>
        @endif

        @if ($customPeriodoSemDatas ?? false)
            <p class="sq-wallet-extrato-hint">Periodo personalizzato: indica le date <strong>Da</strong> e/o <strong>A</strong>.</p>
        @endif

        <form method="GET" action="{{ route('backoffice.rimborsi.trasferimento_wallet') }}" class="sq-wallet-extrato-filtri sq-bo-reemb-trasf-filtri" autocomplete="off">
            @if (($filtros['user_id'] ?? '') !== '')
                <input type="hidden" name="user_id" value="{{ $filtros['user_id'] }}">
            @endif
            <div class="sq-wallet-extrato-filtri__row">
                <div class="sq-wallet-extrato-filtri__campo sq-wallet-ricariche-filtri__campo--cliente">
                    <label class="sq-wallet-extrato-filtri__label" for="filtro-trasf-cliente">
                        Cliente
                        @if (($filtros['user_id'] ?? '') !== '' || ($filtros['cliente'] ?? '') !== '')
                            <a href="{{ route('backoffice.rimborsi.trasferimento_wallet', request()->except(['user_id', 'cliente', 'page'])) }}" class="sq-wallet-ricariche-filtri__clear">Cancella</a>
                        @endif
                    </label>
                    @if ($selectedUser ?? null)
                        <input type="text" id="filtro-trasf-cliente" class="sq-wallet-extrato-filtri__input-readonly" value="{{ $selectedUser->email }}" readonly>
                    @else
                        <input type="search" id="filtro-trasf-cliente" name="cliente" value="{{ $filtros['cliente'] ?? '' }}"
                               class="sq-wallet-extrato-filtri__select" placeholder="E-mail cliente" autocomplete="off">
                    @endif
                </div>
                <div class="sq-wallet-extrato-filtri__campo">
                    <label class="sq-wallet-extrato-filtri__label" for="filtro-trasf-ordine">Ordine</label>
                    <input type="search" id="filtro-trasf-ordine" name="ordine" value="{{ $filtros['ordine'] ?? '' }}"
                           class="sq-wallet-extrato-filtri__select" placeholder="27" autocomplete="off">
                </div>
                <div class="sq-wallet-extrato-filtri__campo">
                    <label class="sq-wallet-extrato-filtri__label" for="filtro-trasf-etichetta">Etichetta</label>
                    <input type="search" id="filtro-trasf-etichetta" name="etichetta" value="{{ $filtros['etichetta'] ?? '' }}"
                           class="sq-wallet-extrato-filtri__select" placeholder="Codice spedizione" autocomplete="off">
                </div>
                <div class="sq-wallet-extrato-filtri__campo">
                    <label class="sq-wallet-extrato-filtri__label" for="filtro-trasf-stato">Situazione</label>
                    <select name="stato" id="filtro-trasf-stato" class="sq-wallet-extrato-filtri__select">
                        <option value="in_attesa" @selected(($filtros['stato'] ?? 'in_attesa') === 'in_attesa')>In attesa trasferimento</option>
                        <option value="senza_richiesta" @selected(($filtros['stato'] ?? '') === 'senza_richiesta')>Senza richiesta registrata</option>
                        <option value="completati" @selected(($filtros['stato'] ?? '') === 'completati')>Trasferiti</option>
                        <option value="tutti" @selected(($filtros['stato'] ?? '') === 'tutti')>Tutti (wallet)</option>
                    </select>
                </div>
                <div class="sq-wallet-extrato-filtri__campo">
                    <label class="sq-wallet-extrato-filtri__label" for="filtro-trasf-periodo">Periodo</label>
                    <select name="periodo" id="filtro-trasf-periodo" class="sq-wallet-extrato-filtri__select js-wallet-extrato-periodo" data-custom-wrap="filtro-trasf-datas-custom">
                        <option value="" @selected(($filtros['periodo'] ?? '') === '')>Qualsiasi</option>
                        <option value="7" @selected(($filtros['periodo'] ?? '') === '7')>Ultimi 7 giorni</option>
                        <option value="15" @selected(($filtros['periodo'] ?? '') === '15')>Ultimi 15 giorni</option>
                        <option value="30" @selected(($filtros['periodo'] ?? '') === '30')>Ultimi 30 giorni</option>
                        <option value="custom" @selected(($filtros['periodo'] ?? '') === 'custom')>Personalizzato</option>
                    </select>
                </div>
                <div class="sq-wallet-extrato-filtri__campo sq-wallet-extrato-filtri__custom-datas @if(($filtros['periodo'] ?? '') === 'custom') is-on @endif" id="filtro-trasf-datas-custom">
                    <div>
                        <label class="sq-wallet-extrato-filtri__label" for="filtro-trasf-de">Da</label>
                        <input type="date" id="filtro-trasf-de" name="data_de" value="{{ $filtros['data_de'] ?? '' }}" class="sq-wallet-extrato-filtri__date">
                    </div>
                    <span class="sq-wallet-extrato-filtri__date-sep">a</span>
                    <div>
                        <label class="sq-wallet-extrato-filtri__label" for="filtro-trasf-ate">A</label>
                        <input type="date" id="filtro-trasf-ate" name="data_a" value="{{ $filtros['data_a'] ?? '' }}" class="sq-wallet-extrato-filtri__date">
                    </div>
                </div>
                <div class="sq-wallet-extrato-filtri__campo sq-wallet-extrato-filtri__campo--btn">
                    <button type="submit" class="sq-bo-reemb-btn">Cerca</button>
                </div>
            </div>
        </form>

        @if (! ($hasActiveFilters ?? false))
            <div class="sq-bo-reemb-empty sq-mt-16">
                Imposta almeno un filtro (cliente, ordine, etichetta o periodo) per visualizzare i rimborsi wallet.
            </div>
        @elseif ($lista->isEmpty())
            <div class="sq-bo-reemb-empty sq-mt-16">Nessun rimborso trovato con questi filtri.</div>
        @else
            <div class="sq-bo-reemb-table-wrap sq-mt-16">
                <table class="sq-bo-reemb-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Codice interno</th>
                            <th>Ordine</th>
                            <th>Cliente</th>
                            <th>Pagamento ordine</th>
                            <th>Importo</th>
                            <th>Accredito wallet</th>
                            <th>Trasferito</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($lista as $r)
                            @php
                                $s = $r->spedizione;
                                $oid = (int) ($r->ordine_id ?? $s?->ordine_id ?? 0);
                                $metodoOrdine = $r->labelMetodoPagamentoOrdine();
                                $isCarta = ($r->ordine ?? $s?->ordine)?->metodoPagamentoOrdine?->isCarta() ?? false;
                                $isBonifico = ($r->ordine ?? $s?->ordine)?->metodoPagamentoOrdine?->isBonifico() ?? false;
                                $beneficiarioDefault = $r->nomeBeneficiarioBonifico();
                            @endphp
                            <tr>
                                <td>#{{ $r->id }}</td>
                                <td>{{ $r->codice_interno ?? '—' }}</td>
                                <td class="sq-td-muted">{{ $ordineLabel($oid > 0 ? $oid : null) }}</td>
                                <td>{{ $s?->user?->email ?? '—' }}</td>
                                <td>{{ $metodoOrdine }}</td>
                                <td class="sq-td-valore">{{ \App\Support\ImportoEuro::format($r->valore) }}</td>
                                <td class="sq-td-muted">{{ $r->data_reale?->format('d/m/Y H:i') ?? '—' }}</td>
                                <td class="sq-td-muted">
                                    @if ($r->isTrasferimentoEsternoCompletato())
                                        {{ $r->data_trasferimento_esterno?->format('d/m/Y H:i') ?? '—' }}
                                        @if ($r->stripe_refund_id)
                                            <br><span class="sq-text-muted sq-font-sm">Stripe {{ $r->stripe_refund_id }}</span>
                                        @elseif ($r->revolut_transaction_id)
                                            <br><span class="sq-text-muted sq-font-sm">Revolut {{ $r->revolut_transaction_id }}</span>
                                        @endif
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>
                                    <div class="sq-bo-reemb-acoes">
                                        @if ($r->canTrasferimentoEsterno())
                                            @if ($isCarta)
                                                <form method="POST" action="{{ route('backoffice.rimborsi.trasferimento_wallet.carta', $r) }}"
                                                      onsubmit="return confirm('Avviare lo storno Stripe per {{ \App\Support\ImportoEuro::format($r->valore) }}? Il wallet verrà addebitato.');">
                                                    @csrf
                                                    <button type="submit" class="sq-filtri-submit">Storno carta</button>
                                                </form>
                                            @elseif ($isBonifico)
                                                <form method="POST" action="{{ route('backoffice.rimborsi.trasferimento_wallet.bonifico', $r) }}"
                                                      class="sq-bo-reemb-trasf-bonifico-form"
                                                      onsubmit="return confirm('Avviare il bonifico Revolut per {{ \App\Support\ImportoEuro::format($r->valore) }}? Il wallet verrà addebitato.');">
                                                    @csrf
                                                    <label class="sq-sr-only" for="iban-{{ $r->id }}">IBAN beneficiario</label>
                                                    <input type="text" id="iban-{{ $r->id }}" name="iban" required maxlength="34"
                                                           class="sq-wallet-extrato-filtri__select sq-bo-reemb-trasf-iban"
                                                           placeholder="IBAN cliente" autocomplete="off">
                                                    <label class="sq-sr-only" for="beneficiario-{{ $r->id }}">Beneficiario</label>
                                                    <input type="text" id="beneficiario-{{ $r->id }}" name="beneficiario" maxlength="120"
                                                           class="sq-wallet-extrato-filtri__select sq-bo-reemb-trasf-benef"
                                                           value="{{ $beneficiarioDefault }}"
                                                           placeholder="Beneficiario">
                                                    <button type="submit" class="sq-bo-reemb-btn sq-bo-reemb-btn--sec" @disabled(! $revolutConfigurato)>Bonifico Revolut</button>
                                                </form>
                                            @endif
                                            <form method="POST" action="{{ route('backoffice.rimborsi.trasferimento_wallet.completato', $r) }}"
                                                  onsubmit="return confirm('Confermi trasferimento completato manualmente e addebito wallet?');">
                                                @csrf
                                                <button type="submit" class="sq-bo-reemb-btn sq-bo-reemb-btn--sec">Segna completato</button>
                                            </form>
                                        @else
                                            <span class="sq-td-muted">—</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @include('partials.tabella-paginazione', ['paginator' => $lista])
        @endif
    </div>
</div>
@endsection
