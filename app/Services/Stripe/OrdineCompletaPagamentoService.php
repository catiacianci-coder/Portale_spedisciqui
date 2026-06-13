<?php

namespace App\Services\Stripe;

use App\Models\metodo_pagamento_ordine;
use App\Models\ordine;
use App\Services\OrdineTotaleIvatoService;
use App\Services\Liccardi\LiccardiTmsAcquistoService;
use App\Services\Sendcloud\SendcloudAcquistoService;
use App\Services\SpedisciOnline\SpedisciOnlineAcquistoService;
use App\Services\SpedizioneStatoService;
use App\Support\OrdineDatiPagamento;
use App\Support\OrdinePagamentoEffettivo;
use App\Support\StripeOrdineStripeIds;
use Illuminate\Support\Facades\DB;

/**
 * Segna un ordine come pagato (gateway carta / conferma Stripe), senza movimento wallet.
 */
class OrdineCompletaPagamentoService
{
    /**
     * @return array{ok: bool, already: bool, reason: ?string}
     */
    public function segnaPagato(
        ordine $ordine,
        int $metodoId,
        ?string $stripeCheckoutSessionId = null,
        ?string $stripePaymentIntentId = null,
    ): array {
        $totaleSvc = app(OrdineTotaleIvatoService::class);
        metodo_pagamento_ordine::query()->where('abilitato', true)->findOrFail($metodoId);

        $blockReason = null;
        $alreadyPaid = false;

        DB::transaction(function () use (
            $ordine,
            $metodoId,
            $stripeCheckoutSessionId,
            $stripePaymentIntentId,
            $totaleSvc,
            &$blockReason,
            &$alreadyPaid,
        ): void {
            $locked = ordine::query()->whereKey($ordine->id)->lockForUpdate()->first();
            if (! $locked) {
                $blockReason = 'missing';

                return;
            }

            if ($locked->haStato(ordine::STATO_PAGATO)) {
                $alreadyPaid = true;

                return;
            }

            if (! $locked->haStato(ordine::STATO_NON_PAGATO)) {
                $blockReason = 'stato';

                return;
            }

            $metodo = metodo_pagamento_ordine::query()->find($metodoId);

            $update = OrdineDatiPagamento::attributiPagamentoCompletato(
                $locked,
                $metodoId,
                $metodo,
                $stripePaymentIntentId,
            );

            if ($stripeCheckoutSessionId !== null && $stripeCheckoutSessionId !== '') {
                $update['stripe_checkout_session_id'] = $stripeCheckoutSessionId;
            }
            if ($stripePaymentIntentId !== null && $stripePaymentIntentId !== '') {
                $update['stripe_payment_intent_id'] = $stripePaymentIntentId;
            }

            $locked->update($update);

            OrdinePagamentoEffettivo::registraSuTariffe($locked->fresh(), $metodoId);
            SpedizioneStatoService::segnaPagataPerOrdine($locked);

            StripeOrdineStripeIds::propagaPaymentIntentSuSpedizioni(
                $locked,
                $stripePaymentIntentId ?? $locked->stripe_payment_intent_id,
            );
        });

        if ($alreadyPaid) {
            if ($stripePaymentIntentId !== null && $stripePaymentIntentId !== '') {
                $ordine->update(['stripe_payment_intent_id' => $stripePaymentIntentId]);
                StripeOrdineStripeIds::propagaPaymentIntentSuSpedizioni($ordine->fresh(), $stripePaymentIntentId);
            }

            return ['ok' => true, 'already' => true, 'reason' => null];
        }

        if ($blockReason !== null) {
            return ['ok' => false, 'already' => false, 'reason' => $blockReason];
        }

        $ordine->refresh();
        app(SpedisciOnlineAcquistoService::class)->elaboraOrdinePagato($ordine);
        app(LiccardiTmsAcquistoService::class)->elaboraOrdinePagato($ordine);
        app(SendcloudAcquistoService::class)->elaboraOrdinePagato($ordine);

        return ['ok' => true, 'already' => false, 'reason' => null];
    }
}
