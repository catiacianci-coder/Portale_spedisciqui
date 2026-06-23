<?php

namespace App\Support;

use App\Models\corriere;
use App\Models\spedizione;
use App\Services\Sendcloud\SendcloudContractResolver;

/**
 * Checkout e post-pagamento: ritiro a domicilio (SDA Spedisci.online o Sendcloud).
 */
final class RitiroCheckoutDomicilio
{
    /** Carrier code Sendcloud con POST /pickups documentato (IT). */
    private const SENDCLOUD_PICKUP_CARRIER_CODES = [
        'brt',
        'gls_it',
        'poste_it_delivery',
        'dhl_express',
    ];

    public static function ritiroADomicilio(?corriere $corriere): bool
    {
        if ($corriere === null) {
            return false;
        }

        $pickup = mb_strtolower(trim((string) $corriere->pickup));

        return $pickup !== '' && str_contains($pickup, 'domicilio');
    }

    public static function corriereRichiedeDataRitiro(?corriere $corriere): bool
    {
        if ($corriere === null) {
            return false;
        }

        if ((int) $corriere->id === SpedisciOnlineEamultiContratti::CORRIERE_SDA_M) {
            return true;
        }

        if (! self::ritiroADomicilio($corriere)) {
            return false;
        }

        if (PiattaformaCorriere::corriereUsaAcquistoSendcloud($corriere)) {
            return self::sendcloudCarrierSupportaPickupApi($corriere);
        }

        return false;
    }

    public static function sendcloudCarrierSupportaPickupApi(corriere $corriere): bool
    {
        $code = strtolower(trim(app(SendcloudContractResolver::class)->carrierCode($corriere)));

        return $code !== '' && in_array($code, self::SENDCLOUD_PICKUP_CARRIER_CODES, true);
    }

    public static function spedizioneRichiedePickup(spedizione $spedizione): bool
    {
        $spedizione->loadMissing('corriereRecord');

        return self::corriereRichiedeDataRitiro($spedizione->corriereRecord)
            && $spedizione->data_ritiro !== null;
    }

    public static function etichettaCheckout(?corriere $corriere): string
    {
        if ($corriere === null) {
            return 'Data ritiro';
        }

        if ((int) $corriere->id === SpedisciOnlineEamultiContratti::CORRIERE_SDA_M) {
            return 'Data ritiro SDA';
        }

        $nome = trim((string) ($corriere->nome_visualizzato ?? $corriere->nome_corriere ?? 'corriere'));

        return 'Data ritiro '.$nome;
    }
}
