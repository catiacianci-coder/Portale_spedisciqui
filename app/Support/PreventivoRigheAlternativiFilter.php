<?php

namespace App\Support;

/**
 * Nasconde corrieri alternativi quando in preventivo basta mostrarne uno (stesso prodotto / stessa famiglia).
 */
final class PreventivoRigheAlternativiFilter
{
    public const GLS_STANDARD = 5;

    public const GLS_LIGHT = 13;

    public const INPOST_MEDIUM = 11;

    public const INPOST_LARGE = 12;

    /**
     * @param  array<int, array<string, mixed>>  $sendcloudQuotePerCorriere
     * @param  array<int, array<string, mixed>>  $spedisciQuotePerCorriere
     * @return list<int>
     */
    public static function corriereIdsDaNascondere(
        array $sendcloudQuotePerCorriere,
        array $spedisciQuotePerCorriere,
    ): array {
        $nascosti = [];

        self::applicaGls(
            self::prezzoApi(self::GLS_STANDARD, $spedisciQuotePerCorriere),
            self::prezzoApi(self::GLS_LIGHT, $spedisciQuotePerCorriere),
            $nascosti,
        );

        self::applicaInpost(
            self::prezzoApi(self::INPOST_MEDIUM, $sendcloudQuotePerCorriere),
            self::prezzoApi(self::INPOST_LARGE, $sendcloudQuotePerCorriere),
            $nascosti,
        );

        return array_values(array_unique($nascosti));
    }

    /**
     * @param  list<int>  $nascosti
     */
    private static function applicaGls(?float $standard, ?float $light, array &$nascosti): void
    {
        if ($standard === null || $light === null) {
            return;
        }

        if (self::prezziUguali($standard, $light)) {
            $nascosti[] = self::GLS_LIGHT;

            return;
        }

        if ($light < $standard) {
            $nascosti[] = self::GLS_STANDARD;

            return;
        }

        $nascosti[] = self::GLS_LIGHT;
    }

    /**
     * @param  list<int>  $nascosti
     */
    private static function applicaInpost(?float $medium, ?float $large, array &$nascosti): void
    {
        if ($medium === null || $large === null) {
            return;
        }

        if ($medium <= $large) {
            $nascosti[] = self::INPOST_LARGE;

            return;
        }

        $nascosti[] = self::INPOST_MEDIUM;
    }

    /**
     * @param  array<int, array<string, mixed>>  $quotePerCorriere
     */
    private static function prezzoApi(int $corriereId, array $quotePerCorriere): ?float
    {
        $quote = $quotePerCorriere[$corriereId]['quote'] ?? null;
        if (! is_array($quote)) {
            return null;
        }

        $amount = $quote['price_amount'] ?? null;
        if ($amount === null || ! is_numeric($amount)) {
            return null;
        }

        $value = (float) $amount;

        return $value > 0 ? round($value, 2) : null;
    }

    private static function prezziUguali(float $a, float $b): bool
    {
        return abs($a - $b) < 0.005;
    }
}
