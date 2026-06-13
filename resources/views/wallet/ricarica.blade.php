@extends('layouts.app')
@section('content')
@php
    $fmtInt = fn ($n) => number_format((int) $n, 0, ',', '.');
    $fmtDec = fn ($n) => number_format((float) $n, 2, ',', '.');
@endphp

<div class="home-spedizione-wrap wallet-ricarica-page">
    <h1 class="sq-h1-ricarica">
        Ricarica il tuo saldo
    </h1>

    <div class="sq-text-wallet-body">
        <p>
            Gestisci i tuoi pagamenti in modo semplice e immediato. Utilizzando il saldo del tuo Wallet, potrai completare l'acquisto delle spedizioni con un solo clic, evitando i tempi d'attesa dei circuiti bancari esterni e velocizzando ogni tua operazione sul portale.
        </p>
        <p>
            <strong>Informazioni sulla ricarica:</strong>
        </p>
        <p>
            <strong>Importo minimo:</strong> La ricarica deve essere di un importo intero pari o superiore a 150 € (come previsto dalle condizioni commerciali vigenti).
        </p>
        <p>
            <strong>Valuta:</strong> Gli importi sono espressi in Euro (EUR).
        </p>
        <p>
            <strong>Vantaggi:</strong> L'utilizzo del Wallet ti permette di accedere, dove previsto, a tariffe dedicate e condizioni agevolate sulla nostra listino spedizioni.
        </p>
    </div>

    <p class="sq-saldo-row sq-text-main">
        Il tuo saldo attuale è <strong class="sq-saldo-strong">{{ $fmtDec($saldoAttuale) }} €</strong>
    </p>

    <hr class="sq-hr-brand">

    @if (session('ok'))
        <div class="sq-alert sq-alert--success sq-alert--success-pad">
            {{ session('ok') }}
        </div>
    @endif
    @if (session('info'))
        <div class="sq-alert sq-alert--info-warm sq-alert--success-pad">
            {{ session('info') }}
        </div>
    @endif
    @if ($errors->any())
        <div class="sq-alert sq-alert--error sq-alert--success-pad">
            {{ $errors->first() }}
        </div>
    @endif

    <h2 class="sq-h2-ricarica wallet-ricarica-subtitle">
        Inserisci un importo intero di almeno {{ $fmtInt($minEuro) }} €
    </h2>

    <form method="POST" action="{{ route('wallet.ricarica.store') }}" class="sq-form-zero">
        @csrf
        <div class="wallet-ricarica-form-row">
            <div class="wallet-ricarica-input-col">
                <label for="importo" class="sq-sr-only">Importo in euro (intero)</label>
                <div class="wallet-ricarica-input-wrap">
                    <input id="importo" name="importo" type="number" step="1" min="{{ $minEuro }}"
                           value="{{ old('importo', $minEuro) }}" required inputmode="numeric"
                           placeholder="Es. {{ $minEuro }}"
                           class="sq-input-ricarica">
                </div>
                <p class="sq-p-note wallet-ricarica-note">Solo numeri interi in euro, senza centesimi.</p>
            </div>
            <button type="submit" class="sq-btn-primary-block wallet-ricarica-submit">
                Procedi al pagamento
            </button>
        </div>
    </form>

    @if (($condicoesWallet ?? null) && trim((string) $condicoesWallet->conteudo_html) !== '')
        <p class="sq-p-note sq-mt-16">
            Per consultare tutte le condizioni, vedi
            <x-inline-content-modal
                id="ricarica-wallet-condicoes"
                :title="$condicoesWallet->titulo"
                :content="$condicoesWallet->conteudo_html"
                trigger-label="come funziona il pagamento con Wallet"
            />.
        </p>
    @endif
</div>
@endsection
