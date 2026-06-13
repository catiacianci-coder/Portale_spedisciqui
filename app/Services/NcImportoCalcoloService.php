<?php

namespace App\Services;

/**
 * Stima l'importo che il cliente avrebbe dovuto pagare con dimensioni/peso da rilevazione corriere.
 * Se nel CSV è presente "importo_dovuto" viene usato quel valore (consigliato per allineamento a listini interni).
 * Altrimenti si applica un moltiplicatore proporzionale al maggiore tra rapporto volumi e rapporto pesi (minimo 1).
 */
class NcImportoCalcoloService
{
    public function importoDovuto(
        float $prezzoPagato,
        ?float $hD,
        ?float $lD,
        ?float $sD,
        ?float $pesoD,
        ?float $hC,
        ?float $lC,
        ?float $sC,
        ?float $pesoC,
        ?float $importoDaCsv,
    ): float {
        if ($importoDaCsv !== null && $importoDaCsv > 0) {
            return round($importoDaCsv, 2);
        }

        $volD = max((float) ($hD ?? 0) * (float) ($lD ?? 0) * (float) ($sD ?? 0), 0.000001);
        $volC = max((float) ($hC ?? 0) * (float) ($lC ?? 0) * (float) ($sC ?? 0), 0.000001);
        $pesoD = max((float) ($pesoD ?? 0), 0.001);
        $pesoC = max((float) ($pesoC ?? 0), 0.001);

        $fVol = $volC / $volD;
        $fPeso = $pesoC / $pesoD;
        $factor = max($fVol, $fPeso, 1.0);

        return round($prezzoPagato * $factor, 2);
    }
}
