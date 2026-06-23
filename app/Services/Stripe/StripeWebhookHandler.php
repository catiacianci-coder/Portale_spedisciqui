<?php

namespace App\Services\Stripe;

use App\Models\ordine;
use App\Models\rimborso;
use App\Support\RimborsoRecordBuilder;
use Illuminate\Support\Facades\Log;
use Stripe\Event;
use Stripe\Stripe;
use Stripe\Webhook;

class StripeWebhookHandler
{
    public function handleRawPayload(string $payload, ?string $signatureHeader): void
    {
        $secret = StripeConfig::webhookSecret();
        if ($secret === '') {
            Log::warning('Stripe webhook: stripe_webhook_secret mancante in parametri globali');

            throw new \RuntimeException('Webhook secret non configurato');
        }

        if (! StripeConfig::isConfigured()) {
            throw new \RuntimeException('Stripe secret key non configurata');
        }

        Stripe::setApiKey(StripeConfig::secretKey());

        try {
            $event = Webhook::constructEvent($payload, (string) $signatureHeader, $secret);
        } catch (\UnexpectedValueException|\Stripe\Exception\SignatureVerificationException $e) {
            Log::warning('Stripe webhook: firma non valida', ['message' => $e->getMessage()]);

            throw $e;
        }

        $this->dispatch($event);
    }

    public function dispatch(Event $event): void
    {
        match ($event->type) {
            'checkout.session.completed' => $this->onCheckoutSessionCompleted($event),
            'checkout.session.async_payment_succeeded' => $this->onCheckoutSessionCompleted($event),
            'payment_intent.succeeded' => $this->onPaymentIntentSucceeded($event),
            'charge.refunded', 'refund.created', 'refund.updated' => $this->onRefundEvent($event),
            default => null,
        };
    }

    private function onPaymentIntentSucceeded(Event $event): void
    {
        /** @var \Stripe\PaymentIntent $intent */
        $intent = $event->data->object;

        $ordineId = (int) ($intent->metadata['ordine_id'] ?? 0);
        $metodoId = (int) ($intent->metadata['metodo_pagamento_id'] ?? 0);

        if ($ordineId <= 0 || $metodoId <= 0) {
            return;
        }

        $ordine = ordine::query()->find($ordineId);
        if (! $ordine) {
            Log::warning('Stripe webhook payment_intent.succeeded: ordine non trovato', ['ordine_id' => $ordineId]);

            return;
        }

        $result = app(OrdineCompletaPagamentoService::class)->segnaPagato(
            $ordine,
            $metodoId,
            null,
            (string) $intent->id,
        );

        if (! $result['ok'] && ! ($result['already'] ?? false)) {
            Log::warning('Stripe webhook payment_intent.succeeded: segnaPagato fallito', [
                'ordine_id' => $ordineId,
                'reason' => $result['reason'] ?? null,
            ]);
        }
    }

    private function onCheckoutSessionCompleted(Event $event): void
    {
        /** @var \Stripe\Checkout\Session $session */
        $session = $event->data->object;

        if ($session->payment_status !== 'paid' && $session->status !== 'complete') {
            return;
        }

        $ordineId = (int) ($session->metadata['ordine_id'] ?? 0);
        $metodoId = (int) ($session->metadata['metodo_pagamento_id'] ?? 0);

        if ($ordineId <= 0 || $metodoId <= 0) {
            Log::warning('Stripe webhook checkout.session.completed: metadata mancanti', [
                'session_id' => $session->id,
            ]);

            return;
        }

        $ordine = ordine::query()->find($ordineId);
        if (! $ordine) {
            Log::warning('Stripe webhook: ordine non trovato', ['ordine_id' => $ordineId]);

            return;
        }

        $paymentIntentId = is_string($session->payment_intent)
            ? $session->payment_intent
            : (is_object($session->payment_intent) ? ($session->payment_intent->id ?? null) : null);

        $result = app(OrdineCompletaPagamentoService::class)->segnaPagato(
            $ordine,
            $metodoId,
            $session->id,
            $paymentIntentId,
        );

        if (! $result['ok'] && ! $result['already']) {
            Log::warning('Stripe webhook: segnaPagato fallito', [
                'ordine_id' => $ordineId,
                'reason' => $result['reason'],
            ]);
        }
    }

    private function onRefundEvent(Event $event): void
    {
        $object = $event->data->object;
        $refundId = $object->id ?? null;
        $metadata = is_array($object->metadata ?? null)
            ? $object->metadata
            : (array) ($object->metadata ?? []);
        $ordineId = (int) ($metadata['ordine_id'] ?? 0);

        if ($ordineId <= 0 && isset($object->payment_intent)) {
            $pi = is_string($object->payment_intent) ? $object->payment_intent : ($object->payment_intent->id ?? null);
            if ($pi) {
                $ordineId = (int) ordine::query()->where('stripe_payment_intent_id', $pi)->value('id');
            }
        }

        if ($ordineId <= 0 || ! $refundId) {
            return;
        }

        $ordine = ordine::query()->find($ordineId);
        if (! $ordine) {
            return;
        }

        $pi = is_string($object->payment_intent ?? null)
            ? $object->payment_intent
            : (is_object($object->payment_intent ?? null) ? ($object->payment_intent->id ?? null) : null);
        if (! $pi && $ordine->stripe_payment_intent_id) {
            $pi = $ordine->stripe_payment_intent_id;
        }

        $amount = isset($object->amount) ? round((int) $object->amount / 100, 2) : null;

        if (! $ordine->stripe_refund_id) {
            $ordine->update(array_filter([
                'stripe_refund_id' => $refundId,
                'stripe_refund_amount' => $amount,
                'stripe_refunded_at' => now(),
            ]));
        }

        if ($pi && ! rimborso::query()->where('stripe_refund_id', $refundId)->exists()) {
            rimborso::query()->create(
                RimborsoRecordBuilder::daRimborsoStripe(
                    $ordine,
                    $refundId,
                    $pi,
                    $amount ?? 0,
                    'Rimborso Stripe (webhook)',
                ),
            );
        }
    }
}
