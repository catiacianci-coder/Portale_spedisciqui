@extends('layouts.app')
@section('content')
@php
    $stripePk = trim((string) ($stripePublicKey ?? ''));
    $totaleFmt = \App\Support\ImportoEuro::format((float) ($metodoJson['totale'] ?? 0));
@endphp
<x-ordine-pagamento-checkout-shell :ordine="$ordine" :metodo-json="$metodoJson">
    <h2 class="sq-ordine-pagamento-panel-title">Carta di credito (Stripe)</h2>

    <p class="sq-ordine-pagamento-total-label">Totale con questo metodo:</p>
    <p class="sq-ordine-pagamento-total-value">{{ $totaleFmt }}</p>

    @if ($stripePk === '')
        <div class="sq-alert sq-alert--error">Stripe non configurato.</div>
    @else
        <div id="sq-stripe-card-element" class="sq-stripe-card-element"></div>
        <p id="sq-stripe-error" class="sq-ordine-pagamento-errore" hidden role="alert"></p>
        <button type="button" class="sq-btn-primary sq-ordine-pagamento-submit-btn" id="sq-stripe-pay-btn">
            Paga con carta
        </button>
    @endif
</x-ordine-pagamento-checkout-shell>

@if ($stripePk !== '')
    <script src="https://js.stripe.com/v3/"></script>
    <script>
    (() => {
        const payBtn = document.getElementById('sq-stripe-pay-btn');
        const errEl = document.getElementById('sq-stripe-error');
        const csrf = @json(csrf_token());
        const cartaUrl = @json(route('ordini.pagamento.carta.process', $ordine));
        const redirectOk = @json(route('ordini.index', ['aba' => 'pagati']));
        const stripePk = @json($stripePk);
        const metodoId = @json((int) $metodoJson['id']);

        if (!window.Stripe || !stripePk || !payBtn) return;

        const stripe = Stripe(stripePk);
        const card = stripe.elements().create('card', {
            hidePostalCode: true,
            style: {
                base: { fontSize: '16px', color: '#1a1a1a', '::placeholder': { color: '#9ca3af' } },
            },
        });
        card.mount('#sq-stripe-card-element');

        const showErr = (msg) => {
            if (!errEl) return;
            if (!msg) { errEl.hidden = true; errEl.textContent = ''; return; }
            errEl.hidden = false;
            errEl.textContent = msg;
        };

        const postJson = (payload) => fetch(cartaUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
            body: JSON.stringify(payload),
        }).then(async (r) => {
            const data = await r.json().catch(() => ({}));
            if (!r.ok) throw new Error(data.message || 'Pagamento non riuscito.');
            return data;
        });

        payBtn.addEventListener('click', () => {
            payBtn.disabled = true;
            showErr('');

            stripe.createPaymentMethod({ type: 'card', card })
                .then((res) => {
                    if (res.error) throw new Error(res.error.message || 'Carta non valida.');
                    return postJson({ metodo_pagamento_id: metodoId, payment_method_id: res.paymentMethod.id });
                })
                .then((data) => {
                    if (data.requires_action && data.client_secret) {
                        return stripe.confirmCardPayment(data.client_secret).then((result) => {
                            if (result.error) throw new Error(result.error.message);
                            const piId = data.payment_intent_id || (result.paymentIntent && result.paymentIntent.id);
                            if (piId) {
                                return postJson({ metodo_pagamento_id: metodoId, payment_intent_id: piId });
                            }
                            return data;
                        });
                    }
                    return data;
                })
                .then((data) => {
                    if (data.ok) {
                        window.location.href = data.redirect_url || redirectOk;
                        return;
                    }
                    throw new Error(data.message || 'Pagamento non completato.');
                })
                .catch((e) => {
                    showErr(e.message || 'Pagamento non riuscito.');
                    payBtn.disabled = false;
                });
        });
    })();
    </script>
@endif
@endsection
