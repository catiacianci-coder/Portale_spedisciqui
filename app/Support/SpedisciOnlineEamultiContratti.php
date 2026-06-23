<?php

namespace App\Support;

use App\Models\corriere;

/**
 * Corrieri eamulti (Spedisci.online): contractCode in corrieres.codice_servizio.
 */
final class SpedisciOnlineEamultiContratti
{
    public const CORRIERE_SDA_M = 4;

    /** @deprecated Usare CORRIERE_SDA_M */
    public const CORRIERE_POSTE_DELIVERY_BUSINESS_STANDARD = 4;

    public const CORRIERE_GLS_STANDARD = 5;

    public const CORRIERE_GLS_LIGHT = 13;

    public const CORRIERE_UPS = 14;

    /**
     * @return list<int>
     */
    public static function corrieriIdsPreventivo(): array
    {
        return [
            self::CORRIERE_SDA_M,
            self::CORRIERE_GLS_STANDARD,
            self::CORRIERE_GLS_LIGHT,
            self::CORRIERE_UPS,
        ];
    }

    public static function contractCodeForCorriere(corriere $corriere): string
    {
        return trim((string) ($corriere->codice_servizio ?? ''));
    }

    public static function contractCode(int $corriereId): string
    {
        $corriere = corriere::query()->find($corriereId);

        return $corriere !== null ? self::contractCodeForCorriere($corriere) : '';
    }
}
