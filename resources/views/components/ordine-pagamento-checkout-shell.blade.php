@props([
    'ordine',
    'metodoJson',
])

<div class="ordine-pagamento-checkout-page">
    <div class="sq-ordine-pagamento-checkout-wrap">
        @if (session('ok'))
            <div class="sq-alert sq-alert--success sq-mb-16">{{ session('ok') }}</div>
        @endif

        @if ($errors->has('pagamento'))
            <div class="sq-alert sq-alert--error sq-mb-16">{{ $errors->first('pagamento') }}</div>
        @endif

        <p class="sq-ordine-pagamento-back">
            <a href="{{ route('ordini.pagamento.show', $ordine) }}" class="sq-ordine-pagamento-back-btn">
                <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                Torna alla scelta del metodo
            </a>
        </p>

        <div class="sq-ordine-pagamento-checkout-panel">
            {{ $slot }}
        </div>
    </div>
</div>
