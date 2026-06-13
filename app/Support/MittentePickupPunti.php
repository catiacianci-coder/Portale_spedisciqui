<?php

namespace App\Support;

use App\Models\corriere;

/**
 * Ritiro mittente: elenco punti Sendcloud in preventivi (solo visualizzazione).
 */
final class MittentePickupPunti
{
    /**
     * @return array{filter_general: string|null, filter_carrier_shop: string|null, carrier_code: string|null, link_label: string}|null
     */
    public static function configDaCorriere(corriere $corriere): ?array
    {
        if (! CorrierePuntoEtichetta::ritiroConsultabileInPreventivi($corriere->pickup, $corriere->punto_ritiro)) {
            return null;
        }

        $filtri = CorrierePuntoRicerca::filtriRitiro($corriere);
        $label = trim((string) ($corriere->punto_ritiro ?? ''));
        if ($filtri === null || $label === '') {
            return null;
        }

        return [
            'filter_general' => $filtri['general_shop_type'] ?? null,
            'filter_carrier_shop' => $filtri['carrier_shop_type'] ?? null,
            'carrier_code' => $filtri['carrier_code'] ?? null,
            'link_label' => $label,
        ];
    }
}
