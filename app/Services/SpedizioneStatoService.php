<?php

namespace App\Services;

use App\Models\ordine;
use App\Models\spedizione;
use App\Models\stato_spedizione;

final class SpedizioneStatoService
{
    public static function imposta(spedizione $spedizione, int $statoId): void
    {
        if ((int) $spedizione->spedizione_stato_id === $statoId) {
            return;
        }

        $spedizione->update(['spedizione_stato_id' => $statoId]);
    }

    public static function impostaTutteOrdine(ordine $ordine, int $statoId): void
    {
        spedizione::query()
            ->where('ordine_id', $ordine->id)
            ->update(['spedizione_stato_id' => $statoId]);
    }

    public static function segnaNonPagata(spedizione $spedizione): void
    {
        self::imposta($spedizione, stato_spedizione::NON_PAGATA);
    }

    public static function segnaPagataPerOrdine(ordine $ordine): void
    {
        self::impostaTutteOrdine($ordine, stato_spedizione::PAGATA);
    }

    public static function segnaGenerata(spedizione $spedizione): void
    {
        self::imposta($spedizione, stato_spedizione::GENERATA);
    }

    public static function segnaAnnullataPerOrdine(ordine $ordine): void
    {
        self::impostaTutteOrdine($ordine, stato_spedizione::ANNULLATA);
    }

    public static function segnaAnnullata(spedizione $spedizione): void
    {
        self::imposta($spedizione, stato_spedizione::ANNULLATA);
    }

    public static function segnaRimborsata(spedizione $spedizione): void
    {
        self::imposta($spedizione, stato_spedizione::RIMBORSATA);
    }
}
