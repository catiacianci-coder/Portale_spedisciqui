<?php

namespace App\Services\Stripe;

use App\Models\ordine;
use App\Services\OrdineTotaleIvatoService;
use App\Support\OrdineTotaliPagamento;
use Stripe\Checkout\Session;
use Stripe\Stripe;

class StripeCheckoutService
{
    public function __construct(
        private readonly OrdineTotaleIvatoService $totaleSvc,
    ) {}

    /**
     * Crea (o riusa) una Checkout Session Stripe e restituisce l’URL di pagamento.
     */
    public function createCheckoutSessionUrl(ordine $ordine, int $metodoId): string
    {
        if (! StripeConfig::isConfigured()) {
            throw new \RuntimeException('Stripe non configurato: imposta stripe_secret_key in parametri globali');
        }

        if (! $this->totaleSvc->metodoIsCarta($metodoId)) {
            throw new \InvalidArgumentException('Il metodo selezionato non è pagamento con carta.');
        }

        abort_unless($ordine->haStato(ordine::STATO_NON_PAGATO), 422);

        $totali = OrdineTotaliPagamento::totaliPerMetodo($ordine, $metodoId);
        $amountCents = (int) round($totali['totale'] * 100);
        if ($amountCents < 50) {
            throw new \InvalidArgumentException('Importo ordine troppo basso per Stripe (minimo '.\App\Support\ImportoEuro::format(0.5).').');
        }

        Stripe::setApiKey(StripeConfig::secretKey());

        $ordine->loadMissing('user');
        $email = $ordine->user?->email;

        $payload = [
            'mode' => 'payment',
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => StripeConfig::currency(),
                    'unit_amount' => $amountCents,
                    'product_data' => [
                        'name' => 'Ordine '.$ordine->codice,
                        'description' => 'Servizio Spedisciqui',
                    ],
                ],
                'quantity' => 1,
            ]],
            'metadata' => [
                'ordine_id' => (string) $ordine->id,
                'user_id' => (string) $ordine->user_id,
                'metodo_pagamento_id' => (string) $metodoId,
                'ordine_codice' => (string) $ordine->codice,
            ],
            'success_url' => route('ordini.stripe.success', $ordine).'?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('ordini.stripe.cancel', $ordine),
        ];

        if ($email) {
            $payload['customer_email'] = $email;
        }

        $session = Session::create($payload);

        $metodo = \App\Models\metodo_pagamento_ordine::query()->find($metodoId);
        $ordine->update([
            'metodo_pagamento_ordinis_id' => $metodoId,
            'metodo_pagamento' => $metodo?->metodo_pagamento,
            'stripe_checkout_session_id' => $session->id,
        ]);

        if (! $session->url) {
            throw new \RuntimeException('Stripe non ha restituito un URL di checkout.');
        }

        return $session->url;
    }

    /**
     * Verifica sessione Stripe e completa l’ordine se il pagamento è andato a buon fine.
     *
     * @return array{ok: bool, already: bool, message: string}
     */
    public function confermaDaSessionId(string $sessionId, ordine $ordine): array
    {
        if (! StripeConfig::isConfigured()) {
            return ['ok' => false, 'already' => false, 'message' => 'Stripe non configurato.'];
        }

        Stripe::setApiKey(StripeConfig::secretKey());
        $session = Session::retrieve($sessionId, ['expand' => ['payment_intent']]);

        if ((int) ($session->metadata['ordine_id'] ?? 0) !== (int) $ordine->id) {
            return ['ok' => false, 'already' => false, 'message' => 'Sessione Stripe non corrisponde a questo ordine.'];
        }

        if ($session->payment_status !== 'paid' && $session->status !== 'complete') {
            return ['ok' => false, 'already' => false, 'message' => 'Pagamento non ancora confermato da Stripe.'];
        }

        $metodoId = (int) ($session->metadata['metodo_pagamento_id'] ?? $ordine->metodo_pagamento_ordinis_id ?? 0);
        if ($metodoId <= 0) {
            return ['ok' => false, 'already' => false, 'message' => 'Metodo di pagamento mancante nella sessione Stripe.'];
        }

        $paymentIntentId = is_string($session->payment_intent)
            ? $session->payment_intent
            : ($session->payment_intent->id ?? null);

        $result = app(OrdineCompletaPagamentoService::class)->segnaPagato(
            $ordine,
            $metodoId,
            $session->id,
            $paymentIntentId,
        );

        if (! $result['ok']) {
            return ['ok' => false, 'already' => false, 'message' => 'Impossibile registrare il pagamento su questo ordine.'];
        }

        if ($result['already']) {
            return ['ok' => true, 'already' => true, 'message' => 'Ordine già risultava pagato.'];
        }

        RitiroOrdinePagamento::salvaPickupTraceInSessione($result['pickup_trace'] ?? null);

        return ['ok' => true, 'already' => false, 'message' => 'Pagamento con carta completato.'];
    }
}
