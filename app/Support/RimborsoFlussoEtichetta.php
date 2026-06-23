<?php

namespace App\Support;

use App\Models\rimborso;
use App\Models\spedizione;
use App\Models\stato_spedizione;

/** Regole condivise richiesta / pagamento rimborso etichetta. */
final class RimborsoFlussoEtichetta
{
    /**
     * Stato spedizione dopo cancellazione etichetta alla richiesta cliente.
     * In DB (stato_spedizionis id=4) la denominazione è «in attesa di rimborso».
     */
    public static function idStatoInAttesaDiRimborso(): int
    {
        return stato_spedizione::ANNULLATA;
    }

    public static function haEtichettaGenerata(spedizione $spedizione): bool
    {
        return rimborso::resolveMotivoFromSpedizione($spedizione) === rimborso::MOTIVO_CON_ETICHETTA;
    }

    public static function corrierePermetteTracking(spedizione $spedizione): bool
    {
        $spedizione->loadMissing('corriereRecord');

        return (bool) ($spedizione->corriereRecord?->trackingsn ?? false);
    }

    /** Con etichetta ma senza API tracking: l’operatore verifica manualmente sul sito corriere. */
    public static function richiedeVerificaManualeOperatore(rimborso $rimborso, ?spedizione $spedizione = null): bool
    {
        if ((int) $rimborso->motivo !== rimborso::MOTIVO_CON_ETICHETTA) {
            return false;
        }

        $spedizione ??= $rimborso->spedizione;
        if (! $spedizione) {
            return false;
        }

        return ! self::corrierePermetteTracking($spedizione);
    }

    public static function isInAttesaDiRimborso(spedizione $spedizione): bool
    {
        return (int) $spedizione->spedizione_stato_id === self::idStatoInAttesaDiRimborso();
    }
}
