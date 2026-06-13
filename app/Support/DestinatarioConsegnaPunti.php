<?php

namespace App\Support;

use App\Models\corriere;

/**
 * Consegna destinatario: punto Poste / ufficio / locker InPost (Sendcloud).
 */
final class DestinatarioConsegnaPunti
{
    public static function richiedePuntoCorriere(corriere $corriere): bool
    {
        return trim((string) ($corriere->punto_consegna ?? '')) !== '';
    }

    /**
     * @return array{carrier_code: string|null, general_shop_type: string|null, carrier_shop_type: string|null}|null
     */
    public static function filtriDaCorriere(corriere $corriere): ?array
    {
        return CorrierePuntoRicerca::filtriConsegna($corriere);
    }
}
