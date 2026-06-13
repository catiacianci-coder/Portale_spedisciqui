<?php

namespace App\Support;

/**
 * Separa via e civico per persistenza DB e payload Sendcloud (address_line_1 / house_number).
 */
final class IndirizzoViaCivico
{
    /**
     * @return array{0: string, 1: string} via, civico
     */
    public static function perSendcloud(?string $indirizzo, ?string $numero, ?string $viaEsplicita = null): array
    {
        $civico = trim((string) $numero);
        $via = trim((string) ($viaEsplicita ?? ''));
        if ($via === '') {
            $via = trim((string) $indirizzo);
        }

        if ($civico !== '') {
            return [self::viaSenzaCivico($via, $civico), $civico];
        }

        return self::estraiDaRigaUnica($via);
    }

    /**
     * Colonna indirizzo_* in DB: solo via quando disponibile.
     */
    public static function perColonnaDatabase(string $via, string $numero, string $indirizzo): string
    {
        if ($via !== '') {
            return $via;
        }

        if ($indirizzo !== '') {
            return $indirizzo;
        }

        return IndirizzoSpedizioneSnapshot::componeIndirizzo($via, $numero);
    }

    private static function viaSenzaCivico(string $via, string $civico): string
    {
        if ($via === '') {
            return '';
        }

        $quoted = preg_quote($civico, '/');
        if (preg_match('/\s+'.$quoted.'(\s+.*)?$/u', $via)) {
            return trim((string) preg_replace('/\s+'.$quoted.'(\s+.*)?$/u', '', $via));
        }

        return $via;
    }

    /**
     * @return array{0: string, 1: string}
     */
    private static function estraiDaRigaUnica(string $riga): array
    {
        if ($riga === '') {
            return ['', ''];
        }

        if (preg_match('/^(.*)\s+(\d+[A-Za-z]?)$/u', $riga, $m)) {
            return [trim($m[1]), trim($m[2])];
        }

        return [$riga, ''];
    }
}
