<?php

namespace App\Support;

/**
 * Riferimento ordine: univoco = id numerico tabella ordinis (nessun prefisso).
 */
final class CodiceOrdine
{
    public static function format(int $id): string
    {
        return (string) $id;
    }

    public static function idDaRiferimento(string $raw): ?int
    {
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }

        if (ctype_digit($raw)) {
            $id = (int) $raw;

            return $id > 0 ? $id : null;
        }

        // Compatibilità input legacy (es. O27 da bookmark o copia-incolla).
        if (preg_match('/^O(\d+)$/i', $raw, $m)) {
            $id = (int) $m[1];

            return $id > 0 ? $id : null;
        }

        return null;
    }
}
