<?php

namespace App\Services\Rimborso;

use App\Models\ordine;
use App\Models\rimborso;
use App\Services\Stripe\StripeConfig;
use Illuminate\Support\Facades\Log;
use Stripe\Refund;
use Stripe\Stripe;

final class RimborsoStripeSpedizioneService
{
    /**
     * Rimborso parziale Stripe: importo e Payment Intent dal record rimborso.
     *
     * @return array{ok: bool, message: string, refund_id: ?string}
     */
    public function rimborsa(rimborso $rimborso, ordine $ordine): array
    {
        if (! StripeConfig::isConfigured()) {
            return ['ok' => false, 'message' => 'Stripe non configurato.', 'refund_id' => null];
        }

        $paymentIntentId = trim((string) (
            $rimborso->stripe_payment_intent_id
            ?? $rimborso->payment_id
            ?? ''
        ));
        if ($paymentIntentId === '' || ! str_starts_with($paymentIntentId, 'pi_')) {
            return ['ok' => false, 'message' => 'Payment Intent Stripe mancante sul record rimborso.', 'refund_id' => null];
        }

        $importo = round((float) $rimborso->valore, 2);
        if ($importo < 0.5) {
            return ['ok' => false, 'message' => 'Importo rimborso troppo basso per Stripe.', 'refund_id' => null];
        }

        $giaRimborsato = (float) rimborso::query()
            ->where('ordine_id', $ordine->id)
            ->whereNotNull('stripe_refund_id')
            ->whereKeyNot($rimborso->id)
            ->sum('valore');

        $totale = (float) ($ordine->pag_effettivo_or ?? $ordine->total_pagamento ?? 0);
        if ($totale > 0 && round($giaRimborsato + $importo, 2) > round($totale, 2) + 0.01) {
            return ['ok' => false, 'message' => 'L’importo supera il totale ancora rimborsabile su Stripe.', 'refund_id' => null];
        }

        Stripe::setApiKey(StripeConfig::secretKey());

        $codiceInterno = trim((string) ($rimborso->codice_interno ?? ''));
        if ($codiceInterno === '' && $rimborso->spedizione_id) {
            $rimborso->loadMissing('spedizione');
            $codiceInterno = trim((string) ($rimborso->spedizione?->codice_interno ?? ''));
        }

        try {
            $refund = Refund::create([
                'payment_intent' => $paymentIntentId,
                'amount' => (int) round($importo * 100),
                'reason' => 'requested_by_customer',
                'metadata' => [
                    'ordine_id' => (string) $ordine->id,
                    'codice_interno' => $codiceInterno,
                    'rimborso_id' => (string) $rimborso->id,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::warning('Stripe rimborso spedizione fallito', [
                'ordine_id' => $ordine->id,
                'rimborso_id' => $rimborso->id,
                'message' => $e->getMessage(),
            ]);

            return ['ok' => false, 'message' => 'Rimborso Stripe non riuscito: '.$e->getMessage(), 'refund_id' => null];
        }

        return [
            'ok' => true,
            'message' => 'Rimborso Stripe registrato.',
            'refund_id' => (string) ($refund->id ?? ''),
        ];
    }
}
