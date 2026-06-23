<?php

namespace App\Support;

use App\Models\spedizione;

/** Nome servizio/corriere nelle tabelle spedizione (colonna «Servizio»). */
final class SpedizioneServizioTabella
{
    public static function nomeVisualizzato(spedizione $s): string
    {
        $s->loadMissing('corriereRecord');

        return trim((string) ($s->corriereRecord?->nome_visualizzato ?? ''));
    }
}
