<?php

namespace App\Support;

/** Formato importi in euro: «€ 12,50». */
final class ImportoEuro
{
    public static function format(float|int|null $importo, int $decimals = 2): string
    {
        if ($importo === null) {
            return '—';
        }

        return '€ '.number_format((float) $importo, $decimals, ',', '.');
    }

    public static function numero(float|int|null $importo, int $decimals = 2): string
    {
        if ($importo === null) {
            return '—';
        }

        return number_format((float) $importo, $decimals, ',', '.');
    }
}
