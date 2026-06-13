<?php

namespace App\Services;

use App\Models\corriere;
use App\Models\tariffa;

/**
 * Prezzo base listino: max(tariffa, colonne regionali attive per corriere e tratta).
 */
final class TariffaPrezzoBaseService
{
    private const REGIONI_ISOLA = ['sicilia', 'calabria', 'sardegna'];

    /**
     * @return list<string> Chiavi regione normalizzate (sicilia|calabria|sardegna) coinvolte in tratta.
     */
    public static function regioniTratta(?string $regioneOrigine, ?string $regioneDestino): array
    {
        $out = [];
        foreach ([$regioneOrigine, $regioneDestino] as $regione) {
            $key = self::normalizzaRegione($regione);
            if ($key !== null && ! in_array($key, $out, true)) {
                $out[] = $key;
            }
        }

        return $out;
    }

    public static function prezzoBase(
        tariffa $tariffa,
        corriere $corriere,
        ?string $regioneOrigine,
        ?string $regioneDestino,
    ): float {
        $base = (float) ($tariffa->tariffa ?? 0);

        foreach (self::regioniTratta($regioneOrigine, $regioneDestino) as $regione) {
            if (! self::corriereAbilitaRegione($corriere, $regione)) {
                continue;
            }

            $regionale = $tariffa->getAttribute($regione);
            if ($regionale === null || $regionale === '') {
                continue;
            }

            $base = max($base, (float) $regionale);
        }

        return round($base, 2);
    }

    public static function corriereAbilitaRegione(corriere $corriere, string $regione): bool
    {
        return match ($regione) {
            'sicilia' => (bool) $corriere->sicilia,
            'calabria' => (bool) $corriere->calabria,
            'sardegna' => (bool) $corriere->sardegna,
            default => false,
        };
    }

    public static function normalizzaRegione(?string $regione): ?string
    {
        $r = mb_strtolower(trim((string) $regione));

        return in_array($r, self::REGIONI_ISOLA, true) ? $r : null;
    }
}
