<?php

namespace App\Support;

final class CodiceOrdine
{
    public const PREFIX = 'O';

    public static function format(int $id): string
    {
        return self::PREFIX.$id;
    }

    public static function idDaRiferimento(string $raw): ?int
    {
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }

        if (preg_match('/^O(\d+)$/i', $raw, $m)) {
            return (int) $m[1];
        }

        if (preg_match('/^ORS-(\d+)$/i', $raw, $m)) {
            return (int) $m[1];
        }

        if (ctype_digit($raw)) {
            return (int) $raw;
        }

        return null;
    }
}
