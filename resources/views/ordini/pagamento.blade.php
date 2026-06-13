@extends('layouts.app')
@section('content')
<div class="sq-bleed-layout">
    <x-sq-page-banner title="Pagamento ordine {{ $ordine->codice }}" icon="fa-credit-card" class="sq-page-banner--full" />
    <div class="ordine-show-page carrello-page sq-page-preventivi ordini-index-page ordine-pagamento-page">

    <p class="sq-mb-16">
        <a href="{{ route('ordini.show', $ordine) }}" class="sq-text-main">
            <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Torna al dettaglio ordine
        </a>
    </p>

    @if (session('ok'))
        <div class="sq-alert sq-alert--success sq-mb-16">{{ session('ok') }}</div>
    @endif

    @if ($errors->has('pagamento'))
        <div class="sq-alert sq-alert--error sq-mb-16">{{ $errors->first('pagamento') }}</div>
    @endif

    @if (! ($stripeConfigured ?? true))
        @php
            $haCarta = collect($metodiJson ?? [])->contains(fn ($m) => ! empty($m['is_carta']));
        @endphp
        @if ($haCarta)
            <div class="sq-alert sq-alert--info-warm sq-mb-16">
                Pagamento con carta temporaneamente non disponibile: mancano le chiavi Stripe in configurazione.
            </div>
        @endif
    @endif

    @include('ordini.partials.spedizioni-ordine-tabella', [
        'ordine' => $ordine,
        'mostraSelezione' => false,
        'podeEditar' => false,
        'servizioPerSpedizione' => $servizioPerSpedizione ?? [],
        'totaleIvatoOrdine' => $totaleIvatoOrdine ?? 0,
        'cardTitle' => 'Riepilogo ordine',
    ])

    @include('ordini.partials.pagamento-metodi', [
        'ordine' => $ordine,
        'metodiJson' => $metodiJson,
        'walletSaldoOk' => $walletSaldoOk,
        'stripeConfigured' => $stripeConfigured,
    ])

    </div>
</div>
@endsection
