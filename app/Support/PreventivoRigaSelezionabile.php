<?php

namespace App\Support;

/**
 * Stessa logica di {@see resources/views/preventivi.blade.php} ($righeOk) e accesso indirizzi/checkout.
 */
final class PreventivoRigaSelezionabile
{
    public static function trovaRiga(array $preventivo, int $corriereId): ?array
    {
        if ($corriereId < 1) {
            return null;
        }

        $riga = collect($preventivo['righe'] ?? [])->first(
            fn ($row) => (int) ($row['corriere']['id'] ?? 0) === $corriereId
        );

        return is_array($riga) && self::isSelezionabile($riga) ? $riga : null;
    }

    public static function isSelezionabile(array $riga): bool
    {
        if (! ($riga['ok_tratta'] ?? false)) {
            return false;
        }

        $usaTariffaInterna = (bool) data_get($riga, 'corriere.tariffa_interna', true);

        return ! $usaTariffaInterna || ! empty($riga['tariffa']);
    }

    /**
     * Per corrieri con tariffa_interna=false serve un prezzo API valido.
     *
     * @param  array<int, array<string, mixed>>  $sendcloudQuotePerCorriere
     * @param  array<int, array<string, mixed>>  $liccardiQuotePerCorriere
     */
    public static function haQuotazioneEsternaValida(
        array $riga,
        array $sendcloudQuotePerCorriere = [],
        array $liccardiQuotePerCorriere = [],
    ): bool {
        if ((bool) data_get($riga, 'corriere.tariffa_interna', true)) {
            return true;
        }

        $cid = (int) data_get($riga, 'corriere.id', 0);
        $piattaforma = PiattaformaCorriere::normalizza(data_get($riga, 'corriere.piattaforma', ''));

        if (PiattaformaCorriere::usaPreventiviSendcloud($piattaforma)) {
            $amount = data_get($sendcloudQuotePerCorriere, $cid.'.quote.price_amount');

            return $amount !== null && (float) $amount > 0;
        }

        if (PiattaformaCorriere::usaPreventiviLiccardiTms($piattaforma)) {
            $amount = data_get($liccardiQuotePerCorriere, $cid.'.quote.price_amount');

            return $amount !== null && (float) $amount > 0;
        }

        return false;
    }
}
