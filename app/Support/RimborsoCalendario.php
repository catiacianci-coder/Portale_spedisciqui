<?php

namespace App\Support;

use Carbon\Carbon;

/**
 * Calendario giorni lavorativi (lun–ven, senza festivi automatici).
 */
final class RimborsoCalendario
{
    /**
     * Data prevista dopo N giorni lavorativi dalla data richiesta (escluso il giorno 0 se N>0).
     */
    public static function dataPrevistaDiasUteis(Carbon $dataRichiesta, int $giorni): Carbon
    {
        $giorni = max(0, $giorni);
        if ($giorni === 0) {
            return $dataRichiesta->copy()->startOfDay();
        }

        $cursor = $dataRichiesta->copy()->startOfDay();
        $aggiunti = 0;

        while ($aggiunti < $giorni) {
            $cursor->addDay();
            if ($cursor->isWeekday()) {
                $aggiunti++;
            }
        }

        return $cursor;
    }
}
