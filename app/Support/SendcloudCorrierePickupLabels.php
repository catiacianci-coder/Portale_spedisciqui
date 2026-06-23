<?php

namespace App\Support;

/**
 * Etichette pickup/consegna per corrieres Sendcloud da codice_servizio e carrier_code.
 *
 * InPost: pickup e consegna mostrano "Locker o InPost Point" (first/last mile su rete InPost).
 * "Domicilio" in pickup attiva il calendario ritiro a domicilio (POST /pickups) solo se
 * il carrier è tra quelli supportati da RitiroCheckoutDomicilio.
 */
final class SendcloudCorrierePickupLabels
{
    public const INPOST_PICKUP_CONSEGNA = 'Locker o InPost Point';

    public const INPOST_PUNTO_RITIRO = 'Vedi Locker o InPost Point';

    public const INPOST_PUNTO_CONSEGNA = 'Seleziona Locker o InPost Point';
    /**
     * @return array{pickup: ?string, consegna: ?string}
     */
    public static function fromCorriere(?string $codiceServizio, ?string $carrierCode): array
    {
        $codice = strtolower(trim((string) $codiceServizio));
        $carrier = strtolower(trim((string) $carrierCode));

        if ($codice === '') {
            return ['pickup' => null, 'consegna' => null];
        }

        if ($carrier === 'inpost_it' || str_starts_with($codice, 'inpost_it:')) {
            return self::inpost($codice);
        }

        if ($carrier === 'poste_it_delivery' || str_starts_with($codice, 'poste_it_delivery:')) {
            return self::poste($codice);
        }

        if (in_array($carrier, ['brt', 'gls_it', 'dhl_express'], true)) {
            return self::corrierePickupDomicilio($codice);
        }

        return self::corrierePickupDomicilio($codice);
    }

    /**
     * @return array{pickup: ?string, consegna: ?string}
     */
    private static function inpost(string $codice): array
    {
        return [
            'pickup' => self::INPOST_PICKUP_CONSEGNA,
            'consegna' => self::INPOST_PICKUP_CONSEGNA,
        ];
    }

    /**
     * @return array{pickup: ?string, consegna: ?string}
     */
    private static function poste(string $codice): array
    {
        if (str_contains($codice, 'dropoff') && ! str_contains($codice, 'shop2home')) {
            return ['pickup' => 'Punto Poste', 'consegna' => self::posteConsegna($codice)];
        }

        if (str_contains($codice, 'shop2home')) {
            return ['pickup' => 'Punto Poste', 'consegna' => 'Domicilio'];
        }

        return ['pickup' => 'Domicilio', 'consegna' => self::posteConsegna($codice)];
    }

    private static function posteConsegna(string $codice): string
    {
        if (str_contains($codice, 'postoffice') || str_contains($codice, 'posttopost') || str_contains($codice, 'puntotopost')) {
            return 'Ufficio Postale';
        }

        if (
            str_contains($codice, 'puntoposte')
            || str_contains($codice, 'puntotopunto')
            || str_contains($codice, 'locker')
            || str_contains($codice, 'shop2shop')
        ) {
            return 'Punto Poste';
        }

        return 'Domicilio';
    }

    /**
     * @return array{pickup: ?string, consegna: ?string}
     */
    private static function corrierePickupDomicilio(string $codice): array
    {
        $pickup = str_contains($codice, 'dropoff') && ! str_contains($codice, 'pickup')
            ? 'Punto'
            : (str_contains($codice, 'flex_delivery') || str_contains($codice, 'signature')
                ? 'Domicilio/Punto'
                : 'Domicilio');

        $consegna = str_contains($codice, 'service_point') && ! str_contains($codice, 'home')
            ? 'Punto'
            : 'Domicilio';

        return ['pickup' => $pickup, 'consegna' => $consegna];
    }
}
