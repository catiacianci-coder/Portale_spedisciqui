<?php

namespace App\Support;

use App\Models\ordine;

final class ChiaveCausaleOrdine
{
    public const PREFIX = 'OS';

    public const LENGTH = 12;

    /** @var non-empty-string */
    private const CHARSET = '23456789ABCDEFGHJKMNPQRSTUVWXYZ';

    public static function genera(): string
    {
        $randomLen = self::LENGTH - strlen(self::PREFIX);
        $charset = self::CHARSET;
        $max = strlen($charset) - 1;
        $suffix = '';

        for ($i = 0; $i < $randomLen; $i++) {
            $suffix .= $charset[random_int(0, $max)];
        }

        return self::PREFIX.$suffix;
    }

    public static function generaUnica(): string
    {
        for ($attempt = 0; $attempt < 25; $attempt++) {
            $chiave = self::genera();
            if (! ordine::query()->where('chiave_causale', $chiave)->exists()) {
                return $chiave;
            }
        }

        throw new \RuntimeException('Impossibile generare una chiave causale univoca.');
    }
}
