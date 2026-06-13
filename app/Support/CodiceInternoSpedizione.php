<?php

namespace App\Support;

use App\Models\spedizione;
use Illuminate\Support\Carbon;

/**
 * Codice spedizione: YYYY + GG + MM + HH + mm + L + NNN + L (senza prefisso).
 */
final class CodiceInternoSpedizione
{
    public static function genera(?Carbon $at = null): string
    {
        $at ??= now();

        $base = $at->format('YdmHi');
        $lettera1 = chr(random_int(65, 90));
        $cifre = (string) random_int(0, 999);
        $cifre = str_pad($cifre, 3, '0', STR_PAD_LEFT);
        $lettera2 = chr(random_int(65, 90));

        return $base.$lettera1.$cifre.$lettera2;
    }

    public static function assegnaUnivoco(?Carbon $at = null, int $maxTentativi = 25): string
    {
        for ($i = 0; $i < $maxTentativi; $i++) {
            $codice = self::genera($at);
            $esiste = spedizione::query()->where('codice_interno', $codice)->exists();
            if (! $esiste) {
                return $codice;
            }
        }

        return self::genera($at).chr(random_int(65, 90));
    }
}
