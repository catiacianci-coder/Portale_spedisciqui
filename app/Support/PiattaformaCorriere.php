<?php

namespace App\Support;

/**
 * Identificativi piattaforma su {@see \App\Models\corriere::piattaforma}.
 */
final class PiattaformaCorriere
{
    public const SENDCLOUD = 'sendcloud';

    /** Preventivo da tariffas interne; acquisto etichetta via Spedisci.online (tenant Quick). */
    public const QUICK_PREVENTIVI_PROPRI = 'quick_spediscionline_preventivi_propri';

    /** Preventivo e acquisto interamente via Spedisci.online (tenant Quick). */
    public const QUICK_SPEDISCIONLINE = 'quick_spediscionline';

    /** Preventivo da tariffas interne; acquisto etichetta via Spedisci.online (tenant Liccardi). */
    public const LICCARDI_PREVENTIVI_PROPRI = 'liccardi_spediscionline_preventivi_propri';

    /** Preventivo e operazioni via API TMS Liccardi diretto. */
    public const LICCARDI_TMS = 'liccardi_tms';

    public static function normalizza(?string $piattaforma): string
    {
        return strtolower(trim((string) $piattaforma));
    }

    /**
     * Chiave tenant in config services.spedisci_online.tenants (quick|liccardi).
     */
    public static function tenantSpedisciOnline(?string $piattaforma): ?string
    {
        $p = self::normalizza($piattaforma);

        if (str_starts_with($p, 'liccardi_')) {
            return 'liccardi';
        }

        if (str_starts_with($p, 'quick_')) {
            return 'quick';
        }

        return null;
    }

    /**
     * Dopo il pagamento ordine, creare l'etichetta tramite API Spedisci.online.
     */
    public static function usaAcquistoSpedisciOnline(?string $piattaforma): bool
    {
        return self::tenantSpedisciOnline($piattaforma) !== null;
    }

    /**
     * In pagina preventivi mostrare il pannello di verifica API rates Spedisci.
     */
    public static function mostraProbeRatesInPreventivi(?string $piattaforma): bool
    {
        return self::usaAcquistoSpedisciOnline($piattaforma) || self::usaPreventiviSendcloud($piattaforma);
    }

    public static function usaPreventiviSendcloud(?string $piattaforma): bool
    {
        return self::normalizza($piattaforma) === self::SENDCLOUD;
    }

    /**
     * Preventivo da API TMS Liccardi (getImporto) quando tariffa_interna è false.
     */
    public static function usaPreventiviLiccardiTms(?string $piattaforma): bool
    {
        $p = self::normalizza($piattaforma);

        return $p === self::LICCARDI_TMS || str_starts_with($p, 'liccardi_');
    }

    public static function corriereUsaPreventivoLiccardiTms(\App\Models\corriere $corriere): bool
    {
        return ! (bool) ($corriere->tariffa_interna ?? true)
            && self::usaPreventiviLiccardiTms($corriere->piattaforma);
    }

    public static function corriereUsaPreventivoSendcloud(\App\Models\corriere $corriere): bool
    {
        return ! (bool) ($corriere->tariffa_interna ?? true)
            && self::usaPreventiviSendcloud($corriere->piattaforma);
    }

    /**
     * Dopo il pagamento: etichetta via API Sendcloud (POST /shipments/announce).
     */
    public static function corriereUsaAcquistoSendcloud(\App\Models\corriere $corriere): bool
    {
        return self::corriereUsaPreventivoSendcloud($corriere);
    }

    /**
     * Dopo il pagamento: etichetta via API REST TMS Liccardi (stesso flusso pagina /test/liccardi-tms).
     * Vale per liccardi_tms e per corrieri liccardi_* con tariffa_interna=false (preventivo getImporto TMS).
     */
    public static function corriereUsaAcquistoLiccardiTms(\App\Models\corriere $corriere): bool
    {
        return self::corriereUsaPreventivoLiccardiTms($corriere);
    }

    /**
     * Dopo il pagamento: etichetta via Spedisci.online (tenant quick/liccardi).
     * Esclude i corrieri che quotano e creano già via TMS Liccardi diretto.
     */
    public static function corriereUsaAcquistoSpedisciOnline(\App\Models\corriere $corriere): bool
    {
        if (! self::usaAcquistoSpedisciOnline($corriere->piattaforma)) {
            return false;
        }

        return ! self::corriereUsaAcquistoLiccardiTms($corriere);
    }
}
