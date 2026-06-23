<?php

namespace App\Support;

use App\Models\User;

/**
 * Prezzo trasporto Liccardi per clienti premium: (importo Liccardi − 3 €) ÷ 2.
 */
final class LiccardiPremiumPricing
{
    public static function utenteLiccardi(?User $user): bool
    {
        return $user !== null && (bool) $user->is_liccardi;
    }

    public static function mostraPreventivoLiccardi(?User $user): bool
    {
        return self::utenteLiccardi($user);
    }

    public static function costoTrasportoBase(float $liccardiPrice): float
    {
        return max(0.0, round(($liccardiPrice - 3.0) / 2.0, 2));
    }
}
