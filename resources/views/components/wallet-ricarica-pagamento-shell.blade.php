@props([
    'ricarica',
    'metodoJson',
])

<div class="ordine-pagamento-checkout-page sq-wallet-ricarica-pagamento-page">
    <div class="sq-ordine-pagamento-checkout-wrap">
        @if (session('ok'))
            <div class="sq-alert sq-alert--success sq-mb-16">{{ session('ok') }}</div>
        @endif

        @if ($errors->has('ricarica'))
            <div class="sq-alert sq-alert--error sq-mb-16">{{ $errors->first('ricarica') }}</div>
        @endif

        <p class="sq-ordine-pagamento-back">
            <a href="{{ route('wallet.ricariche.pagamento.show', $ricarica) }}" class="sq-ordine-pagamento-back-btn">
                <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                Torna alla scelta del metodo
            </a>
        </p>

        <div class="sq-ordine-pagamento-checkout-panel">
            {{ $slot }}
        </div>
    </div>
</div>
