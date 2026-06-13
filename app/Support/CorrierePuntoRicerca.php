<?php

namespace App\Support;

use App\Models\corriere;

/**
 * Parametri GET /service-points Sendcloud da corriere (pickup/consegna/carrier_code).
 */
final class CorrierePuntoRicerca
{
    /**
     * @return array{carrier_code: string|null, general_shop_type: string|null, carrier_shop_type: string|null}|null
     */
    public static function filtriRitiro(corriere $corriere): ?array
    {
        if (! CorrierePuntoEtichetta::haRitiroInPreventivi($corriere->punto_ritiro)) {
            return null;
        }

        return self::filtriPickupDaTesto((string) ($corriere->pickup ?? ''), (string) ($corriere->carrier_code ?? ''));
    }

    /**
     * @return array{carrier_code: string|null, general_shop_type: string|null, carrier_shop_type: string|null}|null
     */
    public static function filtriConsegna(corriere $corriere): ?array
    {
        if (trim((string) ($corriere->punto_consegna ?? '')) === '') {
            return null;
        }

        $carrierCode = trim((string) ($corriere->carrier_code ?? ''));
        if ($carrierCode === 'inpost_it') {
            return [
                'carrier_code' => 'inpost_it',
                'general_shop_type' => 'locker',
                'carrier_shop_type' => null,
            ];
        }

        return self::filtriConsegnaDaTesto((string) ($corriere->consegna ?? ''), $carrierCode);
    }

    /**
     * @return array{carrier_code: string|null, general_shop_type: string|null, carrier_shop_type: string|null}|null
     */
    public static function filtriPickupDaTesto(string $pickup, string $carrierCode = ''): ?array
    {
        $lower = mb_strtolower(trim($pickup));
        if ($lower === '' || str_contains($lower, 'domicil')) {
            return null;
        }

        if (str_contains($lower, 'locker') && $carrierCode === 'inpost_it') {
            return [
                'carrier_code' => 'inpost_it',
                'general_shop_type' => 'locker',
                'carrier_shop_type' => null,
            ];
        }

        if (
            str_contains($lower, 'punto')
            || str_contains($lower, 'tabac')
            || str_contains($lower, 'edicol')
            || str_contains($lower, 'locker')
        ) {
            return [
                'carrier_code' => null,
                'general_shop_type' => null,
                'carrier_shop_type' => 'punto_poste',
            ];
        }

        if (str_contains($lower, 'ufficio') || str_contains($lower, 'poste')) {
            return [
                'carrier_code' => null,
                'general_shop_type' => 'post_office',
                'carrier_shop_type' => null,
            ];
        }

        return null;
    }

    /**
     * @return array{carrier_code: string|null, general_shop_type: string|null, carrier_shop_type: string|null}|null
     */
    public static function filtriConsegnaDaTesto(string $consegna, string $carrierCode = ''): ?array
    {
        $lower = mb_strtolower(trim($consegna));
        if ($lower === '' || str_contains($lower, 'domicil')) {
            return null;
        }

        if (str_contains($lower, 'locker') && $carrierCode === 'inpost_it') {
            return [
                'carrier_code' => 'inpost_it',
                'general_shop_type' => 'locker',
                'carrier_shop_type' => null,
            ];
        }

        if (
            str_contains($lower, 'punto')
            || str_contains($lower, 'tabac')
            || str_contains($lower, 'edicol')
            || str_contains($lower, 'locker')
        ) {
            return [
                'carrier_code' => null,
                'general_shop_type' => null,
                'carrier_shop_type' => 'punto_poste',
            ];
        }

        if (str_contains($lower, 'ufficio') || str_contains($lower, 'poste')) {
            return [
                'carrier_code' => null,
                'general_shop_type' => 'post_office',
                'carrier_shop_type' => null,
            ];
        }

        return null;
    }
}
