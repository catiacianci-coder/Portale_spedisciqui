<?php

namespace App\Support;

final class MetodoPagamentoCodice
{
    public const WALLET = 'wallet';

    public const CARTA = 'carta';

    public const BONIFICO = 'bonifico';

    public static function isWalletCodice(?string $codice): bool
    {
        return strtolower(trim((string) $codice)) === self::WALLET;
    }

    public static function isCartaCodice(?string $codice): bool
    {
        return strtolower(trim((string) $codice)) === self::CARTA;
    }

    public static function isCartaNome(?string $nome): bool
    {
        $nome = strtolower(trim((string) $nome));

        return $nome !== '' && (
            str_contains($nome, 'carta')
            || str_contains($nome, 'credit')
            || str_contains($nome, 'debit')
        );
    }

    public static function isBonificoCodice(?string $codice): bool
    {
        return strtolower(trim((string) $codice)) === self::BONIFICO;
    }
}
