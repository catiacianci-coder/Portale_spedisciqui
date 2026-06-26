@extends('layouts.app')
@section('content')
@php
    $numeroOrdine = $ricarica->numero_ordine_wallet ?? ('ORW-'.$ricarica->id);
@endphp
<div class="sq-bleed-layout">
    <x-sq-page-banner title="Pagamento ricarica {{ $numeroOrdine }}" icon="fa-credit-card" class="sq-page-banner--full" />
    <div class="ordine-show-page ordini-index-page ordine-pagamento-page sq-wallet-ricarica-pagamento-show">

        <p class="sq-mb-16">
            <a href="{{ route('wallet.ricariche') }}" class="sq-text-main">
                <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Torna alle ricariche
            </a>
        </p>

        @if (session('info'))
            <div class="sq-alert sq-alert--info-warm sq-mb-16">{{ session('info') }}</div>
        @endif

        @if ($errors->has('ricarica'))
            <div class="sq-alert sq-alert--error sq-mb-16">{{ $errors->first('ricarica') }}</div>
        @endif

        @php
            $haCarta = collect($metodiJson ?? [])->contains(fn ($m) => ! empty($m['is_carta']));
        @endphp
        @if ($haCarta && ! ($stripeConfigured ?? true))
            <div class="sq-alert sq-alert--info-warm sq-mb-16">
                Pagamento con carta temporaneamente non disponibile: mancano le chiavi Stripe in configurazione.
            </div>
        @endif

        @include('wallet.partials.ricarica-riepilogo-tabella', [
            'ricarica' => $ricarica,
            'cardTitle' => 'Riepilogo ricarica',
        ])

        @include('wallet.partials.pagamento-metodi', [
            'ricarica' => $ricarica,
            'metodiJson' => $metodiJson,
            'stripeConfigured' => $stripeConfigured,
        ])
    </div>
</div>
@endsection
