<?php

namespace App\Services\Stripe;

use App\Models\metodo_pagamento_ordine;
use App\Models\ordine;
use App\Services\OrdineTotaleIvatoService;
use App\Support\OrdinePagamentoEffettivo;
use App\Support\OrdineTotaliPagamento;
use Stripe\Exception\ApiErrorException;
use Stripe\PaymentIntent;
use Stripe\Stripe;

final class StripePaymentIntentService
{
    /**
     * Addebito carta con PaymentIntent (Stripe Elements).
     *
     * @return array{
     *     ok: bool,
     *     requires_action?: bool,
     *     client_secret?: string,
     *     payment_intent_id?: string,
     *     message?: string,
     *     redirect_url?: string
     * }
     */
    public function addebitaOrdine(ordine $ordine, int $metodoId, string $paymentMethodId): array
    {
        if (! StripeConfig::isConfigured()) {
            return ['ok' => false, 'message' => 'Stripe non configurato.'];
        }

        if (! $ordine->haStato(ordine::STATO_NON_PAGATO)) {
            return ['ok' => false, 'message' => 'Questo ordine non è in attesa di pagamento.'];
        }

        $metodo = metodo_pagamento_ordine::query()
            ->where('abilitato', true)
            ->findOrFail($metodoId);

        if (! app(OrdineTotaleIvatoService::class)->metodoIsCarta($metodoId)) {
            return ['ok' => false, 'message' => 'Metodo di pagamento non valido per carta.'];
        }

        $paymentMethodId = trim($paymentMethodId);
        if ($paymentMethodId === '') {
            return ['ok' => false, 'message' => 'Dati carta non validi.'];
        }

        $totali = OrdineTotaliPagamento::totaliPerMetodo($ordine, $metodoId);
        $amountCents = (int) round($totali['totale'] * 100);
        if ($amountCents < 50) {
            return ['ok' => false, 'message' => 'Importo ordine troppo basso per Stripe (minimo '.\App\Support\ImportoEuro::format(0.5).').'];
        }

        Stripe::setApiKey(StripeConfig::secretKey());

        $ordine->loadMissing('user');
        $email = trim((string) ($ordine->user?->email ?? ''));

        $params = [
            'amount' => $amountCents,
            'currency' => StripeConfig::currency(),
            'payment_method' => $paymentMethodId,
            'confirm' => true,
            'description' => 'Ordine '.$ordine->codice,
            'metadata' => [
                'ordine_id' => (string) $ordine->id,
                'metodo_pagamento_id' => (string) $metodoId,
                'ordine_codice' => (string) $ordine->codice,
            ],
            'return_url' => route('ordini.pagamento.carta', [
                'ordine' => $ordine,
                'metodo_pagamento_id' => $metodoId,
            ]),
        ];

        if ($email !== '') {
            $params['receipt_email'] = $email;
        }

        try {
            $intent = PaymentIntent::create($params);
        } catch (ApiErrorException $e) {
            report($e);

            return ['ok' => false, 'message' => 'Pagamento rifiutato da Stripe. Verifica i dati carta e riprova.'];
        }

        if ($intent->status === 'requires_action' && $intent->client_secret) {
            return [
                'ok' => false,
                'requires_action' => true,
                'client_secret' => (string) $intent->client_secret,
                'payment_intent_id' => (string) $intent->id,
            ];
        }

        if ($intent->status !== 'succeeded') {
            return ['ok' => false, 'message' => 'Pagamento non completato (stato: '.$intent->status.').'];
        }

        return $this->completaOrdinePagato($ordine, $metodoId, (string) $intent->id);
    }

    /**
     * @return array{ok: bool, requires_action?: bool, client_secret?: string, message?: string, redirect_url?: string}
     */
    public function finalizzaDaIntent(ordine $ordine, int $metodoId, string $paymentIntentId): array
    {
        if (! StripeConfig::isConfigured()) {
            return ['ok' => false, 'message' => 'Stripe non configurato.'];
        }

        $paymentIntentId = trim($paymentIntentId);
        if ($paymentIntentId === '') {
            return ['ok' => false, 'message' => 'Pagamento non valido.'];
        }

        Stripe::setApiKey(StripeConfig::secretKey());

        try {
            $intent = PaymentIntent::retrieve($paymentIntentId);
        } catch (ApiErrorException $e) {
            report($e);

            return ['ok' => false, 'message' => 'Impossibile verificare il pagamento con Stripe.'];
        }

        if ((int) ($intent->metadata['ordine_id'] ?? 0) !== (int) $ordine->id) {
            return ['ok' => false, 'message' => 'Il pagamento non corrisponde a questo ordine.'];
        }

        if ((int) ($intent->metadata['metodo_pagamento_id'] ?? 0) !== $metodoId) {
            return ['ok' => false, 'message' => 'Metodo di pagamento non coerente con la transazione.'];
        }

        if ($intent->status === 'requires_action' && $intent->client_secret) {
            return [
                'ok' => false,
                'requires_action' => true,
                'client_secret' => (string) $intent->client_secret,
                'payment_intent_id' => $paymentIntentId,
            ];
        }

        if ($intent->status !== 'succeeded') {
            return ['ok' => false, 'message' => 'Pagamento non completato (stato: '.$intent->status.').'];
        }

        return $this->completaOrdinePagato($ordine, $metodoId, $paymentIntentId);
    }

    /**
     * @return array{ok: bool, requires_action?: bool, client_secret?: string, message?: string, redirect_url?: string}
     */
    public function completaOrdinePagato(ordine $ordine, int $metodoId, string $paymentIntentId): array
    {
        $result = app(OrdineCompletaPagamentoService::class)->segnaPagato(
            $ordine,
            $metodoId,
            null,
            $paymentIntentId,
        );

        if (! $result['ok'] && ! ($result['already'] ?? false)) {
            return ['ok' => false, 'message' => 'Impossibile registrare il pagamento su questo ordine.'];
        }

        return [
            'ok' => true,
            'redirect_url' => route('ordini.index', ['aba' => 'pagati']),
            'message' => ($result['already'] ?? false)
                ? 'Ordine già risultava pagato.'
                : 'Pagamento con carta completato.',
        ];
    }

    public function importoOrdineIvato(ordine $ordine, int $metodoCartaId): float
    {
        return OrdinePagamentoEffettivo::importoOrdine($ordine, $metodoCartaId);
    }
}
