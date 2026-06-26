<?php

namespace App\Services\Stripe;

use App\Models\metodo_pagamento_wallet_ricarica;
use App\Models\wallet_ricarica_richiesta;
use App\Services\Wallet\WalletRicaricaAccreditoService;
use App\Support\WalletRicaricaTotaliPagamento;
use Stripe\Exception\ApiErrorException;
use Stripe\PaymentIntent;
use Stripe\Stripe;

final class StripeRicaricaPaymentIntentService
{
    /**
     * @return array{
     *     ok: bool,
     *     requires_action?: bool,
     *     client_secret?: string,
     *     payment_intent_id?: string,
     *     message?: string,
     *     redirect_url?: string
     * }
     */
    public function addebitaRicarica(
        wallet_ricarica_richiesta $ricarica,
        int $metodoId,
        string $paymentMethodId,
    ): array {
        if (! StripeConfig::isConfigured()) {
            return ['ok' => false, 'message' => 'Stripe non configurato.'];
        }

        if ($ricarica->stato !== 'in_attesa') {
            return ['ok' => false, 'message' => 'Questa ricarica non è in attesa di pagamento.'];
        }

        $metodo = metodo_pagamento_wallet_ricarica::query()
            ->where('abilitato', true)
            ->findOrFail($metodoId);

        if (! $metodo->isCarta()) {
            return ['ok' => false, 'message' => 'Metodo di pagamento non valido per carta.'];
        }

        $paymentMethodId = trim($paymentMethodId);
        if ($paymentMethodId === '') {
            return ['ok' => false, 'message' => 'Dati carta non validi.'];
        }

        $totali = WalletRicaricaTotaliPagamento::perMetodo($ricarica, $metodoId);
        $amountCents = (int) round($totali['totale'] * 100);
        if ($amountCents < 50) {
            return ['ok' => false, 'message' => 'Importo troppo basso per Stripe (minimo '.\App\Support\ImportoEuro::format(0.5).').'];
        }

        Stripe::setApiKey(StripeConfig::secretKey());

        $ricarica->loadMissing('user');
        $email = trim((string) ($ricarica->user?->email ?? ''));
        $codice = (string) ($ricarica->numero_ordine_wallet ?? wallet_ricarica_richiesta::PREFIX_NUMERO_ORDINE_WALLET.$ricarica->id);

        $params = [
            'amount' => $amountCents,
            'currency' => StripeConfig::currency(),
            'payment_method' => $paymentMethodId,
            'confirm' => true,
            'description' => 'Ricarica wallet '.$codice,
            'metadata' => [
                'wallet_ricarica_id' => (string) $ricarica->id,
                'metodo_pagamento_wallet_ricarica_id' => (string) $metodoId,
                'ricarica_codice' => $codice,
            ],
            'return_url' => route('wallet.ricariche.pagamento.carta', [
                'ricarica' => $ricarica,
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

        return $this->completaRicaricaPagata($ricarica, $metodoId, (string) $intent->id);
    }

    /**
     * @return array{ok: bool, requires_action?: bool, client_secret?: string, message?: string, redirect_url?: string}
     */
    public function finalizzaDaIntent(
        wallet_ricarica_richiesta $ricarica,
        int $metodoId,
        string $paymentIntentId,
    ): array {
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

        if ((int) ($intent->metadata['wallet_ricarica_id'] ?? 0) !== (int) $ricarica->id) {
            return ['ok' => false, 'message' => 'Il pagamento non corrisponde a questa ricarica.'];
        }

        if ((int) ($intent->metadata['metodo_pagamento_wallet_ricarica_id'] ?? 0) !== $metodoId) {
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

        return $this->completaRicaricaPagata($ricarica, $metodoId, $paymentIntentId);
    }

    /**
     * @return array{ok: bool, message?: string, redirect_url?: string, already?: bool}
     */
    public function completaRicaricaPagata(
        wallet_ricarica_richiesta $ricarica,
        int $metodoId,
        string $paymentIntentId,
    ): array {
        $result = app(WalletRicaricaAccreditoService::class)->accredita(
            $ricarica,
            $metodoId,
            $paymentIntentId,
            'Ricarica '.($ricarica->numero_ordine_wallet ?? wallet_ricarica_richiesta::PREFIX_NUMERO_ORDINE_WALLET.$ricarica->id).' (carta)',
        );

        if (! $result['ok'] && ! ($result['already'] ?? false)) {
            return ['ok' => false, 'message' => $result['message'] ?? 'Impossibile accreditare la ricarica.'];
        }

        return [
            'ok' => true,
            'redirect_url' => route('wallet.ricariche'),
            'message' => ($result['already'] ?? false)
                ? 'Ricarica già accreditata.'
                : 'Pagamento con carta completato. L\'importo è stato accreditato sul wallet.',
        ];
    }
}
