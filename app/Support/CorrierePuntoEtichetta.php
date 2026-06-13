<?php

namespace App\Support;

/**
 * Testi punto ritiro/consegna da tabella corrieres.
 */
final class CorrierePuntoEtichetta
{
    public static function haRitiroInPreventivi(?string $puntoRitiro): bool
    {
        return trim((string) $puntoRitiro) !== '';
    }

    public static function pickupNonDomicilio(?string $pickup): bool
    {
        $pickup = trim((string) $pickup);
        if ($pickup === '') {
            return false;
        }

        return ! str_contains(mb_strtolower($pickup), 'domicilio');
    }

    /**
     * Link consultazione punti ritiro in preventivi: solo se il ritiro non è a domicilio.
     */
    public static function ritiroConsultabileInPreventivi(?string $pickup, ?string $puntoRitiro): bool
    {
        return self::pickupNonDomicilio($pickup) && self::haRitiroInPreventivi($puntoRitiro);
    }

    /** La consegna a punto si gestisce solo in checkout, non in preventivi. */
    public static function haConsegnaInPreventivi(?string $puntoConsegna): bool
    {
        return false;
    }

    public static function etichettaVediPreventivi(?string $testo): ?string
    {
        $t = trim((string) $testo);
        if ($t === '') {
            return null;
        }

        if (preg_match('/^Vedi\s/iu', $t)) {
            return $t;
        }

        if (preg_match('/^Seleziona\s+/iu', $t)) {
            return (string) preg_replace('/^Seleziona\s+/iu', 'Vedi ', $t);
        }

        return null;
    }

    public static function etichettaSelezionaCheckout(?string $puntoConsegna): ?string
    {
        $t = trim((string) $puntoConsegna);

        return $t !== '' ? $t : null;
    }
}
