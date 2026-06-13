<?php

namespace App\Support;

use App\Models\ordine;

final class StripeOrdineStripeIds
{
    public static function propagaPaymentIntentSuSpedizioni(ordine $ordine, ?string $stripePaymentIntentId): void
    {
        $pi = trim((string) ($stripePaymentIntentId ?? ''));
        if ($pi === '' || ! str_starts_with($pi, 'pi_')) {
            return;
        }

        $ordine->spedizioni()->update(['stripe_payment_intent_id' => $pi]);
    }
}
